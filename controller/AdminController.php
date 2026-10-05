<?php
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../model/AdminModel.php';

class AdminController
{
    private PDO $pdo;
    private AdminModel $adminModel;

    public function __construct(PDO $pdo, AdminModel $adminModel)
    {
        $this->pdo = $pdo;
        $this->adminModel = $adminModel;
    }

    public function executar(array $post, bool $enviado, bool $tokenCsrfValido): array
    {
        $admin = exigirAdmin($this->pdo);
        $erroAcao = '';
        $usuarioEdicao = null;

        if ($enviado) {
            if (!$tokenCsrfValido) {
                $erroAcao = 'Sua sessão expirou. Recarregue a página e tente de novo.';
            } else {
                $acao = $this->texto($post, 'acao');
                $id = $this->id($post['id'] ?? null);
                $resultado = ['ok' => false, 'erro' => 'Ação administrativa inválida.'];

                if ($acao === 'salvar_usuario' && $id > 0) {
                    $usuarioEdicao = [
                        'id' => $id,
                        'nome' => $this->texto($post, 'nome'),
                        'email' => $this->texto($post, 'email'),
                        'telefone' => $this->texto($post, 'telefone'),
                        'cpf' => $this->texto($post, 'cpf'),
                        'tipo' => $this->texto($post, 'tipo'),
                        'especialidade' => $this->texto($post, 'especialidade'),
                        'descricao' => $this->texto($post, 'descricao'),
                    ];
                    $dto = new AdminUsuarioDTO(
                        $id,
                        $usuarioEdicao['nome'],
                        $usuarioEdicao['email'],
                        $usuarioEdicao['telefone'],
                        $usuarioEdicao['cpf'] !== '' ? $usuarioEdicao['cpf'] : null,
                        $usuarioEdicao['tipo'],
                        $usuarioEdicao['especialidade'] !== '' ? $usuarioEdicao['especialidade'] : null,
                        $usuarioEdicao['descricao'] !== '' ? $usuarioEdicao['descricao'] : null
                    );
                    $resultado = $this->adminModel->atualizarUsuario($dto);
                } elseif ($acao === 'alterar_tipo' && $id > 0) {
                    $resultado = $this->adminModel->alterarTipo($id, $this->texto($post, 'tipo'));
                } elseif ($acao === 'alternar_ativo' && $id > 0) {
                    $resultado = $this->adminModel->alternarAtivo($id);
                } elseif ($acao === 'excluir_usuario' && $id > 0) {
                    $resultado = $this->adminModel->excluirUsuario($id);
                }

                if ($resultado['ok']) {
                    definirMensagem('success', $resultado['erro']);
                    redirecionar(baseAplicacao() . 'view/admin/dashboard.php');
                }
                $erroAcao = $resultado['erro'];
            }
        }

        if ($usuarioEdicao === null) {
            $idEdicao = $this->id($_GET['editar'] ?? null);
            if ($idEdicao > 0) {
                $usuarioEdicao = $this->adminModel->buscarUsuario($idEdicao);
                if (!$usuarioEdicao) {
                    $erroAcao = 'Esse usuário não pode ser editado.';
                }
            }
        }

        return [
            'adminAtual' => $admin,
            'dadosPainel' => $this->adminModel->dadosPainel(),
            'usuarioEdicao' => $usuarioEdicao,
            'erroAcao' => $erroAcao,
        ];
    }

    private function texto(array $dados, string $campo): string
    {
        $valor = $dados[$campo] ?? '';

        return is_string($valor) ? trim($valor) : '';
    }

    private function id($valor): int
    {
        $id = filter_var($valor, FILTER_VALIDATE_INT);

        return $id !== false && $id > 0 ? $id : 0;
    }
}