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
$erroAcao = $dados['erroAcao'];
$tituloPagina = 'Painel administrativo';
require __DIR__ . '/../../includes/header.php';
?>

<section class="topo-pagina">
    <div class="container">
        <p class="texto-suave mb-2">Administração</p>
        <h1>Painel master</h1>
        <p class="texto-suave mb-0">Olá, <?= e(primeiroNome($adminAtual['nome'])) ?>. Gerencie usuários e acompanhe a operação.</p>
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
                    <button class="btn btn-marrom" type="submit">Salvar alterações</button>
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
                                            <button class="btn btn-sm btn-contorno" type="submit"><?= (bool) $usuario['ativo'] ? 'Bloquear' : 'Ativar' ?></button>
                                        </form>
                                        <form method="post" action="view/admin/dashboard.php">
                                            <?= campoCsrf() ?>
                                            <input type="hidden" name="acao" value="alterar_tipo">
                                            <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">
                                            <select class="form-select form-select-sm" name="tipo" aria-label="Novo perfil para <?= e($usuario['nome']) ?>">
                                                <option value="cliente" <?= $usuario['tipo'] === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                                                <option value="barbeiro" <?= $usuario['tipo'] === 'barbeiro' ? 'selected' : '' ?>>Barbeiro</option>
                                            </select>
                                            <button class="btn btn-sm btn-contorno mt-1" type="submit">Alterar perfil</button>
                                        </form>
                                        <form method="post" action="view/admin/dashboard.php">
                                            <?= campoCsrf() ?>
                                            <input type="hidden" name="acao" value="excluir_usuario">
                                            <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit" data-confirmar="Excluir esta conta? Agendamentos associados impedem a exclusão.">Excluir</button>
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

    <section class="mb-5">
        <h2 class="h4 mb-3">Agendamentos recentes</h2>
        <div class="cartao p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table tabela-agenda align-middle mb-0">
                    <thead><tr><th>Data</th><th>Horário</th><th>Cliente</th><th>Barbeiro</th><th>Serviços</th><th>Status</th><th class="text-end">Total</th></tr></thead>
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
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$dadosPainel['agendamentosRecentes']): ?>
                        <tr><td colspan="7" class="text-center texto-suave py-4">Nenhum agendamento registrado.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section>
        <h2 class="h4 mb-3">Serviços cadastrados</h2>
        <div class="cartao p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table tabela-agenda align-middle mb-0">
                    <thead><tr><th>Serviço</th><th>Duração</th><th>Preço</th><th>Situação</th></tr></thead>
                    <tbody>
                    <?php foreach ($dadosPainel['servicos'] as $servico): ?>
                        <tr>
                            <td><strong><?= e($servico['nome']) ?></strong><div class="texto-suave small"><?= e($servico['descricao'] ?? '') ?></div></td>
                            <td><?= (int) $servico['duracao'] ?> min.</td>
                            <td><?= e(formatarPreco($servico['preco'])) ?></td>
                            <td><?= (bool) $servico['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$dadosPainel['servicos']): ?>
                        <tr><td colspan="4" class="text-center texto-suave py-4">Nenhum serviço cadastrado.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>