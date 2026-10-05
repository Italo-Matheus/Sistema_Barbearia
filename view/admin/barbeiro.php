<?php
require_once __DIR__ . '/../../model/dao/Conexao.php';
require_once __DIR__ . '/../../includes/funcoes.php';

exigirLogin('barbeiro', $pdo);
$usuario = carregarUsuario($pdo);

// Próximos agendamentos deste barbeiro, do mais próximo para o mais distante.
$consulta = $pdo->prepare(
    "SELECT a.id, a.data, a.horario, a.observacao, a.valor_total,
            c.nome AS cliente_nome,
            GROUP_CONCAT(s.nome ORDER BY s.id SEPARATOR ', ') AS servicos
     FROM agendamentos a
     JOIN usuarios c              ON c.id = a.cliente_id
     JOIN agendamento_servicos ags ON ags.agendamento_id = a.id
     JOIN servicos s              ON s.id = ags.servico_id
     WHERE a.barbeiro_id = :barbeiro
       AND a.status = 'agendado'
       AND (a.data > :hoje OR (a.data = :hoje_igual AND a.horario >= :agora))
     GROUP BY a.id, a.data, a.horario, a.observacao, a.valor_total, c.nome
     ORDER BY a.data, a.horario"
);
$consulta->execute([
    'barbeiro'   => $usuario['id'],
    'hoje'       => date('Y-m-d'),
    'hoje_igual' => date('Y-m-d'),
    'agora'      => date('H:i:s'),
]);
$agendamentos = $consulta->fetchAll();

$tituloPagina = 'Minha agenda';
require __DIR__ . '/../../includes/header.php';
?>

<section class="topo-pagina">
    <div class="container">
        <h1>Olá, <?= e(primeiroNome($usuario['nome'])) ?>!</h1>
        <p class="texto-suave mb-0">Confira seus próximos horários.</p>
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
                    <?php if (!empty($usuario['especialidade'])): ?>
                        <dt>Especialidade</dt>
                        <dd><?= e($usuario['especialidade']) ?></dd>
                    <?php endif; ?>
                    <?php if (!empty($usuario['descricao'])): ?>
                        <dt>Sobre você</dt>
                        <dd><?= e($usuario['descricao']) ?></dd>
                    <?php endif; ?>
                </dl>
            </aside>
        </div>

        <div class="col-lg-8">
            <h2 class="h4 mb-3">Próximos agendamentos</h2>

            <?php if (!$agendamentos): ?>
                <div class="cartao vazio">
                    <p class="mb-0">Sua agenda está livre por enquanto. Assim que um cliente reservar, o horário aparece aqui.</p>
                </div>
            <?php else: ?>
                <div class="cartao p-0 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table tabela-agenda align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Data</th>
                                    <th scope="col">Horário</th>
                                    <th scope="col">Cliente</th>
                                    <th scope="col">Serviços</th>
                                    <th scope="col" class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agendamentos as $agendamento): ?>
                                    <tr>
                                        <td class="text-nowrap">
                                            <?= e(formatarData($agendamento['data'])) ?>
                                            <?php if (rotuloDia($agendamento['data']) !== ''): ?>
                                                <span class="selo"><?= e(rotuloDia($agendamento['data'])) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="hora"><?= e(formatarHorario($agendamento['horario'])) ?></td>
                                        <td>
                                            <?= e($agendamento['cliente_nome']) ?>
                                            <?php if (!empty($agendamento['observacao'])): ?>
                                                <div class="obs"><i class="bi bi-chat-left-text" aria-hidden="true"></i> <?= e($agendamento['observacao']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($agendamento['servicos']) ?></td>
                                        <td class="text-end text-nowrap fw-semibold"><?= e(formatarPreco($agendamento['valor_total'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
