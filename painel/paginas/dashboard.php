<?php
/**
 * painel/paginas/dashboard.php
 * Conteúdo interno do Dashboard. Carregado dinamicamente via painel/index.php
 */

// Validação extra para garantir que o banco e a sessão estejam disponíveis
if (!isset($pdo) || !isset($_SESSION['id_empresa'])) {
    die("Acesso restrito ou sessão expirada.");
}

try {
    // 1. Métricas do topo em UMA única consulta (subqueries escalares), em vez de 4 idas ao banco.
    //    Cada ocorrência de :empresa precisa de um nome próprio porque a conexão usa
    //    PDO::ATTR_EMULATE_PREPARES = false (prepares nativos não aceitam o mesmo placeholder repetido).
    $stmtMetricas = $pdo->prepare("
        SELECT
            (SELECT COUNT(id) FROM chamados WHERE empresa_id = :empresa1 AND status = 'Aberto')  AS total_abertos,
            (SELECT COUNT(id) FROM chamados WHERE empresa_id = :empresa2 AND status = 'Fechado') AS total_fechados,
            (SELECT COUNT(id) FROM clientes WHERE empresa_id = :empresa3)                        AS total_clientes,
            (SELECT COUNT(id) FROM usuarios WHERE empresa = :empresa4 AND ativo = 1)             AS total_usuarios
    ");
    $stmtMetricas->execute([
        ':empresa1' => $_SESSION['id_empresa'],
        ':empresa2' => $_SESSION['id_empresa'],
        ':empresa3' => $_SESSION['id_empresa'],
        ':empresa4' => $_SESSION['id_empresa'],
    ]);
    $metricas = $stmtMetricas->fetch(PDO::FETCH_ASSOC);

    $total_abertos   = $metricas['total_abertos'];
    $total_fechados  = $metricas['total_fechados'];
    $total_clientes  = $metricas['total_clientes'];
    $total_usuarios  = $metricas['total_usuarios'];

    // 2. Query para listar os últimos 4 chamados atualizados
    $stmtChamados = $pdo->prepare("
        SELECT id, assunto, cliente_nome, status 
        FROM chamados 
        WHERE empresa_id = :empresa 
        ORDER BY data_atualizacao DESC 
        LIMIT 4
    ");
    $stmtChamados->execute([':empresa' => $_SESSION['id_empresa']]);
    $ultimos_chamados = $stmtChamados->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Valores de contingência caso as tabelas ainda estejam sendo estruturadas
    $total_abertos = 0;
    $total_fechados = 0;
    $total_clientes = 0;
    $total_usuarios = 0;
    $ultimos_chamados = [];
}
?>

<!-- GRIDS DE CARTÕES DE MÉTRICA -->
<section class="hd-metrics-grid">
    <div class="hd-stat hd-stat--blue">
        <div class="hd-stat__header">
            <span class="hd-stat__icon"><i class="fa-solid fa-ticket"></i></span>
            <h3 class="hd-stat__label">Chamados Abertos</h3>
        </div>
        <p class="hd-stat__value"><?php echo (int)$total_abertos; ?></p>
        <i class="fa-solid fa-ticket hd-stat__watermark" aria-hidden="true"></i>
    </div>

    <div class="hd-stat hd-stat--green">
        <div class="hd-stat__header">
            <span class="hd-stat__icon"><i class="fa-solid fa-circle-check"></i></span>
            <h3 class="hd-stat__label">Chamados Concluídos</h3>
        </div>
        <p class="hd-stat__value"><?php echo (int)$total_fechados; ?></p>
        <i class="fa-solid fa-circle-check hd-stat__watermark" aria-hidden="true"></i>
    </div>

    <div class="hd-stat hd-stat--orange">
        <div class="hd-stat__header">
            <span class="hd-stat__icon"><i class="fa-solid fa-users"></i></span>
            <h3 class="hd-stat__label">Clientes</h3>
        </div>
        <p class="hd-stat__value"><?php echo (int)$total_clientes; ?></p>
        <i class="fa-solid fa-users hd-stat__watermark" aria-hidden="true"></i>
    </div>

    <div class="hd-stat hd-stat--red">
        <div class="hd-stat__header">
            <span class="hd-stat__icon"><i class="fa-solid fa-user-shield"></i></span>
            <h3 class="hd-stat__label">Usuários</h3>
        </div>
        <p class="hd-stat__value"><?php echo (int)$total_usuarios; ?></p>
        <i class="fa-solid fa-user-shield hd-stat__watermark" aria-hidden="true"></i>
    </div>
</section>

<!-- VISÃO GERAL (GRÁFICO + TABELA) -->
<section class="hd-details-grid">
    
    <!-- Bloco do Gráfico -->
    <div class="hd-card">
        <div class="hd-card__header hd-card__header--row">
            <h2 class="hd-card__title" style="font-size: 1.15rem;">
                <i class="fa-solid fa-chart-line" style="color: var(--cor-primaria); margin-right: 0.5rem;"></i>
                Visão Geral
            </h2>
            <span style="font-size: 0.85rem; color: var(--muted-color); font-weight: 600;">Chamados por mês</span>
        </div>
        <div class="hd-card__body" style="min-height: 280px; flex: 1; position: relative;">
            <canvas id="canvasChart"></canvas>
        </div>
    </div>

    <!-- Bloco da Tabela -->
    <div class="hd-card">
        <div class="hd-card__header hd-card__header--row">
            <div>
                <h2 class="hd-card__title" style="font-size: 1.15rem;">
                    <i class="fa-regular fa-clock" style="color: var(--cor-primaria); margin-right: 0.5rem;"></i>
                    Últimos Chamados
                </h2>
            </div>
            <a href="chamados" class="hd-btn hd-btn--outline hd-btn--sm">Ver todos</a>
        </div>
        <div class="hd-card__body">
            <div class="hd-table-responsive">
                <table class="hd-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Assunto</th>
                            <th>Cliente</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($ultimos_chamados)): ?>
                            <?php foreach ($ultimos_chamados as $index => $chamado): ?>
                                <?php 
                                    $assunto = htmlspecialchars($chamado['assunto'], ENT_QUOTES, 'UTF-8');
                                    $cliente = htmlspecialchars($chamado['cliente_nome'], ENT_QUOTES, 'UTF-8');
                                    $status  = strtolower($chamado['status']);
                                    
                                    $badge_class = 'hd-badge--pending';
                                    if ($status === 'aberto') $badge_class = 'hd-badge--open';
                                    if ($status === 'fechado' || $status === 'resolvido') $badge_class = 'hd-badge--closed';
                                ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><strong><?php echo $assunto; ?></strong></td>
                                    <td><?php echo $cliente; ?></td>
                                    <td><span class="hd-badge <?php echo $badge_class; ?>"><?php echo ucfirst($status); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Fallback estático de amostragem amigável -->
                            <tr>
                                <td>1</td>
                                <td><strong>Problema no servidor</strong></td>
                                <td>João Souza</td>
                                <td><span class="hd-badge hd-badge--open">Aberto</span></td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td><strong>Atualização de Sistema</strong></td>
                                <td>Ana Lima</td>
                                <td><span class="hd-badge hd-badge--pending">Pendente</span></td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td><strong>Erro de login</strong></td>
                                <td>Patrícia Melo</td>
                                <td><span class="hd-badge hd-badge--closed">Fechado</span></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</section>

<!-- Script de Inicialização do Gráfico do Dashboard -->
<script>
    (function() {
        const ctx = document.getElementById('canvasChart').getContext('2d');
        const rootStyle = getComputedStyle(document.documentElement);
        const primaryColor = rootStyle.getPropertyValue('--cor-primaria').trim() || '#4f46e5';

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                datasets: [{
                    label: 'Chamados',
                    data: [4.5, 6, 5.2, 7.5, 5.8, 8.5],
                    borderColor: primaryColor,
                    backgroundColor: primaryColor + '1A', 
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: primaryColor
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    })();
</script>