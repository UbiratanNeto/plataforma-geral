<?php
/**
 * painel/verificar.php
 * Controle de acesso e segurança das páginas restritas do painel.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Bloqueia o acesso caso a sessão não possua o ID do usuário (usuário não logado)
if (empty($_SESSION['id'])) {
    header('Location: ../index.php');
    exit;
}

// 2. Inclui a conexão com o banco de dados (voltando uma pasta para achar o conexao.php na raiz)
require_once __DIR__ . '/../conexao.php';

try {
    // 3. Busca no banco os dados em tempo real para verificar se o usuário continua ativo ou mudou de nível
    $stmt = $pdo->prepare("SELECT id, nome, email, telefone, cpf, nivel, cargo_id, ativo, empresa, foto, cep, estado, cidade, bairro, endereco, numero, complemento FROM usuarios WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $_SESSION['id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se o usuário foi excluído do banco enquanto estava logado
    if (!$usuario) {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header('Location: ../index.php?erro=sessao');
        exit;
    }

    // Se o usuário foi inativado pelo administrador
    if (empty($usuario['ativo']) || $usuario['ativo'] === '0') {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header('Location: ../index.php?erro=inativo');
        exit;
    }

    // 4. Sincroniza e atualiza os dados da sessão com as informações em tempo real do banco
    $_SESSION['nome']       = $usuario['nome'];
    $_SESSION['email']      = $usuario['email'];
    $_SESSION['nivel']      = $usuario['nivel'];
    $_SESSION['cargo_id']   = $usuario['cargo_id'] !== null ? (int) $usuario['cargo_id'] : null;
    $_SESSION['id_empresa'] = (int) $usuario['empresa'];
    $_SESSION['foto']       = $usuario['foto'];

    // Demais dados de perfil, usados só para pré-preencher o modal "Editar Perfil"
    $_SESSION['telefone']    = $usuario['telefone'];
    $_SESSION['cpf']         = $usuario['cpf'];
    $_SESSION['cep']         = $usuario['cep'];
    $_SESSION['estado']      = $usuario['estado'];
    $_SESSION['cidade']      = $usuario['cidade'];
    $_SESSION['bairro']      = $usuario['bairro'];
    $_SESSION['endereco']    = $usuario['endereco'];
    $_SESSION['numero']      = $usuario['numero'];
    $_SESSION['complemento'] = $usuario['complemento'];

    // 5. Cria as variáveis locais limpas e protegidas contra ataques XSS para usar no HTML das páginas
    $id_usuario_logado = (int) $_SESSION['id'];
    $id_empresa_sessao = (int) $_SESSION['id_empresa'];
    
    $nome_sessao       = htmlspecialchars($_SESSION['nome'] ?? '', ENT_QUOTES, 'UTF-8');
    $email_sessao      = htmlspecialchars($_SESSION['email'] ?? '', ENT_QUOTES, 'UTF-8');
    $nivel_sessao      = htmlspecialchars($_SESSION['nivel'] ?? '', ENT_QUOTES, 'UTF-8');

} catch (PDOException $e) {
    // Caso ocorra uma falha crítica de conexão ao validar a sessão, evita expor detalhes do banco
    die("Erro de segurança: Não foi possível validar sua sessão ativa no momento.");
}