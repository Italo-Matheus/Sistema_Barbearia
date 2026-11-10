<?php
require_once __DIR__ . '/dao/UsuarioDAO.php';
require_once __DIR__ . '/dao/AgendamentoDAO.php';
require_once __DIR__ . '/dao/ServicoDAO.php';
require_once __DIR__ . '/dao/HorarioDAO.php';
require_once __DIR__ . '/dto/AdminUsuarioDTO.php';
require_once __DIR__ . '/../includes/funcoes.php';

class AdminModel
{
    private UsuarioDAO $usuarioDAO;
    private AgendamentoDAO $agendamentoDAO;
    private ServicoDAO $servicoDAO;
    private HorarioDAO $horarioDAO;

    public function __construct(PDO $pdo)
    {
        $this->usuarioDAO = new UsuarioDAO($pdo);
        $this->agendamentoDAO = new AgendamentoDAO($pdo);
        $this->servicoDAO = new ServicoDAO($pdo);
        $this->horarioDAO = new HorarioDAO($pdo);
    }

    public function dadosPainel(): array
    {
        $contagens = $this->usuarioDAO->contarPorTipo();

        return [
            'metricas' => [
                'clientes' => $contagens['clientes'],
                'barbeiros' => $contagens['barbeiros'],
                'agendamentos' => $this->agendamentoDAO->contarTodos(),
            ],
            'usuarios' => $this->usuarioDAO->listarTodos(),
            'agendamentosRecentes' => $this->agendamentoDAO->listarRecentesAdmin(100),
            'servicos' => $this->servicoDAO->listarTodosAdmin(),
            'horarios' => $this->horarioDAO->listarTodos(),
        ];
    }

    public function buscarServico(int $id): ?array
    {
        return $this->servicoDAO->buscarPorId($id);
    }

    public function dadosEdicaoAgendamento(int $id): ?array
    {
        $agendamento = $this->agendamentoDAO->buscarParaEdicaoAdmin($id);
        if (!$agendamento) {
            return null;
        }

        return [
            'agendamento' => $agendamento,
            'barbeiros' => $this->usuarioDAO->listarBarbeiros(),
            'servicos' => $this->servicoDAO->listarAtivos(),
        ];
    }

    public function atualizarAgendamentoAdmin(array $dados): array
    {
        $id = filter_var($dados['id'] ?? null, FILTER_VALIDATE_INT);
        $barbeiroId = filter_var($dados['barbeiro_id'] ?? null, FILTER_VALIDATE_INT);
        $data = trim((string) ($dados['data'] ?? ''));
        $horario = trim((string) ($dados['horario'] ?? ''));
        $observacao = trim((string) ($dados['observacao'] ?? ''));
        $status = trim((string) ($dados['status'] ?? ''));
        $servicoIds = $dados['servicos'] ?? [];

        if ($id === false || $id < 1 || $barbeiroId === false || $barbeiroId < 1) {
            return ['ok' => false, 'erro' => 'Agendamento ou barbeiro inválido.'];
        }
        $dataObj = DateTime::createFromFormat('!Y-m-d', $data);
        if (!$dataObj || $dataObj->format('Y-m-d') !== $data) {
            return ['ok' => false, 'erro' => 'Informe uma data válida.'];
        }
        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $horario) !== 1) {
            return ['ok' => false, 'erro' => 'Informe um horário válido.'];
        }
        if (strlen($observacao) > 255) {
            return ['ok' => false, 'erro' => 'A observação pode ter até 255 caracteres.'];
        }
        if (!in_array($status, ['agendado', 'concluido', 'cancelado'], true)) {
            return ['ok' => false, 'erro' => 'Selecione um status válido.'];
        }
        if (!is_array($servicoIds)) {
            return ['ok' => false, 'erro' => 'Selecione ao menos um serviço.'];
        }
        $servicoIds = array_values(array_unique(array_filter(
            array_map(
                static fn($valor): int => is_scalar($valor) ? (int) $valor : 0,
                $servicoIds
            ),
            static fn(int $servicoId): bool => $servicoId > 0
        )));
        if (!$servicoIds) {
            return ['ok' => false, 'erro' => 'Selecione ao menos um serviço.'];
        }

        $horarioBanco = $horario . ':00';
        if ($status === 'agendado') {
            if ($data < date('Y-m-d') || ($data === date('Y-m-d') && $horario <= date('H:i'))) {
                return ['ok' => false, 'erro' => 'Um agendamento ativo deve ficar em uma data e horário futuros.'];
            }
            $diaSemana = (int) $dataObj->format('w');
            if (!$this->horarioDAO->existe($diaSemana, $horarioBanco)) {
                return ['ok' => false, 'erro' => 'Esse horário não está disponível na agenda desse dia.'];
            }
        }

        try {
            $salvo = $this->agendamentoDAO->atualizarAdmin(
                (int) $id,
                (int) $barbeiroId,
                $data,
                $horarioBanco,
                $observacao,
                $status,
                $servicoIds
            );
        } catch (InvalidArgumentException $erro) {
            return ['ok' => false, 'erro' => $erro->getMessage()];
        }

        return $salvo
            ? ['ok' => true, 'erro' => 'Agendamento atualizado com sucesso.']
            : ['ok' => false, 'erro' => 'Não foi possível atualizar o agendamento.'];
    }

    public function salvarServico(
        int $id,
        string $nome,
        string $descricao,
        string $precoTexto,
        string $duracaoTexto
    ): array {
        $nome = trim($nome);
        $descricao = trim($descricao);
        $precoTexto = str_replace(',', '.', trim($precoTexto));
        $duracao = filter_var($duracaoTexto, FILTER_VALIDATE_INT);

        if ($nome === '' || strlen($nome) > 80) {
            return ['ok' => false, 'erro' => 'Informe o nome do serviço com até 80 caracteres.'];
        }
        if (strlen($descricao) > 200) {
            return ['ok' => false, 'erro' => 'A descrição pode ter até 200 caracteres.'];
        }
        if (!is_numeric($precoTexto) || !is_finite((float) $precoTexto)
            || (float) $precoTexto < 0.01 || (float) $precoTexto > 99999999.99) {
            return ['ok' => false, 'erro' => 'Informe um preço válido, maior que zero.'];
        }
        if ($duracao === false || $duracao < 5 || $duracao > 480) {
            return ['ok' => false, 'erro' => 'A duração deve ser de 5 a 480 minutos.'];
        }
        if ($id > 0 && !$this->servicoDAO->buscarPorId($id)) {
            return ['ok' => false, 'erro' => 'Esse serviço não existe mais.'];
        }

        $preco = round((float) $precoTexto, 2);
        $descricao = $descricao !== '' ? $descricao : null;
        if ($id > 0) {
            $salvo = $this->servicoDAO->atualizar($id, $nome, $descricao, $preco, $duracao);
        } else {
            $salvo = $this->servicoDAO->cadastrar($nome, $descricao, $preco, $duracao) > 0;
        }

        return $salvo
            ? ['ok' => true, 'erro' => $id > 0 ? 'Serviço atualizado com sucesso.' : 'Serviço cadastrado com sucesso.']
            : ['ok' => false, 'erro' => 'Não foi possível salvar o serviço.'];
    }

    public function alternarServico(int $id): array
    {
        $servico = $this->servicoDAO->buscarPorId($id);
        if (!$servico) {
            return ['ok' => false, 'erro' => 'Esse serviço não existe mais.'];
        }

        $ativo = !(bool) $servico['ativo'];
        if (!$this->servicoDAO->alterarAtivo($id, $ativo)) {
            return ['ok' => false, 'erro' => 'Não foi possível atualizar a disponibilidade do serviço.'];
        }

        return [
            'ok' => true,
            'erro' => $ativo ? 'Serviço reativado.' : 'Serviço removido do catálogo e preservado no histórico.',
        ];
    }

    public function salvarHorario(int $id, string $diaTexto, string $horarioTexto): array
    {
        $diaSemana = filter_var($diaTexto, FILTER_VALIDATE_INT);
        $horarioTexto = trim($horarioTexto);
        $horaValida = preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $horarioTexto) === 1;

        if ($diaSemana === false || $diaSemana < 0 || $diaSemana > 6 || !$horaValida) {
            return ['ok' => false, 'erro' => 'Informe um dia da semana e horário válidos.'];
        }
        if ($id > 0 && !$this->horarioDAO->buscarPorId($id)) {
            return ['ok' => false, 'erro' => 'Esse horário não existe mais.'];
        }

        try {
            $salvo = $id > 0
                ? $this->horarioDAO->atualizar($id, $diaSemana, $horarioTexto . ':00')
                : $this->horarioDAO->cadastrar($diaSemana, $horarioTexto . ':00');
        } catch (PDOException $erro) {
            if ($erro->getCode() === '23000') {
                return ['ok' => false, 'erro' => 'Esse horário já está cadastrado para o dia escolhido.'];
            }
            throw $erro;
        }

        return $salvo
            ? ['ok' => true, 'erro' => $id > 0 ? 'Horário atualizado.' : 'Horário adicionado.']
            : ['ok' => false, 'erro' => 'Não foi possível salvar o horário.'];
    }

    public function excluirHorario(int $id): array
    {
        if (!$this->horarioDAO->buscarPorId($id)) {
            return ['ok' => false, 'erro' => 'Esse horário não existe mais.'];
        }
        if (!$this->horarioDAO->excluir($id)) {
            return ['ok' => false, 'erro' => 'Não foi possível excluir o horário.'];
        }

        return ['ok' => true, 'erro' => 'Horário removido da agenda disponível.'];
    }

    public function buscarUsuario(int $id): ?array
    {
        return $this->usuarioDAO->listarGerenciavelPorId($id);
    }

    public function atualizarUsuario(AdminUsuarioDTO $usuario): array
    {
        $atual = $this->usuarioDAO->listarGerenciavelPorId($usuario->id);
        if (!$atual) {
            return ['ok' => false, 'erro' => 'Esse usuário não pode ser editado.'];
        }

        $erros = [];
        $usuario->nome = trim($usuario->nome);
        $usuario->email = strtolower(trim($usuario->email));
        $usuario->telefone = apenasDigitos($usuario->telefone);
        $usuario->cpf = $usuario->cpf !== null ? apenasDigitos($usuario->cpf) : null;
        $usuario->especialidade = trim((string) $usuario->especialidade);
        $usuario->descricao = trim((string) $usuario->descricao);

        if (strlen($usuario->nome) < 5 || strpos($usuario->nome, ' ') === false || strlen($usuario->nome) > 120) {
            $erros[] = 'Informe o nome completo com até 120 caracteres.';
        }
        if (!filter_var($usuario->email, FILTER_VALIDATE_EMAIL) || strlen($usuario->email) > 160) {
            $erros[] = 'Informe um e-mail válido com até 160 caracteres.';
        } elseif ($this->usuarioDAO->emailUsadoPorOutro($usuario->id, $usuario->email)) {
            $erros[] = 'Esse e-mail já pertence a outra conta.';
        }
        if (strlen($usuario->telefone) < 10 || strlen($usuario->telefone) > 11) {
            $erros[] = 'Informe um telefone com DDD.';
        }
        if ($usuario->cpf !== null && $usuario->cpf !== '' && !cpfValido($usuario->cpf)) {
            $erros[] = 'Informe um CPF válido.';
        } elseif ($usuario->cpf !== null && $usuario->cpf !== '' && $this->usuarioDAO->cpfUsadoPorOutro($usuario->id, $usuario->cpf)) {
            $erros[] = 'Esse CPF já pertence a outra conta.';
        }
        if (!in_array($usuario->tipo, ['cliente', 'barbeiro'], true)) {
            $erros[] = 'O perfil deve ser cliente ou barbeiro. Administradores são provisionados separadamente.';
        }
        if (strlen($usuario->especialidade) > 100 || strlen($usuario->descricao) > 300) {
            $erros[] = 'Especialidade ou descrição ultrapassa o limite permitido.';
        }
        if ($atual['tipo'] !== $usuario->tipo && $this->usuarioDAO->temAgendamentos($usuario->id)) {
            $erros[] = 'O perfil não pode ser alterado porque esse usuário possui agendamentos vinculados.';
        }

        if ($erros) {
            return ['ok' => false, 'erro' => implode(' ', $erros)];
        }

        $ehBarbeiro = $usuario->tipo === 'barbeiro';
        $usuario->cpf = $usuario->cpf !== '' ? $usuario->cpf : null;
        $usuario->especialidade = $ehBarbeiro && $usuario->especialidade !== '' ? $usuario->especialidade : null;
        $usuario->descricao = $ehBarbeiro && $usuario->descricao !== '' ? $usuario->descricao : null;

        if (!$this->usuarioDAO->atualizarGerenciavel($usuario)) {
            return ['ok' => false, 'erro' => 'Não foi possível atualizar esse usuário.'];
        }

        return ['ok' => true, 'erro' => 'Usuário atualizado com sucesso.'];
    }

    public function alterarTipo(int $id, string $tipo): array
    {
        $usuario = $this->usuarioDAO->listarGerenciavelPorId($id);
        if (!$usuario || !in_array($tipo, ['cliente', 'barbeiro'], true)) {
            return ['ok' => false, 'erro' => 'Perfil ou usuário inválido.'];
        }
        if ($usuario['tipo'] !== $tipo && $this->usuarioDAO->temAgendamentos($id)) {
            return ['ok' => false, 'erro' => 'O perfil não pode ser alterado porque esse usuário possui agendamentos vinculados.'];
        }

        $this->usuarioDAO->alterarTipoGerenciavel($id, $tipo);

        return ['ok' => true, 'erro' => 'Perfil alterado com sucesso.'];
    }

    public function alternarAtivo(int $id): array
    {
        $usuario = $this->usuarioDAO->listarGerenciavelPorId($id);
        if (!$usuario) {
            return ['ok' => false, 'erro' => 'Esse usuário não pode ser bloqueado ou ativado.'];
        }

        $this->usuarioDAO->alterarAtivoGerenciavel($id, !(bool) $usuario['ativo']);

        return [
            'ok' => true,
            'erro' => $usuario['ativo'] ? 'Usuário bloqueado.' : 'Usuário ativado.',
        ];
    }

    public function excluirUsuario(int $id): array
    {
        if (!$this->usuarioDAO->listarGerenciavelPorId($id)) {
            return ['ok' => false, 'erro' => 'Esse usuário não pode ser excluído.'];
        }
        if ($this->usuarioDAO->temAgendamentos($id)) {
            return ['ok' => false, 'erro' => 'Não é possível excluir um usuário com agendamentos. Você pode bloqueá-lo para preservar o histórico.'];
        }
        if (!$this->usuarioDAO->excluirGerenciavelSemAgendamentos($id)) {
            return ['ok' => false, 'erro' => 'Não foi possível excluir esse usuário.'];
        }

        return ['ok' => true, 'erro' => 'Usuário excluído com sucesso.'];
    }
}