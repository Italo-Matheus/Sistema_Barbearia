<?php
/**
 * Funções de apoio usadas por todas as páginas.
 * Inclua depois de config/conexao.php.
 */

// ---------------------------------------------------------------------
// Configurações simples do sistema (mude aqui, vale para todas as páginas)
// ---------------------------------------------------------------------
const NOME_BARBEARIA = 'TeamCurtz';

// Horários de atendimento: cada horário é uma vaga de um barbeiro.
const HORARIOS_ATENDIMENTO = ['08:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00', '17:00', '19:30'];

// Até quantos dias à frente o cliente pode reservar.
const DIAS_MAXIMOS_AGENDAMENTO = 30;

const DIAS_SEMANA  = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
const MESES        = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
const MESES_CURTOS = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

date_default_timezone_set('America/Sao_Paulo');

// ---------------------------------------------------------------------
// Sessão
// ---------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// ---------------------------------------------------------------------
// Saída segura e redirecionamento
// ---------------------------------------------------------------------

/** Protege qualquer texto que vai para o HTML (evita XSS). */
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function redirecionar(string $destino): void
{
    header('Location: ' . $destino);
    exit;
}

/** Lê um campo de texto enviado por POST, já sem espaços nas pontas. */
function campo(string $nome): string
{
    $valor = $_POST[$nome] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

/** Senhas não passam por trim(): espaços fazem parte da senha. */
function campoSenha(string $nome): string
{
    $valor = $_POST[$nome] ?? '';
    return is_string($valor) ? $valor : '';
}

// ---------------------------------------------------------------------
// Mensagens para o usuário (aparecem uma vez e somem)
// ---------------------------------------------------------------------

/** $tipo: success, danger, warning ou info (classes de alerta do Bootstrap). */
function definirMensagem(string $tipo, string $texto): void
{
    $_SESSION['mensagens'][] = ['tipo' => $tipo, 'texto' => $texto];
}

function exibirMensagens(): void
{
    if (empty($_SESSION['mensagens'])) {
        return;
    }
    echo '<div class="container mt-4">';
    foreach ($_SESSION['mensagens'] as $mensagem) {
        echo '<div class="alert alert-' . e($mensagem['tipo']) . ' alert-dismissible fade show" role="alert">'
            . e($mensagem['texto'])
            . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button></div>';
    }
    echo '</div>';
    unset($_SESSION['mensagens']);
}

/** Lista de erros de um formulário, em um único alerta. */
function exibirErros(array $erros): void
{
    if (!$erros) {
        return;
    }
    echo '<div class="alert alert-danger" role="alert">';
    if (count($erros) === 1) {
        echo e(reset($erros));
    } else {
        echo '<ul class="mb-0 ps-3">';
        foreach ($erros as $erro) {
            echo '<li>' . e($erro) . '</li>';
        }
        echo '</ul>';
    }
    echo '</div>';
}

/** Ajudantes para mostrar o erro embaixo de cada campo. */
function classeInvalido(array $erros, string $campo): string
{
    return isset($erros[$campo]) ? ' is-invalid' : '';
}

function erroCampo(array $erros, string $campo): string
{
    return isset($erros[$campo]) ? '<div class="invalid-feedback">' . e($erros[$campo]) . '</div>' : '';
}

// ---------------------------------------------------------------------
// Proteção CSRF: todo formulário POST leva um token da sessão
// ---------------------------------------------------------------------

function tokenCsrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function campoCsrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(tokenCsrf()) . '">';
}

function csrfValido(): bool
{
    $enviado = $_POST['csrf'] ?? '';
    return is_string($enviado) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $enviado);
}

// ---------------------------------------------------------------------
// Autenticação
// ---------------------------------------------------------------------

function usuarioLogado(): bool
{
    return !empty($_SESSION['usuario_id']);
}

function tipoUsuario(): ?string
{
    return $_SESSION['usuario_tipo'] ?? null;
}

function baseAplicacao(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
    $viewPosition = strpos($scriptName, '/view/');
    $baseHref = $viewPosition !== false
        ? substr($scriptName, 0, $viewPosition + 1)
        : rtrim(dirname($scriptName), '/') . '/';

    return $baseHref !== '' ? $baseHref : '/';
}

/** Página inicial de cada tipo de usuário. */
function areaDoUsuario(): string
{
    return match (tipoUsuario()) {
        'admin' => 'view/admin/dashboard.php',
        'barbeiro' => 'view/admin/barbeiro.php',
        default => 'cliente.php',
    };
}

function exigirAdmin(PDO $pdo): array
{
    if (!usuarioLogado()) {
        definirMensagem('info', 'Entre na sua conta para continuar.');
        redirecionar(baseAplicacao() . 'view/login.php');
    }

    $consulta = $pdo->prepare('SELECT id, nome, tipo, ativo FROM usuarios WHERE id = ? LIMIT 1');
    $consulta->execute([(int) $_SESSION['usuario_id']]);
    $usuario = $consulta->fetch();

    if (!$usuario || !(bool) $usuario['ativo'] || $usuario['tipo'] !== 'admin') {
        encerrarSessao();
        definirMensagem('danger', 'Acesso restrito ao perfil administrador. Entre com uma conta autorizada.');
        redirecionar(baseAplicacao() . 'view/login.php');
    }

    $_SESSION['usuario_tipo'] = 'admin';
    $_SESSION['usuario_nome'] = $usuario['nome'];

    return $usuario;
}

/**
 * Use no topo das páginas privadas.
 * Sem login, manda para o login. Com o tipo errado, manda para a área certa.
 */
function exigirLogin(?string $tipo = null, ?PDO $pdo = null): void
{
    if (!usuarioLogado()) {
        definirMensagem('info', 'Entre na sua conta para continuar.');
        redirecionar(baseAplicacao() . 'view/login.php');
    }

    if ($pdo !== null) {
        $consulta = $pdo->prepare('SELECT nome, tipo, ativo FROM usuarios WHERE id = ? LIMIT 1');
        $consulta->execute([(int) $_SESSION['usuario_id']]);
        $usuario = $consulta->fetch();

        if (!$usuario || !(bool) $usuario['ativo']) {
            encerrarSessao();
            definirMensagem('warning', 'Sua conta está indisponível. Entre novamente ou fale com a barbearia.');
            redirecionar(baseAplicacao() . 'view/login.php');
        }

        $_SESSION['usuario_tipo'] = $usuario['tipo'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
    }

    if ($tipo !== null && tipoUsuario() !== $tipo) {
        definirMensagem('warning', 'Essa página é de outro tipo de conta. Levamos você para o seu espaço.');
        redirecionar(baseAplicacao() . areaDoUsuario());
    }
}

/** Busca no banco os dados do usuário logado (a sessão guarda só o id). */
function carregarUsuario(PDO $pdo): array
{
    $consulta = $pdo->prepare(
        'SELECT id, nome, email, telefone, cpf, tipo, especialidade, descricao, ativo
         FROM usuarios WHERE id = ?'
    );
    $consulta->execute([$_SESSION['usuario_id']]);
    $usuario = $consulta->fetch();

    if (!$usuario || !(bool) $usuario['ativo']) {
        encerrarSessao();
        definirMensagem('warning', 'Sua conta está indisponível. Entre novamente ou fale com a barbearia.');
        redirecionar(baseAplicacao() . 'view/login.php');
    }
    return $usuario;
}

function encerrarSessao(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

// ---------------------------------------------------------------------
// Formatação
// ---------------------------------------------------------------------

function apenasDigitos(string $valor): string
{
    return preg_replace('/\D+/', '', $valor);
}

function formatarPreco($valor): string
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function formatarHorario(string $horario): string
{
    return substr($horario, 0, 5);
}

/** AAAA-MM-DD vira DD/MM/AAAA. */
function formatarData(string $data): string
{
    $objeto = DateTime::createFromFormat('Y-m-d', $data);
    return $objeto ? $objeto->format('d/m/Y') : $data;
}

function formatarTelefone(string $telefone): string
{
    $d = apenasDigitos($telefone);
    if (strlen($d) === 11) {
        return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 5), substr($d, 7));
    }
    if (strlen($d) === 10) {
        return sprintf('(%s) %s-%s', substr($d, 0, 2), substr($d, 2, 4), substr($d, 6));
    }
    return $telefone;
}

function formatarCpf(string $cpf): string
{
    $d = apenasDigitos($cpf);
    if (strlen($d) !== 11) {
        return $cpf;
    }
    return sprintf('%s.%s.%s-%s', substr($d, 0, 3), substr($d, 3, 3), substr($d, 6, 3), substr($d, 9));
}

function primeiroNome(string $nome): string
{
    return explode(' ', trim($nome))[0];
}

/** Quebra uma data (AAAA-MM-DD) em partes prontas para exibir. */
function dataPartes(string $data): array
{
    $ts = strtotime($data);
    return [
        'dia'       => date('j', $ts),
        'mes'       => MESES[(int) date('n', $ts) - 1],
        'mes_curto' => MESES_CURTOS[(int) date('n', $ts) - 1],
        'semana'    => DIAS_SEMANA[(int) date('w', $ts)],
    ];
}

/** Ex.: "sábado, 26 de setembro" */
function dataExtenso(string $data): string
{
    $p = dataPartes($data);
    return $p['semana'] . ', ' . $p['dia'] . ' de ' . $p['mes'];
}

/** "Hoje" ou "Amanhã" quando for o caso; texto vazio nos demais dias. */
function rotuloDia(string $data): string
{
    if ($data === date('Y-m-d')) {
        return 'Hoje';
    }
    if ($data === date('Y-m-d', strtotime('+1 day'))) {
        return 'Amanhã';
    }
    return '';
}

// ---------------------------------------------------------------------
// Validação
// ---------------------------------------------------------------------

/** Confere os dígitos verificadores do CPF (recebe só números). */
function cpfValido(string $cpf): bool
{
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }
    for ($posicao = 9; $posicao < 11; $posicao++) {
        $soma = 0;
        for ($i = 0; $i < $posicao; $i++) {
            $soma += (int) $cpf[$i] * (($posicao + 1) - $i);
        }
        $digito = ((10 * $soma) % 11) % 10;
        if ((int) $cpf[$posicao] !== $digito) {
            return false;
        }
    }
    return true;
}
