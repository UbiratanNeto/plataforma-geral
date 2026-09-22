<?php
/**
 * painel/funcoes/logs.php
 * Registro de auditoria (tabela `logs`) — quem fez o quê, quando e em qual registro.
 */

/**
 * Grava uma entrada na tabela de logs. Usuário, empresa, rota, IP e navegador são
 * descobertos sozinhos (sessão/$_SERVER) — só é preciso informar o que é específico
 * da ação em si.
 *
 * @param PDO         $pdo         Conexão já aberta (a mesma $pdo de conexao.php)
 * @param string      $acao        'login' | 'logout' | 'inserir' | 'editar' | 'excluir'
 * @param string      $entidade    Nome da entidade afetada (ex.: 'usuario', 'cliente', 'cargo')
 * @param int|null    $registro_id ID do registro afetado (null quando não se aplica, ex.: login)
 * @param string|null $descricao   Texto livre com detalhes (ex.: "Cliente João Silva criado")
 */
function registrarLog(PDO $pdo, string $acao, string $entidade, ?int $registro_id = null, ?string $descricao = null): void
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO logs (empresa, usuario_id, acao, entidade, registro_id, descricao, rota, ip, user_agent)
            VALUES (:empresa, :usuario_id, :acao, :entidade, :registro_id, :descricao, :rota, :ip, :user_agent)
        ");
        $stmt->execute([
            ':empresa'     => (int) ($_SESSION['id_empresa'] ?? 0),
            ':usuario_id'  => (int) ($_SESSION['id'] ?? 0),
            ':acao'        => $acao,
            ':entidade'    => $entidade,
            ':registro_id' => $registro_id,
            ':descricao'   => $descricao,
            ':rota'        => $_SERVER['REQUEST_URI'] ?? null,
            ':ip'          => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    } catch (Throwable $e) {
        // Nunca deixa uma falha de log derrubar a ação principal (salvar/excluir já aconteceu).
        error_log('Falha ao registrar log: ' . $e->getMessage());
    }
}
