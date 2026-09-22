<?php
/**
 * painel/includes/modal-configuracoes.php
 * Modal "Editar Configurações do Sistema" — acionado pelo link no dropdown do topbar.
 * Depende de variáveis já carregadas em conexao.php/verificar.php:
 * $nome_sistema, $telefone_sistema, $email_sistema, $endereco, $primary, $secondary,
 * $smtp_host, $smtp_porta, $smtp_seguranca, $logo, $icone
 */
$config_logo_arquivo   = basename($logo ?? '');
$config_logo_existe    = $config_logo_arquivo !== '' && file_exists(__DIR__ . '/../../uploads/' . $config_logo_arquivo);
$config_icone_arquivo  = basename($icone ?? '');
$config_icone_existe   = $config_icone_arquivo !== '' && file_exists(__DIR__ . '/../../uploads/' . $config_icone_arquivo);
?>
<!-- Fundo Escurecido da Modal (Invisível por padrão) -->
<div id="modalConfig" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15, 23, 42, 0.6); z-index:9999; align-items:center; justify-content:center; backdrop-filter: blur(3px);">

    <!-- Modal Editar Configurações -->
    <div class="hd-card" style="width: 650px; max-width: 95%; max-height: 90vh; overflow-y: auto;">

        <!-- Cabeçalho com Botão de Fechar -->
        <div class="hd-card__header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="hd-card__title">Editar Configurações do Sistema</h3>
            <button onclick="document.getElementById('modalConfig').style.display='none'" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color: var(--muted-color);">&times;</button>
        </div>

        <div class="hd-card__body">
            <form id="formConfiguracoes" action="scripts/salvar_config.php" method="POST" enctype="multipart/form-data">
                <div class="hd-form-grid">

                    <h4 class="hd-form-section-title">Dados do Sistema</h4>
                    <div class="hd-field" style="grid-column: 1 / -1;">
                        <label class="hd-field__label" for="nome_sistema">Nome do Sistema</label>
                        <input type="text" id="nome_sistema" name="nome_sistema" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($nome_sistema ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nome do Sistema">
                    </div>
                    <div class="hd-field">
                        <label class="hd-field__label" for="telefone_sistema">Telefone</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <?php $ddi_sistema_atual = $ddi_sistema ?? '55'; ?>
                            <select id="ddi_sistema" name="ddi_sistema" class="hd-field__input hd-field__input--plain" style="max-width: 90px; flex-shrink: 0;">
                                <option value="55" <?php echo $ddi_sistema_atual === '55' ? 'selected' : ''; ?>>🇧🇷 +55</option>
                                <option value="351" <?php echo $ddi_sistema_atual === '351' ? 'selected' : ''; ?>>🇵🇹 +351</option>
                            </select>
                            <input type="text" id="telefone_sistema" name="telefone_sistema" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($telefone_sistema ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Telefone" style="flex: 1;">
                        </div>
                    </div>
                    <div class="hd-field">
                        <label class="hd-field__label" for="email_sistema">E-mail do Sistema</label>
                        <input type="email" id="email_sistema" name="email_sistema" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($email_sistema ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="E-mail do Sistema">
                    </div>
                    <div class="hd-field" style="grid-column: 1 / -1;">
                        <label class="hd-field__label" for="endereco">Endereço</label>
                        <input type="text" id="endereco" name="endereco" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($endereco ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Endereço">
                    </div>
                    <div class="hd-field" style="grid-column: 1 / -1;">
                        <label class="hd-field__label" for="url_sistema">URL de Acesso do Sistema</label>
                        <input type="text" id="url_sistema" name="url_sistema" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($url_sistema ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="http://localhost/helpdesk">
                        <p class="hd-field__hint" style="margin-top: 0.25rem;">Usada em mensagens automáticas (ex.: boas-vindas por WhatsApp). Sem barra no final.</p>
                    </div>

                    <div class="hd-form-divider"></div>
                    <h4 class="hd-form-section-title">Identidade Visual</h4>

                    <div class="hd-field">
                        <label class="hd-field__label" for="logo">Logo</label>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <?php if ($config_logo_existe): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($config_logo_arquivo, ENT_QUOTES, 'UTF-8'); ?>" alt="" style="width: 2.75rem; height: 2.75rem; border-radius: 8px; object-fit: cover; flex-shrink: 0; border: 1px solid var(--border-line);">
                            <?php endif; ?>
                            <input type="file" id="logo" name="logo" accept=".png,.jpg,.jpeg,.webp" class="hd-field__input hd-field__input--plain" style="flex: 1;">
                        </div>
                    </div>
                    <div class="hd-field">
                        <label class="hd-field__label" for="icone">Ícone</label>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <?php if ($config_icone_existe): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($config_icone_arquivo, ENT_QUOTES, 'UTF-8'); ?>" alt="" style="width: 2.75rem; height: 2.75rem; border-radius: 8px; object-fit: cover; flex-shrink: 0; border: 1px solid var(--border-line);">
                            <?php endif; ?>
                            <input type="file" id="icone" name="icone" accept=".png,.jpg,.jpeg,.webp" class="hd-field__input hd-field__input--plain" style="flex: 1;">
                        </div>
                    </div>
                    <p class="hd-field__hint" style="grid-column: 1 / -1; margin-top: -0.5rem;">PNG, JPG ou WEBP — até 2MB. Deixe em branco para manter a imagem atual.</p>

                    <div class="hd-form-divider"></div>
                    <h4 class="hd-form-section-title">Cores do Painel</h4>

                    <div class="hd-field">
                        <label class="hd-field__label" for="cor_primaria">Cor Primária</label>
                        <div class="hd-color-swatch-group">
                            <input type="color" class="hd-color-swatch" id="cor_primaria_seletor" value="<?php echo htmlspecialchars($primary, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true" tabindex="-1">
                            <input type="text" id="cor_primaria" name="cor_primaria" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($primary, ENT_QUOTES, 'UTF-8'); ?>" placeholder="#4f46e5">
                        </div>
                    </div>
                    <div class="hd-field">
                        <label class="hd-field__label" for="cor_secundaria">Cor Secundária</label>
                        <div class="hd-color-swatch-group">
                            <input type="color" class="hd-color-swatch" id="cor_secundaria_seletor" value="<?php echo htmlspecialchars($secondary, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true" tabindex="-1">
                            <input type="text" id="cor_secundaria" name="cor_secundaria" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($secondary, ENT_QUOTES, 'UTF-8'); ?>" placeholder="#818cf8">
                        </div>
                    </div>
                    <p class="hd-field__hint" style="grid-column: 1 / -1; margin-top: -0.5rem;">Dica: você pode colar o HEX no campo de texto.</p>

                    <div class="hd-form-divider"></div>
                    <h4 class="hd-form-section-title">Configuração SMTP</h4>

                    <div class="hd-field">
                        <label class="hd-field__label" for="smtp_host">Servidor SMTP (Host)</label>
                        <input type="text" id="smtp_host" name="smtp_host" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($smtp_host ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Servidor SMTP (Host)">
                    </div>
                    <div class="hd-field">
                        <label class="hd-field__label" for="smtp_porta">Porta SMTP</label>
                        <input type="number" id="smtp_porta" name="smtp_porta" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars((string) ($smtp_porta ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Porta SMTP">
                    </div>
                    <div class="hd-field">
                        <label class="hd-field__label" for="smtp_senha">Senha SMTP</label>
                        <!-- Propositalmente sem "value": nunca imprimimos a senha salva de volta no formulário -->
                        <input type="password" id="smtp_senha" name="smtp_senha" class="hd-field__input hd-field__input--plain" placeholder="Senha SMTP" autocomplete="new-password">
                    </div>
                    <div class="hd-field">
                        <label class="hd-field__label" for="smtp_seguranca">Segurança</label>
                        <?php $smtp_seguranca_atual = $smtp_seguranca ?? ''; ?>
                        <select id="smtp_seguranca" name="smtp_seguranca" class="hd-field__input hd-field__input--plain">
                            <option value="" <?php echo $smtp_seguranca_atual === '' ? 'selected' : ''; ?>>Nenhuma</option>
                            <option value="tls" <?php echo $smtp_seguranca_atual === 'tls' ? 'selected' : ''; ?>>TLS</option>
                            <option value="ssl" <?php echo $smtp_seguranca_atual === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                        </select>
                    </div>
                    <p class="hd-field__hint" style="grid-column: 1 / -1; margin-top: -0.5rem;">Deixe a senha em branco para não alterar (quando você implementar o update).</p>

                    <div class="hd-form-divider"></div>
                    <div style="grid-column: 1 / -1; display: flex; justify-content: space-between; align-items: center;">
                        <h4 class="hd-form-section-title" style="margin: 0;">Apis do Sistema</h4>
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="button" id="btnTestarWhatsapp" class="hd-btn hd-btn--ghost hd-btn--sm">
                                <i class="fa-brands fa-whatsapp" style="margin-right: 0.35rem;"></i>Testar WhatsApp
                            </button>
                            <button type="button" id="btnTestarIA" class="hd-btn hd-btn--ghost hd-btn--sm">
                                <i class="fa-solid fa-wand-magic-sparkles" style="margin-right: 0.35rem;"></i>Testar IA
                            </button>
                        </div>
                    </div>

                    <div class="hd-field" style="grid-column: 1 / -1;">
                        <label class="hd-field__label" for="api_whatsapp">Api Whatsapp</label>
                        <?php $api_whatsapp_atual = $api_whatsapp ?? ''; ?>
                        <select id="api_whatsapp" name="api_whatsapp" class="hd-field__input hd-field__input--plain">
                            <option value="" <?php echo $api_whatsapp_atual === '' ? 'selected' : ''; ?>>Nenhuma</option>
                            <option value="menuia" <?php echo $api_whatsapp_atual === 'menuia' ? 'selected' : ''; ?>>Menuia (WhatsApp V2)</option>
                            <option value="meta" <?php echo $api_whatsapp_atual === 'meta' ? 'selected' : ''; ?>>WhatsApp Cloud (Meta)</option>
                            <option value="evolution" <?php echo $api_whatsapp_atual === 'evolution' ? 'selected' : ''; ?>>Evolution API</option>
                        </select>
                    </div>

                    <!-- Campos por provedor — só um grupo fica visível por vez, controlado pelo select acima.
                         display:contents faz os filhos participarem do grid do pai como se o wrapper não existisse. -->
                    <div id="camposApiMenuia" style="display: contents;">
                        <div class="hd-field">
                            <label class="hd-field__label" for="token_whatsapp">Token</label>
                            <input type="text" id="token_whatsapp" name="token_whatsapp" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($token_whatsapp ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Token de API">
                        </div>
                        <div class="hd-field">
                            <label class="hd-field__label" for="device_whatsapp">ID da Conexão (Device)</label>
                            <input type="text" id="device_whatsapp" name="device_whatsapp" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($device_whatsapp ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="deviceId">
                        </div>
                        <p class="hd-field__hint" style="grid-column: 1 / -1; margin-top: -0.5rem;">Token e ID da conexão ficam disponíveis no painel da própria API (ex.: Menuia &rarr; Guia de API &rarr; Suas conexões).</p>
                    </div>

                    <div id="camposApiMeta" style="display: contents;">
                        <div class="hd-field">
                            <label class="hd-field__label" for="whatsapp_cloud_phone_id">Phone Number ID</label>
                            <input type="text" id="whatsapp_cloud_phone_id" name="whatsapp_cloud_phone_id" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($whatsapp_cloud_phone_id ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="phoneNumberId">
                        </div>
                        <div class="hd-field">
                            <label class="hd-field__label" for="whatsapp_cloud_token">Access Token</label>
                            <input type="text" id="whatsapp_cloud_token" name="whatsapp_cloud_token" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($whatsapp_cloud_token ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="accessToken">
                        </div>
                        <p class="hd-field__hint" style="grid-column: 1 / -1; margin-top: -0.5rem;">Disponível em Menuia &rarr; Canais &rarr; WhatsApp Cloud.</p>
                    </div>

                    <div id="camposApiEvolution" style="display: contents;">
                        <div class="hd-field" style="grid-column: 1 / -1;">
                            <label class="hd-field__label" for="evolution_url">URL do Servidor</label>
                            <input type="text" id="evolution_url" name="evolution_url" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($evolution_url ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://sua-evolution.com">
                        </div>
                        <div class="hd-field">
                            <label class="hd-field__label" for="evolution_instance">Instância</label>
                            <input type="text" id="evolution_instance" name="evolution_instance" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($evolution_instance ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="nome-da-instancia">
                        </div>
                        <div class="hd-field">
                            <label class="hd-field__label" for="evolution_apikey">API Key</label>
                            <input type="text" id="evolution_apikey" name="evolution_apikey" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($evolution_apikey ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="apikey">
                        </div>
                        <p class="hd-field__hint" style="grid-column: 1 / -1; margin-top: -0.5rem;">Servidor próprio (self-hosted) — URL, instância e API Key ficam disponíveis no painel/documentação da sua instalação Evolution.</p>
                    </div>

                    <div class="hd-field">
                        <label class="hd-field__label" for="api_ia">Api IA</label>
                        <?php $api_ia_atual = $api_ia ?? ''; ?>
                        <select id="api_ia" name="api_ia" class="hd-field__input hd-field__input--plain">
                            <option value="" <?php echo $api_ia_atual === '' ? 'selected' : ''; ?>>Nenhuma</option>
                            <option value="chatgpt" <?php echo $api_ia_atual === 'chatgpt' ? 'selected' : ''; ?>>ChatGPT</option>
                            <option value="gemini" <?php echo $api_ia_atual === 'gemini' ? 'selected' : ''; ?>>Gemini</option>
                            <option value="claude" <?php echo $api_ia_atual === 'claude' ? 'selected' : ''; ?>>Claude</option>
                        </select>
                    </div>
                    <div class="hd-field">
                        <label class="hd-field__label" for="token_ia">Token</label>
                        <input type="text" id="token_ia" name="token_ia" class="hd-field__input hd-field__input--plain" value="<?php echo htmlspecialchars($token_ia ?? '', ENT_QUOTES, 'UTF-8'); ?>" placeholder="Token ChatGPT, Gemini ou Claude">
                    </div>
                    <p class="hd-field__hint" style="grid-column: 1 / -1; margin-top: -0.5rem;">Token/API Key gerado no painel do provedor escolhido.</p>
                </div>

                <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="hd-btn hd-btn--ghost" style="flex: 1;" onclick="document.getElementById('modalConfig').style.display='none'">Cancelar</button>
                    <button type="submit" class="hd-btn hd-btn--primary" style="flex: 1;">Salvar Configurações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Sincroniza o seletor de cor visual com o campo de texto hex, nos dois sentidos -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const pares = [
        ['cor_primaria_seletor', 'cor_primaria'],
        ['cor_secundaria_seletor', 'cor_secundaria'],
    ];
    pares.forEach(function ([idSeletor, idTexto]) {
        const seletor = document.getElementById(idSeletor);
        const texto = document.getElementById(idTexto);
        if (!seletor || !texto) return;

        seletor.addEventListener('input', function () {
            texto.value = seletor.value;
        });
        texto.addEventListener('input', function () {
            if (/^#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})$/.test(texto.value)) {
                seletor.value = texto.value;
            }
        });
    });

    // Alterna os campos visíveis de API de WhatsApp conforme o provedor escolhido —
    // só o grupo do provedor selecionado fica na tela, o resto some (mas mantém o valor salvo).
    const selectApi = document.getElementById('api_whatsapp');
    const gruposApi = {
        menuia: document.getElementById('camposApiMenuia'),
        meta: document.getElementById('camposApiMeta'),
        evolution: document.getElementById('camposApiEvolution'),
    };

    function atualizarCamposApi() {
        Object.keys(gruposApi).forEach(function (chave) {
            gruposApi[chave].style.display = (chave === selectApi.value) ? 'contents' : 'none';
        });
    }

    if (selectApi) {
        selectApi.addEventListener('change', atualizarCamposApi);
        atualizarCamposApi();
    }

    // Botões "Testar WhatsApp" / "Testar IA" — chamam de verdade o provedor já salvo,
    // sem precisar sair do modal ou abrir outra página pra conferir.
    function testarApi(url, botao) {
        const textoOriginal = botao.innerHTML;
        botao.disabled = true;
        botao.innerHTML = 'Testando...';

        fetch(url, { method: 'POST' })
            .then(function (resposta) { return resposta.json(); })
            .then(function (dados) {
                const detalhes = dados.raw || (dados.resposta ? JSON.stringify(dados.resposta, null, 2) : '');
                let html = '<div style="text-align:left;">';
                html += '<p>' + dados.msg + '</p>';
                if (dados.api) {
                    html += '<p><strong>API:</strong> ' + dados.api + (dados.http ? ' | <strong>HTTP:</strong> ' + dados.http : '') + '</p>';
                }
                if (detalhes) {
                    html += '<p><strong>Detalhes técnicos:</strong></p>'
                        + '<pre style="white-space:pre-wrap; max-height:220px; overflow:auto; background:#f8fafc; padding:0.6rem; border-radius:6px; font-size:0.75rem;">'
                        + detalhes.replace(/</g, '&lt;')
                        + '</pre>';
                }
                html += '</div>';

                Swal.fire({
                    icon: dados.ok ? 'success' : 'error',
                    title: dados.ok ? 'Teste realizado com sucesso' : 'Falha no teste',
                    html: html,
                    confirmButtonColor: '#667eea',
                    confirmButtonText: 'Fechar',
                });
            })
            .catch(function () {
                Mensagens.erro('Erro de conexão', 'Não foi possível rodar o teste agora.');
            })
            .finally(function () {
                botao.disabled = false;
                botao.innerHTML = textoOriginal;
            });
    }

    const btnTestarWhatsapp = document.getElementById('btnTestarWhatsapp');
    const btnTestarIA = document.getElementById('btnTestarIA');
    if (btnTestarWhatsapp) {
        btnTestarWhatsapp.addEventListener('click', function () { testarApi('scripts/testar_whatsapp.php', btnTestarWhatsapp); });
    }
    if (btnTestarIA) {
        btnTestarIA.addEventListener('click', function () { testarApi('scripts/testar_ia.php', btnTestarIA); });
    }
});
</script>

<!-- O envio do formulário (fetch + alerta de sucesso/erro) é tratado de forma genérica
     por painel/assets/js/ajax-form.js, que já reconhece este form pela action. -->
