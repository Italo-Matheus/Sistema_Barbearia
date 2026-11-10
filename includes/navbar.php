<?php
/**
 * Barra de navegação. O menu muda conforme o visitante:
 * sem login, cliente ou barbeiro.
 */
$paginaAtual = basename($_SERVER['SCRIPT_NAME']);
$logado      = usuarioLogado();
$tipo        = tipoUsuario();
$dadosCabecalho = null;

if ($logado && isset($pdo) && $pdo instanceof PDO) {
    $consultaCabecalho = $pdo->prepare(
        'SELECT nome, email, telefone, tipo, foto_perfil
         FROM usuarios WHERE id = ? AND ativo = 1 LIMIT 1'
    );
    $consultaCabecalho->execute([(int) $_SESSION['usuario_id']]);
    $dadosCabecalho = $consultaCabecalho->fetch() ?: null;
}
?>
<nav class="navbar navbar-barbearia sticky-top" data-bs-theme="dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-scissors" aria-hidden="true"></i>
            <span><?= e(NOME_BARBEARIA) ?></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas"
                data-bs-target="#menuLateral" aria-controls="menuLateral"
                aria-expanded="false" aria-label="Abrir menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <aside class="offcanvas offcanvas-end menu-lateral" tabindex="-1" id="menuLateral"
               aria-labelledby="menuLateralTitulo">
            <div class="offcanvas-header">
                <h2 class="offcanvas-title h4 mb-0" id="menuLateralTitulo">Menu</h2>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar menu"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column">
                <ul class="navbar-nav mb-4">
                    <li class="nav-item">
                        <a class="nav-link<?= $paginaAtual === 'index.php' ? ' active' : '' ?>" href="index.php">Início</a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="index.php#servicos">Serviços</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#como-funciona">Como funciona</a></li>
                </ul>

                <?php if (!$logado): ?>
                    <div class="d-flex flex-column gap-2 mt-auto">
                        <a class="btn btn-contorno-claro" href="view/login.php">Entrar</a>
                        <a class="btn btn-contorno-claro" href="view/cadastro.php">Criar conta</a>
                        <a class="btn btn-ouro" href="view/usuario/agendamento.php">Agendar horário</a>
                    </div>
                <?php else: ?>
                    <?php
                    $nomeCabecalho = $dadosCabecalho['nome'] ?? ($_SESSION['usuario_nome'] ?? 'Meu perfil');
                    $fotoCabecalho = $dadosCabecalho['foto_perfil'] ?? '';
                    ?>
                    <section class="menu-perfil" aria-label="Informações da conta">
                        <div class="painel-perfil-identidade">
                            <?php if ($fotoCabecalho !== ''): ?>
                                <img class="painel-perfil-avatar" src="<?= e(baseAplicacao() . $fotoCabecalho) ?>"
                                     alt="Foto de perfil de <?= e($nomeCabecalho) ?>">
                            <?php else: ?>
                                <div class="painel-perfil-avatar painel-perfil-avatar-vazio" aria-hidden="true">
                                    <i class="bi bi-person"></i>
                                </div>
                            <?php endif; ?>
                            <h3 class="h5 mb-1"><?= e($nomeCabecalho) ?></h3>
                            <span class="badge text-bg-light"><?= e(ucfirst((string) ($dadosCabecalho['tipo'] ?? $tipo ?? 'usuário'))) ?></span>
                        </div>

                        <dl class="dados painel-perfil-dados">
                            <?php if (!empty($dadosCabecalho['email'])): ?>
                                <dt>E-mail</dt>
                                <dd class="text-break"><?= e($dadosCabecalho['email']) ?></dd>
                            <?php endif; ?>
                            <?php if (!empty($dadosCabecalho['telefone'])): ?>
                                <dt>Telefone</dt>
                                <dd><?= e(formatarTelefone((string) $dadosCabecalho['telefone'])) ?></dd>
                            <?php endif; ?>
                        </dl>
                    </section>

                    <div class="d-flex flex-column gap-2 mt-auto">
                        <a class="btn btn-contorno-claro" href="view/usuario/perfil.php">
                            <i class="bi bi-pencil-square me-1" aria-hidden="true"></i> Ver e editar perfil
                        </a>
                        <a class="btn btn-contorno-claro" href="view/usuario/chat.php">
                            <i class="bi bi-chat-dots me-1" aria-hidden="true"></i> Mensagens
                        </a>
                        <?php if ($tipo === 'cliente'): ?>
                            <a class="btn btn-contorno-claro" href="cliente.php">Meus agendamentos</a>
                            <a class="btn btn-ouro" href="view/usuario/agendamento.php">Agendar horário</a>
                        <?php elseif ($tipo === 'barbeiro'): ?>
                            <a class="btn btn-contorno-claro" href="view/admin/barbeiro.php">Minha agenda</a>
                        <?php elseif ($tipo === 'admin'): ?>
                            <a class="btn btn-contorno-claro" href="view/admin/dashboard.php">Painel administrativo</a>
                        <?php endif; ?>
                        <a class="btn btn-contorno-claro" href="view/alterar_senha.php">Alterar senha</a>
                        <a class="btn btn-contorno-claro" href="logout.php"
                           data-confirmar="Tem certeza de que quer sair?" data-confirmar-titulo="Sair da conta?" data-confirmar-tipo="info">
                            <i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> Sair
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</nav>
