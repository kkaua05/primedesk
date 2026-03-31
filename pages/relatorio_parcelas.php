<?php
require_once 'config/database.php';
require_once 'includes/verificar_sessao.php';

$database = new Database();
$conn = $database->getConnection();

// Filtros
$filtroStatus = isset($_GET['status']) ? $_GET['status'] : '';
$filtroCliente = isset($_GET['cliente']) ? $_GET['cliente'] : '';
$filtroPeriodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'todos';

// Query base para parcelas
$query = "SELECT f.*, c.nome as cliente_nome, c.operadora
          FROM financeiro f
          INNER JOIN clientes c ON f.cliente_id = c.id
          WHERE (f.tipo_lancamento = 'parcelado' OR f.total_parcelas > 1)";

$params = [];

if ($filtroStatus) {
    $query .= " AND f.status = :status";
    $params[':status'] = $filtroStatus;
}

if ($filtroCliente) {
    $query .= " AND f.cliente_id = :cliente";
    $params[':cliente'] = $filtroCliente;
}

if ($filtroPeriodo == 'pendentes') {
    $query .= " AND f.status = 'Pendente'";
} elseif ($filtroPeriodo == 'pagas') {
    $query .= " AND f.status = 'Pago'";
} elseif ($filtroPeriodo == 'vencidas') {
    $query .= " AND f.status = 'Pendente' AND f.data_vencimento < CURDATE()";
}

$query .= " ORDER BY f.data_vencimento DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$parcelas = $stmt->fetchAll();

// Buscar clientes para o filtro
$stmt = $conn->query("SELECT id, nome FROM clientes WHERE status = 'Ativo' ORDER BY nome");
$clientes = $stmt->fetchAll();

// Totais
$stmt = $conn->query("SELECT COUNT(*) as total FROM financeiro WHERE (tipo_lancamento = 'parcelado' OR total_parcelas > 1)");
$totalParcelas = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM financeiro WHERE (tipo_lancamento = 'parcelado' OR total_parcelas > 1) AND status = 'Pendente'");
$parcelasPendentes = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM financeiro WHERE (tipo_lancamento = 'parcelado' OR total_parcelas > 1) AND status = 'Pago'");
$parcelasPagas = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro WHERE (tipo_lancamento = 'parcelado' OR total_parcelas > 1) AND status = 'Pendente'");
$valorPendente = $stmt->fetch()['total'];
?>
<div class="page-header">
    <div>
        <h2><i class="fas fa-layer-group"></i> Gestão de Parcelas</h2>
        <p style="color: var(--text-light); margin-top: 5px;">
            Visualize e gerencie todas as parcelas do sistema
        </p>
    </div>
    <button onclick="window.print()" class="btn btn-secondary">
        <i class="fas fa-print"></i> Imprimir
    </button>
</div>

<!-- Cards de Resumo -->
<div class="cards-grid">
    <div class="card">
        <h3><i class="fas fa-layer-group"></i> Total de Parcelas</h3>
        <div class="value"><?php echo $totalParcelas; ?></div>
    </div>
    <div class="card warning">
        <h3><i class="fas fa-clock"></i> Parcelas Pendentes</h3>
        <div class="value"><?php echo $parcelasPendentes; ?></div>
    </div>
    <div class="card success">
        <h3><i class="fas fa-check-circle"></i> Parcelas Pagas</h3>
        <div class="value"><?php echo $parcelasPagas; ?></div>
    </div>
    <div class="card danger">
        <h3><i class="fas fa-dollar-sign"></i> Valor Pendente</h3>
        <div class="value">R$ <?php echo number_format($valorPendente, 2, ',', '.'); ?></div>
    </div>
</div>

<!-- Filtros -->
<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-filter"></i> Filtros</h3>
    </div>
    <form method="GET" style="padding: 20px;">
        <input type="hidden" name="page" value="relatorio_parcelas">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div class="form-group" style="margin-bottom: 0;">
                <label>Cliente</label>
                <select name="cliente" class="form-control">
                    <option value="">Todos os Clientes</option>
                    <?php foreach($clientes as $cli): ?>
                        <option value="<?php echo $cli['id']; ?>" <?php echo ($filtroCliente == $cli['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cli['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="">Todos os Status</option>
                    <option value="Pendente" <?php echo ($filtroStatus == 'Pendente') ? 'selected' : ''; ?>>Pendente</option>
                    <option value="Pago" <?php echo ($filtroStatus == 'Pago') ? 'selected' : ''; ?>>Pago</option>
                    <option value="Cancelado" <?php echo ($filtroStatus == 'Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Período</label>
                <select name="periodo" class="form-control">
                    <option value="todos" <?php echo ($filtroPeriodo == 'todos') ? 'selected' : ''; ?>>Todos</option>
                    <option value="pendentes" <?php echo ($filtroPeriodo == 'pendentes') ? 'selected' : ''; ?>>Pendentes</option>
                    <option value="pagas" <?php echo ($filtroPeriodo == 'pagas') ? 'selected' : ''; ?>>Pagas</option>
                    <option value="vencidas" <?php echo ($filtroPeriodo == 'vencidas') ? 'selected' : ''; ?>>Vencidas</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0; display: flex; align-items: flex-end;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="index.php?page=relatorio_parcelas" class="btn btn-secondary" style="margin-left: 10px;">
                    <i class="fas fa-times"></i> Limpar
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Lista de Parcelas -->
<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-list"></i> Lista de Parcelas</h3>
        <span class="badge bg-primary"><?php echo count($parcelas); ?> registros</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Descrição</th>
                <th>Parcela</th>
                <th>Valor</th>
                <th>Vencimento</th>
                <th>Pagamento</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($parcelas) > 0): ?>
                <?php foreach($parcelas as $p): ?>
                    <?php
                    $diasAtraso = 0;
                    if ($p['status'] == 'Pendente' && strtotime($p['data_vencimento']) < time()) {
                        $diasAtraso = floor((time() - strtotime($p['data_vencimento'])) / 86400);
                    }
                    
                    $totalParc = $p['total_parcelas'] ?? 1;
                    $parcelaAtual = $p['parcela_atual'] ?? 1;
                    ?>
                <tr class="<?php echo $diasAtraso > 0 ? 'bg-danger-light' : ''; ?>">
                    <td><?php echo $p['id']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($p['cliente_nome']); ?></strong>
                        <br><small style="color: var(--text-light);"><?php echo htmlspecialchars($p['operadora']); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($p['descricao']); ?></td>
                    <td>
                        <span class="badge bg-info">
                            <?php echo $parcelaAtual; ?>/<?php echo $totalParc; ?>
                        </span>
                    </td>
                    <td class="text-success">
                        <strong>R$ <?php echo number_format($p['valor'], 2, ',', '.'); ?></strong>
                    </td>
                    <td>
                        <?php echo date('d/m/Y', strtotime($p['data_vencimento'])); ?>
                        <?php if($diasAtraso > 0): ?>
                            <br><small style="color: #ef4444; font-weight: 600;">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo $diasAtraso; ?> dias
                            </small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($p['data_pagamento']): ?>
                            <?php echo date('d/m/Y', strtotime($p['data_pagamento'])); ?>
                        <?php else: ?>
                            <span style="color: var(--text-light);">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?php
                            echo ($p['status'] == 'Pago') ? 'bg-success' :
                                (($p['status'] == 'Cancelado') ? 'bg-danger' : 'bg-warning');
                        ?>">
                            <?php echo $p['status']; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">
                        <div style="padding: 40px;">
                            <i class="fas fa-inbox" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 15px;"></i>
                            <p style="color: #64748b; margin: 0;">Nenhuma parcela encontrada</p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.bg-danger-light {
    background-color: #fef2f2 !important;
}
.text-success {
    color: #10b981 !important;
}
.badge-info {
    background-color: #3b82f6 !important;
    color: white !important;
}
</style>