<?php
require_once __DIR__ . '/model/dao/Conexao.php';
require_once __DIR__ . '/controller/AgendamentoController.php';

$modeloAgendamento = new AgendamentoModel($pdo);
$controller = new AgendamentoController($modeloAgendamento);
$enviado = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$controller->agendar($_POST, $enviado, $enviado && csrfValido());