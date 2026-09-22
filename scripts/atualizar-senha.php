<?php
/**
 * scripts/atualizar-senha.php
 * Script de processamento em segundo plano para persistir a nova senha redefinida no banco de dados
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Força o fuso horário para bater exatamente com o que gravamos no banco
date_default_timezone_set('Europe/Lisbon');

// Sobe um nível para achar a conexão na raiz
require_once __DIR__ . '/../conexao.php'; 

// Garante o retorno estritamente em formato JSON com codificação correta
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = filter_input(INPUT_POST, 'token', FILTER_UNSAFE_RAW);
    $nova_senha = filter_input(INPUT_POST, 'nova_senha', FILTER_UNSAFE_RAW);

    if (!$token || !$nova_senha || strlen($nova_senha) < 6) {
        echo json_encode([
            'success' => false, 
            'message' => 'Dados inválidos. A nova senha deve ter no mínimo 6 caracteres.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $token = trim($token);
    $data_atual = date('Y-m-d H:i:s'); // Gera a data/hora atual com base no fuso horário do PHP

    // 1. Validar o token de segurança na tabela
    $stmt = $pdo->prepare("
        SELECT id, usuario_id FROM recuperacao_senha 
        WHERE token = :token 
          AND usado = 0 
          AND expira_em > :data_atual 
        LIMIT 1
    ");
    $stmt->execute([
        ':token'      => $token,
        ':data_atual' => $data_atual
    ]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        echo json_encode([
            'success' => false, 
            'message' => 'Este link de recuperação é inválido ou já expirou.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Criptografar a nova senha usando o padrão seguro de hash do PHP
    $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        // 3. Atualizar a senha na tabela usuarios
        $upUser = $pdo->prepare("UPDATE usuarios SET senha = :senha WHERE id = :id");
        $upUser->execute([
            ':senha' => $senha_hash,
            ':id'    => $pedido['usuario_id']
        ]);

        // 4. Marcar o token como usado para inutilizá-lo (Segurança contra reutilização)
        $upToken = $pdo->prepare("UPDATE recuperacao_senha SET usado = 1 WHERE id = :id");
        $upToken->execute([':id' => $pedido['id']]);

        $pdo->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Senha atualizada com sucesso! Você já pode entrar no sistema.'
        ], JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        // Se houver qualquer falha no banco de dados, cancela a transação com segurança
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        echo json_encode([
            'success' => false, 
            'message' => 'Erro interno ao atualizar a senha. Por favor, tente novamente.'
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

echo json_encode([
    'success' => false, 
    'message' => 'Requisição inválida.'
], JSON_UNESCAPED_UNICODE);