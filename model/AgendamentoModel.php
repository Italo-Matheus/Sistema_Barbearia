<?php
require_once __DIR__ . '/dao/AgendamentoDAO.php';
require_once __DIR__ . '/dao/AgendamentoServicoDAO.php';
require_once __DIR__ . '/dao/ServicoDAO.php';
require_once __DIR__ . '/dao/UsuarioDAO.php';
require_once __DIR__ . '/../includes/funcoes.php';

class AgendamentoModel
{
    private AgendamentoDAO $agendamentoDAO;
    private ServicoDAO $servicoDAO;
    private UsuarioDAO $usuarioDAO;

    public function __construct(PDO $pdo)
    {
        $this->agendamentoDAO = new AgendamentoDAO($pdo);
        $this->servicoDAO = new ServicoDAO($pdo);
        $this->usuarioDAO = new UsuarioDAO($pdo);
    }

    public function listarServicos(): array
    {
        return $this->servicoDAO->listarAtivos();
    }

    public function listarBarbeiros(): array
    {
        return $this->usuarioDAO->listarBarbeiros();
    }

    public function validarData(string $data, string $hoje, string $dataMaxima): string
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

    public function montarHorarios(int $barbeiroId, string $data, string $hoje): array
    {
        $ocupados = array_map(
            'formatarHorario',
            $this->agendamentoDAO->listarHorariosOcupados($barbeiroId, $data)
        );
        $agora = date('H:i');
        $horarios = [];

        foreach (HORARIOS_ATENDIMENTO as $horario) {
            if (in_array($horario, $ocupados, true)) {
                $horarios[$horario] = 'ocupado';
            } elseif ($data === $hoje && $horario <= $agora) {
                $horarios[$horario] = 'passou';
            } else {
                $horarios[$horario] = 'livre';
            }
        }

        return $horarios;
    }

    public function criar(
        int $clienteId,
        int $barbeiroId,
        string $data,
        string $horario,
        string $observacao,
        array $idsServicos
    ): int|false {
        return $this->agendamentoDAO->criar(
            $clienteId,
            $barbeiroId,
            $data,
            $horario,
            $observacao,
            $idsServicos
        );
    }
}