<?php
require_once __DIR__ . '/dao/UsuarioDAO.php';

class UsuarioLoginModel
{
    private UsuarioDAO $usuarioDAO;

    public function __construct(UsuarioDAO $usuarioDAO)
    {
        $this->usuarioDAO = $usuarioDAO;
    }

    public function autenticar(string $email, string $senha): ?array
    {
        $usuario = $this->usuarioDAO->buscarPorEmail($email);

        if (!$usuario || !(bool) $usuario['ativo'] || !password_verify($senha, $usuario['senha'])) {
            return null;
        }

        return $usuario;
    }
}