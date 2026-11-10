<?php
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../model/AdminModel.php';

class AdminController
{
    private PDO $pdo;
    private AdminModel $adminModel;

    public function __construct(PDO $pdo, AdminModel $adminModel)
    {
        $this->pdo = $pdo;
        $this->adminModel = $adminModel;
    }

    public function executar(array $post, bool $enviado, bool $tokenCsrfValido): array
    {
        $admin = exigirAdmin($this->pdo);
        $erroAcao = '';
        $usuarioEdicao = null;
        $servicoEdicao = null;
        $agendamentoEdicao = null;
        $opcoesAgendamento = null;

        if ($enviado) {
            if (!$tokenCsrfValido) {
                $erroAcao = 'Sua sessão expirou. Recarregue a página e tente de novo.';
            } else {
                $acao = $this->texto($post, 'acao');
                $id = $this->id($post['id'] ?? null);
                $resultado = ['ok' => false, 'erro' => 'Ação administrativa inválida.'];

                if ($acao === 'salvar_usuario' && $id > 0) {
                    $usuarioEdicao = [
                        'id' => $id,
                        'nome' => $this->texto($post, 'nome'),
                        'email' => $this->texto($post, 'email'),
                        'telefone' => $this->texto($post, 'telefone'),
                        'cpf' => $this->texto($post, 'cpf'),
                        'tipo' => $this->texto($post, 'tipo'),
                        'especialidade' => $this->texto($post, 'especialidade'),
                        'descricao' => $this->texto($post, 'descricao'),
                    ];
                    $dto = new AdminUsuarioDTO(
                        $id,
                        $usuarioEdicao['nome'],
                        $usuarioEdicao['email'],
                        $usuarioEdicao['telefone'],
                        $usuarioEdicao['cpf'] !== '' ? $usuarioEdicao['cpf'] : null,
                        $usuarioEdicao['tipo'],
                        $usuarioEdicao['especialidade'] !== '' ? $usuarioEdicao['especialidade'] : null,
                        $usuarioEdicao['descricao'] !== '' ? $usuarioEdicao['descricao'] : null
                    );
                    $resultado = $this->adminModel->atualizarUsuario($dto);
                } elseif ($acao === 'salvar_servico') {
                    $idServico = $this->idOpcional($post['id'] ?? '0');
                    if ($idServico === null) {
                        $resultado = ['ok' => false, 'erro' => 'Identificador de serviço inválido.'];
                    } else {
                        $servicoEdicao = [
                            'id' => $idServico,
                            'nome' => $this->texto($post, 'nome'),
                            'descricao' => $this->texto($post, 'descricao'),
                            'preco' => $this->texto($post, 'preco'),
                            'duracao' => $this->texto($post, 'duracao'),
                        ];
                        $resultado = $this->adminModel->salvarServico(
                            $idServico,
                            $servicoEdicao['nome'],
                            $servicoEdicao['descricao'],
                            $servicoEdicao['preco'],
                            $servicoEdicao['duracao']
                        );
                    }
                } elseif ($acao === 'alternar_servico' && $id > 0) {
                    $resultado = $this->adminModel->alternarServico($id);
                } elseif ($acao === 'salvar_agendamento' && $id > 0) {
                    $servicosPostados = $post['servicos'] ?? [];
                    $servicosPostados = is_array($servicosPostados) ? $servicosPostados : [];
                    $agendamentoEdicao = [
                        'id' => $id,
                        'barbeiro_id' => $this->texto($post, 'barbeiro_id'),
                        'data' => $this->texto($post, 'data'),
                        'horario' => $this->texto($post, 'horario'),
                        'observacao' => $this->texto($post, 'observacao'),
                        'status' => $this->texto($post, 'status'),
                        'servico_ids' => array_map(
                            static fn($valor): int => is_scalar($valor) ? (int) $valor : 0,
                            $servicosPostados
                        ),
                    ];
                    $resultado = $this->adminModel->atualizarAgendamentoAdmin([
                        'id' => $id,
                        'barbeiro_id' => $agendamentoEdicao['barbeiro_id'],
                        'data' => $agendamentoEdicao['data'],
                        'horario' => $agendamentoEdicao['horario'],
                        'observacao' => $agendamentoEdicao['observacao'],
                        'status' => $agendamentoEdicao['status'],
                        'servicos' => $servicosPostados,
                    ]);
                } elseif ($acao === 'salvar_horario') {
                    $idHorario = $this->idOpcional($post['id'] ?? '0');
                    $resultado = $idHorario === null
                        ? ['ok' => false, 'erro' => 'Identificador de horário inválido.']
                        : $this->adminModel->salvarHorario(
                            $idHorario,
                            $this->texto($post, 'dia_semana'),
                            $this->texto($post, 'horario')
                        );
                } elseif ($acao === 'excluir_horario' && $id > 0) {
                    $resultado = $this->adminModel->excluirHorario($id);
                } elseif ($acao === 'alterar_tipo' && $id > 0) {
                    $resultado = $this->adminModel->alterarTipo($id, $this->texto($post, 'tipo'));
                } elseif ($acao === 'alternar_ativo' && $id > 0) {
                    $resultado = $this->adminModel->alternarAtivo($id);
                } elseif ($acao === 'excluir_usuario' && $id > 0) {
                    $resultado = $this->adminModel->excluirUsuario($id);
                }

                if ($resultado['ok']) {
                    definirMensagem('success', $resultado['erro']);
                    redirecionar(baseAplicacao() . 'view/admin/dashboard.php');
                }
                $erroAcao = $resultado['erro'];
            }
        }

        if ($usuarioEdicao === null) {
            $idEdicao = $this->id($_GET['editar'] ?? null);
            if ($idEdicao > 0) {
                $usuarioEdicao = $this->adminModel->buscarUsuario($idEdicao);
                if (!$usuarioEdicao) {
                    $erroAcao = 'Esse usuário não pode ser editado.';
                }
            }
        }

        if ($servicoEdicao === null) {
            $idServicoEdicao = $this->id($_GET['editar_servico'] ?? null);
            if ($idServicoEdicao > 0) {
                $servicoEdicao = $this->adminModel->buscarServico($idServicoEdicao);
                if (!$servicoEdicao) {
                    $erroAcao = 'Esse serviço não pode ser editado.';
                }
            }
        }

        if ($agendamentoEdicao === null) {
            $idAgendamentoEdicao = $this->id($_GET['editar_agendamento'] ?? null);
            if ($idAgendamentoEdicao > 0) {
                $opcoesAgendamento = $this->adminModel->dadosEdicaoAgendamento($idAgendamentoEdicao);
                if ($opcoesAgendamento) {
                    $agendamentoEdicao = $opcoesAgendamento['agendamento'];
                } else {
                    $erroAcao = 'Esse agendamento não pode ser encontrado.';
                }
            }
        } elseif ((int) $agendamentoEdicao['id'] > 0) {
            $opcoesAgendamento = $this->adminModel->dadosEdicaoAgendamento((int) $agendamentoEdicao['id']);
            if (!$opcoesAgendamento) {
                $agendamentoEdicao = null;
                $erroAcao = 'Esse agendamento não pode ser encontrado.';
            }
        }

        return [
            'adminAtual' => $admin,
            'dadosPainel' => $this->adminModel->dadosPainel(),
            'usuarioEdicao' => $usuarioEdicao,
            'servicoEdicao' => $servicoEdicao,
            'agendamentoEdicao' => $agendamentoEdicao,
            'opcoesAgendamento' => $opcoesAgendamento,
            'erroAcao' => $erroAcao,
        ];
    }

    private function texto(array $dados, string $campo): string
    {
        $valor = $dados[$campo] ?? '';

        return is_string($valor) ? trim($valor) : '';
    }

    private function id($valor): int
    {
        $id = filter_var($valor, FILTER_VALIDATE_INT);

        return $id !== false && $id > 0 ? $id : 0;
    }

    private function idOpcional($valor): ?int
    {
        if ($valor === null || $valor === '' || $valor === '0' || $valor === 0) {
            return 0;
        }

        $id = filter_var($valor, FILTER_VALIDATE_INT);

        return $id !== false && $id > 0 ? $id : null;
    }
}