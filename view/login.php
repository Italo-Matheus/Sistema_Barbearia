<?php
require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../controller/LoginController.php';

$modeloLogin = new UsuarioLoginModel(new UsuarioDAO($pdo));
$controller = new LoginController($modeloLogin);
$enviado = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$controller->entrar($_POST, $enviado, $enviado && csrfValido());
