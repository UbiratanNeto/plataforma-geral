<?php
/**
 * painel/includes/modal-perfil.php
 * Modal "Editar Perfil do Usuário" — acionado pelo link no dropdown do topbar.
 * Depende de $_SESSION['nome']/['email']/['foto'], já preenchidos pelo verificar.php.
 */
$perfil_foto_arquivo = basename($_SESSION['foto'] ?? '');
$perfil_foto_existe  = $perfil_foto_arquivo !== '' && file_exists(__DIR__ . '/../../uploads/perfil/' . $perfil_foto_arquivo);

// Assinatura não fica em $_SESSION (só é lida aqui, ao montar a modal) — busca direto no
// banco pra sempre refletir a última salva, sem precisar manter sessão sincronizada.
$perfil_assinatura_url = null;
if (!empty($_SESSION['id'])) {
    $stmtAssinaturaPerfil = $pdo->prepare("SELECT assinatura FROM usuarios WHERE id = ?");
    $stmtAssinaturaPerfil->execute([$_SESSION['id']]);
    $perfil_assinatura_arquivo = $stmtAssinaturaPerfil->fetchColumn();
    if ($perfil_assinatura_arquivo && file_exists(__DIR__ . '/../../uploads/assinaturas/' . $perfil_assinatura_arquivo)) {
        $perfil_assinatura_url = '../uploads/assinaturas/' . $perfil_assinatura_arquivo;
    }
}
?>
<!-- Fundo Escurecido da Modal de Perfil (Invisível por padrão) -->
<div id="modalPerfil" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.6); z-index:9999; align-items:center; justify-content:center; backdrop-filter: blur(3px);">

    <!-- Modal Editar Perfil -->
    <div class="hd-card" style="width: 960px; max-width: 95%; max-height: 90vh; overflow-y: auto;">

        <!-- Cabeçalho com Botão de Fechar -->
        <div class="hd-card__header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="hd-card__title">Editar Perfil do Usuário</h3>
            <button onclick="document.getElementById('modalPerfil').style.display='none'" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color: var(--muted-color);">&times;</button>
        </div>

        <div class="hd-card__body">
            <form id="formPerfil" action="scripts/salvar_perfil.php" method="POST" enctype="multipart/form-data">
                <div class="hd-form-grid hd-form-grid--wide">

                    <h4 class="hd-form-section-title">Dados Pessoais</h4>
                    <div class="hd-field">
                        <label class="hd-field__label" for="perfil_nome">Nome Completo</label>
                        <input type="text" id="perfil_nome" name="nome" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($_SESSION['nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nome Completo">
                    </div>
                    <div class="hd-field">
                        <label class="hd-field__label" for="perfil_telefone">Telefone</label>
                        <input type="text" id="perfil_telefone" name="telefone" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($_SESSION['telefone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Telefone">
                    </div>
                    <div class="hd-field">
                        <label class="hd-field__label" for="perfil_email">E-mail</label>
                        <input type="email" id="perfil_email" name="email" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($_SESSION['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="E-mail">
                    </div>
                    <div class="hd-field">
                        <label class="hd-field__label" for="perfil_cpf">CPF</label>
                        <input type="text" id="perfil_cpf" name="cpf" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($_SESSION['cpf'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="CPF">
                    </div>

                    <div class="hd-form-divider"></div>
                    <h4 class="hd-form-section-title">Endereço</h4>

                    <?php $perfil_estado_atual = $_SESSION['estado'] ?? ''; ?>
                    <div class="hd-field">
                        <label class="hd-field__label" for="perfil_cep">CEP</label>
                        <input type="text" id="perfil_cep" name="cep" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($_SESSION['cep'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="CEP">
                    </div>
                    <div class="hd-field">
                        <label class="hd-field__label" for="perfil_estado">Estado</label>
                        <select id="perfil_estado" name="estado" class="hd-field__input hd-field__input--plain">
                            <option value="" <?php echo $perfil_estado_atual === '' ? 'selected' : ''; ?>>Selecione o Estado</option>
                            <option value="AC" <?php echo $perfil_estado_atual === 'AC' ? 'selected' : ''; ?>>Acre</option>
                            <option value="AL" <?php echo $perfil_estado_atual === 'AL' ? 'selected' : ''; ?>>Alagoas</option>
                            <option value="AP" <?php echo $perfil_estado_atual === 'AP' ? 'selected' : ''; ?>>Amapá</option>
                            <option value="AM" <?php echo $perfil_estado_atual === 'AM' ? 'selected' : ''; ?>>Amazonas</option>
                            <option value="BA" <?php echo $perfil_estado_atual === 'BA' ? 'selected' : ''; ?>>Bahia</option>
                            <option value="CE" <?php echo $perfil_estado_atual === 'CE' ? 'selected' : ''; ?>>Ceará</option>
                            <option value="DF" <?php echo $perfil_estado_atual === 'DF' ? 'selected' : ''; ?>>Distrito Federal</option>
                            <option value="ES" <?php echo $perfil_estado_atual === 'ES' ? 'selected' : ''; ?>>Espírito Santo</option>
                            <option value="GO" <?php echo $perfil_estado_atual === 'GO' ? 'selected' : ''; ?>>Goiás</option>
                            <option value="MA" <?php echo $perfil_estado_atual === 'MA' ? 'selected' : ''; ?>>Maranhão</option>
                            <option value="MT" <?php echo $perfil_estado_atual === 'MT' ? 'selected' : ''; ?>>Mato Grosso</option>
                            <option value="MS" <?php echo $perfil_estado_atual === 'MS' ? 'selected' : ''; ?>>Mato Grosso do Sul</option>
                            <option value="MG" <?php echo $perfil_estado_atual === 'MG' ? 'selected' : ''; ?>>Minas Gerais</option>
                            <option value="PA" <?php echo $perfil_estado_atual === 'PA' ? 'selected' : ''; ?>>Pará</option>
                            <option value="PB" <?php echo $perfil_estado_atual === 'PB' ? 'selected' : ''; ?>>Paraíba</option>
                            <option value="PR" <?php echo $perfil_estado_atual === 'PR' ? 'selected' : ''; ?>>Paraná</option>
                            <option value="PE" <?php echo $perfil_estado_atual === 'PE' ? 'selected' : ''; ?>>Pernambuco</option>
                            <option value="PI" <?php echo $perfil_estado_atual === 'PI' ? 'selected' : ''; ?>>Piauí</option>
                            <option value="RJ" <?php echo $perfil_estado_atual === 'RJ' ? 'selected' : ''; ?>>Rio de Janeiro</option>
                            <option value="RN" <?php echo $perfil_estado_atual === 'RN' ? 'selected' : ''; ?>>Rio Grande do Norte</option>
                            <option value="RS" <?php echo $perfil_estado_atual === 'RS' ? 'selected' : ''; ?>>Rio Grande do Sul</option>
                            <option value="RO" <?php echo $perfil_estado_atual === 'RO' ? 'selected' : ''; ?>>Rondônia</option>
                            <option value="RR" <?php echo $perfil_estado_atual === 'RR' ? 'selected' : ''; ?>>Roraima</option>
                            <option value="SC" <?php echo $perfil_estado_atual === 'SC' ? 'selected' : ''; ?>>Santa Catarina</option>
                            <option value="SP" <?php echo $perfil_estado_atual === 'SP' ? 'selected' : ''; ?>>São Paulo</option>
                            <option value="SE" <?php echo $perfil_estado_atual === 'SE' ? 'selected' : ''; ?>>Sergipe</option>
                            <option value="TO" <?php echo $perfil_estado_atual === 'TO' ? 'selected' : ''; ?>>Tocantins</option>
                        </select>
                    </div>
                    <div class="hd-field">
                        <label class="hd-field__label" for="perfil_cidade">Cidade</label>
                        <input type="text" id="perfil_cidade" name="cidade" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($_SESSION['cidade'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Cidade">
                    </div>
                    <div class="hd-field">
                        <label class="hd-field__label" for="perfil_bairro">Bairro</label>
                        <input type="text" id="perfil_bairro" name="bairro" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($_SESSION['bairro'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Bairro">
                    </div>
                    <div class="hd-field">
                        <label class="hd-field__label" for="perfil_endereco">Endereço</label>
                        <input type="text" id="perfil_endereco" name="endereco" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($_SESSION['endereco'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Endereço">
                    </div>
                    <div class="hd-field">
                        <label class="hd-field__label" for="perfil_numero">Número</label>
                        <input type="text" id="perfil_numero" name="numero" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($_SESSION['numero'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Número">
                    </div>
                    <div class="hd-field">
                        <label class="hd-field__label" for="perfil_complemento">Complemento</label>
                        <input type="text" id="perfil_complemento" name="complemento" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($_SESSION['complemento'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Complemento">
                    </div>

                    <div class="hd-form-divider"></div>

                    <div class="hd-field" style="grid-column: 1 / -1; display: flex; flex-direction: row; gap: 2rem; flex-wrap: wrap; align-items: flex-start;">
                        <div style="flex: 1; min-width: 260px;">
                            <h4 class="hd-form-section-title" style="margin-bottom: 0.75rem;">Foto do Perfil</h4>
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <?php if ($perfil_foto_existe): ?>
                                    <img src="../uploads/perfil/<?php echo htmlspecialchars($perfil_foto_arquivo, ENT_QUOTES, 'UTF-8'); ?>" alt="" style="width: 3.5rem; height: 3.5rem; border-radius: 50%; object-fit: cover; flex-shrink: 0;">
                                <?php else: ?>
                                    <div class="user-avatar-placeholder" style="width: 3.5rem; height: 3.5rem; font-size: 1.3rem;">
                                        <?php echo htmlspecialchars(strtoupper(substr(trim($_SESSION['nome'] ?? 'U'), 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php endif; ?>
                                <input type="file" id="perfil_foto" name="foto" accept=".png,.jpg,.jpeg,.webp" class="hd-field__input hd-field__input--plain" style="flex: 1;">
                            </div>
                            <p class="hd-field__hint">PNG, JPG ou WEBP — até 2MB. Deixe em branco para manter a foto atual.</p>
                        </div>
                        <div style="flex: 1; min-width: 260px;">
                            <h4 class="hd-form-section-title" style="margin-bottom: 0.75rem;">Trocar Senha</h4>
                            <label class="hd-field__label" for="perfil_nova_senha">Nova Senha</label>
                            <input type="password" id="perfil_nova_senha" name="nova_senha" class="hd-field__input hd-field__input--plain" placeholder="Nova Senha" autocomplete="new-password" minlength="3">
                            <p class="hd-field__hint">Deixe em branco para não alterar.</p>
                        </div>
                    </div>

                    <div class="hd-form-divider"></div>
                    <h4 class="hd-form-section-title">Assinatura</h4>

                    <div class="hd-field" style="grid-column: 1 / -1; display: flex; flex-direction: row; align-items: center; justify-content: flex-start; gap: 1rem;">
                        <p class="hd-field__hint" style="margin: 0;" id="perfil_assinatura_status">
                            <?php echo $perfil_assinatura_url ? 'Assinatura cadastrada' : 'Nenhuma assinatura cadastrada'; ?>
                        </p>
                        <button type="button" class="hd-btn hd-btn--primary hd-btn--sm" id="btnAbrirAssinaturaPerfil" onclick="abrirModalAssinaturaPerfil()">
                            <i class="fa-solid fa-pen" style="margin-right: 0.4rem;"></i><span id="perfil_assinatura_botao_texto"><?php echo $perfil_assinatura_url ? 'Refazer assinatura' : 'Fazer assinatura'; ?></span>
                        </button>
                    </div>
                </div>

                <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="hd-btn hd-btn--ghost" style="flex: 1;" onclick="document.getElementById('modalPerfil').style.display='none'">Cancelar</button>
                    <button type="submit" class="hd-btn hd-btn--primary" style="flex: 1;">Atualizar Perfil</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal "Fazer Assinatura" — separada da modal de Perfil, abre por cima dela (z-index maior).
     Salva sozinha, com botão próprio, sem depender do "Atualizar Perfil" principal. -->
<div id="modalAssinaturaPerfil" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.6); z-index:10000; align-items:center; justify-content:center; backdrop-filter: blur(3px);">
    <div class="hd-card" style="width: 520px; max-width: 95%; max-height: 90vh; overflow-y: auto;">
        <div class="hd-card__header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="hd-card__title">Fazer Assinatura</h3>
            <button onclick="fecharModalAssinaturaPerfil()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color: var(--muted-color);">&times;</button>
        </div>
        <div class="hd-card__body">
            <p class="hd-field__hint" style="margin-top: 0;">Assine dentro do quadro, usando o mouse, o dedo ou uma caneta touch. O quadro é exatamente o espaço da assinatura no relatório, que sai com a linha preta logo abaixo dele.</p>
            <!-- Proporção fixa 11:4 = 220x80px, a área da imagem da assinatura no PDF (ver
                 blocoAssinaturaRelatorio()). O que é desenhado aqui ocupa o mesmo espaço no relatório. -->
            <div style="width: 100%; aspect-ratio: 11 / 4; border: 1px solid var(--border-line); border-radius: 8px; background: #fff; overflow: hidden;">
                <canvas id="perfil_canvas_assinatura" style="display: block; width: 100%; height: 100%; touch-action: none;"></canvas>
            </div>
            <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                <button type="button" class="hd-btn hd-btn--ghost" style="flex: 1;" onclick="limparAssinaturaPerfil()">Limpar</button>
                <button type="button" class="hd-btn hd-btn--primary" style="flex: 1;" id="btnSalvarAssinaturaPerfil" onclick="salvarAssinaturaPerfil()">Salvar Assinatura</button>
            </div>
        </div>
    </div>
</div>

<!-- Biblioteca IMask, hospedada localmente (deixou de depender do CDN externo) -->
<script src="assets/js/imask.min.js"></script>

<!-- Seus scripts de Máscara e Funções/ViaCEP -->
<script src="assets/js/masks.js<?php echo asset_v('painel/assets/js/masks.js'); ?>"></script>
<script src="assets/js/functions.js<?php echo asset_v('painel/assets/js/functions.js'); ?>"></script>

<!-- Assinatura via canvas (biblioteca signature_pad) — modal própria, salva sozinha -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/4.1.7/signature_pad.umd.min.js"></script>
<script>
    var perfilSignaturePad = null;
    var PERFIL_ASSINATURA_URL = <?php echo json_encode($perfil_assinatura_url, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    function abrirModalAssinaturaPerfil() {
        document.getElementById('modalAssinaturaPerfil').style.display = 'flex';

        // Só inicializa/redimensiona quando a modal já está visível — antes disso o
        // canvas tem largura 0 e o traço fica desalinhado do clique.
        // Como o quadro é pequeno, o canvas é desenhado com o dobro (no mínimo) de pixels do tamanho
        // na tela — senão a imagem salva ficaria com pouca definição ao ser impressa no relatório.
        var canvas = document.getElementById('perfil_canvas_assinatura');
        var ratio = Math.max(window.devicePixelRatio || 1, 2);
        var largura = canvas.offsetWidth;
        var altura = canvas.offsetHeight;
        canvas.width = largura * ratio;
        canvas.height = altura * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        perfilSignaturePad = new SignaturePad(canvas, { backgroundColor: '#ffffff' });

        if (PERFIL_ASSINATURA_URL) {
            var img = new Image();
            img.onload = function () {
                canvas.getContext('2d').drawImage(img, 0, 0, largura, altura);
            };
            img.src = PERFIL_ASSINATURA_URL;
        }
    }

    function fecharModalAssinaturaPerfil() {
        document.getElementById('modalAssinaturaPerfil').style.display = 'none';
    }

    function limparAssinaturaPerfil() {
        if (perfilSignaturePad) perfilSignaturePad.clear();
    }

    function salvarAssinaturaPerfil() {
        if (!perfilSignaturePad || perfilSignaturePad.isEmpty()) {
            Mensagens.aviso('Assinatura vazia', 'Desenhe sua assinatura no quadro antes de salvar.');
            return;
        }

        var botao = document.getElementById('btnSalvarAssinaturaPerfil');
        var textoOriginal = botao.textContent;
        botao.disabled = true;
        botao.textContent = 'Salvando...';

        fetch('scripts/salvar_assinatura_perfil.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ assinatura: perfilSignaturePad.toDataURL('image/png') })
        })
            .then(function (resposta) { return resposta.json(); })
            .then(function (dados) {
                if (dados.ok) {
                    PERFIL_ASSINATURA_URL = dados.assinatura_url;
                    document.getElementById('perfil_assinatura_status').textContent = 'Assinatura cadastrada';
                    document.getElementById('perfil_assinatura_botao_texto').textContent = 'Refazer assinatura';
                    fecharModalAssinaturaPerfil();
                    Mensagens.sucesso('Sucesso!', dados.msg);
                } else {
                    Mensagens.erro('Atenção', dados.msg);
                }
            })
            .catch(function () {
                Mensagens.erro('Erro de conexão', 'Não foi possível salvar a assinatura agora.');
            })
            .finally(function () {
                botao.disabled = false;
                botao.textContent = textoOriginal;
            });
    }
</script>

<!-- O envio do formulário (fetch + upload de arquivo + alerta de sucesso/erro) é tratado
     de forma genérica por painel/assets/js/ajax-form.js, que já reconhece este form pela action. -->
