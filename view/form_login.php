<?php require __DIR__ . '/../includes/header.php'; ?>

<section class="auth">
    <div class="container">
        <div class="auth-cartao">
            <div class="row g-0">
                <div class="col-lg-5 d-none d-lg-block">
                    <div class="auth-lateral">
                        <h2>Sua cadeira está esperando por você.</h2>
                        <p>Entre para ver seus horários e reservar o próximo corte.</p>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="auth-form">
                        <h1>Que bom ter você por aqui!</h1>
                        <p class="texto-suave mb-4">Entre com o seu e-mail e a sua senha.</p>

                        <?php exibirErros($erro !== '' ? [$erro] : []); ?>

                        <form method="post" action="view/login.php">
                            <?= campoCsrf() ?>

                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?= e($email) ?>" autocomplete="email" required autofocus>
                            </div>

                            <div class="mb-4">
                                <label for="senha" class="form-label">Senha</label>
                                <input type="password" class="form-control" id="senha" name="senha"
                                       autocomplete="current-password" required>
                            </div>

                            <button type="submit" class="btn btn-marrom btn-lg w-100">Entrar</button>
                        </form>

                        <p class="texto-suave text-center mt-4 mb-0">
                            Ainda não tem conta? <a href="view/cadastro.php">Criar conta</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>