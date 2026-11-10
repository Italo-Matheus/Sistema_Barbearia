<?php
require_once __DIR__ . '/../includes/funcoes.php';
require_once __DIR__ . '/../model/dao/ChatDAO.php';

class ChatController
{
    private PDO $pdo;
    private ChatDAO $chatDAO;

    public function __construct(PDO $pdo, ChatDAO $chatDAO)
    {
        $this->pdo = $pdo;
        $this->chatDAO = $chatDAO;
    }

    public function tratarRequisicao(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, private');

        try {
            if (!usuarioLogado()) {
                $this->responder(['erro' => 'Autenticação necessária.'], 401);
            }

            $usuarioId = (int) $_SESSION['usuario_id'];
            $ativo = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE id = ? AND ativo = 1 LIMIT 1');
            $ativo->execute([$usuarioId]);
            if (!$ativo->fetchColumn()) {
                $this->responder(['erro' => 'Sua conta não está disponível.'], 401);
            }

            $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            if ($metodo === 'GET') {
                $this->lerHistorico($usuarioId);
            } elseif ($metodo === 'POST') {
                $this->enviarMensagem($usuarioId);
            } else {
                header('Allow: GET, POST');
                $this->responder(['erro' => 'Método não permitido.'], 405);
            }
        } catch (Throwable $erro) {
            error_log('Erro na API de chat: ' . $erro->getMessage());
            $this->responder(['erro' => 'Não foi possível processar a conversa agora.'], 500);
        }
    }

    private function lerHistorico(int $usuarioId): void
    {
        $destinatarioId = $this->id($_GET['destinatario_id'] ?? null);
        if ($destinatarioId < 1) {
            $this->responder(['erro' => 'Informe um destinatário válido.'], 422);
        }
        if (!$this->chatDAO->podeConversar($usuarioId, $destinatarioId)) {
            $this->responder(['erro' => 'Você não tem autorização para acessar esta conversa.'], 403);
        }

        $this->chatDAO->marcarComoVisualizadas($usuarioId, $destinatarioId);
        $mensagens = $this->chatDAO->recuperarHistorico($usuarioId, $destinatarioId);
        $this->responder(['mensagens' => $mensagens]);
    }

    private function enviarMensagem(int $usuarioId): void
    {
        $dados = $this->dadosPost();
        $destinatarioId = $this->id($dados['destinatario_id'] ?? null);
        $mensagem = $dados['mensagem'] ?? null;
        if ($destinatarioId < 1 || !is_string($mensagem)) {
            $this->responder(['erro' => 'Informe destinatário e mensagem.'], 422);
        }

        $mensagem = trim($mensagem);
        $mensagem = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $mensagem) ?? '';
        if ($mensagem === '' || strlen($mensagem) > 2000) {
            $this->responder(['erro' => 'A mensagem deve ter entre 1 e 2000 caracteres.'], 422);
        }
        if (!$this->csrfValido($dados)) {
            $this->responder(['erro' => 'Token de segurança inválido. Atualize a página e tente novamente.'], 419);
        }
        if (!$this->chatDAO->podeConversar($usuarioId, $destinatarioId)) {
            $this->responder(['erro' => 'Você não tem autorização para enviar mensagens a este usuário.'], 403);
        }

        $mensagemSalva = $this->chatDAO->inserirMensagem($usuarioId, $destinatarioId, $mensagem);
        $this->responder(['mensagem' => $mensagemSalva], 201);
    }

    private function dadosPost(): array
    {
        $tipoConteudo = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($tipoConteudo, 'application/json') !== false) {
            $corpo = file_get_contents('php://input');
            $dados = json_decode($corpo ?: '', true);
            return is_array($dados) ? $dados : [];
        }

        return $_POST;
    }

    private function csrfValido(array $dados): bool
    {
        $enviado = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($dados['csrf'] ?? '');

        return is_string($enviado)
            && isset($_SESSION['csrf'])
            && hash_equals($_SESSION['csrf'], $enviado);
    }

    private function id($valor): int
    {
        $id = filter_var($valor, FILTER_VALIDATE_INT);

        return $id !== false && $id > 0 ? $id : -1;
    }

    private function responder(array $dados, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
}
