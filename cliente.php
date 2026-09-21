<?php
require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/includes/funcoes.php';

exigirLogin('cliente');
$usuario = carregarUsuario($pdo);

// Próximos agendamentos: de hoje em diante, sem contar horários que já passaram.
$consulta = $pdo->prepare(
    "SELECT a.id, a.data, a.horario, a.observacao, a.valor_total,
            b.nome AS barbeiro_nome,
            GROUP_CONCAT(s.nome ORDER BY s.id SEPARATOR ', ') AS servicos
     FROM agendamentos a
     JOIN usuarios b              ON b.id = a.barbeiro_id
     JOIN agendamento_servicos ags ON ags.agendamento_id = a.id
     JOIN servicos s              ON s.id = ags.servico_id
     WHERE a.cliente_id = :cliente
       AND a.status = 'agendado'
       AND (a.data > :hoje OR (a.data = :hoje_igual AND a.horario >= :agora))
     GROUP BY a.id, a.data, a.horario, a.observacao, a.valor_total, b.nome
     ORDER BY a.data, a.horario"
);
$consulta->execute([
    'cliente'    => $usuario['id'],
    'hoje'       => date('Y-m-d'),
    'hoje_igual' => date('Y-m-d'),
    'agora'      => date('H:i:s'),
]);
$agendamentos = $consulta->fetchAll();

$tituloPagina = 'Meus horários';
require __DIR__ . '/includes/header.php';
?>

<section class="topo-pagina">
    <div class="container d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div>
            <h1>Olá, <?= e(primeiroNome($usuario['nome'])) ?>!</h1>
            <p class="texto-suave mb-0">Confira seus próximos horários.</p>
        </div>
        <a class="btn btn-marrom btn-lg" href="agendamento.php">Novo agendamento</a>
    </div>
</section>

<div class="container conteudo">
    <div class="row g-4">
        <div class="col-lg-4">
            <aside class="cartao">
                <h2 class="h5 mb-3">Seus dados</h2>
                <dl class="dados mb-0">
                    <dt>Nome</dt>
                    <dd><?= e($usuario['nome']) ?></dd>
                    <dt>E-mail</dt>
                    <dd class="text-break"><?= e($usuario['email']) ?></dd>
                    <dt>Telefone</dt>
                    <dd><?= e(formatarTelefone($usuario['telefone'])) ?></dd>
                    <?php if (!empty($usuario['cpf'])): ?>
                        <dt>CPF</dt>
                        <dd><?= e(formatarCpf($usuario['cpf'])) ?></dd>
                    <?php endif; ?>
                </dl>
            </aside>
        </div>

        <div class="col-lg-8">
            <h2 class="h4 mb-3">Próximos horários</h2>

            <?php if (!$agendamentos): ?>
                <div class="cartao vazio">
                    <p class="mb-3">Você ainda não tem nenhum horário marcado. Vamos reservar o primeiro?</p>
                    <a class="btn btn-marrom" href="agendamento.php">Agendar horário</a>
                </div>
            <?php else: ?>
                <div class="d-grid gap-3">
                    <?php foreach ($agendamentos as $agendamento): ?>
                        <?php $partes = dataPartes($agendamento['data']); ?>
                        <article class="agendamento">
                            <div class="data-bloco" aria-hidden="true">
                                <span class="dia"><?= e($partes['dia']) ?></span>
                                <span class="mes"><?= e($partes['mes_curto']) ?></span>
                            </div>
                            <div class="agendamento-info">
                                <p class="agendamento-servicos"><?= e($agendamento['servicos']) ?></p>
                                <p class="agendamento-detalhe">
                                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                                    <?= e(dataExtenso($agendamento['data'])) ?>
                                    <?php if (rotuloDia($agendamento['data']) !== ''): ?>
                                        <span class="selo"><?= e(rotuloDia($agendamento['data'])) ?></span>
                                    <?php endif; ?>
                                </p>
                                <p class="agendamento-detalhe">
                                    <i class="bi bi-clock" aria-hidden="true"></i> <?= e(formatarHorario($agendamento['horario'])) ?>
                                </p>
                                <p class="agendamento-detalhe">
                                    <i class="bi bi-person" aria-hidden="true"></i> Com <?= e($agendamento['barbeiro_nome']) ?>
                                </p>
                                <?php if (!empty($agendamento['observacao'])): ?>
                                    <p class="agendamento-detalhe">
                                        <i class="bi bi-chat-left-text" aria-hidden="true"></i> <?= e($agendamento['observacao']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="agendamento-valor"><?= e(formatarPreco($agendamento['valor_total'])) ?></div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
