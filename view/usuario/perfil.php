<?php
require_once __DIR__ . '/../../model/dao/Conexao.php';
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../model/PerfilModel.php';
require_once __DIR__ . '/../../controller/PerfilController.php';

$modeloPerfil = new PerfilModel(new UsuarioDAO($pdo));
$controller = new PerfilController($pdo, $modeloPerfil);
$enviado = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$dadosPerfil = $controller->executarPerfil($_GET, $_POST, $_FILES, $enviado, $enviado && csrfValido());

$erro = $dadosPerfil['erro'] ?? '';
$perfil = $dadosPerfil['perfil'] ?? null;
if (!$perfil) {
    $tituloPagina = 'Perfil';
    require __DIR__ . '/../../includes/header.php';
    ?>
    <div class="container conteudo">
        <div class="alert alert-danger" role="alert"><?= e($erro !== '' ? $erro : 'Perfil não encontrado.') ?></div>
        <a class="btn btn-contorno" href="<?= e(areaDoUsuario()) ?>">Voltar</a>
    </div>
    <?php
    require __DIR__ . '/../../includes/footer.php';
    return;
}

$visualizandoOutro = (bool) $dadosPerfil['visualizandoOutro'];
$historico = $dadosPerfil['historico'] ?? [];
$tituloPagina = 'Perfil de ' . $perfil['nome'];
$scriptPagina = $visualizandoOutro ? '' : 'assets/js/chat.js';
$fotoUrl = !empty($perfil['foto_perfil'])
    ? baseAplicacao() . $perfil['foto_perfil']
    : '';
require __DIR__ . '/../../includes/header.php';
?>

<section class="topo-pagina">
    <div class="container">
        <p class="texto-suave mb-2"><?= $visualizandoOutro ? 'Perfil do usuário' : 'Sua conta' ?></p>
        <h1><?= e($perfil['nome']) ?></h1>
        <p class="texto-suave mb-0"><?= $visualizandoOutro ? 'Dados e histórico de agendamentos.' : 'Consulte e atualize seus dados pessoais.' ?></p>
    </div>
</section>

<div class="container conteudo">
    <?php if ($erro !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= e($erro) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <section class="cartao perfil-card">
                <div class="perfil-cabecalho">
                    <?php if ($fotoUrl !== ''): ?>
                        <img class="perfil-avatar" src="<?= e($fotoUrl) ?>" alt="Foto de perfil de <?= e($perfil['nome']) ?>">
                    <?php else: ?>
                        <div class="perfil-avatar perfil-avatar-vazio" aria-label="Sem foto de perfil">
                            <i class="bi bi-person" aria-hidden="true"></i>
                        </div>
                    <?php endif; ?>
                    <h2 class="h4 mb-1"><?= e($perfil['nome']) ?></h2>
                    <span class="badge text-bg-light"><?= e(ucfirst((string) $perfil['tipo'])) ?></span>
                </div>

                <dl class="dados mt-4 mb-0">
                    <dt>E-mail</dt>
                    <dd class="text-break"><?= e($perfil['email']) ?></dd>
                    <dt>Telefone</dt>
                    <dd><?= e(formatarTelefone((string) $perfil['telefone'])) ?></dd>
                    <?php if (!empty($perfil['cpf'])): ?>
                        <dt>CPF</dt>
                        <dd><?= e(formatarCpf((string) $perfil['cpf'])) ?></dd>
                    <?php endif; ?>
                    <?php if (!empty($perfil['especialidade'])): ?>
                        <dt>Especialidade</dt>
                        <dd><?= e($perfil['especialidade']) ?></dd>
                    <?php endif; ?>
                    <?php if (!empty($perfil['descricao'])): ?>
                        <dt>Sobre</dt>
                        <dd><?= e($perfil['descricao']) ?></dd>
                    <?php endif; ?>
                </dl>
            </section>

            <?php if (!$visualizandoOutro): ?>
                <section class="cartao mt-4">
                    <h2 class="h5 mb-3">Editar dados</h2>
                    <form method="post" action="<?= e(baseAplicacao()) ?>view/usuario/perfil.php">
                        <?= campoCsrf() ?>
                        <input type="hidden" name="acao" value="salvar_dados">
                        <div class="mb-3">
                            <label class="form-label" for="perfil_nome">Nome</label>
                            <input class="form-control" id="perfil_nome" name="nome" value="<?= e($perfil['nome']) ?>" maxlength="120" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="perfil_email">E-mail</label>
                            <input class="form-control" type="email" id="perfil_email" name="email" value="<?= e($perfil['email']) ?>" maxlength="160" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="perfil_telefone">Telefone</label>
                            <input class="form-control" type="tel" id="perfil_telefone" name="telefone" value="<?= e($perfil['telefone']) ?>" maxlength="20" required>
                        </div>
                        <button class="btn btn-marrom" type="submit">Salvar dados</button>
                    </form>

                    <hr class="my-4">
                    <h3 class="h5 mb-3">Foto de perfil</h3>
                    <form method="post" action="<?= e(baseAplicacao()) ?>view/usuario/perfil.php" enctype="multipart/form-data">
                        <?= campoCsrf() ?>
                        <input type="hidden" name="acao" value="salvar_foto">
                        <label class="form-label" for="foto_perfil">Imagem JPG ou PNG (máximo 2 MB)</label>
                        <input class="form-control mb-3" type="file" id="foto_perfil" name="foto_perfil"
                               accept=".jpg,.jpeg,.png,image/jpeg,image/png" required>
                        <button class="btn btn-contorno" type="submit">Atualizar foto</button>
                    </form>
                </section>
            <?php endif; ?>
        </div>

        <div class="col-lg-8">
            <section aria-labelledby="historico-titulo">
                <h2 class="h4 mb-3" id="historico-titulo">Histórico de agendamentos</h2>
                <?php if (!$historico): ?>
                    <div class="cartao vazio">
                        <p class="mb-0">Ainda não há agendamentos no histórico deste perfil.</p>
                    </div>
                <?php else: ?>
                    <div class="d-grid gap-3">
                        <?php foreach ($historico as $agendamento): ?>
                            <article class="cartao">
                                <div class="d-flex flex-wrap justify-content-between gap-3">
                                    <div>
                                        <h3 class="h5 mb-2"><?= e($agendamento['servicos'] ?: 'Agendamento') ?></h3>
                                        <p class="texto-suave mb-1">
                                            <?= e(formatarData((string) $agendamento['data'])) ?> às
                                            <?= e(formatarHorario((string) $agendamento['horario'])) ?>
                                        </p>
                                        <p class="texto-suave mb-0"><?= e($agendamento['contraparte_nome']) ?></p>
                                    </div>
                                    <div class="text-lg-end">
                                        <span class="badge text-bg-light"><?= e(ucfirst((string) $agendamento['status'])) ?></span>
                                        <p class="fw-semibold mt-2 mb-0"><?= e(formatarPreco($agendamento['valor_total'])) ?></p>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
