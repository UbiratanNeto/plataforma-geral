<?php
/**
 * painel/includes/sidebar.php
 * Menu lateral dinâmico — renderizado a partir do catálogo único (menus.php) e filtrado
 * pelas permissões do usuário logado (permissoes.php). Criar uma página nova ou mudar
 * quem pode ver o quê não exige tocar neste arquivo — só o catálogo/as permissões.
 */

require_once __DIR__ . '/permissoes.php';

// Define qual a página atual baseado no parâmetro 'p' da URL (ex: index.php?p=dashboard)
// Se não houver nenhum parâmetro, define 'dashboard' como padrão
$atual = $_GET['p'] ?? 'dashboard';

$catalogo = require __DIR__ . '/menus.php';
$grupos   = $catalogo['grupos'];
$paginas  = $catalogo['paginas'];

$menusPermitidos = menusPermitidos($pdo, $_SESSION['id'] ?? null, $_SESSION['cargo_id'] ?? null);

// Função idêntica à do seu curso para ativar o menu correto
function menuAtivo($pagina, $atual) {
    return $pagina === $atual ? 'hd-sidebar__item--active' : '';
}

// Mesma ideia do menuAtivo(), mas pra grupos com submenu: destaca o cabeçalho
// do grupo (ex.: "Cadastros") quando a página atual é uma das filhas dele.
function grupoAtivo(array $paginasFilhas, $atual) {
    return in_array($atual, $paginasFilhas, true) ? 'hd-sidebar__group--active' : '';
}
?>
<!-- Sidebar (.hd-layout__sidebar) -->
<aside class="hd-layout__sidebar">
    <div>
        <div class="hd-sidebar__brand">
            <div class="hd-sidebar__brand-icon" aria-hidden="true">
                <i class="fa-solid fa-headset"></i>
            </div>
            <span><?php echo htmlspecialchars($nome_sistema ?? 'Helpdesk', ENT_QUOTES, 'UTF-8'); ?></span>
        </div>

        <ul class="hd-sidebar__menu">
            <?php foreach ($catalogo['ordem_principal'] as $item): ?>

                <?php if (isset($grupos[$item])): ?>
                    <!-- $item é um grupo (ex.: "cadastros") — junta as filhas permitidas -->
                    <?php
                        $grupo = $grupos[$item];
                        $filhas = array_keys(array_filter($paginas, function ($p) use ($item) {
                            return ($p['grupo'] ?? null) === $item;
                        }));
                        $filhasPermitidas = array_values(array_intersect($filhas, $menusPermitidos));
                    ?>
                    <?php if (empty($filhasPermitidas)): continue; endif; // sem nenhuma filha liberada, esconde o grupo inteiro ?>
                    <?php $grupoAberto = in_array($atual, $filhasPermitidas, true); ?>

                    <li class="hd-sidebar__item hd-sidebar__item--group <?php echo grupoAtivo($filhasPermitidas, $atual); ?>">
                        <button type="button"
                                class="hd-sidebar__link hd-sidebar__group-toggle"
                                data-submenu="<?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?>"
                                aria-expanded="<?php echo $grupoAberto ? 'true' : 'false'; ?>">
                            <i class="<?php echo htmlspecialchars($grupo['icone'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                            <span><?php echo htmlspecialchars($grupo['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <i class="fa-solid fa-chevron-down hd-sidebar__chevron" aria-hidden="true"></i>
                        </button>

                        <ul class="hd-sidebar__submenu" data-submenu-content="<?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $grupoAberto ? '' : 'hidden'; ?>>
                            <?php foreach ($filhasPermitidas as $slug): ?>
                                <li class="hd-sidebar__subitem <?php echo menuAtivo($slug, $atual); ?>">
                                    <a href="<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>" class="hd-sidebar__sublink">
                                        <i class="<?php echo htmlspecialchars($paginas[$slug]['icone'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                                        <span><?php echo htmlspecialchars($paginas[$slug]['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>

                <?php elseif (in_array($item, $menusPermitidos, true) && isset($paginas[$item])): ?>
                    <!-- $item é uma página solta -->
                    <li class="hd-sidebar__item <?php echo menuAtivo($item, $atual); ?>">
                        <a href="<?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?>" class="hd-sidebar__link">
                            <i class="<?php echo htmlspecialchars($paginas[$item]['icone'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                            <span><?php echo htmlspecialchars($paginas[$item]['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                    </li>
                <?php endif; ?>

            <?php endforeach; ?>
        </ul>
    </div>

    <div class="hd-sidebar__footer">
        <!-- O logout.php está na raiz, então usamos ../logout.php -->
        <a href="../logout.php" class="hd-sidebar__footer-link">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Sair do Sistema</span>
        </a>
    </div>
</aside>
