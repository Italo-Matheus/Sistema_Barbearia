<?php
require_once __DIR__ . '/../../model/dao/Conexao.php';
require_once __DIR__ . '/../../includes/funcoes.php';
require_once __DIR__ . '/../../model/AdminModel.php';
require_once __DIR__ . '/../../controller/AdminController.php';

$modeloAdmin = new AdminModel($pdo);
$controller = new AdminController($pdo, $modeloAdmin);
$enviado = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$dados = $controller->executar($_POST, $enviado, $enviado && csrfValido());
$adminAtual = $dados['adminAtual'];
$dadosPainel = $dados['dadosPainel'];
$usuarioEdicao = $dados['usuarioEdicao'];
$servicoEdicao = $dados['servicoEdicao'];
$agendamentoEdicao = $dados['agendamentoEdicao'];
$opcoesAgendamento = $dados['opcoesAgendamento'];
$erroAcao = $dados['erroAcao'];
$horariosPorDia = array_fill(0, 7, []);
foreach ($dadosPainel['horarios'] as $horarioCadastrado) {
    $horariosPorDia[(int) $horarioCadastrado['dia_semana']][] = $horarioCadastrado;
}
$tituloPagina = 'Painel administrativo';
require __DIR__ . '/../../includes/header.php';
?>

<section class="topo-pagina">
    <div class="container">
        <p class="texto-suave mb-2">Administração</p>
        <h1>Painel master</h1>
        <p class="texto-suave mb-3">Olá, <?= e(primeiroNome($adminAtual['nome'])) ?>. Gerencie usuários, serviços, preços e horários de atendimento.</p>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-ouro" href="#agendamentos">Gerenciar agendamentos</a>
            <a class="btn btn-ouro" href="#gerenciar-servicos">Gerenciar cortes e preços</a>
            <a class="btn btn-contorno-claro" href="#gerenciar-horarios">Configurar horários</a>
        </div>
    </div>
</section>

<div class="container conteudo">
    <?php if ($erroAcao !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= e($erroAcao) ?></div>
    <?php endif; ?>

    <section aria-label="Métricas do sistema" class="row g-3 mb-5">
        <div class="col-md-4">
            <article class="cartao h-100">
                <p class="texto-suave mb-1">Total de clientes</p>
                <p class="display-6 fw-semibold mb-0"><?= (int) $dadosPainel['metricas']['clientes'] ?></p>
            </article>
        </div>
        <div class="col-md-4">
            <article class="cartao h-100">
                <p class="texto-suave mb-1">Total de barbeiros</p>
                <p class="display-6 fw-semibold mb-0"><?= (int) $dadosPainel['metricas']['barbeiros'] ?></p>
            </article>
        </div>
        <div class="col-md-4">
            <article class="cartao h-100">
                <p class="texto-suave mb-1">Total de agendamentos</p>
                <p class="display-6 fw-semibold mb-0"><?= (int) $dadosPainel['metricas']['agendamentos'] ?></p>
            </article>
        </div>
    </section>

    <?php if ($usuarioEdicao): ?>
        <section class="mb-5" id="editar-usuario">
            <h2 class="h4 mb-3">Editar usuário</h2>
            <form class="cartao" method="post" action="view/admin/dashboard.php">
                <?= campoCsrf() ?>
                <input type="hidden" name="acao" value="salvar_usuario">
                <input type="hidden" name="id" value="<?= (int) $usuarioEdicao['id'] ?>">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nome" class="form-label">Nome completo</label>
                        <input class="form-control" id="nome" name="nome" maxlength="120" required value="<?= e($usuarioEdicao['nome']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">E-mail</label>
                        <input class="form-control" id="email" name="email" type="email" maxlength="160" required value="<?= e($usuarioEdicao['email']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input class="form-control" id="telefone" name="telefone" inputmode="tel" required value="<?= e($usuarioEdicao['telefone']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="cpf" class="form-label">CPF</label>
                        <input class="form-control" id="cpf" name="cpf" inputmode="numeric" value="<?= e($usuarioEdicao['cpf'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="tipo" class="form-label">Perfil</label>
                        <select class="form-select" id="tipo" name="tipo" required>
                            <option value="cliente" <?= $usuarioEdicao['tipo'] === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                            <option value="barbeiro" <?= $usuarioEdicao['tipo'] === 'barbeiro' ? 'selected' : '' ?>>Barbeiro</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="especialidade" class="form-label">Especialidade</label>
                        <input class="form-control" id="especialidade" name="especialidade" maxlength="100" value="<?= e($usuarioEdicao['especialidade'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="descricao" class="form-label">Descrição</label>
                        <input class="form-control" id="descricao" name="descricao" maxlength="300" value="<?= e($usuarioEdicao['descricao'] ?? '') ?>">
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button class="btn btn-marrom" type="submit" data-confirmar="Salvar as alterações deste usuário?" data-confirmar-titulo="Salvar alterações?" data-confirmar-tipo="info">Salvar alterações</button>
                    <a class="btn btn-contorno" href="view/admin/dashboard.php">Cancelar</a>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <section class="mb-5">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div>
                <h2 class="h4 mb-1">Usuários</h2>
                <p class="texto-suave mb-0">Administradores são protegidos contra alterações neste painel.</p>
            </div>
            <span class="texto-suave"><?= count($dadosPainel['usuarios']) ?> contas</span>
        </div>
        <div class="cartao p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table tabela-agenda align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Usuário</th>
                            <th scope="col">Contato</th>
                            <th scope="col">Perfil</th>
                            <th scope="col">Situação</th>
                            <th scope="col">Cadastro</th>
                            <th scope="col">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($dadosPainel['usuarios'] as $usuario): ?>
                        <tr>
                            <td>
                                <strong><?= e($usuario['nome']) ?></strong>
                                <?php if (!empty($usuario['cpf'])): ?>
                                    <div class="texto-suave small">CPF <?= e(formatarCpf($usuario['cpf'])) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div><?= e($usuario['email']) ?></div>
                                <div class="texto-suave small"><?= e(formatarTelefone($usuario['telefone'])) ?></div>
                            </td>
                            <td><?= e(ucfirst($usuario['tipo'])) ?></td>
                            <td><?= (bool) $usuario['ativo'] ? 'Ativo' : 'Bloqueado' ?></td>
                            <td class="text-nowrap"><?= e(formatarData(substr($usuario['criado_em'], 0, 10))) ?></td>
                            <td>
                                <?php if (in_array($usuario['tipo'], ['cliente', 'barbeiro'], true)): ?>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a class="btn btn-sm btn-contorno" href="view/admin/dashboard.php?editar=<?= (int) $usuario['id'] ?>#editar-usuario">Editar</a>
                                        <form method="post" action="view/admin/dashboard.php">
                                            <?= campoCsrf() ?>
                                            <input type="hidden" name="acao" value="alternar_ativo">
                                            <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">
                                            <button class="btn btn-sm btn-contorno" type="submit" data-confirmar="<?= (bool) $usuario['ativo'] ? 'Desativar esta conta?' : 'Ativar esta conta?' ?>" data-confirmar-titulo="<?= (bool) $usuario['ativo'] ? 'Desativar usuário?' : 'Ativar usuário?' ?>" data-confirmar-tipo="aviso"><?= (bool) $usuario['ativo'] ? 'Desativar' : 'Ativar' ?></button>
                                        </form>
                                        <form method="post" action="view/admin/dashboard.php">
                                            <?= campoCsrf() ?>
                                            <input type="hidden" name="acao" value="alterar_tipo">
                                            <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">
                                            <select class="form-select form-select-sm" name="tipo" aria-label="Novo perfil para <?= e($usuario['nome']) ?>">
                                                <option value="cliente" <?= $usuario['tipo'] === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                                                <option value="barbeiro" <?= $usuario['tipo'] === 'barbeiro' ? 'selected' : '' ?>>Barbeiro</option>
                                            </select>
                                            <button class="btn btn-sm btn-contorno mt-1" type="submit" data-confirmar="Alterar o perfil deste usuário?" data-confirmar-titulo="Alterar perfil?" data-confirmar-tipo="aviso">Alterar perfil</button>
                                        </form>
                                        <form method="post" action="view/admin/dashboard.php">
                                            <?= campoCsrf() ?>
                                            <input type="hidden" name="acao" value="excluir_usuario">
                                            <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit" data-confirmar="Excluir esta conta? Agendamentos associados impedem a exclusão." data-confirmar-titulo="Excluir usuário?" data-confirmar-tipo="perigo" data-confirmar-botao="Sim, excluir">Excluir</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="texto-suave">Protegido</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$dadosPainel['usuarios']): ?>
                        <tr><td colspan="6" class="text-center texto-suave py-4">Nenhum usuário cadastrado.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <?php if ($agendamentoEdicao && $opcoesAgendamento): ?>
        <?php
        $servicosAgendamentoSelecionados = array_map('intval', $agendamentoEdicao['servico_ids'] ?? []);
        $dataAgendamentoEdicao = substr((string) ($agendamentoEdicao['data'] ?? ''), 0, 10);
        $horarioAgendamentoEdicao = substr((string) ($agendamentoEdicao['horario'] ?? ''), 0, 5);
        ?>
        <section class="mb-5" id="editar-agendamento">
            <h2 class="h4 mb-3">Editar agendamento #<?= (int) $agendamentoEdicao['id'] ?></h2>
            <p class="texto-suave">Cliente: <?= e($agendamentoEdicao['cliente_nome']) ?>. A alteração atualiza também o valor total conforme os serviços escolhidos.</p>
            <form class="cartao" method="post" action="view/admin/dashboard.php#editar-agendamento">
                <?= campoCsrf() ?>
                <input type="hidden" name="acao" value="salvar_agendamento">
                <input type="hidden" name="id" value="<?= (int) $agendamentoEdicao['id'] ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="agendamento-barbeiro">Barbeiro</label>
                        <select class="form-select" id="agendamento-barbeiro" name="barbeiro_id" required>
                            <?php foreach ($opcoesAgendamento['barbeiros'] as $barbeiro): ?>
                                <option value="<?= (int) $barbeiro['id'] ?>"
                                    <?= (int) $agendamentoEdicao['barbeiro_id'] === (int) $barbeiro['id'] ? 'selected' : '' ?>>
                                    <?= e($barbeiro['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="agendamento-data">Data</label>
                        <input class="form-control" id="agendamento-data" name="data" type="date"
                               value="<?= e($dataAgendamentoEdicao) ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="agendamento-horario">Horário</label>
                        <input class="form-control" id="agendamento-horario" name="horario" type="time"
                               value="<?= e($horarioAgendamentoEdicao) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="agendamento-status">Status</label>
                        <select class="form-select" id="agendamento-status" name="status" required>
                            <?php foreach (['agendado' => 'Agendado', 'concluido' => 'Concluído', 'cancelado' => 'Cancelado'] as $valorStatus => $rotuloStatus): ?>
                                <option value="<?= e($valorStatus) ?>"
                                    <?= $agendamentoEdicao['status'] === $valorStatus ? 'selected' : '' ?>>
                                    <?= e($rotuloStatus) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <fieldset>
                            <legend class="form-label">Serviços</legend>
                            <div class="row g-2">
                                <?php foreach ($opcoesAgendamento['servicos'] as $servico): ?>
                                    <?php $servicoId = (int) $servico['id']; ?>
                                    <div class="col-md-6 col-lg-4">
                                        <label class="form-check cartao py-2 px-3 h-100">
                                            <input class="form-check-input" type="checkbox" name="servicos[]"
                                                   value="<?= $servicoId ?>"
                                                <?= in_array($servicoId, $servicosAgendamentoSelecionados, true) ? 'checked' : '' ?>>
                                            <span class="form-check-label">
                                                <?= e($servico['nome']) ?> · <?= e(formatarPreco($servico['preco'])) ?>
                                            </span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="agendamento-observacao">Observação</label>
                        <textarea class="form-control" id="agendamento-observacao" name="observacao"
                                  maxlength="255" rows="2"><?= e($agendamentoEdicao['observacao'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button class="btn btn-marrom" type="submit"
                            data-confirmar="Salvar as alterações deste agendamento?"
                            data-confirmar-titulo="Atualizar agendamento?" data-confirmar-tipo="info">
                        Salvar alterações
                    </button>
                    <a class="btn btn-contorno" href="view/admin/dashboard.php">Cancelar</a>
                </div>
            </form>
        </section>
    <?php endif; ?>

    <section class="mb-5" id="agendamentos">
        <h2 class="h4 mb-3">Agendamentos recentes (até 100)</h2>
        <div class="cartao p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table tabela-agenda align-middle mb-0">
                    <thead><tr><th>Data</th><th>Horário</th><th>Cliente</th><th>Barbeiro</th><th>Serviços</th><th>Status</th><th class="text-end">Total</th><th>Ações</th></tr></thead>
                    <tbody>
                    <?php foreach ($dadosPainel['agendamentosRecentes'] as $agendamento): ?>
                        <tr>
                            <td class="text-nowrap"><?= e(formatarData($agendamento['data'])) ?></td>
                            <td><?= e(formatarHorario($agendamento['horario'])) ?></td>
                            <td><?= e($agendamento['cliente_nome']) ?></td>
                            <td><?= e($agendamento['barbeiro_nome']) ?></td>
                            <td><?= e($agendamento['servicos'] ?? '') ?></td>
                            <td><?= e(ucfirst($agendamento['status'])) ?></td>
                            <td class="text-end text-nowrap"><?= e(formatarPreco($agendamento['valor_total'])) ?></td>
                            <td>
                                <a class="btn btn-sm btn-contorno"
                                   href="view/admin/dashboard.php?editar_agendamento=<?= (int) $agendamento['id'] ?>#editar-agendamento">
                                    Editar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$dadosPainel['agendamentosRecentes']): ?>
                        <tr><td colspan="8" class="text-center texto-suave py-4">Nenhum agendamento registrado.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="mb-5" id="gerenciar-servicos">
        <h2 class="h4 mb-3"><?= $servicoEdicao ? 'Editar serviço ou corte' : 'Adicionar serviço ou corte' ?></h2>
        <form class="cartao mb-4" method="post" action="view/admin/dashboard.php#gerenciar-servicos">
            <?= campoCsrf() ?>
            <input type="hidden" name="acao" value="salvar_servico">
            <input type="hidden" name="id" value="<?= (int) ($servicoEdicao['id'] ?? 0) ?>">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label" for="servico-nome">Nome do corte ou serviço</label>
                    <input class="form-control" id="servico-nome" name="nome" maxlength="80" required
                           value="<?= e($servicoEdicao['nome'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="servico-descricao">Descrição</label>
                    <input class="form-control" id="servico-descricao" name="descricao" maxlength="200"
                           value="<?= e($servicoEdicao['descricao'] ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="servico-preco">Preço (R$)</label>
                    <input class="form-control" id="servico-preco" name="preco" type="number" min="0.01"
                           max="99999999.99" step="0.01" required
                           value="<?= e($servicoEdicao['preco'] ?? '') ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label" for="servico-duracao">Min.</label>
                    <input class="form-control" id="servico-duracao" name="duracao" type="number" min="5"
                           max="480" step="1" required value="<?= e($servicoEdicao['duracao'] ?? '30') ?>">
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-4">
                <button class="btn btn-marrom" type="submit"><?= $servicoEdicao ? 'Salvar serviço' : 'Adicionar serviço' ?></button>
                <?php if ($servicoEdicao): ?>
                    <a class="btn btn-contorno" href="view/admin/dashboard.php#gerenciar-servicos">Cancelar edição</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
            <div>
                <h3 class="h5 mb-1">Serviços cadastrados</h3>
                <p class="texto-suave mb-0">Ao excluir um serviço, ele deixa de aparecer para novas reservas e seu histórico é preservado.</p>
            </div>
        </div>
        <div class="cartao p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table tabela-agenda align-middle mb-0">
                    <thead><tr><th>Serviço</th><th>Duração</th><th>Preço</th><th>Situação</th><th>Ações</th></tr></thead>
                    <tbody>
                    <?php foreach ($dadosPainel['servicos'] as $servico): ?>
                        <tr>
                            <td><strong><?= e($servico['nome']) ?></strong><div class="texto-suave small"><?= e($servico['descricao'] ?? '') ?></div></td>
                            <td><?= (int) $servico['duracao'] ?> min.</td>
                            <td><?= e(formatarPreco($servico['preco'])) ?></td>
                            <td><?= (bool) $servico['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-sm btn-contorno" href="view/admin/dashboard.php?editar_servico=<?= (int) $servico['id'] ?>#gerenciar-servicos">Editar</a>
                                    <form method="post" action="view/admin/dashboard.php#gerenciar-servicos">
                                        <?= campoCsrf() ?>
                                        <input type="hidden" name="acao" value="alternar_servico">
                                        <input type="hidden" name="id" value="<?= (int) $servico['id'] ?>">
                                        <button class="btn btn-sm <?= (bool) $servico['ativo'] ? 'btn-outline-danger' : 'btn-contorno' ?>"
                                                type="submit"
                                                data-confirmar="<?= (bool) $servico['ativo'] ? 'Remover este serviço das novas reservas? O histórico será preservado.' : 'Reativar este serviço para novas reservas?' ?>"
                                                data-confirmar-titulo="<?= (bool) $servico['ativo'] ? 'Excluir serviço?' : 'Reativar serviço?' ?>"
                                                data-confirmar-tipo="<?= (bool) $servico['ativo'] ? 'perigo' : 'info' ?>">
                                            <?= (bool) $servico['ativo'] ? 'Excluir' : 'Reativar' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$dadosPainel['servicos']): ?>
                        <tr><td colspan="5" class="text-center texto-suave py-4">Nenhum serviço cadastrado.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="mb-5" id="gerenciar-horarios">
        <h2 class="h4 mb-3">Horários disponíveis para agendamento</h2>
        <form class="cartao mb-4" method="post" action="view/admin/dashboard.php#gerenciar-horarios">
            <?= campoCsrf() ?>
            <input type="hidden" name="acao" value="salvar_horario">
            <input type="hidden" name="id" value="0">
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label" for="novo-dia-semana">Dia da semana</label>
                    <select class="form-select" id="novo-dia-semana" name="dia_semana" required>
                        <?php foreach (DIAS_SEMANA as $numeroDia => $nomeDia): ?>
                            <option value="<?= $numeroDia ?>"><?= e(ucfirst($nomeDia)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="novo-horario">Horário</label>
                    <input class="form-control" id="novo-horario" name="horario" type="time" required>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-marrom w-100" type="submit">Adicionar horário</button>
                </div>
            </div>
        </form>

        <div class="row g-3">
            <?php foreach (DIAS_SEMANA as $numeroDia => $nomeDia): ?>
                <div class="col-lg-6">
                    <section class="cartao h-100">
                        <h3 class="h5 mb-3"><?= e(ucfirst($nomeDia)) ?></h3>
                        <?php if (!$horariosPorDia[$numeroDia]): ?>
                            <p class="texto-suave mb-0">Nenhum horário disponível neste dia.</p>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($horariosPorDia[$numeroDia] as $horarioCadastrado): ?>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <form class="d-flex flex-wrap align-items-center gap-2 flex-grow-1"
                                              method="post" action="view/admin/dashboard.php#gerenciar-horarios">
                                            <?= campoCsrf() ?>
                                            <input type="hidden" name="acao" value="salvar_horario">
                                            <input type="hidden" name="id" value="<?= (int) $horarioCadastrado['id'] ?>">
                                            <select class="form-select form-select-sm" name="dia_semana"
                                                    aria-label="Dia do horário" required style="max-width: 190px">
                                                <?php foreach (DIAS_SEMANA as $opcaoDia => $nomeOpcaoDia): ?>
                                                    <option value="<?= $opcaoDia ?>"
                                                        <?= (int) $horarioCadastrado['dia_semana'] === $opcaoDia ? 'selected' : '' ?>>
                                                        <?= e(ucfirst($nomeOpcaoDia)) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input class="form-control form-control-sm" name="horario" type="time"
                                                   aria-label="Horário" required style="max-width: 130px"
                                                   value="<?= e(substr($horarioCadastrado['horario'], 0, 5)) ?>">
                                            <button class="btn btn-sm btn-contorno" type="submit">Salvar</button>
                                        </form>
                                        <form method="post" action="view/admin/dashboard.php#gerenciar-horarios">
                                            <?= campoCsrf() ?>
                                            <input type="hidden" name="acao" value="excluir_horario">
                                            <input type="hidden" name="id" value="<?= (int) $horarioCadastrado['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit"
                                                    data-confirmar="Remover este horário das novas reservas?"
                                                    data-confirmar-titulo="Excluir horário?" data-confirmar-tipo="perigo">
                                                Excluir
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>