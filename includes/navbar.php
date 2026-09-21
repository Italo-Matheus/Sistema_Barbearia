<?php
/**
 * Barra de navegação. O menu muda conforme o visitante:
 * sem login, cliente ou barbeiro.
 */
$paginaAtual = basename($_SERVER['SCRIPT_NAME']);
$logado      = usuarioLogado();
$tipo        = tipoUsuario();
?>
<nav class="navbar navbar-expand-lg navbar-barbearia sticky-top" data-bs-theme="dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-scissors" aria-hidden="true"></i>
            <span><?= e(NOME_BARBEARIA) ?></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#menuPrincipal" aria-controls="menuPrincipal"
                aria-expanded="false" aria-label="Abrir menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link<?= $paginaAtual === 'index.php' ? ' active' : '' ?>" href="index.php">Início</a>
                </li>
                <li class="nav-item"><a class="nav-link" href="index.php#servicos">Serviços</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#como-funciona">Como funciona</a></li>
                <?php if ($tipo === 'cliente'): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $paginaAtual === 'cliente.php' ? ' active' : '' ?>" href="cliente.php">Meus horários</a>
                    </li>
                <?php elseif ($tipo === 'barbeiro'): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $paginaAtual === 'barbeiro.php' ? ' active' : '' ?>" href="barbeiro.php">Minha agenda</a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 pb-3 pb-lg-0">
                <?php if (!$logado): ?>
                    <a class="btn btn-contorno-claro" href="login.php">Entrar</a>
                    <a class="btn btn-contorno-claro" href="cadastro.php">Criar conta</a>
                    <a class="btn btn-ouro" href="agendamento.php">Agendar horário</a>
                <?php else: ?>
                    <span class="navbar-text me-lg-2">Olá, <?= e(primeiroNome($_SESSION['usuario_nome'] ?? '')) ?></span>
                    <?php if ($tipo === 'cliente'): ?>
                        <a class="btn btn-ouro" href="agendamento.php">Agendar horário</a>
                    <?php endif; ?>
                    <a class="btn btn-contorno-claro" href="logout.php"
                       data-confirmar="Tem certeza de que quer sair?">Sair</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
