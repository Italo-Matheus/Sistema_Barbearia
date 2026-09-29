<?php
require_once __DIR__ . '/../model/dao/Conexao.php';
require_once __DIR__ . '/../includes/funcoes.php';

// Os serviços e os preços vêm do banco, nada fica fixo no HTML.
$servicos = $pdo->query(
    'SELECT nome, descricao, preco, duracao FROM servicos WHERE ativo = 1 ORDER BY id LIMIT 6'
)->fetchAll();

// A "comanda" do topo usa os três primeiros serviços como exemplo.
$exemplo      = array_slice($servicos, 0, 3);
$totalExemplo = array_sum(array_column($exemplo, 'preco'));

$tituloPagina = 'Barbearia moderna e acolhedora';
require __DIR__ . '/../includes/header.php';
?>

<!-- Apresentação -->
<section class="hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <h1>Qualidade garantida! Venha e agende seu horário no conforto da sua casa.</h1>
                <p class="lead mt-4 mb-4">
                    Na <?= e(NOME_BARBEARIA) ?> você escolhe os serviços, o barbeiro de confiança
                    e o melhor horário em poucos cliques. É só chegar, sentar e relaxar.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a class="btn btn-ouro btn-lg" href="agendamento.php">Agendar horário</a>
                    <?php if (!usuarioLogado()): ?>
                        <a class="btn btn-contorno-claro btn-lg" href="cadastro.php">Criar conta</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="listra" aria-hidden="true"></div>
</section>



<!-- Serviços -->
<section class="secao" id="servicos">
    <div class="container">
        <div class="secao-titulo">
            <h2>Escolha seu corte</h2>
            <p>Cada serviço é feito com calma e atenção. Os valores abaixo são os mesmos que você vê na hora de reservar.</p>
        </div>

        <?php if ($servicos): ?>
            <div class="quadro-precos">
                <?php foreach ($servicos as $servico): ?>
                    <div class="item-servico">
                        <div class="linha-preco">
                            <span class="nome"><?= e($servico['nome']) ?></span>
                            <span class="pontilhado"></span>
                            <span class="preco"><?= e(formatarPreco($servico['preco'])) ?></span>
                        </div>
                        <p class="detalhe">
                            <?= e($servico['descricao']) ?>
                            <span class="text-nowrap">Duração: <?= (int) $servico['duracao'] ?> min.</span>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
            <a class="btn btn-marrom btn-lg mt-5" href="agendamento.php">Reservar meu horário</a>
        <?php else: ?>
            <p class="texto-suave">Nossos serviços serão publicados em breve. Volte logo!</p>
        <?php endif; ?>
    </div>
</section>

<!-- Como funciona -->
<section class="secao secao-areia" id="como-funciona">
    <div class="container">
        <div class="secao-titulo">
            <h2>Reservar é simples</h2>
            <p>Quatro passos e o seu horário fica guardado.</p>
        </div>

        <ol class="passos">
            <li class="passo">
                <h3>Crie sua conta</h3>
                <p>Leva menos de um minuto. Escolha “Cliente” e preencha seus dados.</p>
            </li>
            <li class="passo">
                <h3>Escolha o que você quer</h3>
                <p>Selecione um ou mais serviços e o barbeiro de sua confiança.</p>
            </li>
            <li class="passo">
                <h3>Marque dia e horário</h3>
                <p>Veja só os horários livres e reserve o que combina com a sua rotina.</p>
            </li>
            <li class="passo">
                <h3>É só aparecer</h3>
                <p>Seu horário fica na sua área. Confira quando quiser.</p>
            </li>
        </ol>
    </div>
</section>

<!-- Chamada final -->
<section class="chamada">
    <div class="container text-center">
        <h2>Vamos reservar seu horário?</h2>
        <p class="mb-4">Seu próximo corte está a poucos cliques de distância.</p>
        <a class="btn btn-ouro btn-lg" href="agendamento.php">Agendar horário</a>
    </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
