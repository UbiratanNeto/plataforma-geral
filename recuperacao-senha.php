<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Define o fuso horário correto para a verificação de expiração no PHP
date_default_timezone_set('Europe/Lisbon');

// Inclui o arquivo de conexão com o banco de dados
require_once __DIR__ . '/conexao.php';

// Garante acesso às variáveis de estilo dinâmico vindas da conexao.php
global $cor_primaria, $cor_secundaria;

// Se as variáveis estiverem vazias, define as cores roxas padrão para o layout
$primary = (!empty($cor_primaria)) ? $cor_primaria : '#4f46e5';
$secondary = (!empty($cor_secundaria)) ? $cor_secundaria : '#818cf8';

$token = filter_input(INPUT_GET, 'token', FILTER_DEFAULT);
if ($token) {
    $token = trim($token);
}

$isValidToken = false;
$errorMessage = '';

if ($token) {
    try {
        // Consulta na tabela em português 'recuperacao_senha' e 'usuarios'
        $stmt = $pdo->prepare("
            SELECT r.id, r.expira_em 
            FROM recuperacao_senha r
            INNER JOIN usuarios u ON r.usuario_id = u.id
            WHERE r.token = :token 
              AND r.usado = 0 
              AND u.ativo = 1
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $recovery = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($recovery) {
            $currentTime = time(); 
            $expiresAt = strtotime($recovery['expira_em']); 

            // Valida se o token não expirou
            if ($currentTime <= $expiresAt) {
                $isValidToken = true;
            } else {
                $errorMessage = 'Este link de recuperação expirou (validade de 30 minutos).';
            }
        } else {
            $errorMessage = 'Este link de recuperação é inválido ou já foi utilizado.';
        }
    } catch (PDOException $e) {
        $errorMessage = 'Ocorreu um erro no sistema. Por favor, tente novamente mais tarde.';
    }
} else {
    $errorMessage = 'O token de segurança está ausente.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir senha - HelpDesk</title>
    
    <!-- SweetAlert2 para notificações bonitas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- CSS Unificado do sistema -->
    <link rel="stylesheet" href="css/main.css">

    <!-- Injeção dinâmica de variáveis CSS para cor primária e secundária -->
    <style>
        :root {
            --cor-primaria: <?php echo $primary; ?>;
            --cor-secundaria: <?php echo $secondary; ?>;
        }
    </style>
</head>

<body>

    <div class="login-container">
        <div class="hd-card login-card">
            <div class="hd-card__header">
                <div class="hd-card__brand">
                    <div class="hd-card__icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2Zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2ZM5 8h6a1 1 0 0 1 1 1v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="hd-card__title">Redefinir senha</h1>
                        <p class="hd-card__subtitle">Insira a sua nova senha de acesso abaixo</p>
                    </div>
                </div>
            </div>

            <div class="hd-card__body">
                <?php if (!$isValidToken): ?>
                    <div class="hd-alert hd-alert--error">
                        <p><?php echo htmlspecialchars($errorMessage); ?></p>
                    </div>
                    <a href="index.php" class="hd-btn hd-btn--primary" style="width: 100%; margin-top: 1rem;">
                        Voltar para o Login
                    </a>
                <?php else: ?>
                    <form id="formResetPassword" class="login-form">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                        <div class="hd-field">
                            <label for="nova_senha" class="hd-field__label">Nova senha</label>
                            <div class="hd-field__wrap">
                                <span class="hd-field__icon" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2Zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2Z"/>
                                    </svg>
                                </span>
                                <input type="password" id="nova_senha" name="nova_senha" class="hd-field__input" required minlength="6" placeholder="Mínimo 6 caracteres">
                            </div>
                        </div>

                        <div class="hd-field">
                            <label for="confirm_password" class="hd-field__label">Confirmar nova senha</label>
                            <div class="hd-field__wrap">
                                <span class="hd-field__icon" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2Zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2Z"/>
                                    </svg>
                                </span>
                                <input type="password" id="confirm_password" class="hd-field__input" required minlength="6" placeholder="Repita a senha">
                            </div>
                        </div>

                        <button type="submit" id="btnSubmit" class="hd-btn hd-btn--primary">
                            Atualizar senha
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('formResetPassword');
            if (!form) return;

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const password = form.querySelector('input[name="nova_senha"]').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                const btn = document.getElementById('btnSubmit');

                // Puxa a cor do PHP para manter o botão do SweetAlert no tom correto
                const primaryColor = '<?php echo $primary; ?>';

                if (password !== confirmPassword) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Aviso',
                        text: 'As senhas não coincidem.',
                        confirmButtonColor: primaryColor
                    });
                    return;
                }

                btn.disabled = true;
                btn.innerText = 'Atualizando...';

                const formData = new FormData(form);

                fetch('scripts/atualizar-senha.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Sucesso!',
                                text: data.message,
                                confirmButtonText: 'Ir para o Login',
                                confirmButtonColor: primaryColor,
                                allowOutsideClick: false
                            }).then((result) => {
                                if (result.isConfirmed) window.location.href = 'index.php';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Erro',
                                text: data.message,
                                confirmButtonColor: primaryColor
                            });
                            btn.disabled = false;
                            btn.innerText = 'Atualizar senha';
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro de Sistema',
                            text: 'Não foi possível conectar ao servidor.',
                            confirmButtonColor: primaryColor
                        });
                        btn.disabled = false;
                        btn.innerText = 'Atualizar senha';
                    });
            });
        });
    </script>
</body>

</html>