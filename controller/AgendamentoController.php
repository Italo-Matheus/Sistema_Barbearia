<?php
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../model/AgendamentoModel.php';

class AgendamentoController
{
    private AgendamentoModel $agendamentoModel;

    public function __construct(AgendamentoModel $agendamentoModel)
    {
        $this->agendamentoModel = $agendamentoModel;
    }

    public function agendar(array $post, bool $enviado, bool $tokenCsrfValido): void
    {
        exigirLogin('cliente');

        $clienteId = (int) $_SESSION['usuario_id'];
        $hoje = date('Y-m-d');
        $dataMaxima = date('Y-m-d', strtotime('+' . DIAS_MAXIMOS_AGENDAMENTO . ' days'));
        $servicos = $this->agendamentoModel->listarServicos();
        $barbeiros = $this->agendamentoModel->listarBarbeiros();
        $servicosPorId = [];
        $barbeirosPorId = [];

        foreach ($servicos as $servico) {
            $servicosPorId[(int) $servico['id']] = $servico;
        }
        foreach ($barbeiros as $barbeiro) {
            $barbeirosPorId[(int) $barbeiro['id']] = $barbeiro;
        }

        $acao = $this->texto($post, 'acao');
        $barbeiroId = (int) $this->texto($post, 'barbeiro_id');
        $data = $this->texto($post, 'data');
        $horario = $this->texto($post, 'horario');
        $observacao = $this->texto($post, 'observacao');
        $idsServicos = $this->idsServicos($post);
        $servicosEscolhidos = [];
        $totalEscolhido = 0.0;

        foreach ($idsServicos as $idServico) {
            if (isset($servicosPorId[$idServico])) {
                $servicosEscolhidos[] = $servicosPorId[$idServico];
                $totalEscolhido += (float) $servicosPorId[$idServico]['preco'];
            }
        }
        $totalEscolhido = round($totalEscolhido, 2);

        $etapa = 1;
        $erros = [];
        $horarios = [];

        if ($enviado && in_array($acao, ['ver_horarios', 'confirmar'], true)) {
            if (!$tokenCsrfValido) {
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

                $erroData = $this->agendamentoModel->validarData($data, $hoje, $dataMaxima);
                if ($erroData !== '') {
                    $erros[] = $erroData;
                }

                if (!$erros) {
                    $etapa = 2;
                    $horarios = $this->agendamentoModel->montarHorarios($barbeiroId, $data, $hoje);

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
                                $agendamentoId = $this->agendamentoModel->criar(
                                    $clienteId,
                                    $barbeiroId,
                                    $data,
                                    $horario,
                                    $observacao,
                                    $idsServicos
                                );
                                if ($agendamentoId !== false) {
                                    definirMensagem('success', 'Seu horário foi reservado com sucesso! Te esperamos por aqui.');
                                    redirecionar('cliente.php');
                                }

                                $erros[] = 'Esse horário acabou de ser reservado. Escolha outro horário.';
                                $horario = '';
                                $horarios = $this->agendamentoModel->montarHorarios($barbeiroId, $data, $hoje);
                            } catch (Throwable $erro) {
                                error_log('Erro ao agendar: ' . $erro->getMessage());
                                $erros[] = 'Não conseguimos reservar seu horário agora. Tente de novo em instantes.';
                            }
                        }
                    }
                }
            }
        }

        $temHorarioLivre = in_array('livre', $horarios, true);
        $tituloPagina = 'Agendar horário';
        require __DIR__ . '/../view/usuario/agendamento.php';
    }

    private function texto(array $dados, string $campo): string
    {
        $valor = $dados[$campo] ?? '';

        return is_string($valor) ? trim($valor) : '';
    }

    private function idsServicos(array $post): array
    {
        if (!isset($post['servicos']) || !is_array($post['servicos'])) {
            return [];
        }

        $ids = [];
        foreach ($post['servicos'] as $id) {
            if (is_scalar($id)) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique($ids));
    }
}