<?php
/**
 * Gestão de Usuários — lista (DataTables), cria, edita e exclui usuários com acesso ao sistema.
 */
?>
<div class="hd-welcome">
    <h1 class="hd-welcome__title">Gestão de Usuários</h1>
    <p class="hd-welcome__subtitle">Gerencie todos os usuários com acesso ao sistema</p>
</div>

<div class="hd-card">
    <div class="hd-card__header hd-card__header--row">
        <h2 class="hd-card__title" style="font-size: 1.15rem;">
            <i class="fa-solid fa-users" style="color: var(--cor-primaria); margin-right: 0.5rem;"></i>
            Usuários
        </h2>
        <?php if (podeExecutarAcao($pdo, $_SESSION['id'] ?? null, $_SESSION['cargo_id'] ?? null, 'criar')): ?>
        <button type="button" class="hd-btn hd-btn--primary hd-btn--sm" onclick="novo()">
            <i class="fa-solid fa-plus" style="margin-right: 0.4rem;"></i>Novo Usuário
        </button>
        <?php endif; ?>
    </div>
    <div class="hd-card__body">
        <div class="table-responsive">
            <table id="tabelaUsuarios" class="table table-striped table-hover align-middle" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Telefone</th>
                        <th>Nível</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Usuário (Cadastrar / Editar) — componente Modal do Bootstrap 5 -->
<div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="formUsuario" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUsuarioLabel">Cadastrar Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="usuario_id" name="id">

                    <h6 class="text-uppercase text-muted small fw-bold mb-3">Dados Pessoais</h6>
                    <div class="row g-3">
                        <div class="col-12 d-flex align-items-center gap-3">
                            <img id="previewFotoUsuario" src="../uploads/perfil/sem_foto.png" alt="" class="rounded-circle" style="width: 3.5rem; height: 3.5rem; object-fit: cover; flex-shrink: 0;">
                            <div class="flex-grow-1">
                                <input type="file" id="usuario_foto" name="foto" accept=".png,.jpg,.jpeg,.webp" class="form-control">
                                <div class="form-text">PNG, JPG ou WEBP — até 2MB.</div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="usuario_nome">Nome Completo *</label>
                            <input type="text" id="usuario_nome" name="nome" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="usuario_email">E-mail *</label>
                            <input type="email" id="usuario_email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="usuario_cpf">CPF</label>
                            <input type="text" id="usuario_cpf" name="cpf" class="form-control" placeholder="000.000.000-00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="usuario_telefone">Telefone</label>
                            <div class="input-group">
                                <select id="usuario_ddi" name="ddi" class="form-select" style="max-width: 100px;">
                                    <option value="" selected>DDI</option>
                                    <option value="55">🇧🇷 +55</option>
                                    <option value="351">🇵🇹 +351</option>
                                </select>
                                <input type="text" id="usuario_telefone" name="telefone" class="form-control" placeholder="(00) 00000-0000">
                            </div>
                            <div class="form-text">DDI usado no envio de WhatsApp (boas-vindas, notificações).</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="usuario_senha">Senha <span id="usuarioSenhaHint" class="text-muted fw-normal small">(mínimo 6 caracteres)</span></label>
                            <input type="password" id="usuario_senha" name="senha" class="form-control" autocomplete="new-password" minlength="6">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="usuario_nivel">Nível de Acesso *</label>
                            <select id="usuario_nivel" name="cargo_id" class="form-select" required>
                                <option value="">Carregando...</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="usuario_ativo">Status *</label>
                            <select id="usuario_ativo" name="ativo" class="form-select" required>
                                <option value="1">Ativo</option>
                                <option value="0">Inativo</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="text-uppercase text-muted small fw-bold mb-3">Endereço</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label" for="usuario_cep">CEP</label>
                            <input type="text" id="usuario_cep" name="cep" class="form-control" placeholder="CEP">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="usuario_estado">Estado</label>
                            <select id="usuario_estado" name="estado" class="form-select">
                                <option value="">Selecione</option>
                                <option value="AC">Acre</option>
                                <option value="AL">Alagoas</option>
                                <option value="AP">Amapá</option>
                                <option value="AM">Amazonas</option>
                                <option value="BA">Bahia</option>
                                <option value="CE">Ceará</option>
                                <option value="DF">Distrito Federal</option>
                                <option value="ES">Espírito Santo</option>
                                <option value="GO">Goiás</option>
                                <option value="MA">Maranhão</option>
                                <option value="MT">Mato Grosso</option>
                                <option value="MS">Mato Grosso do Sul</option>
                                <option value="MG">Minas Gerais</option>
                                <option value="PA">Pará</option>
                                <option value="PB">Paraíba</option>
                                <option value="PR">Paraná</option>
                                <option value="PE">Pernambuco</option>
                                <option value="PI">Piauí</option>
                                <option value="RJ">Rio de Janeiro</option>
                                <option value="RN">Rio Grande do Norte</option>
                                <option value="RS">Rio Grande do Sul</option>
                                <option value="RO">Rondônia</option>
                                <option value="RR">Roraima</option>
                                <option value="SC">Santa Catarina</option>
                                <option value="SP">São Paulo</option>
                                <option value="SE">Sergipe</option>
                                <option value="TO">Tocantins</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="usuario_cidade">Cidade</label>
                            <input type="text" id="usuario_cidade" name="cidade" class="form-control" placeholder="Cidade">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="usuario_endereco">Endereço</label>
                            <input type="text" id="usuario_endereco" name="endereco" class="form-control" placeholder="Endereço">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="usuario_numero">Número</label>
                            <input type="text" id="usuario_numero" name="numero" class="form-control" placeholder="Número">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="usuario_bairro">Bairro</label>
                            <input type="text" id="usuario_bairro" name="bairro" class="form-control" placeholder="Bairro">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="usuario_complemento">Complemento</label>
                            <input type="text" id="usuario_complemento" name="complemento" class="form-control" placeholder="Complemento">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarUsuario">Salvar Usuário</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Visualizar Usuário — somente leitura, reaproveita scripts/usuarios/buscar.php -->
<div class="modal fade" id="modalVisualizarUsuario" tabindex="-1" aria-labelledby="modalVisualizarUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisualizarUsuarioLabel">Detalhes do Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <img id="verFotoUsuario" src="../uploads/perfil/sem_foto.png" alt="" class="rounded-circle" style="width: 4rem; height: 4rem; object-fit: cover;">
                    <div>
                        <h5 class="mb-1" id="verNome">-</h5>
                        <span class="badge" id="verStatus">-</span>
                    </div>
                </div>

                <h6 class="text-uppercase text-muted small fw-bold mb-3">Dados Pessoais</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6"><small class="text-muted d-block">E-mail</small><span id="verEmail">-</span></div>
                    <div class="col-md-6"><small class="text-muted d-block">Telefone</small><span id="verTelefone">-</span></div>
                    <div class="col-md-6"><small class="text-muted d-block">CPF</small><span id="verCpf">-</span></div>
                    <div class="col-md-6"><small class="text-muted d-block">Nível de Acesso</small><span id="verNivel">-</span></div>
                </div>

                <h6 class="text-uppercase text-muted small fw-bold mb-3">Endereço</h6>
                <div class="row g-3">
                    <div class="col-md-3"><small class="text-muted d-block">CEP</small><span id="verCep">-</span></div>
                    <div class="col-md-3"><small class="text-muted d-block">Estado</small><span id="verEstado">-</span></div>
                    <div class="col-md-6"><small class="text-muted d-block">Cidade</small><span id="verCidade">-</span></div>
                    <div class="col-md-8"><small class="text-muted d-block">Endereço</small><span id="verEndereco">-</span></div>
                    <div class="col-md-4"><small class="text-muted d-block">Número</small><span id="verNumero">-</span></div>
                    <div class="col-md-6"><small class="text-muted d-block">Bairro</small><span id="verBairro">-</span></div>
                    <div class="col-md-6"><small class="text-muted d-block">Complemento</small><span id="verComplemento">-</span></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Permissões (por usuário) — checklist gerado a partir de painel/includes/menus.php -->
<div class="modal fade" id="modalPermissoes" tabindex="-1" aria-labelledby="modalPermissoesLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="formPermissoes">
                <div class="modal-header hd-modal-header--gradient">
                    <h5 class="modal-title d-flex align-items-center gap-2" id="modalPermissoesLabel">
                        <i class="fa-solid fa-shield-halved"></i>
                        Permissões do Usuário
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="permissoes_usuario_id" name="usuario_id">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h6 class="fw-bold mb-1" id="permissoesNomeUsuario"></h6>
                            <p class="text-muted small mb-0">Marque os menus que este usuário poderá acessar.</p>
                        </div>
                        <div class="btn-group btn-group-sm flex-shrink-0">
                            <button type="button" class="btn btn-outline-primary" id="btnMarcarTodasPermissoes">Marcar todas</button>
                            <button type="button" class="btn btn-outline-secondary" id="btnDesmarcarTodasPermissoes">Desmarcar todas</button>
                        </div>
                    </div>

                    <!-- Permissões de AÇÃO — globais (valem em qualquer tela: Usuários, Cargos,
                         Clientes...), diferente do checklist abaixo que é por página. -->
                    <div class="hd-permissao-secao mb-3">
                        <div class="hd-permissao-secao__grupo-header">
                            <span class="hd-permissao-secao__grupo-titulo">
                                <i class="fa-solid fa-sliders"></i>
                                Ações no sistema
                            </span>
                        </div>
                        <div class="hd-permissao-item hd-permissao-item--acoes">
                            <div class="hd-permissao-acao">
                                <span class="hd-permissao-item__label"><i class="fa-solid fa-plus"></i>Criar registros</span>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch" name="permissao_criar" value="1" id="permissao_criar">
                                </div>
                            </div>
                            <div class="hd-permissao-acao">
                                <span class="hd-permissao-item__label"><i class="fa-solid fa-pen"></i>Editar registros</span>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch" name="permissao_editar" value="1" id="permissao_editar">
                                </div>
                            </div>
                            <div class="hd-permissao-acao">
                                <span class="hd-permissao-item__label"><i class="fa-solid fa-trash"></i>Excluir registros</span>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch" name="permissao_excluir" value="1" id="permissao_excluir">
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                        $catalogoMenus = require __DIR__ . '/../includes/menus.php';
                        foreach ($catalogoMenus['ordem_principal'] as $item):
                            if (isset($catalogoMenus['grupos'][$item])):
                                $grupo = $catalogoMenus['grupos'][$item];
                                $filhas = array_filter($catalogoMenus['paginas'], function ($p) use ($item) {
                                    return ($p['grupo'] ?? null) === $item;
                                });
                    ?>
                        <div class="hd-permissao-secao">
                            <div class="hd-permissao-secao__grupo-header">
                                <span class="hd-permissao-secao__grupo-titulo">
                                    <i class="<?php echo htmlspecialchars($grupo['icone'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                                    <?php echo htmlspecialchars($grupo['label'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <span class="hd-permissao-badge-submenu">Submenu</span>
                            </div>
                            <?php foreach ($filhas as $slug => $pagina): ?>
                                <div class="hd-permissao-item">
                                    <span class="hd-permissao-item__label">
                                        <i class="<?php echo htmlspecialchars($pagina['icone'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                                        <?php echo htmlspecialchars($pagina['label'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input permissao-checkbox" type="checkbox" role="switch" name="permissoes[]" value="<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>" id="uperm_<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php
                            elseif (isset($catalogoMenus['paginas'][$item])):
                                $pagina = $catalogoMenus['paginas'][$item];
                    ?>
                        <div class="hd-permissao-secao">
                            <div class="hd-permissao-item">
                                <span class="hd-permissao-item__label">
                                    <i class="<?php echo htmlspecialchars($pagina['icone'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                                    <?php echo htmlspecialchars($pagina['label'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input permissao-checkbox" type="checkbox" role="switch" name="permissoes[]" value="<?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?>" id="uperm_<?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                        </div>
                    <?php
                            endif;
                        endforeach;
                    ?>

                    <!-- "Outros": permissões que o usuário já tem gravadas mas que não correspondem a
                         nenhuma página do catálogo atual (ex.: página removida depois de já ter sido
                         liberada). Preenchido via JS em permissoes(id) — não escondemos isso sem avisar. -->
                    <div id="permissoesOutrosWrapper" class="hd-permissao-secao" style="display: none;">
                        <div class="hd-permissao-secao__grupo-header">
                            <span class="hd-permissao-secao__grupo-titulo">Outros</span>
                        </div>
                        <div id="permissoesOutrosLista"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarPermissoes">Salvar Permissões</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmação de exclusão — substitui o confirm() nativo do navegador -->
<div class="modal fade" id="modalConfirmarExclusao" tabindex="-1" aria-labelledby="modalConfirmarExclusaoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmarExclusaoLabel">Excluir usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Tem certeza que deseja excluir este usuário? Essa ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarExclusao">Excluir</button>
            </div>
        </div>
    </div>
</div>

<!-- jQuery + Bootstrap 5 JS + DataTables: nessa ordem, exigida pelas próprias libs -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.11/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.11/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script src="assets/js/usuarios.js<?php echo asset_v('painel/assets/js/usuarios.js'); ?>"></script>
