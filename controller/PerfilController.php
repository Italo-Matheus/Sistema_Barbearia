<?php
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../model/PerfilModel.php';

class PerfilController
{
    private PDO $pdo;
    private PerfilModel $perfilModel;

    public function __construct(PDO $pdo, PerfilModel $perfilModel)
    {
        $this->pdo = $pdo;
        $this->perfilModel = $perfilModel;
    }

    public function alterarSenha(array $post, bool $enviado, bool $tokenCsrfValido): void
    {
        exigirLogin(null, $this->pdo);
        $erro = '';

        if ($enviado) {
            if (!$tokenCsrfValido) {
                $erro = 'Sua sessão expirou. Recarregue a página e tente novamente.';
            } else {
                $resultado = $this->perfilModel->alterarSenha(
                    (int) $_SESSION['usuario_id'],
                    $this->texto($post, 'senha_atual'),
                    $this->texto($post, 'senha_nova'),
                    $this->texto($post, 'confirmar_senha')
                );

                if ($resultado['ok']) {
                    definirMensagem('success', $resultado['erro']);
                    redirecionar(baseAplicacao() . 'view/alterar_senha.php');
                }
                $erro = $resultado['erro'];
            }
        }

        $tituloPagina = 'Alterar senha';
        require __DIR__ . '/../view/alterar_senha.php';
    }

    private function texto(array $dados, string $campo): string
    {
        $valor = $dados[$campo] ?? '';

        return is_string($valor) ? $valor : '';
    }
}