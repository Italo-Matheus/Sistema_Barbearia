<?php
require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../controller/UsuarioController.php';

$modeloCadastro = new UsuarioCadastroModel(new UsuarioDAO($pdo));
$controller = new UsuarioController($modeloCadastro);
$enviado = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$controller->cadastrar($_POST, $enviado, $enviado && csrfValido());