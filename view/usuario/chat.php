<?php
require_once __DIR__ . '/../../model/dao/Conexao.php';
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../model/dao/ChatDAO.php';

exigirLogin(null, $pdo);
$usuario = carregarUsuario($pdo);
$chatContatos = (new ChatDAO($pdo))->listarContatos((int) $usuario['id']);
$chatUsuarioId = (int) $usuario['id'];
$scriptPagina = 'assets/js/chat.js';
$tituloPagina = 'Mensagens';
require __DIR__ . '/../../includes/header.php';
?>

<section class="topo-pagina">
    <div class="container">
        <p class="texto-suave mb-2">Comunicação</p>
        <h1>Mensagens</h1>
        <p class="texto-suave mb-0">Converse com os clientes, barbeiros e administradores disponíveis.</p>
    </div>
</section>

<div class="container conteudo">
    <?php require __DIR__ . '/../../includes/chat.php'; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
