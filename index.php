<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/conexao.php';

// Limpeza de tokens de recuperação expirados: executada direto no banco (sem requisição
// HTTP para o próprio servidor, que bloqueava a tela de login) e no máximo 1x por hora
// por sessão — o DELETE é barato, mas não precisa rodar em toda visita.
$agora = time();
if (($agora - ($_SESSION['ultima_limpeza_tokens'] ?? 0)) > 3600) {
    try {
        $data_limite = date('Y-m-d H:i:s', strtotime('-1 day'));
        $stmtLimpeza = $pdo->prepare("DELETE FROM recuperacao_senha WHERE usado = 1 OR expira_em < :data_limite");
        $stmtLimpeza->execute([':data_limite' => $data_limite]);
    } catch (PDOException $e) {
        // Tabela pode ainda não existir; a limpeza não é crítica para o login
    }
    $_SESSION['ultima_limpeza_tokens'] = $agora;
}

// Token CSRF para o formulário de login
if (empty($_SESSION['csrf_token_login'])) {
    $_SESSION['csrf_token_login'] = bin2hex(random_bytes(32));
}

// Mensagem de erro/situação de login via sessão (flash)
$loginMensagem = $_SESSION['login_mensagem'] ?? null;
if ($loginMensagem !== null) {
    unset($_SESSION['login_mensagem']);
}

// ===== CARGO ADMINISTRADOR PADRÃO (criado só se ainda não existir) =====
// Mesmo princípio do usuário Administrador logo abaixo: SELECT COUNT barato, sem
// cache, roda em toda visita à tela de login, pra sempre garantir que o cargo
// básico exista mesmo se a tabela for limpa.
$stmtCargo = $pdo->query("SELECT COUNT(*) FROM cargos WHERE nome = 'Administrador'");
if ($stmtCargo->fetchColumn() == 0) {
    // acesso_total = 1: o Administrador padrão precisa nascer enxergando o sistema
    // inteiro, sem depender de alguém configurar permissões manualmente antes de logar.
    $insCargo = $pdo->prepare("INSERT INTO cargos (nome, acesso_total, empresa) VALUES (:nome, 1, :empresa)");
    $insCargo->execute([
        ':nome'    => 'Administrador',
        ':empresa' => $id_empresa,
    ]);
}

// ===== USUÁRIO ADMINISTRADOR PADRÃO (criado só se ainda não existir) =====
// Roda em toda visita à tela de login (sem cache de sessão): é um SELECT COUNT barato,
// e sem cache aqui o sistema sempre consegue se auto-recuperar se a tabela for limpa.
$stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE nivel = 'Administrador'");
if ($stmt->fetchColumn() == 0) {
    $senha_hash = password_hash($senha_padrao, PASSWORD_DEFAULT);
    $ins = $pdo->prepare("
        INSERT INTO usuarios (nome, email, senha, nivel, ativo, empresa, foto)
        VALUES (:nome, :email, :senha, 'Administrador', :ativo, :empresa, 'sem_foto.png')
    ");
    $ins->execute([
        ':nome'   => 'Administrador',
        ':email'  => $email_sistema,
        ':senha'  => $senha_hash,
        ':ativo'  => '1',
        ':empresa' => $id_empresa,
    ]);
}

// Definição de cores de segurança (fallback) se não existirem no banco
$primary = (!empty($cor_primaria)) ? $cor_primaria : '#4f46e5';
$secondary = (!empty($cor_secundaria)) ? $cor_secundaria : '#818cf8';
$bg_color = (!empty($cor_fundo)) ? $cor_fundo : '#f8fafc';
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo htmlspecialchars($nome_sistema ?? 'Helpdesk'); ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

    <!-- O seu arquivo ÚNICO de estilos (Localizado na raiz css/) -->
    <link rel="stylesheet" href="css/main.css">

    <!-- PONTE DE COMUNICAÇÃO: Passa os valores de cor salvos no banco para o seu CSS unificado -->
    <style>
        :root {
            --cor-primaria: <?php echo $primary; ?>;
            --cor-secundaria: <?php echo $secondary; ?>;
            --helpdesk-bg: <?php echo $bg_color; ?>;
        }
    </style>

    <?php if ($loginMensagem !== null): ?>
        <script>
            window.LOGIN_MENSAGEM = <?php echo json_encode($loginMensagem, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        </script>
    <?php endif; ?>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="login-container">
        <div class="hd-card login-card">
            <div class="hd-card__header text-center">
                <!-- Ícone Grande Centralizado no Topo (Estilo Imagem 2) -->
                <div class="mb-3">
                    <?php if (!empty($icone) && file_exists(__DIR__ . '/uploads/' . basename($icone))): ?>
                        <img src="uploads/<?php echo htmlspecialchars($icone, ENT_QUOTES, 'UTF-8'); ?>" alt="Logo Sistema" style="max-width: 140px; height: auto;">
                    <?php else: ?>
                        <!-- Fallback: mesmo arquivo padrão já usado em uploads/ pra outras imagens do sistema -->
                        <img src="uploads/sem_foto.png" alt="Logo Sistema" style="max-width: 140px; height: auto;">
                    <?php endif; ?>
                </div>

                <!-- Título e Subtítulo abaixo da Logo -->
                <div>
                    <h1 class="hd-card__title"><?php echo htmlspecialchars($nome_sistema ?? 'Helpdesk'); ?></h1>
                    <p class="hd-card__subtitle">Acesse sua conta</p>
                </div>
            </div>

            <div class="hd-card__body">
                <form class="login-form" action="autenticar.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token_login']); ?>">

                    <div class="hd-field">
                        <label for="username" class="hd-field__label">E-mail</label>
                        <div class="hd-field__wrap">
                            <span class="hd-field__icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4Zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10Z" />
                                </svg>
                            </span>
                            <input type="email" class="hd-field__input" id="username" name="username" placeholder="Digite seu e-mail" maxlength="100" required autocomplete="username">
                        </div>
                    </div>

                    <div class="hd-field">
                        <label for="password" class="hd-field__label">Senha</label>
                        <div class="hd-field__wrap">
                            <span class="hd-field__icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2Zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2Z" />
                                </svg>
                            </span>
                            <input type="password" class="hd-field__input" id="password" name="password" placeholder="Digite sua senha" maxlength="255" required autocomplete="current-password">
                        </div>
                    </div>

                    <div class="hd-check">
                        <input class="hd-check__input" type="checkbox" id="remember" name="remember">
                        <label class="hd-check__label" for="remember">Salvar acesso</label>
                    </div>

                    <button type="submit" class="hd-btn hd-btn--primary">Entrar</button>
                </form>
            </div>

            <div class="hd-card__footer hd-card__footer--center">
                <button type="button" class="hd-link" data-bs-toggle="modal" data-bs-target="#modalRecuperarSenha">
                    Esqueceu sua senha?
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: recuperação de senha -->
    <div class="modal fade hd-modal" id="modalRecuperarSenha" tabindex="-1" aria-labelledby="modalRecuperarSenhaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered hd-modal-dialog">
            <div class="modal-content hd-card p-0" style="border: none; overflow: hidden;">

                <div class="hd-card__header hd-card__header--row">
                    <div class="hd-card__brand">
                        <div class="hd-card__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="hd-card__title" id="modalRecuperarSenhaLabel">Recuperar senha</h2>
                            <p class="hd-card__subtitle">Enviaremos as instruções por e-mail</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close hd-modal__close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="hd-card__body">
                    <form id="formRecuperarSenha" novalidate>
                        <div class="hd-field">
                            <label for="emailRecuperar" class="hd-field__label">E-mail</label>
                            <div class="hd-field__wrap">
                                <span class="hd-field__icon" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4Zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10Z" />
                                    </svg>
                                </span>
                                <input type="email" class="hd-field__input" id="emailRecuperar" name="email" placeholder="Digite seu e-mail" maxlength="100" required autocomplete="email" style="padding-left: 2.65rem;">
                            </div>
                            <p class="hd-field__hint">Verifique também a caixa de spam.</p>
                        </div>

                        <button type="button" class="hd-btn hd-btn--primary mb-2" id="btnEnviarRecuperacao">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true" style="margin-right: 0.55rem; display: inline-block; vertical-align: text-bottom;">
                                <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-4.178-6.431-6.431-4.179a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.036 9.434l4.418 2.909-2.604-6.631L6.036 9.434Z" />
                            </svg>
                            Enviar instruções
                        </button>
                    </form>
                </div>

                <div class="hd-card__footer" style="padding-top: 0.5rem;">
                    <button type="button" class="hd-btn hd-btn--ghost" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

    <script src="js/mensagens.js"></script>
    <script>
        window.LOGIN_MODO_TESTE = <?php echo json_encode($modo_teste ?? false, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        window.LOGIN_USUARIO_TESTE = <?php echo json_encode($usuario_teste ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        window.LOGIN_SENHA_TESTE = <?php echo json_encode($senha_teste ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        window.LOGIN_FLASH = <?php echo json_encode($_SESSION['flash'] ?? null);
                                unset($_SESSION['flash']);
                                ?>;
    </script>
    <script src="js/login.js"></script>
</body>

</html>
