<?php
/**
 * scripts/limpar_tokens_expirados.php
 * Script executado em segundo plano para limpar tokens expirados ou já utilizados da tabela
 */

// Configura o fuso horário para bater exatamente com o seu banco de dados
date_default_timezone_set('Europe/Lisbon');

// Sobe um nível para achar a conexão na raiz
require_once __DIR__ . '/../conexao.php'; 

// Garante que o retorno seja interpretado estritamente como JSON
header('Content-Type: application/json; charset=utf-8');

// Define o limite de 24 horas atrás para remoção física de históricos expirados
$data_limite = date('Y-m-d H:i:s', strtotime('-1 day'));

try {
    // Inicia uma transação para garantir isolamento e performance na deleção
    $pdo->beginTransaction();

    // Deleta tokens marcados como usados OU tokens que expiraram há mais de 24 horas
    $stmt = $pdo->prepare("
        DELETE FROM recuperacao_senha 
        WHERE usado = 1 
           OR expira_em < :data_limite
    ");
    
    $stmt->execute([':data_limite' => $data_limite]);
    $linhas_afetadas = $stmt->rowCount();

    // Confirma a operação no banco de dados
    $pdo->commit();

    // Retorna a resposta limpa e estruturada
    echo json_encode([
        'success' => true,
        'message' => "Limpeza concluída com sucesso! Registros deletados: {$linhas_afetadas}."
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // Se algo der errado, desfaz a alteração de segurança
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => "Erro ao limpar tokens: " . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}