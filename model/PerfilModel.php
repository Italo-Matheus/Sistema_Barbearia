<?php
require_once __DIR__ . '/dao/UsuarioDAO.php';
require_once __DIR__ . '/dao/ChatDAO.php';

class PerfilModel
{
    private UsuarioDAO $usuarioDAO;
    private ?ChatDAO $chatDAO;

    public function __construct(UsuarioDAO $usuarioDAO, ?ChatDAO $chatDAO = null)
    {
        $this->usuarioDAO = $usuarioDAO;
        $this->chatDAO = $chatDAO;
    }

    public function buscarPerfil(int $id): ?array
    {
        return $this->usuarioDAO->buscarPerfil($id);
    }

    public function listarHistorico(int $usuarioId, string $tipo): array
    {
        return in_array($tipo, ['cliente', 'barbeiro'], true)
            ? $this->usuarioDAO->listarHistoricoAgendamentos($usuarioId, $tipo)
            : [];
    }

    public function listarContatosChat(int $usuarioId): array
    {
        return $this->chatDAO?->listarContatos($usuarioId) ?? [];
    }

    public function atualizarDadosPerfil(int $usuarioId, string $nome, string $email, string $telefone): array
    {
        $nome = trim($nome);
        $email = strtolower(trim($email));
        $telefone = preg_replace('/\D+/', '', $telefone);

        if (strlen($nome) < 3 || strlen($nome) > 120) {
            return ['ok' => false, 'erro' => 'Informe um nome com até 120 caracteres.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 160) {
            return ['ok' => false, 'erro' => 'Informe um endereço de e-mail válido.'];
        }
        if (strlen($telefone) < 10 || strlen($telefone) > 11) {
            return ['ok' => false, 'erro' => 'Informe um telefone com DDD.'];
        }
        if ($this->usuarioDAO->emailUsadoPorOutro($usuarioId, $email)) {
            return ['ok' => false, 'erro' => 'Esse e-mail já pertence a outra conta.'];
        }
        if (!$this->usuarioDAO->atualizarDadosPerfil($usuarioId, $nome, $email, $telefone)) {
            return ['ok' => false, 'erro' => 'Não foi possível atualizar seus dados.'];
        }

        return ['ok' => true, 'erro' => 'Dados do perfil atualizados com sucesso.'];
    }

    public function atualizarFotoPerfil(int $usuarioId, string $caminho): bool
    {
        return $this->usuarioDAO->atualizarFotoPerfil($usuarioId, $caminho);
    }

    public function alterarSenha(int $usuarioId, string $senhaAtual, string $senhaNova, string $confirmacao): array
    {
        if (strlen($senhaNova) < 8 || strlen($senhaNova) > 72) {
            return ['ok' => false, 'erro' => 'A nova senha deve ter entre 8 e 72 caracteres.'];
        }
        if ($senhaNova !== $confirmacao) {
            return ['ok' => false, 'erro' => 'A confirmação não corresponde à nova senha.'];
        }

        $hashAtual = $this->usuarioDAO->buscarHashSenha($usuarioId);
        if ($hashAtual === null || !password_verify($senhaAtual, $hashAtual)) {
            return ['ok' => false, 'erro' => 'A senha atual está incorreta.'];
        }
        if (password_verify($senhaNova, $hashAtual)) {
            return ['ok' => false, 'erro' => 'Escolha uma senha diferente da atual.'];
        }

        $novoHash = password_hash($senhaNova, PASSWORD_DEFAULT);
        if (!$this->usuarioDAO->atualizarSenha($usuarioId, $novoHash)) {
            return ['ok' => false, 'erro' => 'Não foi possível atualizar sua senha agora.'];
        }

        return ['ok' => true, 'erro' => 'Senha atualizada com sucesso.'];
    }
}