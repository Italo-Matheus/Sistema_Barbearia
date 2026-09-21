<?php
require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/includes/funcoes.php';

// Quem já está logado não precisa criar outra conta.
if (usuarioLogado()) {
    redirecionar(areaDoUsuario());
}

$dados = [
    'tipo'          => 'cliente',
    'nome'          => '',
    'email'         => '',
    'telefone'      => '',
    'cpf'           => '',
    'especialidade' => '',
    'descricao'     => '',
];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($dados as $chave => $valor) {
        $dados[$chave] = campo($chave);
    }
    $senha       = campoSenha('senha');
    $confirmacao = campoSenha('confirmar_senha');

    if (!csrfValido()) {
        $erros['geral'] = 'Sua sessão expirou. Recarregue a página e tente de novo.';
    }

    // --- Validação (sempre repetida no PHP, mesmo que o HTML já valide) ---
    if (!in_array($dados['tipo'], ['cliente', 'barbeiro'], true)) {
        $erros['tipo'] = 'Escolha se você é cliente ou barbeiro.';
    }

    if (strlen($dados['nome']) < 5 || strpos($dados['nome'], ' ') === false) {
        $erros['nome'] = 'Informe seu nome completo.';
    } elseif (strlen($dados['nome']) > 120) {
        $erros['nome'] = 'O nome pode ter até 120 caracteres.';
    }

    $dados['email'] = strtolower($dados['email']);
    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL) || strlen($dados['email']) > 160) {
        $erros['email'] = 'Esse e-mail não parece correto. Confira e tente de novo.';
    }

    $telefone = apenasDigitos($dados['telefone']);
    if (strlen($telefone) < 10 || strlen($telefone) > 11) {
        $erros['telefone'] = 'Informe o telefone com DDD, por exemplo (61) 91234-5678.';
    }

    

    if (strlen($senha) < 8) {
        $erros['senha'] = 'Use uma senha com pelo menos 8 caracteres.';
    } elseif (strlen($senha) > 72) {
        $erros['senha'] = 'A senha pode ter até 72 caracteres.';
    }
    if ($senha !== $confirmacao) {
        $erros['confirmar_senha'] = 'As senhas não são iguais.';
    }

    if ($dados['tipo'] === 'barbeiro') {
        if (strlen($dados['especialidade']) > 100) {
            $erros['especialidade'] = 'A especialidade pode ter até 100 caracteres.';
        }
        if (strlen($dados['descricao']) > 300) {
            $erros['descricao'] = 'A descrição pode ter até 300 caracteres.';
        }
    }

    // --- E-mail e CPF não podem estar em outra conta ---
    if (!isset($erros['email'])) {
        $consulta = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
        $consulta->execute([$dados['email']]);
        if ($consulta->fetch()) {
            $erros['email'] = 'Esse e-mail já tem uma conta. Que tal entrar?';
        }
    }
   

    // --- Tudo certo: salva a conta ---
    if (!$erros) {
        $ehBarbeiro = $dados['tipo'] === 'barbeiro';
        try {
            $insercao = $pdo->prepare(
                'INSERT INTO usuarios (nome, email, telefone, cpf, senha, tipo, especialidade, descricao)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insercao->execute([
                $dados['nome'],
                $dados['email'],
                $telefone,
                password_hash($senha, PASSWORD_DEFAULT), // a senha nunca é guardada em texto puro
                $dados['tipo'],
                $ehBarbeiro && $dados['especialidade'] !== '' ? $dados['especialidade'] : null,
                $ehBarbeiro && $dados['descricao'] !== '' ? $dados['descricao'] : null,
            ]);

            definirMensagem('success', 'Sua conta foi criada! Agora é só entrar.');
            redirecionar('login.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') { // duplicidade que escapou da checagem acima
                $erros['email'] = 'Esse e-mail ou CPF já tem uma conta. Que tal entrar?';
            } else {
                error_log('Erro ao cadastrar: ' . $e->getMessage());
                $erros['geral'] = 'Não conseguimos criar sua conta agora. Tente de novo em instantes.';
            }
        }
    }
}

$ehBarbeiro   = $dados['tipo'] === 'barbeiro';
$tituloPagina = 'Criar conta';
require __DIR__ . '/includes/header.php';
?>

<section class="auth">
    <div class="container">
        <div class="auth-cartao">
            <div class="row g-0">
                <div class="col-lg-5 d-none d-lg-block">
                    <div class="auth-lateral">
                        <h2>Uma conta, dois jeitos de usar.</h2>
                        <p>Clientes reservam o horário do seu próximo corte. Barbeiros acompanham a agenda sem complicação.</p>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="auth-form">
                        <h1>Vamos criar sua conta?</h1>
                        <p class="texto-suave mb-4">Leva menos de um minuto. Já tem conta? <a href="login.php">Entrar</a></p>

                        <?php
                        if (isset($erros['geral'])) {
                            exibirErros([$erros['geral']]);
                        } elseif ($erros) {
                            exibirErros(['Quase lá! Confira os campos destacados abaixo.']);
                        }
                        ?>

                        <form method="post" action="cadastro.php">
                            <?= campoCsrf() ?>

                            <p class="form-label">Você é</p>
                            <div class="row g-3 mb-4">
                                <div class="col-sm-6">
                                    <input class="opcao-input visually-hidden" type="radio" name="tipo"
                                           id="tipo-cliente" value="cliente" <?= $ehBarbeiro ? '' : 'checked' ?>>
                                    <label class="opcao" for="tipo-cliente">
                                        <span class="marca"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
                                        <span class="opcao-titulo">Cliente</span>
                                        <span class="opcao-texto">Quero reservar meus horários.</span>
                                    </label>
                                </div>
                                <div class="col-sm-6">
                                    <input class="opcao-input visually-hidden" type="radio" name="tipo"
                                           id="tipo-barbeiro" value="barbeiro" <?= $ehBarbeiro ? 'checked' : '' ?>>
                                    <label class="opcao" for="tipo-barbeiro">
                                        <span class="marca"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
                                        <span class="opcao-titulo">Barbeiro</span>
                                        <span class="opcao-texto">Quero ver minha agenda.</span>
                                    </label>
                                </div>
                                <?php if (isset($erros['tipo'])): ?>
                                    <div class="col-12"><div class="text-danger small"><?= e($erros['tipo']) ?></div></div>
                                <?php endif; ?>
                            </div>

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

                                <!-- Aparece só para barbeiros (o JavaScript mostra e esconde) -->
                                <div class="col-12 campos-barbeiro<?= $ehBarbeiro ? '' : ' oculto' ?>" id="campos-barbeiro">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <p class="fw-semibold mb-0">Conte um pouco sobre você <span class="texto-suave fw-normal">(opcional)</span></p>
                                        </div>
                                        <div class="col-12">
                                            <label for="especialidade" class="form-label">Especialidade</label>
                                            <input type="text" class="form-control<?= classeInvalido($erros, 'especialidade') ?>" id="especialidade"
                                                   name="especialidade" value="<?= e($dados['especialidade']) ?>" maxlength="100"
                                                   placeholder="Ex.: degradê e barba desenhada">
                                            <?= erroCampo($erros, 'especialidade') ?>
                                        </div>
                                        <div class="col-12">
                                            <label for="descricao" class="form-label">Descrição profissional</label>
                                            <textarea class="form-control<?= classeInvalido($erros, 'descricao') ?>" id="descricao" name="descricao"
                                                      rows="3" maxlength="300"
                                                      placeholder="Fale da sua experiência em poucas linhas."><?= e($dados['descricao']) ?></textarea>
                                            <?= erroCampo($erros, 'descricao') ?>
                                        </div>
                                    </div>
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

<?php require __DIR__ . '/includes/footer.php'; ?>
