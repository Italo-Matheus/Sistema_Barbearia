<?php
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../model/UsuarioLoginModel.php';

class LoginController
{
    private UsuarioLoginModel $loginModel;

    public function __construct(UsuarioLoginModel $loginModel)
    {
        $this->loginModel = $loginModel;
    }

    public function entrar(array $post, bool $enviado, bool $tokenCsrfValido): void
    {
        if (usuarioLogado()) {
            redirecionar('../' . areaDoUsuario());
        }

        $email = '';
        $erro = '';

        if ($enviado) {
            $email = strtolower($this->texto($post, 'email'));
            $senha = $this->textoSenha($post, 'senha');

            if (!$tokenCsrfValido) {
                $erro = 'Sua sessão expirou. Recarregue a página e tente de novo.';
            } elseif ($email === '' || $senha === '') {
                $erro = 'Preencha seu e-mail e sua senha para entrar.';
            } else {
                $usuario = $this->loginModel->autenticar($email, $senha);

                if ($usuario) {
                    session_regenerate_id(true);
                    $_SESSION['usuario_id'] = (int) $usuario['id'];
                    $_SESSION['usuario_nome'] = $usuario['nome'];
                    $_SESSION['usuario_tipo'] = $usuario['tipo'];

                    definirMensagem('success', 'Que bom ter você por aqui, ' . primeiroNome($usuario['nome']) . '!');
                    redirecionar('../' . areaDoUsuario());
                }

                $erro = 'Não conseguimos entrar com esses dados. Confira seu e-mail e sua senha.';
            }
        }

        $tituloPagina = 'Entrar';
        require __DIR__ . '/../view/form_login.php';
    }

    private function texto(array $dados, string $campo): string
    {
        $valor = $dados[$campo] ?? '';

        return is_string($valor) ? trim($valor) : '';
    }

    private function textoSenha(array $dados, string $campo): string
    {
        $valor = $dados[$campo] ?? '';

        return is_string($valor) ? $valor : '';
    }
}