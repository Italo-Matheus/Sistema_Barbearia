<?php
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../model/PerfilModel.php';

class PerfilController
{
    private PDO $pdo;
    private PerfilModel $perfilModel;

    public function __construct(PDO $pdo, PerfilModel $perfilModel)
    {
        $this->pdo = $pdo;
        $this->perfilModel = $perfilModel;
    }

    public function alterarSenha(array $post, bool $enviado, bool $tokenCsrfValido): void
    {
        exigirLogin(null, $this->pdo);
        $erro = '';

        if ($enviado) {
            if (!$tokenCsrfValido) {
                $erro = 'Sua sessão expirou. Recarregue a página e tente novamente.';
            } else {
                $resultado = $this->perfilModel->alterarSenha(
                    (int) $_SESSION['usuario_id'],
                    $this->texto($post, 'senha_atual'),
                    $this->texto($post, 'senha_nova'),
                    $this->texto($post, 'confirmar_senha')
                );

                if ($resultado['ok']) {
                    definirMensagem('success', $resultado['erro']);
                    redirecionar(baseAplicacao() . 'view/alterar_senha.php');
                }
                $erro = $resultado['erro'];
            }
        }

        $tituloPagina = 'Alterar senha';
        require __DIR__ . '/../view/alterar_senha.php';
    }

    public function executarPerfil(array $get, array $post, array $files, bool $enviado, bool $tokenCsrfValido): array
    {
        exigirLogin(null, $this->pdo);
        $usuarioAtualId = (int) $_SESSION['usuario_id'];
        $idInformado = array_key_exists('id', $get);
        $perfilId = $idInformado ? $this->id($get['id']) : $usuarioAtualId;
        $visualizandoOutro = $perfilId !== $usuarioAtualId;

        if ($perfilId < 1) {
            http_response_code(400);
            return ['erro' => 'Identificador de perfil inválido.'];
        }
        if ($visualizandoOutro && tipoUsuario() !== 'admin') {
            http_response_code(403);
            return ['erro' => 'Somente administradores podem visualizar perfis de terceiros.'];
        }
        if ($visualizandoOutro) {
            exigirAdmin($this->pdo);
        }

        $perfil = $this->perfilModel->buscarPerfil($perfilId);
        if (!$perfil) {
            http_response_code(404);
            return ['erro' => 'Perfil não encontrado.'];
        }

        $erro = '';
        if ($enviado) {
            if ($visualizandoOutro) {
                http_response_code(403);
                $erro = 'Não é permitido alterar o perfil de outra pessoa por esta página.';
            } elseif (!$tokenCsrfValido) {
                $erro = 'Sua sessão expirou. Recarregue a página e tente novamente.';
            } else {
                $acao = $this->texto($post, 'acao');
                if ($acao === 'salvar_dados') {
                    $resultado = $this->perfilModel->atualizarDadosPerfil(
                        $perfilId,
                        $this->texto($post, 'nome'),
                        $this->texto($post, 'email'),
                        $this->texto($post, 'telefone')
                    );
                    if ($resultado['ok']) {
                        definirMensagem('success', $resultado['erro']);
                        redirecionar(baseAplicacao() . 'view/usuario/perfil.php');
                    }
                    $erro = $resultado['erro'];
                } elseif ($acao === 'salvar_foto') {
                    $resultado = $this->salvarFoto($perfilId, $files['foto_perfil'] ?? null, $perfil['foto_perfil'] ?? null);
                    if ($resultado['ok']) {
                        definirMensagem('success', 'Foto de perfil atualizada com sucesso.');
                        redirecionar(baseAplicacao() . 'view/usuario/perfil.php');
                    }
                    $erro = $resultado['erro'];
                } else {
                    $erro = 'Ação de perfil inválida.';
                }
            }

            $perfil = $this->perfilModel->buscarPerfil($perfilId) ?? $perfil;
        }

        return [
            'perfil' => $perfil,
            'historico' => $this->perfilModel->listarHistorico($perfilId, (string) $perfil['tipo']),
            'visualizandoOutro' => $visualizandoOutro,
            'erro' => $erro,
        ];
    }

    private function salvarFoto(int $usuarioId, $arquivo, ?string $fotoAtual): array
    {
        if (!is_array($arquivo) || !isset($arquivo['error'], $arquivo['tmp_name'], $arquivo['size'], $arquivo['name'])) {
            return ['ok' => false, 'erro' => 'Selecione uma imagem para enviar.'];
        }
        if (!is_int($arquivo['error']) || !is_string($arquivo['tmp_name'])
            || !is_string($arquivo['name']) || !is_numeric($arquivo['size'])) {
            return ['ok' => false, 'erro' => 'Os dados do arquivo enviado são inválidos.'];
        }
        if ($arquivo['error'] === UPLOAD_ERR_INI_SIZE || $arquivo['error'] === UPLOAD_ERR_FORM_SIZE) {
            return ['ok' => false, 'erro' => 'A imagem deve ter no máximo 2 MB.'];
        }
        if ($arquivo['error'] === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'erro' => 'Selecione uma imagem para enviar.'];
        }
        if ($arquivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($arquivo['tmp_name'])) {
            return ['ok' => false, 'erro' => 'O envio da imagem falhou. Selecione o arquivo novamente.'];
        }

        $limiteBytes = 2 * 1024 * 1024;
        if ((int) $arquivo['size'] < 1 || (int) $arquivo['size'] > $limiteBytes) {
            return ['ok' => false, 'erro' => 'A imagem deve ter no máximo 2 MB.'];
        }

        $extensao = strtolower(pathinfo((string) $arquivo['name'], PATHINFO_EXTENSION));
        $extensoesMime = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
        if (!isset($extensoesMime[$extensao])) {
            return ['ok' => false, 'erro' => 'Use uma imagem nos formatos JPG ou PNG.'];
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($arquivo['tmp_name']);
        if ($mime !== $extensoesMime[$extensao]) {
            return ['ok' => false, 'erro' => 'O conteúdo do arquivo não corresponde a uma imagem JPG ou PNG.'];
        }

        $diretorio = __DIR__ . '/../assets/img/perfis';
        if (!is_dir($diretorio) && !mkdir($diretorio, 0755, true) && !is_dir($diretorio)) {
            error_log('Não foi possível criar o diretório de fotos de perfil.');
            return ['ok' => false, 'erro' => 'Não foi possível preparar o armazenamento da foto.'];
        }

        $nomeArquivo = 'perfil_' . str_replace('.', '', uniqid('', true)) . '.' . $extensao;
        $destino = $diretorio . '/' . $nomeArquivo;
        if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
            return ['ok' => false, 'erro' => 'Não foi possível salvar a imagem enviada.'];
        }

        $caminhoBanco = 'assets/img/perfis/' . $nomeArquivo;
        try {
            if (!$this->perfilModel->atualizarFotoPerfil($usuarioId, $caminhoBanco)) {
                unlink($destino);
                return ['ok' => false, 'erro' => 'Não foi possível atualizar a foto no perfil.'];
            }
        } catch (Throwable $erro) {
            if (is_file($destino)) {
                unlink($destino);
            }
            error_log('Erro ao atualizar foto de perfil: ' . $erro->getMessage());
            return ['ok' => false, 'erro' => 'Ocorreu um erro ao atualizar sua foto.'];
        }

        if (is_string($fotoAtual) && strpos($fotoAtual, 'assets/img/perfis/') === 0) {
            $fotoAnterior = $diretorio . '/' . basename($fotoAtual);
            if (is_file($fotoAnterior)) {
                unlink($fotoAnterior);
            }
        }

        return ['ok' => true, 'erro' => ''];
    }

    private function texto(array $dados, string $campo): string
    {
        $valor = $dados[$campo] ?? '';

        return is_string($valor) ? $valor : '';
    }

    private function id($valor): int
    {
        $id = filter_var($valor, FILTER_VALIDATE_INT);

        return $id !== false && $id > 0 ? $id : 0;
    }
}