<?php
/**
 * painel/includes/footer.php
 * Rodapé global do Painel de Controle
 */

// Ano dinâmico para os direitos autorais
$ano_atual = date('Y');
?>
<footer class="hd-footer" style="margin-top: auto; padding: 1.5rem 0; border-top: 1px solid #e2e8f0;">
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem; color: #64748b; font-size: 0.875rem;">
        
        <!-- Lado Esquerdo: Direitos Autorais -->
        <div>
            <span>&copy; <?php echo $ano_atual; ?> <strong><?php echo htmlspecialchars($nome_sistema ?? 'Helpdesk', ENT_QUOTES, 'UTF-8'); ?></strong>. Todos os direitos reservados.</span>
        </div>
        
        <!-- Lado Direito: Versão e Informações do Sistema -->
        <div style="display: flex; align-items: center; gap: 1rem;">
            <span>Versão 1.0.0</span>
            <span style="color: #cbd5e1;">|</span>
            <a href="https://github.com" target="_blank" rel="noopener noreferrer" style="color: #64748b; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#var(--cor-primaria)'" onmouseout="this.style.color='#64748b'">
                <i class="fa-solid fa-circle-info"></i> Suporte
            </a>
        </div>
        
    </div>
</footer>