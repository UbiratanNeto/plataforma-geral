<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define o fuso horário correto para a sincronização com o banco
date_default_timezone_set('Europe/Lisbon');

// Sobe um nível para achar a conexão na raiz
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../painel/funcoes/email.php'; // Inclui a função de envio de e-mail

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

    if (!$email) {
        echo json_encode(['success' => false, 'message' => 'Por favor, insira um e-mail válido.']);
        exit;
    }

    // 1. Verifica se o usuário existe e está ativo
    $stmt = $pdo->prepare("SELECT id, nome FROM usuarios WHERE email = :email AND ativo = '1' LIMIT 1");
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {

        // 2. Gerar Token seguro
        $token = bin2hex(random_bytes(32));

        // 3. Validade de 30 minutos (sincronizada com o fuso horário do PHP)
        $expira_em = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        // 4. Salvar na tabela de recuperação
        $ins = $pdo->prepare("
            INSERT INTO recuperacao_senha (usuario_id, token, expira_em, usado) 
            VALUES (:usuario_id, :token, :expira_em, 0)
        ");

        $ins->execute([
            ':usuario_id' => $usuario['id'],
            ':token'      => $token,
            ':expira_em'  => $expira_em
        ]);

        // 5. Link dinâmico apontando para o domínio/servidor atual (local ou produção)
        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $link_recuperacao = $protocolo . $host . "/helpdesk/recuperacao-senha.php?token=" . $token;

        // ===== CONFIGURAÇÃO DE CORES DINÂMICAS PARA O E-MAIL =====
        $primary = (!empty($cor_primaria)) ? $cor_primaria : '#4f46e5';
        $nome_app = (!empty($nome_sistema)) ? $nome_sistema : 'Sistema Helpdesk';

        // ===== ENVIO DE E-MAIL (Com identidade visual dinâmica do sistema) =====
        $assunto = "Recuperação de Senha - " . $nome_app;

        // Corpo do e-mail com design amigável em HTML utilizando as cores do sistema
        $mensagem = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;'>
                <h2 style='color: {$primary}; margin-top: 0;'>Olá, " . htmlspecialchars($usuario['nome']) . "!</h2>
                <p style='color: #333; font-size: 16px;'>Recebemos uma solicitação para redefinir a senha da sua conta no <strong>{$nome_app}</strong>.</p>
                <p style='color: #333; font-size: 16px;'>Para prosseguir e escolher uma nova senha, basta clicar no botão abaixo:</p>
                
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='{$link_recuperacao}' style='background-color: {$primary}; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;'>Redefinir senha</a>
                </p>
                
                <p style='font-size: 12px; color: #666;'>Este link de segurança é válido por apenas 30 minutos. Se não foi você quem solicitou esta alteração, pode ignorar este e-mail com segurança.</p>
                <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 11px; color: #999;'>Caso o botão acima não funcione, copie e cole o seguinte link no seu navegador:<br>{$link_recuperacao}</p>
            </div>
        ";

        // Executa a função do PHPMailer definida em email.php
        $enviou = enviarEmailGlobal($email, $assunto, $mensagem);

        if ($enviou) {
            echo json_encode([
                'success' => true,
                'message' => 'Instruções enviadas com sucesso! Verifique sua caixa de entrada.'
            ]);
        } else {
            // Se falhar, avisa o usuário (o log detalhado já foi gerado dentro de email.php)
            echo json_encode([
                'success' => false,
                'message' => 'O token de segurança foi criado, mas ocorreu um erro interno ao tentar despachar o e-mail. Por favor, contate o administrador.'
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Este e-mail não foi encontrado em nosso sistema ou a conta está inativa.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Requisição inválida.']);