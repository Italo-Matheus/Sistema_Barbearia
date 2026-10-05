<?php
if (!defined('ALTERAR_SENHA_EM_EXECUCAO')) {
    define('ALTERAR_SENHA_EM_EXECUCAO', true);
    require_once __DIR__ . '/../model/dao/Conexao.php';
    require_once __DIR__ . '/../controller/PerfilController.php';

    $modeloPerfil = new PerfilModel(new UsuarioDAO($pdo));
    $controller = new PerfilController($pdo, $modeloPerfil);
    $enviado = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    $controller->alterarSenha($_POST, $enviado, $enviado && csrfValido());
    return;
}

$erro = $erro ?? '';
require __DIR__ . '/../includes/header.php';
?>

<section class="topo-pagina">
    <div class="container">
        <p class="texto-suave mb-2">Segurança da conta</p>
        <h1>Alterar senha</h1>
        <p class="texto-suave mb-0">Confirme sua senha atual para definir uma nova.</p>
    </div>
</section>

<div class="container conteudo">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-xl-6">
            <?php if ($erro !== ''): ?>
                <div class="alert alert-danger" role="alert"><?= e($erro) ?></div>
            <?php endif; ?>

            <form class="cartao" method="post" action="view/alterar_senha.php">
                <?= campoCsrf() ?>
                <div class="mb-3">
                    <label for="senha_atual" class="form-label">Senha atual</label>
                    <input class="form-control" type="password" id="senha_atual" name="senha_atual"
                           autocomplete="current-password" required>
                </div>
                <div class="mb-3">
                    <label for="senha_nova" class="form-label">Nova senha</label>
                    <input class="form-control" type="password" id="senha_nova" name="senha_nova"
                           minlength="8" maxlength="72" autocomplete="new-password" required>
                    <div class="form-text">Use entre 8 e 72 caracteres.</div>
                </div>
                <div class="mb-4">
                    <label for="confirmar_senha" class="form-label">Confirme a nova senha</label>
                    <input class="form-control" type="password" id="confirmar_senha" name="confirmar_senha"
                           minlength="8" maxlength="72" autocomplete="new-password" required>
                </div>
                <button class="btn btn-marrom" type="submit">Salvar nova senha</button>
                <a class="btn btn-contorno ms-2" href="<?= e(areaDoUsuario()) ?>">Cancelar</a>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>