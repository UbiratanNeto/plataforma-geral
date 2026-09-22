<?php
/**
 * painel/paginas/logs.php
 * Histórico de ações do sistema (login, inserir, editar, excluir) — lista via DataTables,
 * somente leitura (sem criar/editar/excluir — o log em si é imutável).
 */
?>
<div class="hd-welcome">
    <h1 class="hd-welcome__title">Logs do Sistema</h1>
    <p class="hd-welcome__subtitle">Histórico de ações realizadas no sistema</p>
</div>

<div class="hd-card">
    <div class="hd-card__header hd-card__header--row">
        <h2 class="hd-card__title" style="font-size: 1.15rem;">
            <i class="fa-solid fa-clock-rotate-left" style="color: var(--cor-primaria); margin-right: 0.5rem;"></i>
            Logs
        </h2>
        <div style="display: flex; gap: 0.5rem;">
            <button type="button" id="btnLimparFiltro" class="hd-btn hd-btn--ghost hd-btn--sm">
                <i class="fa-solid fa-xmark" style="margin-right: 0.4rem;"></i>Limpar
            </button>
            <button type="button" id="btnRelatorioLogs" class="hd-btn hd-btn--primary hd-btn--sm">
                <i class="fa-solid fa-file-pdf" style="margin-right: 0.4rem;"></i>Relatório
            </button>
        </div>
    </div>
    <div class="hd-card__body">
        <div class="row g-3 align-items-end" style="margin-bottom: 1.5rem;">
            <div class="col-md-3">
                <label class="form-label" for="filtro_data_inicial">Data inicial</label>
                <input type="date" id="filtro_data_inicial" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="filtro_data_final">Data final</label>
                <input type="date" id="filtro_data_final" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="filtro_usuario">Usuário</label>
                <input type="text" id="filtro_usuario" class="form-control" placeholder="Nome do funcionário...">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="filtro_acao">Ação</label>
                <select id="filtro_acao" class="form-select">
                    <option value="">Todas</option>
                    <option value="login">Login</option>
                    <option value="logout">Logout</option>
                    <option value="inserir">Inserir</option>
                    <option value="editar">Editar</option>
                    <option value="excluir">Excluir</option>
                </select>
            </div>
        </div>

        <div class="row g-3 align-items-end" style="margin-bottom: 1.5rem;">
            <div class="col-12">
                <label class="form-label d-block">Atalhos</label>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-primary btn-sm" data-atalho="hoje">Hoje</button>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-atalho="ontem">Ontem</button>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-atalho="mes">Este mês</button>
                    <button type="button" class="btn btn-outline-primary btn-sm" data-atalho="7dias">Últimos 7 dias</button>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="tabelaLogs" class="table table-striped table-hover align-middle" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Usuário</th>
                        <th>Ação</th>
                        <th>Entidade</th>
                        <th>Descrição</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- jQuery + Bootstrap 5 JS + DataTables: nessa ordem, exigida pelas próprias libs -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.11/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.11/js/dataTables.bootstrap5.min.js"></script>

<script src="assets/js/logs.js<?php echo asset_v('painel/assets/js/logs.js'); ?>"></script>
