<?php
require_once __DIR__ . '/../includes/funcoes.php';

// Limpa os dados da sessão e troca o identificador.
encerrarSessao();

definirMensagem('info', 'Você saiu da sua conta. Volte sempre!');
redirecionar('index.php');
