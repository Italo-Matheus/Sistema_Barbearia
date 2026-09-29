<?php
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../model/dao/UsuarioDAO.php';

class UsuarioController
{
    private UsuarioDAO $usuarioDAO;

    public function __construct(UsuarioDAO $usuarioDAO)
    {
        $this->usuarioDAO = $usuarioDAO;
    }

    public function cadastrar(array $post, bool $enviado, bool $tokenCsrfValido): void
    {
        if (usuarioLogado()) {
            redirecionar(areaDoUsuario());
        }

        $dados = [
            'tipo' => 'cliente',
            'nome' => '',
            'email' => '',
            'telefone' => '',
            'cpf' => '',
            'especialidade' => '',
            'descricao' => '',
        ];
        $erros = [];

        if ($enviado) {
            foreach ($dados as $campo => $valor) {
                $dados[$campo] = $this->texto($post, $campo);
            }
            $senha = $this->senha($post, 'senha');
            $confirmacao = $this->senha($post, 'confirmar_senha');

            if (!$tokenCsrfValido) {
                $erros['geral'] = 'Sua sessão expirou. Recarregue a página e tente de novo.';
            }

            if (!in_array($dados['tipo'], ['cliente', 'barbeiro'], true)) {
                $erros['tipo'] = 'Escolha se você é cliente ou barbeiro.';
            }

            if (strlen($dados['nome']) < 5 || strpos($dados['nome'], ' ') === false) {
                $erros['nome'] = 'Informe seu nome completo.';
            } elseif (strlen($dados['nome']) > 120) {
                $erros['nome'] = 'O nome pode ter até 120 caracteres.';
            }

            $dados['email'] = strtolower($dados['email']);
            if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL) || strlen($dados['email']) > 160) {
                $erros['email'] = 'Esse e-mail não parece correto. Confira e tente de novo.';
            }

            $telefone = apenasDigitos($dados['telefone']);
            if (strlen($telefone) < 10 || strlen($telefone) > 11) {
                $erros['telefone'] = 'Informe o telefone com DDD, por exemplo (61) 91234-5678.';
            }

            $cpf = apenasDigitos($dados['cpf']);
            if ($dados['cpf'] !== '' && !cpfValido($cpf)) {
                $erros['cpf'] = 'Esse CPF não parece correto. Confira os números.';
            }

            if (strlen($senha) < 8) {
                $erros['senha'] = 'Use uma senha com pelo menos 8 caracteres.';
            } elseif (strlen($senha) > 72) {
                $erros['senha'] = 'A senha pode ter até 72 caracteres.';
            }
            if ($senha !== $confirmacao) {
                $erros['confirmar_senha'] = 'As senhas não são iguais.';
            }

            if ($dados['tipo'] === 'barbeiro') {
                if (strlen($dados['especialidade']) > 100) {
                    $erros['especialidade'] = 'A especialidade pode ter até 100 caracteres.';
                }
                if (strlen($dados['descricao']) > 300) {
                    $erros['descricao'] = 'A descrição pode ter até 300 caracteres.';
                }
            }

            if (!isset($erros['email']) && $this->usuarioDAO->emailExiste($dados['email'])) {
                $erros['email'] = 'Esse e-mail já tem uma conta. Que tal entrar?';
            }
            if ($cpf !== '' && !isset($erros['cpf']) && $this->usuarioDAO->cpfExiste($cpf)) {
                $erros['cpf'] = 'Esse CPF já está cadastrado. Que tal entrar?';
            }

            if (!$erros) {
                $ehBarbeiro = $dados['tipo'] === 'barbeiro';
                try {
                    $usuario = new UsuarioDTO(
                        $dados['nome'],
                        $dados['email'],
                        $telefone,
                        $cpf !== '' ? $cpf : null,
                        password_hash($senha, PASSWORD_DEFAULT),
                        $dados['tipo'],
                        $ehBarbeiro && $dados['especialidade'] !== '' ? $dados['especialidade'] : null,
                        $ehBarbeiro && $dados['descricao'] !== '' ? $dados['descricao'] : null
                    );
                    $this->usuarioDAO->cadastrar($usuario);

                    definirMensagem('success', 'Sua conta foi criada! Agora é só entrar.');
                    redirecionar('login.php');
                } catch (PDOException $erro) {
                    if ($erro->getCode() === '23000') {
                        $erros['email'] = 'Esse e-mail ou CPF já tem uma conta. Que tal entrar?';
                    } else {
                        error_log('Erro ao cadastrar: ' . $erro->getMessage());
                        $erros['geral'] = 'Não conseguimos criar sua conta agora. Tente de novo em instantes.';
                    }
                }
            }
        }

        $ehBarbeiro = $dados['tipo'] === 'barbeiro';
        $tituloPagina = 'Criar conta';
        require __DIR__ . '/../view/cadastrar_usuario.php';
    }

    private function texto(array $dados, string $campo): string
    {
        $valor = $dados[$campo] ?? '';

        return is_string($valor) ? trim($valor) : '';
    }

    private function senha(array $dados, string $campo): string
    {
        $valor = $dados[$campo] ?? '';

        return is_string($valor) ? $valor : '';
    }
}