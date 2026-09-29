<?php
require_once __DIR__ . '/model/dao/Conexao.php';
require_once __DIR__ . '/controller/UsuarioController.php';

$controller = new UsuarioController(new UsuarioDAO($pdo));
$enviado = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$controller->cadastrar($_POST, $enviado, $enviado && csrfValido());