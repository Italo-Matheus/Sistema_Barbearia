<?php
require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../model/dao/ChatDAO.php';
require_once __DIR__ . '/../controller/ChatController.php';

$controller = new ChatController($pdo, new ChatDAO($pdo));
$controller->tratarRequisicao();
