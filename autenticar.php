<?php
/**
 * autenticar.php
 * Autenticação de login - valida usuário/senha e inicia sessão
 * Medidas de segurança aplicadas: CSRF, validação de entrada, limite de tentativas, regeneração de sessão
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/painel/funcoes/logs.php';

// ---------- Só processa requisições via POST ----------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// ---------- Proteção CSRF ----------
$token = $_POST['csrf_token'] ?? '';
if (empty($token) || !hash_equals($_SESSION['csrf_token_login'] ?? '', $token)) {
    $_SESSION['login_mensagem'] = '1'; // Código para token inválido ou expirado
    header('Location: index.php');
    exit;
}
// Consome o token de uso único
unset($_SESSION['csrf_token_login']);

// ---------- Limite de tentativas (Proteção contra Brute Force) ----------
$max_tentativas = 5;
$bloqueio_minutos = 10;
if (empty($_SESSION['login_tentativas'])) {
    $_SESSION['login_tentativas'] = 0;
    $_SESSION['login_ultima_tentativa'] = time();
}
if ($_SESSION['login_tentativas'] >= $max_tentativas) {
    $passou = (time() - $_SESSION['login_ultima_tentativa']) > ($bloqueio_minutos * 60);
    if (!$passou) {
        $_SESSION['login_mensagem'] = 'bloqueado';
        header('Location: index.php');
        exit;
    }
    $_SESSION['login_tentativas'] = 0;
}

// ---------- Entrada: sanitização básica e validação ----------
$username = str_replace(["\0", "\r", "\n"], '', trim($_POST['username'] ?? ''));
$password = $_POST['password'] ?? '';

// Limita tamanhos máximos para evitar ataques de estouro de memória (payload gigante)
$username = mb_substr($username, 0, 100, 'UTF-8');
if (strlen($password) > 255) {
    $password = '';
}

if ($username === '' || $password === '') {
    $_SESSION['login_tentativas'] = ($_SESSION['login_tentativas'] ?? 0) + 1;
    $_SESSION['login_ultima_tentativa'] = time();
    $_SESSION['login_mensagem'] = '1';
    header('Location: index.php');
    exit;
}

// Valida formato do e-mail
if (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['login_tentativas'] = ($_SESSION['login_tentativas'] ?? 0) + 1;
    $_SESSION['login_ultima_tentativa'] = time();
    $_SESSION['login_mensagem'] = '1';
    header('Location: index.php');
    exit;
}

// ---------- Consulta Segura (Prepared Statement contra SQL Injection) ----------
$stmt = $pdo->prepare("SELECT id, nome, email, senha, nivel, cargo_id, ativo, empresa FROM usuarios WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $username]);
$usuario = $stmt->fetch();

// Valida existência do usuário e verifica o hash da senha
if (!$usuario || !password_verify($password, $usuario['senha'])) {
    $_SESSION['login_tentativas'] = ($_SESSION['login_tentativas'] ?? 0) + 1;
    $_SESSION['login_ultima_tentativa'] = time();
    $_SESSION['login_mensagem'] = '1'; // Código de credenciais incorretas
    header('Location: index.php');
    exit;
}

// Verifica se a conta do usuário está ativa
if (empty($usuario['ativo']) || $usuario['ativo'] === '0') {
    $_SESSION['login_mensagem'] = 'inativo';
    header('Location: index.php');
    exit;
}

// === SUCESSO NA AUTENTICAÇÃO ===
// Zera o contador de erros
$_SESSION['login_tentativas'] = 0;

// Regenera o ID da sessão para prevenir Session Fixation (sequestro de sessão)
session_regenerate_id(true);

// Grava os dados do usuário na sessão ativa
$_SESSION['id']         = (int) $usuario['id'];
$_SESSION['nome']       = $usuario['nome'];
$_SESSION['email']      = $usuario['email'];
$_SESSION['nivel']      = $usuario['nivel'];
$_SESSION['cargo_id']   = $usuario['cargo_id'] !== null ? (int) $usuario['cargo_id'] : null;
$_SESSION['id_empresa'] = (int) $usuario['empresa'];

registrarLog($pdo, 'login', 'usuarios', (int) $usuario['id'], 'Login realizado com sucesso');

// Redireciona com caminho explícito para o painel (URL amigável)
header('Location: painel/dashboard');
exit;