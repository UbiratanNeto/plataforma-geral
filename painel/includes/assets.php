<?php
/**
 * painel/includes/assets.php
 * Cache-busting pros arquivos estáticos locais (css/js do próprio projeto, não CDN).
 * Sem isso, como main.css/usuarios.js/etc. não mandam Cache-Control, o navegador às
 * vezes serve a versão antiga do cache mesmo depois de editar o arquivo — já nos
 * confundiu mais de uma vez achando que uma mudança "não tinha feito efeito".
 */

/**
 * Devolve "?v=<timestamp da última modificação>" pra colar no final da URL de um
 * asset local. Se o arquivo não for encontrado, cai num timestamp atual (não quebra
 * a página, só perde o cache-busting nesse caso).
 *
 * @param string $caminhoRelativoAaRaiz caminho a partir da raiz do projeto (ex.: 'css/main.css')
 */
function asset_v(string $caminhoRelativoRaiz): string {
    $caminhoAbsoluto = __DIR__ . '/../../' . ltrim($caminhoRelativoRaiz, '/');
    $mtime = @filemtime($caminhoAbsoluto);
    return '?v=' . ($mtime ?: time());
}
