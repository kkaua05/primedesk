<?php
require_once 'config/database.php';
require_once 'includes/verificar_sessao.php';

$database = new Database();
$conn = $database->getConnection();

// Filtros - Converter para inteiro para evitar problemas de comparação
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('n');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');
$filtroStatus = isset($_GET['status']) ? $_GET['status'] : '';
$filtroVendedor = isset($_GET['vendedor']) ? $_GET['vendedor'] : '';

// Query base - Usar parâmetros numéricos
$query = "SELECT f.*, c.nome as cliente_nome, c.operadora, c.vendedor 
          FROM financeiro f 
          INNER JOIN clientes c ON f.cliente_id = c.id 
          WHERE MONTH(f.data_vencimento) = :mes 
          AND YEAR(f.data_vencimento) = :ano";

$params = [':mes' => $mes, ':ano' => $ano];

if ($filtroStatus) {
    $query .= " AND f.status = :status";
    $params[':status'] = $filtroStatus;
}

if ($filtroVendedor) {
    $query .= " AND c.vendedor LIKE :vendedor";
    $params[':vendedor'] = "%$filtroVendedor%";
}

$query .= " ORDER BY f.data_vencimento DESC, f.id DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$financeiro = $stmt->fetchAll();

// Totais do mês - Usar os mesmos parâmetros
$stmt = $conn->prepare("SELECT COALESCE(SUM(f.valor), 0) as total 
                        FROM financeiro f 
                        WHERE MONTH(f.data_pagamento) = :mes 
                        AND YEAR(f.data_pagamento) = :ano 
                        AND f.status = 'Pago'");
$stmt->execute([':mes' => $mes, ':ano' => $ano]);
$receitaTotal = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COALESCE(SUM(valor), 0) as total 
                        FROM financeiro 
                        WHERE MONTH(data_vencimento) = :mes 
                        AND YEAR(data_vencimento) = :ano 
                        AND status = 'Pendente'");
$stmt->execute([':mes' => $mes, ':ano' => $ano]);
$pendenteTotal = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COALESCE(SUM(valor), 0) as total 
                        FROM financeiro 
                        WHERE MONTH(data_vencimento) = :mes 
                        AND YEAR(data_vencimento) = :ano 
                        AND status = 'Cancelado'");
$stmt->execute([':mes' => $mes, ':ano' => $ano]);
$canceladoTotal = $stmt->fetch()['total'];

// Contar pagamentos recebidos
$pagosCount = 0;
foreach($financeiro as $f) {
    if($f['status'] == 'Pago') $pagosCount++;
}

// Nomes dos meses para exibição
$nomes_meses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];
?>

<div class="page-header">
    <h2><i class="fas fa-chart-line"></i> Financeiro Mensal</h2>
    <button onclick="window.location.reload()" class="btn btn-primary btn-sm">
        <i class="fas fa-sync-alt"></i> Atualizar
    </button>
</div>

<div class="cards-grid">
    <div class="card success">
        <h3><i class="fas fa-dollar-sign"></i> Receita Total do Mês</h3>
        <div class="value">R$ <?php echo number_format($receitaTotal, 2, ',', '.'); ?></div>
    </div>
    <div class="card">
        <h3><i class="fas fa-check-circle"></i> Pagamentos Recebidos</h3>
        <div class="value"><?php echo $pagosCount; ?></div>
    </div>
    <div class="card warning">
        <h3><i class="fas fa-clock"></i> Pagamentos Pendentes</h3>
        <div class="value">R$ <?php echo number_format($pendenteTotal, 2, ',', '.'); ?></div>
    </div>
    <div class="card danger">
        <h3><i class="fas fa-times-circle"></i> Cancelamentos</h3>
        <div class="value">R$ <?php echo number_format($canceladoTotal, 2, ',', '.'); ?></div>
    </div>
</div>

<div class="table-container">
    <div class="table-header">
        <h3>Lançamentos do Mês <?php echo str_pad($mes, 2, '0', STR_PAD_LEFT); ?>/<?php echo $ano; ?></h3>
        <form method="GET" class="filters">
            <input type="hidden" name="page" value="financeiro_mensal">
            <select name="mes">
                <?php for($i = 1; $i <= 12; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo ($mes == $i) ? 'selected' : ''; ?>>
                    <?php echo $nomes_meses[$i]; ?>
                </option>
                <?php endfor; ?>
            </select>
            <select name="ano">
                <?php for($i = date('Y') - 2; $i <= date('Y') + 1; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo ($ano == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
            <select name="status">
                <option value="">Todos Status</option>
                <option value="Pago" <?php echo ($filtroStatus == 'Pago') ? 'selected' : ''; ?>>Pago</option>
                <option value="Pendente" <?php echo ($filtroStatus == 'Pendente') ? 'selected' : ''; ?>>Pendente</option>
                <option value="Cancelado" <?php echo ($filtroStatus == 'Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
            </select>
            <input type="text" name="vendedor" placeholder="Vendedor" value="<?php echo htmlspecialchars($filtroVendedor); ?>">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-search"></i> Filtrar
            </button>
            <a href="index.php?page=financeiro_mensal" class="btn btn-secondary btn-sm">
                <i class="fas fa-times"></i> Limpar
            </a>
        </form>
    </div>
    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Descrição</th>
                <th>Valor</th>
                <th>Vencimento</th>
                <th>Pagamento</th>
                <th>Forma Pgto</th>
                <th>Vendedor</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($financeiro) > 0): ?>
                <?php foreach($financeiro as $f): ?>
                <tr>
                    <td><?php echo htmlspecialchars($f['cliente_nome']); ?></td>
                    <td><?php echo htmlspecialchars($f['descricao']); ?></td>
                    <td>R$ <?php echo number_format($f['valor'], 2, ',', '.'); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($f['data_vencimento'])); ?></td>
                    <td><?php echo $f['data_pagamento'] ? date('d/m/Y H:i', strtotime($f['data_pagamento'])) : '-'; ?></td>
                    <td>
                        <?php if(isset($f['forma_pagamento']) && $f['forma_pagamento']): ?>
                        <span class="badge" style="background-color: #e0e7ff; color: #3730a3;">
                            <?php echo htmlspecialchars($f['forma_pagamento']); ?>
                        </span>
                        <?php else: ?>
                        <span class="badge bg-warning">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($f['vendedor']); ?></td>
                    <td>
                        <span class="badge <?php 
                            echo ($f['status'] == 'Pago') ? 'bg-success' : 
                                 (($f['status'] == 'Cancelado') ? 'bg-danger' : 'bg-warning'); 
                        ?>">
                            <?php echo $f['status']; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">
                        <div style="padding: 40px;">
                            <i class="fas fa-inbox" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 15px;"></i>
                            <p style="color: #64748b; margin: 0;">Nenhum lançamento encontrado para este período</p>
                            <p style="color: #94a3b8; font-size: 0.9rem; margin: 5px 0 0 0;">
                                Mês: <?php echo $nomes_meses[$mes]; ?>/<?php echo $ano; ?>
                            </p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
// Debug para verificar os dados
console.log('Mês:', <?php echo $mes; ?>);
console.log('Ano:', <?php echo $ano; ?>);
console.log('Total de lançamentos:', <?php echo count($financeiro); ?>);
</script>