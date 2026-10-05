<?php
require_once __DIR__ . '/dao/UsuarioDAO.php';

class PerfilModel
{
    private UsuarioDAO $usuarioDAO;

    public function __construct(UsuarioDAO $usuarioDAO)
    {
        $this->usuarioDAO = $usuarioDAO;
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