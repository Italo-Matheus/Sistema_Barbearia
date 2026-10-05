<?php
require_once __DIR__ . '/dao/UsuarioDAO.php';
require_once __DIR__ . '/dao/AgendamentoDAO.php';
require_once __DIR__ . '/dao/ServicoDAO.php';
require_once __DIR__ . '/dto/AdminUsuarioDTO.php';
require_once __DIR__ . '/../includes/funcoes.php';

class AdminModel
{
    private UsuarioDAO $usuarioDAO;
    private AgendamentoDAO $agendamentoDAO;
    private ServicoDAO $servicoDAO;

    public function __construct(PDO $pdo)
    {
        $this->usuarioDAO = new UsuarioDAO($pdo);
        $this->agendamentoDAO = new AgendamentoDAO($pdo);
        $this->servicoDAO = new ServicoDAO($pdo);
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
            'agendamentosRecentes' => $this->agendamentoDAO->listarRecentesAdmin(),
            'servicos' => $this->servicoDAO->listarTodosAdmin(),
        ];
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