<?php require __DIR__ . '/../../includes/header.php'; ?>

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

        <form method="post" action="agendamento2.php">
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

        <form method="post" action="agendamento2.php">
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

<?php require __DIR__ . '/../../includes/footer.php'; ?>
