<?php
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php'; 

// Carrega as variáveis do arquivo .env localizado na raiz do projeto
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->safeLoad(); // Carrega de forma segura sem quebrar se o arquivo não existir

function enviarEmailGlobal($para, $assunto, $mensagem) {
    $mail = new PHPMailer(true);

    try {
        // --- CONFIGURAÇÕES LINDAS VIA VARIÁVEIS DE AMBIENTE ---
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'] ?? 'localhost';
        $mail->SMTPAuth   = true;                           
        $mail->Username   = $_ENV['SMTP_USER'] ?? '';
        $mail->Password   = $_ENV['SMTP_PASS'] ?? '';
        $mail->Port       = $_ENV['SMTP_PORT'] ?? 587;
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->CharSet    = 'UTF-8';                        

        // --- DESTINATÁRIOS ---
        $mail->setFrom($_ENV['SMTP_USER'], 'Sistema HelpDesk');
        $mail->addAddress($para);

        // --- CONTEÚDO DO E-MAIL ---
        $mail->isHTML(true);                                
        $mail->Subject = $assunto;
        $mail->Body    = $mensagem;
        $mail->AltBody = strip_tags($mensagem);

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erro PHPMailer: " . $mail->ErrorInfo);
        return false;
    }
}