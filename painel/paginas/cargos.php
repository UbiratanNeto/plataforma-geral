<?php
/**
 * Gestão de Cargos — lista (DataTables), cria, edita e exclui cargos.
 * Mesmo padrão Bootstrap 5 + DataTables + funções globais genéricas da tela de Usuários.
 */
?>
<div class="hd-welcome">
    <h1 class="hd-welcome__title">Gestão de Cargos</h1>
    <p class="hd-welcome__subtitle">Gerencie os cargos disponíveis no sistema</p>
</div>

<div class="hd-card">
    <div class="hd-card__header hd-card__header--row">
        <h2 class="hd-card__title" style="font-size: 1.15rem;">
            <i class="fa-solid fa-briefcase" style="color: var(--cor-primaria); margin-right: 0.5rem;"></i>
            Cargos
        </h2>
        <?php if (podeExecutarAcao($pdo, $_SESSION['id'] ?? null, $_SESSION['cargo_id'] ?? null, 'criar')): ?>
        <button type="button" class="hd-btn hd-btn--primary hd-btn--sm" onclick="novo()">
            <i class="fa-solid fa-plus" style="margin-right: 0.4rem;"></i>Novo Cargo
        </button>
        <?php endif; ?>
    </div>
    <div class="hd-card__body">
        <div class="table-responsive">
            <table id="tabelaCargos" class="table table-striped table-hover align-middle" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Cargo (Cadastrar / Editar) -->
<div class="modal fade" id="modalCargo" tabindex="-1" aria-labelledby="modalCargoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formCargo">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCargoLabel">Cadastrar Cargo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="cargo_id" name="id">
                    <div class="mb-3">
                        <label class="form-label" for="cargo_nome">Nome do Cargo *</label>
                        <input type="text" id="cargo_nome" name="nome" class="form-control" required maxlength="75">
                    </div>

                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" role="switch" id="cargo_acesso_total" name="acesso_total" value="1">
                        <label class="form-check-label" for="cargo_acesso_total">
                            Acesso total <span class="text-muted fw-normal">(usuários com esse cargo veem todos os menus do sistema)</span>
                        </label>
                    </div>
                    <p class="form-text">As demais permissões (por menu) agora são configuradas por usuário, na tela de Usuários.</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarCargo">Salvar Cargo</button>
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
                <h5 class="modal-title" id="modalConfirmarExclusaoLabel">Excluir cargo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Tem certeza que deseja excluir este cargo? Essa ação não pode ser desfeita.</p>
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

<script src="assets/js/cargos.js<?php echo asset_v('painel/assets/js/cargos.js'); ?>"></script>
