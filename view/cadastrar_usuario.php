<?php
$tituloPagina = 'Criar conta';
require __DIR__ . '/../includes/header.php';
?>

<section class="auth">
    <div class="container">
        <div class="auth-cartao">
            <div class="row g-0">
                <div class="col-lg-5 d-none d-lg-block">
                    <div class="auth-lateral">
                        <h2>Seu próximo corte começa aqui.</h2>
                        <p>Crie sua conta para reservar seus horários com praticidade.</p>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="auth-form">
                        <h1>Vamos criar sua conta?</h1>
                        <p class="texto-suave mb-4">Leva menos de um minuto. Já tem conta? <a href="view/login.php">Entrar</a></p>

                        <?php
                        if (isset($erros['geral'])) {
                            exibirErros([$erros['geral']]);
                        } elseif ($erros) {
                            exibirErros(['Quase lá! Confira os campos destacados abaixo.']);
                        }
                        ?>

                        <form method="post" action="view/cadastro.php">
                            <?= campoCsrf() ?>

                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="nome" class="form-label">Nome completo</label>
                                    <input type="text" class="form-control<?= classeInvalido($erros, 'nome') ?>" id="nome" name="nome"
                                           value="<?= e($dados['nome']) ?>" maxlength="120" autocomplete="name" required>
                                    <?= erroCampo($erros, 'nome') ?>
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label">E-mail</label>
                                    <input type="email" class="form-control<?= classeInvalido($erros, 'email') ?>" id="email" name="email"
                                           value="<?= e($dados['email']) ?>" maxlength="160" autocomplete="email" required>
                                    <?= erroCampo($erros, 'email') ?>
                                </div>

                                <div class="col-md-6">
                                    <label for="telefone" class="form-label">Telefone com DDD</label>
                                    <input type="tel" class="form-control<?= classeInvalido($erros, 'telefone') ?>" id="telefone" name="telefone"
                                           value="<?= e($dados['telefone']) ?>" maxlength="20" autocomplete="tel" placeholder="(61) 91234-5678" required>
                                    <?= erroCampo($erros, 'telefone') ?>
                                </div>

                                <div class="col-md-6">
                                    <label for="cpf" class="form-label">CPF <span class="texto-suave fw-normal">(opcional)</span></label>
                                    <input type="text" class="form-control<?= classeInvalido($erros, 'cpf') ?>" id="cpf" name="cpf"
                                           value="<?= e($dados['cpf']) ?>" maxlength="14" inputmode="numeric" placeholder="000.000.000-00">
                                    <?= erroCampo($erros, 'cpf') ?>
                                </div>

                                <div class="w-100 m-0"></div>

                                <div class="col-md-6">
                                    <label for="senha" class="form-label">Senha</label>
                                    <input type="password" class="form-control<?= classeInvalido($erros, 'senha') ?>" id="senha" name="senha"
                                           minlength="8" maxlength="72" autocomplete="new-password" required>
                                    <?= erroCampo($erros, 'senha') ?>
                                    <div class="form-text">Pelo menos 8 caracteres.</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="confirmar_senha" class="form-label">Confirme a senha</label>
                                    <input type="password" class="form-control<?= classeInvalido($erros, 'confirmar_senha') ?>" id="confirmar_senha"
                                           name="confirmar_senha" minlength="8" maxlength="72" autocomplete="new-password" required>
                                    <?= erroCampo($erros, 'confirmar_senha') ?>
                                </div>

                            </div>

                            <button type="submit" class="btn btn-marrom btn-lg w-100 mt-4">Criar minha conta</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
