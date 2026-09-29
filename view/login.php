<?php
require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

if (usuarioLogado()) {
    redirecionar(areaDoUsuario());
}

$email = '';
$erro  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(campo('email'));
    $senha = campoSenha('senha');

    if (!csrfValido()) {
        $erro = 'Sua sessão expirou. Recarregue a página e tente de novo.';
    } elseif ($email === '' || $senha === '') {
        $erro = 'Preencha seu e-mail e sua senha para entrar.';
    } else {
        $consulta = $pdo->prepare('SELECT id, nome, senha, tipo FROM usuarios WHERE email = ?');
        $consulta->execute([$email]);
        $usuario = $consulta->fetch();

        // password_verify compara a senha digitada com o hash guardado no banco
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            session_regenerate_id(true); // novo identificador de sessão a cada login

            $_SESSION['usuario_id']   = (int) $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_tipo'] = $usuario['tipo'];

            definirMensagem('success', 'Que bom ter você por aqui, ' . primeiroNome($usuario['nome']) . '!');

            // Cliente vai direto para o agendamento; barbeiro, para a sua agenda.
            redirecionar($usuario['tipo'] === 'barbeiro' ? 'barbeiro.php' : 'agendamento.php');
        }

        $erro = 'Não conseguimos entrar com esses dados. Confira seu e-mail e sua senha.';
    }
}

$tituloPagina = 'Entrar';
require __DIR__ . '/../includes/header.php';
?>

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

                        <form method="post" action="login.php">
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
                            Ainda não tem conta? <a href="cadastro.php">Criar conta</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
