<?php
/**
 * Gestão de Clientes — lista (DataTables), cria, edita e exclui clientes.
 * Mesmo padrão Bootstrap 5 + DataTables + funções globais genéricas de Usuários/Cargos.
 */
?>
<div class="hd-welcome">
    <h1 class="hd-welcome__title">Gestão de Clientes</h1>
    <p class="hd-welcome__subtitle">Gerencie os clientes que abrem chamados no sistema</p>
</div>

<div class="hd-card">
    <div class="hd-card__header hd-card__header--row">
        <div style="display: flex; gap: 0.5rem;">
            <?php if (podeExecutarAcao($pdo, $_SESSION['id'] ?? null, $_SESSION['cargo_id'] ?? null, 'criar')): ?>
            <button type="button" class="hd-btn hd-btn--primary hd-btn--sm" onclick="novo()">
                <i class="fa-solid fa-plus" style="margin-right: 0.4rem;"></i>Novo Cliente
            </button>
            <?php endif; ?>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button type="button" id="btnImportarClientes" class="hd-btn hd-btn--ghost hd-btn--sm">
                <i class="fa-solid fa-file-import" style="margin-right: 0.4rem;"></i>Importar
            </button>
            <button type="button" id="btnExportarClientes" class="hd-btn hd-btn--ghost hd-btn--sm">
                <i class="fa-solid fa-file-excel" style="margin-right: 0.4rem;"></i>Exportar
            </button>
            <button type="button" id="btnRelatorioClientes" class="hd-btn hd-btn--ghost hd-btn--sm">
                <i class="fa-solid fa-file-pdf" style="margin-right: 0.4rem;"></i>Relatório
            </button>
        </div>
    </div>
    <input type="file" id="inputImportarClientes" accept=".xlsx" style="display: none;">
    <div class="hd-card__body">
        <div class="table-responsive">
            <table id="tabelaClientes" class="table table-striped table-hover align-middle" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Telefone</th>
                        <th>Tipo</th>
                        <th>Ativo</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Cliente (Cadastrar / Editar) -->
<div class="modal fade" id="modalCliente" tabindex="-1" aria-labelledby="modalClienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="formCliente" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalClienteLabel">Cadastrar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="cliente_id" name="id">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="cliente_nome">Nome *</label>
                            <input type="text" id="cliente_nome" name="nome" class="form-control" required maxlength="120">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="cliente_telefone">Telefone *</label>
                            <div class="input-group">
                                <select id="cliente_ddi" name="ddi" class="form-select" style="max-width: 90px;">
                                    <option value="" selected>DDI</option>
                                    <option value="55">🇧🇷 +55</option>
                                    <option value="351">🇵🇹 +351</option>
                                </select>
                                <input type="text" id="cliente_telefone" name="telefone" class="form-control" placeholder="(00) 00000-0000" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="cliente_cpf_cnpj">CPF/CNPJ</label>
                            <input type="text" id="cliente_cpf_cnpj" name="cpf_cnpj" class="form-control" maxlength="20">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="cliente_email">E-mail</label>
                            <input type="email" id="cliente_email" name="email" class="form-control" maxlength="120">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="cliente_tipo">Tipo</label>
                            <select id="cliente_tipo" name="tipo" class="form-select">
                                <option value="Pessoa Física">Pessoa Física</option>
                                <option value="Pessoa Jurídica">Pessoa Jurídica</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="cliente_ativo">Ativo</label>
                            <select id="cliente_ativo" name="ativo" class="form-select">
                                <option value="Sim">Sim</option>
                                <option value="Não">Não</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="cliente_notificar">Notificar cadastro?</label>
                            <select id="cliente_notificar" name="notificar_cadastro" class="form-select">
                                <option value="Sim">Sim</option>
                                <option value="Não">Não</option>
                            </select>
                        </div>

                        <div class="col-12 d-flex align-items-center gap-3">
                            <img id="previewFotoCliente" src="../uploads/clientes/sem_foto.png" alt="" class="rounded-circle" style="width: 3.5rem; height: 3.5rem; object-fit: cover; flex-shrink: 0;">
                            <div class="flex-grow-1">
                                <input type="file" id="cliente_foto" name="foto" accept=".png,.jpg,.jpeg,.webp" class="form-control">
                                <div class="form-text">PNG, JPG ou WEBP — até 2MB.</div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="text-uppercase text-muted small fw-bold mb-3">Endereço</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label" for="cliente_cep">CEP</label>
                            <input type="text" id="cliente_cep" name="cep" class="form-control" placeholder="CEP">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="cliente_estado">Estado</label>
                            <select id="cliente_estado" name="estado" class="form-select">
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
                            <label class="form-label" for="cliente_cidade">Cidade</label>
                            <input type="text" id="cliente_cidade" name="cidade" class="form-control" placeholder="Cidade">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="cliente_endereco">Endereço</label>
                            <input type="text" id="cliente_endereco" name="endereco" class="form-control" placeholder="Endereço">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="cliente_numero">Número</label>
                            <input type="text" id="cliente_numero" name="numero" class="form-control" placeholder="Número">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cliente_bairro">Bairro</label>
                            <input type="text" id="cliente_bairro" name="bairro" class="form-control" placeholder="Bairro">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cliente_complemento">Complemento</label>
                            <input type="text" id="cliente_complemento" name="complemento" class="form-control" placeholder="Complemento">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="cliente_observacoes">Observações</label>
                            <textarea id="cliente_observacoes" name="observacoes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarCliente">Salvar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Visualizar Cliente — somente leitura -->
<div class="modal fade" id="modalVisualizarCliente" tabindex="-1" aria-labelledby="modalVisualizarClienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisualizarClienteLabel">Detalhes do Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <img id="verFotoCliente" src="../uploads/clientes/sem_foto.png" alt="" class="rounded-circle" style="width: 4rem; height: 4rem; object-fit: cover;">
                    <div>
                        <h5 class="mb-1" id="verNome">-</h5>
                        <span class="badge" id="verStatus">-</span>
                    </div>
                </div>

                <h6 class="text-uppercase text-muted small fw-bold mb-3">Dados</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-6"><small class="text-muted d-block">E-mail</small><span id="verEmail">-</span></div>
                    <div class="col-md-6"><small class="text-muted d-block">Telefone</small><span id="verTelefone">-</span></div>
                    <div class="col-md-6"><small class="text-muted d-block">CPF/CNPJ</small><span id="verCpfCnpj">-</span></div>
                    <div class="col-md-6"><small class="text-muted d-block">Tipo</small><span id="verTipo">-</span></div>
                </div>

                <h6 class="text-uppercase text-muted small fw-bold mb-3">Endereço</h6>
                <div class="row g-3 mb-4">
                    <div class="col-md-3"><small class="text-muted d-block">CEP</small><span id="verCep">-</span></div>
                    <div class="col-md-3"><small class="text-muted d-block">Estado</small><span id="verEstado">-</span></div>
                    <div class="col-md-6"><small class="text-muted d-block">Cidade</small><span id="verCidade">-</span></div>
                    <div class="col-md-8"><small class="text-muted d-block">Endereço</small><span id="verEndereco">-</span></div>
                    <div class="col-md-4"><small class="text-muted d-block">Número</small><span id="verNumero">-</span></div>
                    <div class="col-md-6"><small class="text-muted d-block">Bairro</small><span id="verBairro">-</span></div>
                    <div class="col-md-6"><small class="text-muted d-block">Complemento</small><span id="verComplemento">-</span></div>
                </div>

                <h6 class="text-uppercase text-muted small fw-bold mb-3">Observações</h6>
                <p id="verObservacoes" class="mb-0">-</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmação de exclusão — substitui o confirm() nativo do navegador -->
<div class="modal fade" id="modalConfirmarExclusao" tabindex="-1" aria-labelledby="modalConfirmarExclusaoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalConfirmarExclusaoLabel">Excluir cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Tem certeza que deseja excluir este cliente? Essa ação não pode ser desfeita.</p>
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

<script src="assets/js/clientes.js<?php echo asset_v('painel/assets/js/clientes.js'); ?>"></script>
