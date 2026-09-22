/**
 * painel/assets/js/painel.js
 * Gerencia a alternância da Sidebar e persistência no localStorage
 */

document.addEventListener('DOMContentLoaded', () => {
    // Seleciona o container principal que engloba o layout
    // No seu CSS atual, o layout usa a classe .hd-layout
    const app = document.querySelector('.hd-layout');
    
    // Seleciona o botão de toggle (ID definido no seu topbar.php)
    const btnToggle = document.getElementById('btn-toggle-sidebar');

    if (btnToggle && app) {
        btnToggle.addEventListener('click', () => {
            // Alterna a classe de estado no container principal
            // O seu CSS usa .hd-layout--sidebar-collapsed
            app.classList.toggle('hd-layout--sidebar-collapsed');
            
            // Salva o estado no localStorage para manter a preferência do usuário
            const isCollapsed = app.classList.contains('hd-layout--sidebar-collapsed');
            localStorage.setItem('sidebar-collapsed', isCollapsed ? '1' : '0');
        });

        // Verifica o estado salvo anteriormente ao carregar a página
        if (localStorage.getItem('sidebar-collapsed') === '1') {
            app.classList.add('hd-layout--sidebar-collapsed');
        }
    }

    // Alterna qualquer grupo de menu com submenu (ex.: "Cadastros"). Genérico:
    // funciona pra qualquer grupo futuro que siga o mesmo par de atributos
    // data-submenu (no botão) / data-submenu-content (na lista), sem precisar
    // de código novo aqui. O estado inicial (aberto/fechado) já vem pronto do
    // PHP (variável $xxxOpen no sidebar.php), então aqui só cuidamos do clique.
    document.querySelectorAll('.hd-sidebar__group-toggle').forEach((botao) => {
        botao.addEventListener('click', () => {
            const nomeGrupo = botao.getAttribute('data-submenu');
            const submenu = document.querySelector(`[data-submenu-content="${nomeGrupo}"]`);
            if (!submenu) return;

            const vaiAbrir = submenu.hasAttribute('hidden');
            submenu.toggleAttribute('hidden', !vaiAbrir);
            botao.setAttribute('aria-expanded', vaiAbrir ? 'true' : 'false');
        });
    });
});