<?php
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../model/UsuarioCadastroModel.php';

class UsuarioController
{
    private UsuarioCadastroModel $cadastroModel;

    public function __construct(UsuarioCadastroModel $cadastroModel)
    {
        $this->cadastroModel = $cadastroModel;
    }

    public function cadastrar(array $post, bool $enviado, bool $tokenCsrfValido): void
    {
        if (usuarioLogado()) {
            redirecionar('../' . areaDoUsuario());
        }

        $resultado = $this->cadastroModel->processar($post, $enviado, $tokenCsrfValido);
        if ($resultado['cadastrado']) {
            definirMensagem('success', 'Sua conta foi criada! Agora é só entrar.');
            redirecionar('../view/login.php');
        }

        $dados = $resultado['dados'];
        $erros = $resultado['erros'];
        $ehBarbeiro = $dados['tipo'] === 'barbeiro';
        $tituloPagina = 'Criar conta';
        require __DIR__ . '/../view/cadastrar_usuario.php';
    }
}