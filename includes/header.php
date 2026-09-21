<?php
/**
 * Abre o HTML, carrega os estilos e mostra a navbar.
 * Antes de incluir, a página pode definir $tituloPagina.
 */
$tituloPagina = $tituloPagina ?? 'Barbearia';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Escolha seus serviços, seu barbeiro e o melhor horário. Agendamento simples na <?= e(NOME_BARBEARIA) ?>.">
    <title><?= e($tituloPagina) ?> — <?= e(NOME_BARBEARIA) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Young+Serif&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">

    <!-- Avisa o CSS que o JavaScript está ativo (a página funciona sem ele) -->
    <script>document.documentElement.classList.add('js');</script>
</head>
<body>
<?php require __DIR__ . '/navbar.php'; ?>
<?php exibirMensagens(); ?>
<main>
