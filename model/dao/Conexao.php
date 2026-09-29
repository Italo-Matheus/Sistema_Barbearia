<?php
/**
 * Conexão única com o banco de dados (PDO).
 *
 * Só este arquivo conhece os dados de acesso. Todas as páginas usam
 * a variável $pdo criada aqui.
 */

$dbHost    = 'localhost';
$dbNome    = 'barbearia';
$dbUsuario = 'root';  // padrão do XAMPP e do WAMP
$dbSenha   = '';      // padrão do XAMPP e do WAMP: sem senha

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbNome};charset=utf8mb4",
        $dbUsuario,
        $dbSenha,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // erros viram exceções
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // linhas como arrays
            PDO::ATTR_EMULATE_PREPARES   => false,                  // prepared statements de verdade
        ]
    );
} catch (PDOException $e) {
    error_log('Erro de conexão com o banco: ' . $e->getMessage());
    http_response_code(500);
    exit('Não conseguimos acessar o banco de dados agora. Confira se o MySQL está ligado e os dados em config/conexao.php.');
}

// Os dados de acesso não precisam ficar disponíveis para o resto do sistema.
unset($dbHost, $dbNome, $dbUsuario, $dbSenha);
