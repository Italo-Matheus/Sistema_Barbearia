<?php
require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/includes/funcoes.php';

// Só clientes logados agendam.
exigirLogin('cliente');

$clienteId  = (int) $_SESSION['usuario_id'];
$hoje       = date('Y-m-d');
$dataMaxima = date('Y-m-d', strtotime('+' . DIAS_MAXIMOS_AGENDAMENTO . ' days'));

// ---------------------------------------------------------------------
// Funções usadas só nesta página
// ---------------------------------------------------------------------

/** Devolve uma mensagem de erro, ou texto vazio se a data estiver ok. */
function validarDataAgendamento(string $data, string $hoje, string $dataMaxima): string
{
    $objeto = DateTime::createFromFormat('Y-m-d', $data);
    if (!$objeto || $objeto->format('Y-m-d') !== $data) {
        return 'Escolha uma data válida.';
    }
    if ($data < $hoje) {
        return 'Escolha uma data a partir de hoje.';
    }
    if ($data > $dataMaxima) {
        return 'Por enquanto, as reservas abrem para os próximos ' . DIAS_MAXIMOS_AGENDAMENTO . ' dias.';
    }
    return '';
}

/**
 * Monta a lista de horários do dia para um barbeiro.
 * Cada horário é 'livre', 'ocupado' (já reservado) ou 'passou' (hoje, já foi).
 */
function montarHorarios(PDO $pdo, int $barbeiroId, string $data, string $hoje): array
{
    $consulta = $pdo->prepare(
        "SELECT horario FROM agendamentos
         WHERE barbeiro_id = ? AND data = ? AND status <> 'cancelado'"
    );
    $consulta->execute([$barbeiroId, $data]);
    $ocupados = array_map('formatarHorario', $consulta->fetchAll(PDO::FETCH_COLUMN));

    $agora = date('H:i');
    $lista = [];
    foreach (HORARIOS_ATENDIMENTO as $horario) {
        if (in_array($horario, $ocupados, true)) {
            $lista[$horario] = 'ocupado';
        } elseif ($data === $hoje && $horario <= $agora) {
            $lista[$horario] = 'passou';
        } else {
            $lista[$horario] = 'livre';
        }
    }
    return $lista;
}

/**
 * Grava o agendamento e seus serviços numa transação.
 * Devolve false se o horário foi ocupado por outra pessoa nesse meio tempo.
 */
function criarAgendamento(
    PDO $pdo, int $clienteId, int $barbeiroId, string $data,
    string $horario, string $observacao, array $servicos, float $total
): bool {
    $pdo->beginTransaction();
    try {
        // Trava a linha do barbeiro: se dois clientes tentarem o mesmo horário ao mesmo
        // tempo, o segundo espera o primeiro terminar e então encontra o horário ocupado.
        $trava = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND tipo = 'barbeiro' FOR UPDATE");
        $trava->execute([$barbeiroId]);
        $trava->closeCursor();

        // Verificação principal: mesmo barbeiro + mesma data + mesmo horário.
        $conflito = $pdo->prepare(
            "SELECT COUNT(*) FROM agendamentos
             WHERE barbeiro_id = ? AND data = ? AND horario = ? AND status <> 'cancelado'"
        );
        $conflito->execute([$barbeiroId, $data, $horario]);
        if ((int) $conflito->fetchColumn() > 0) {
            $pdo->rollBack();
            return false;
        }

        $agendamento = $pdo->prepare(
            'INSERT INTO agendamentos (cliente_id, barbeiro_id, data, horario, observacao, valor_total)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $agendamento->execute([
            $clienteId, $barbeiroId, $data, $horario,
            $observacao !== '' ? $observacao : null,
            number_format($total, 2, '.', ''),
        ]);
        $agendamentoId = (int) $pdo->lastInsertId();

        // O preço de cada serviço é copiado para cá: mudar o preço depois não afeta este agendamento.
        $item = $pdo->prepare('INSERT INTO agendamento_servicos (agendamento_id, servico_id, valor) VALUES (?, ?, ?)');
        foreach ($servicos as $servico) {
            $item->execute([$agendamentoId, (int) $servico['id'], $servico['preco']]);
        }

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

// ---------------------------------------------------------------------
// Dados vindos do banco (preços e barbeiros nunca vêm do formulário)
// ---------------------------------------------------------------------
$servicos  = $pdo->query('SELECT id, nome, descricao, preco, duracao FROM servicos WHERE ativo = 1 ORDER BY id')->fetchAll();
$barbeiros = $pdo->query("SELECT id, nome, especialidade, descricao FROM usuarios WHERE tipo = 'barbeiro' ORDER BY nome")->fetchAll();

$servicosPorId  = [];
$barbeirosPorId = [];
foreach ($servicos as $servico) {
    $servicosPorId[(int) $servico['id']] = $servico;
}
foreach ($barbeiros as $barbeiro) {
    $barbeirosPorId[(int) $barbeiro['id']] = $barbeiro;
}

// ---------------------------------------------------------------------
// O que o cliente enviou
// ---------------------------------------------------------------------
$acao       = campo('acao');
$barbeiroId = (int) campo('barbeiro_id');
$data       = campo('data');
$horario    = campo('horario');
$observacao = campo('observacao');

$idsServicos = [];
if (isset($_POST['servicos']) && is_array($_POST['servicos'])) {
    foreach ($_POST['servicos'] as $id) {
        if (is_scalar($id)) {
            $idsServicos[] = (int) $id;
        }
    }
    $idsServicos = array_values(array_unique($idsServicos));
}

// Serviços escolhidos e total, sempre calculados com os preços do banco.
$servicosEscolhidos = [];
$totalEscolhido     = 0.0;
foreach ($idsServicos as $id) {
    if (isset($servicosPorId[$id])) {
        $servicosEscolhidos[] = $servicosPorId[$id];
        $totalEscolhido      += (float) $servicosPorId[$id]['preco'];
    }
}
$totalEscolhido = round($totalEscolhido, 2);

// ---------------------------------------------------------------------
// Fluxo: etapa 1 (serviços, barbeiro e dia) -> etapa 2 (horário e confirmação)
// ---------------------------------------------------------------------
$etapa    = 1;
$erros    = [];
$horarios = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($acao, ['ver_horarios', 'confirmar'], true)) {
    if (!csrfValido()) {
        $erros[] = 'Sua sessão expirou. Recarregue a página e tente de novo.';
    } else {
        if (!$idsServicos) {
            $erros[] = 'Escolha pelo menos um serviço.';
        } elseif (count($servicosEscolhidos) !== count($idsServicos)) {
            $erros[] = 'Um dos serviços escolhidos não está mais disponível. Escolha novamente.';
        }
        if (!isset($barbeirosPorId[$barbeiroId])) {
            $erros[] = 'Escolha um barbeiro.';
        }
        $erroData = validarDataAgendamento($data, $hoje, $dataMaxima);
        if ($erroData !== '') {
            $erros[] = $erroData;
        }

        if (!$erros) {
            $etapa    = 2;
            $horarios = montarHorarios($pdo, $barbeiroId, $data, $hoje);

            if ($acao === 'confirmar') {
                if (strlen($observacao) > 255) {
                    $erros[] = 'A observação pode ter até 255 caracteres.';
                }
                if (!isset($horarios[$horario])) {
                    $erros[] = 'Escolha um horário para continuar.';
                } elseif ($horarios[$horario] === 'passou') {
                    $erros[] = 'Esse horário já passou. Escolha outro horário.';
                    $horario = '';
                } elseif ($horarios[$horario] === 'ocupado') {
                    $erros[] = 'Esse horário acabou de ser reservado. Escolha outro horário.';
                    $horario = '';
                }

                if (!$erros) {
                    try {
                        $reservado = criarAgendamento(
                            $pdo, $clienteId, $barbeiroId, $data, $horario,
                            $observacao, $servicosEscolhidos, $totalEscolhido
                        );
                        if ($reservado) {
                            definirMensagem('success', 'Seu horário foi reservado com sucesso! Te esperamos por aqui.');
                            redirecionar('cliente.php');
                        }
                        // Alguém reservou no mesmo instante: mostra os horários atualizados.
                        $erros[]  = 'Esse horário acabou de ser reservado. Escolha outro horário.';
                        $horario  = '';
                        $horarios = montarHorarios($pdo, $barbeiroId, $data, $hoje);
                    } catch (Throwable $e) {
                        error_log('Erro ao agendar: ' . $e->getMessage());
                        $erros[] = 'Não conseguimos reservar seu horário agora. Tente de novo em instantes.';
                    }
                }
            }
        }
    }
}

$temHorarioLivre = in_array('livre', $horarios, true);
$tituloPagina    = 'Agendar horário';
require __DIR__ . '/includes/header.php';
?>

<section class="topo-pagina">
    <div class="container">
        <p class="texto-suave mb-2">Etapa <?= $etapa ?> de 2</p>
        <?php if ($etapa === 1): ?>
            <h1>Escolha seu corte</h1>
            <p class="texto-suave mb-0">Vamos reservar seu horário? Selecione os serviços, o barbeiro e o dia.</p>
        <?php else: ?>
            <h1>Escolha seu horário</h1>
            <p class="texto-suave mb-0">Falta pouco! Confira o resumo e confirme a reserva.</p>
        <?php endif; ?>
    </div>
</section>

<div class="container conteudo">
    <?php exibirErros($erros); ?>

    <?php if ($etapa === 1): ?>
        <!-- ============ ETAPA 1: serviços, barbeiro e data ============ -->
        <?php if (!$servicos || !$barbeiros): ?>
            <div class="alert alert-info">
                Ainda estamos preparando a agenda: precisamos de pelo menos um serviço e um barbeiro cadastrados.
                Volte em breve!
            </div>
        <?php endif; ?>

        <form method="post" action="agendamento.php">
            <?= campoCsrf() ?>

            <div class="row g-4">
                <div class="col-lg-8">
                    <h2 class="h4 mb-3">Serviços</h2>
                    <p class="texto-suave">Você pode escolher mais de um.</p>
                    <div class="row g-3 mb-5">
                        <?php foreach ($servicos as $servico): ?>
                            <?php $idServico = (int) $servico['id']; ?>
                            <div class="col-md-6">
                                <input class="opcao-input visually-hidden" type="checkbox" name="servicos[]"
                                       id="servico-<?= $idServico ?>" value="<?= $idServico ?>"
                                       data-nome="<?= e($servico['nome']) ?>" data-preco="<?= e($servico['preco']) ?>"
                                       <?= in_array($idServico, $idsServicos, true) ? 'checked' : '' ?>>
                                <label class="opcao" for="servico-<?= $idServico ?>">
                                    <span class="marca"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
                                    <span class="opcao-titulo"><?= e($servico['nome']) ?></span>
                                    <span class="opcao-texto"><?= e($servico['descricao']) ?></span>
                                    <span class="opcao-rodape">
                                        <span><i class="bi bi-clock" aria-hidden="true"></i> <?= (int) $servico['duracao'] ?> min.</span>
                                        <strong><?= e(formatarPreco($servico['preco'])) ?></strong>
                                    </span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <h2 class="h4 mb-3">Barbeiro</h2>
                    <div class="row g-3 mb-5">
                        <?php foreach ($barbeiros as $barbeiro): ?>
                            <?php $idBarbeiro = (int) $barbeiro['id']; ?>
                            <div class="col-md-6">
                                <input class="opcao-input visually-hidden" type="radio" name="barbeiro_id"
                                       id="barbeiro-<?= $idBarbeiro ?>" value="<?= $idBarbeiro ?>" required
                                       <?= $barbeiroId === $idBarbeiro ? 'checked' : '' ?>>
                                <label class="opcao" for="barbeiro-<?= $idBarbeiro ?>">
                                    <span class="marca"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
                                    <span class="opcao-titulo"><?= e($barbeiro['nome']) ?></span>
                                    <span class="opcao-texto"><?= e($barbeiro['especialidade'] ?: 'Barbeiro da casa') ?></span>
                                    <?php if (!empty($barbeiro['descricao'])): ?>
                                        <span class="opcao-texto"><?= e($barbeiro['descricao']) ?></span>
                                    <?php endif; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <h2 class="h4 mb-3">Dia</h2>
                    <label for="data" class="form-label">Escolha o dia da sua visita</label>
                    <input type="date" class="form-control campo-data" id="data" name="data"
                           value="<?= e($data) ?>" min="<?= e($hoje) ?>" max="<?= e($dataMaxima) ?>" required>
                    <div class="form-text">As reservas abrem para os próximos <?= DIAS_MAXIMOS_AGENDAMENTO ?> dias.</div>
                </div>

                <div class="col-lg-4">
                    <aside class="cartao resumo">
                        <h2 class="h5 mb-3">Seu pedido</h2>
                        <div id="resumo-servicos">
                            <?php if ($servicosEscolhidos): ?>
                                <?php foreach ($servicosEscolhidos as $servico): ?>
                                    <div class="resumo-linha">
                                        <span><?= e($servico['nome']) ?></span>
                                        <span><?= e(formatarPreco($servico['preco'])) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="texto-suave mb-0">Os serviços que você escolher aparecem aqui.</p>
                            <?php endif; ?>
                        </div>
                        <div class="resumo-total">
                            <span>Total</span>
                            <span id="resumo-total"><?= e(formatarPreco($totalEscolhido)) ?></span>
                        </div>
                        <button type="submit" name="acao" value="ver_horarios" class="btn btn-marrom btn-lg w-100 mt-4"
                                <?= (!$servicos || !$barbeiros) ? 'disabled' : '' ?>>
                            Ver horários livres
                        </button>
                    </aside>
                </div>
            </div>
        </form>

    <?php else: ?>
        <!-- ============ ETAPA 2: horário, observação e confirmação ============ -->
        <?php $barbeiroEscolhido = $barbeirosPorId[$barbeiroId]; ?>

        <form method="post" action="agendamento.php">
            <?= campoCsrf() ?>
            <?php foreach ($servicosEscolhidos as $servico): ?>
                <input type="hidden" name="servicos[]" value="<?= (int) $servico['id'] ?>">
            <?php endforeach; ?>
            <input type="hidden" name="barbeiro_id" value="<?= $barbeiroId ?>">
            <input type="hidden" name="data" value="<?= e($data) ?>">

            <div class="row g-4">
                <div class="col-lg-7">
                    <h2 class="h4 mb-1">Horários de <?= e(dataExtenso($data)) ?></h2>
                    <p class="texto-suave">Com <?= e($barbeiroEscolhido['nome']) ?>. Os horários riscados já foram reservados.</p>

                    <?php if (!$temHorarioLivre): ?>
                        <div class="alert alert-warning">
                            Não há horários livres nesse dia com esse barbeiro. Que tal escolher outro dia ou outro barbeiro?
                        </div>
                    <?php endif; ?>

                    <div class="row g-2 row-cols-3 row-cols-sm-4 mb-4">
                        <?php foreach ($horarios as $hora => $situacao): ?>
                            <?php $idHora = 'hora-' . str_replace(':', '', $hora); ?>
                            <div class="col">
                                <input class="opcao-input visually-hidden" type="radio" name="horario"
                                       id="<?= $idHora ?>" value="<?= e($hora) ?>" required
                                       <?= $situacao !== 'livre' ? 'disabled' : '' ?>
                                       <?= $horario === $hora ? 'checked' : '' ?>>
                                <label class="opcao opcao-horario" for="<?= $idHora ?>">
                                    <span class="hora-valor"><?= e($hora) ?></span>
                                    <?php if ($situacao !== 'livre'): ?>
                                        <small><?= $situacao === 'ocupado' ? 'Reservado' : 'Já passou' ?></small>
                                    <?php endif; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <label for="observacao" class="form-label">Observação <span class="texto-suave fw-normal">(opcional)</span></label>
                    <textarea class="form-control" id="observacao" name="observacao" rows="3" maxlength="255"
                              placeholder="Ex.: quero manter o comprimento em cima."><?= e($observacao) ?></textarea>
                </div>

                <div class="col-lg-5">
                    <aside class="cartao resumo">
                        <h2 class="h5 mb-3">Resumo da reserva</h2>

                        <?php foreach ($servicosEscolhidos as $servico): ?>
                            <div class="resumo-linha">
                                <span><?= e($servico['nome']) ?></span>
                                <span><?= e(formatarPreco($servico['preco'])) ?></span>
                            </div>
                        <?php endforeach; ?>

                        <div class="resumo-total">
                            <span>Total</span>
                            <span><?= e(formatarPreco($totalEscolhido)) ?></span>
                        </div>

                        <dl class="resumo-detalhes">
                            <dt>Barbeiro</dt>
                            <dd><?= e($barbeiroEscolhido['nome']) ?></dd>
                            <dt>Data</dt>
                            <dd><?= e(dataExtenso($data)) ?></dd>
                            <dt>Horário</dt>
                            <dd id="resumo-horario"><?= $horario !== '' ? e($horario) : 'Escolha ao lado' ?></dd>
                        </dl>

                        <button type="submit" name="acao" value="confirmar" class="btn btn-marrom btn-lg w-100"
                                <?= $temHorarioLivre ? '' : 'disabled' ?>>
                            Confirmar agendamento
                        </button>
                        <button type="submit" name="acao" value="alterar" class="btn btn-contorno w-100 mt-2" formnovalidate>
                            Alterar minhas escolhas
                        </button>
                    </aside>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
