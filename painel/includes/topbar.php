<?php

/**
 * painel/includes/topbar.php
 */
$user_nome  = $_SESSION['nome'] ?? 'Usuário';
$user_email = $_SESSION['email'] ?? 'sem-email@sistema.com';
$user_nivel = $_SESSION['nivel'] ?? $_SESSION['cargo'] ?? 'Colaborador';
$primeira_letra = !empty(trim($user_nome)) ? strtoupper(substr(trim($user_nome), 0, 1)) : 'U';

// Foto de perfil: só usa se o nome vier preenchido E o arquivo existir de verdade em uploads/perfil.
// basename() evita que um valor malicioso no banco vire um caminho de arquivo (../../etc).
$foto_arquivo = basename($_SESSION['foto'] ?? '');
$foto_existe  = $foto_arquivo !== '' && file_exists(__DIR__ . '/../../uploads/perfil/' . $foto_arquivo);
?>
<header class="hd-topbar">
    <!-- Botão de menu: O CSS .hd-topbar já alinha isso à esquerda -->
    <button id="btn-toggle-sidebar" title="Menu" style="background:none; border:none; cursor:pointer; font-size: 1.2rem; color: var(--muted-color);">
        <i class="fa-solid fa-bars"></i>
    </button>

    <!-- Container da direita: O CSS .hd-topbar coloca isso na direita automaticamente -->
    <div style="display: flex; align-items: center; gap: 15px;">

        <!-- Ícone de Notificação -->
        <button title="Notificações" style="background:none; border:none; cursor:pointer; font-size: 1.1rem; color: var(--muted-color);">
            <i class="fa-regular fa-bell"></i>
        </button>

        <!-- Dropdown do Usuário -->
        <div class="user-dropdown-container">
            <button class="user-dropdown-trigger" id="userDropdownBtn">
                <?php if ($foto_existe): ?>
                    <img class="user-avatar-photo" src="../uploads/perfil/<?php echo htmlspecialchars($foto_arquivo, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                <?php else: ?>
                    <div class="user-avatar-placeholder">
                        <?php echo htmlspecialchars($primeira_letra, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
                <span class="user-display-name"><?php echo htmlspecialchars($user_nome, ENT_QUOTES, 'UTF-8'); ?></span>
                <i class="fa-solid fa-chevron-down arrow-icon"></i>
            </button>

            <div class="user-dropdown-menu" id="userDropdownMenu">
                <div class="user-dropdown-info">
                    <p class="user-dropdown-level"><strong>Nível:</strong> <?php echo htmlspecialchars($user_nivel, ENT_QUOTES, 'UTF-8'); ?></p>
                </div>

                <div class="user-dropdown-divider"></div>

                <!-- Novos Links Adicionados -->
                <a href="#" class="user-dropdown-item" onclick="document.getElementById('modalConfig').style.display='flex'; return false;">
                    <i class="fa-solid fa-gear"></i>
                    Editar Configurações
                </a>
                <a href="#" class="user-dropdown-item" onclick="document.getElementById('modalPerfil').style.display='flex'; return false;">
                    <i class="fa-solid fa-user-edit"></i>
                    Editar Perfil
                </a>

                <div class="user-dropdown-divider"></div>
                <a href="../logout.php" class="user-dropdown-item logout-btn">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Sair
                </a>
            </div>
        </div>
    </div>

</header>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropdownBtn = document.getElementById('userDropdownBtn');
        const dropdownMenu = document.getElementById('userDropdownMenu');
        if (dropdownBtn && dropdownMenu) {
            dropdownBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownMenu.classList.toggle('active');
            });
            document.addEventListener('click', function() {
                dropdownMenu.classList.remove('active');
            });
        }
    });
</script>