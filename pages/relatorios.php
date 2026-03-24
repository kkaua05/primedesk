<?php
require_once 'includes/verificar_sessao.php';
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

$filtroOperadora = isset($_GET['operadora']) ? $_GET['operadora'] : '';
$filtroVendedor = isset($_GET['vendedor']) ? $_GET['vendedor'] : '';
$filtroConsultor = isset($_GET['consultor']) ? $_GET['consultor'] : '';
$filtroPeriodo = isset($_GET['periodo']) ? $_GET['periodo'] : 'todos';
$filtroStatusFin = isset($_GET['status_fin']) ? $_GET['status_fin'] : '';

// Relatório de Clientes
$queryClientes = "SELECT * FROM clientes WHERE 1=1";
$paramsClientes = [];
if ($filtroOperadora) {
    $queryClientes .= " AND operadora = :operadora";
    $paramsClientes[':operadora'] = $filtroOperadora;
}
if ($filtroVendedor) {
    $queryClientes .= " AND vendedor LIKE :vendedor";
    $paramsClientes[':vendedor'] = "%$filtroVendedor%";
}
if ($filtroConsultor) {
    $queryClientes .= " AND consultor LIKE :consultor";
    $paramsClientes[':consultor'] = "%$filtroConsultor%";
}
$stmt = $conn->prepare($queryClientes);
$stmt->execute($paramsClientes);
$clientesRelatorio = $stmt->fetchAll();

// Relatório Financeiro
$queryFin = "SELECT f.*, c.nome as cliente_nome FROM financeiro f
             INNER JOIN clientes c ON f.cliente_id = c.id WHERE 1=1";
$paramsFin = [];
if ($filtroStatusFin) {
    $queryFin .= " AND f.status = :status";
    $paramsFin[':status'] = $filtroStatusFin;
}
if ($filtroPeriodo == 'mes') {
    $queryFin .= " AND MONTH(f.data_vencimento) = MONTH(CURRENT_DATE())
                  AND YEAR(f.data_vencimento) = YEAR(CURRENT_DATE())";
} elseif ($filtroPeriodo == 'ano') {
    $queryFin .= " AND YEAR(f.data_vencimento) = YEAR(CURRENT_DATE())";
}
$stmt = $conn->prepare($queryFin);
$stmt->execute($paramsFin);
$financeiroRelatorio = $stmt->fetchAll();
?>
<div class="page-header">
    <h2><i class="fas fa-file-alt"></i> Relatórios Profissionais</h2>
</div>
<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-users"></i> Relatório de Clientes</h3>
        <div>
            <button onclick="exportarTabela('tabelaClientes', 'clientes')" class="btn btn-success btn-sm">
                <i class="fas fa-file-excel"></i> Excel
            </button>
            <button onclick="exportarTabela('tabelaClientes', 'clientes', 'pdf')" class="btn btn-danger btn-sm">
                <i class="fas fa-file-pdf"></i> PDF
            </button>
        </div>
    </div>
    <form method="GET" class="filters" style="padding: 0 20px 20px;">
        <input type="hidden" name="page" value="relatorios">
        <select name="operadora">
            <option value="">Todas Operadoras</option>
            <option value="VIVO" <?php echo ($filtroOperadora == 'VIVO') ? 'selected' : ''; ?>>VIVO</option>
            <option value="TIM" <?php echo ($filtroOperadora == 'TIM') ? 'selected' : ''; ?>>TIM</option>
            <option value="CLARO" <?php echo ($filtroOperadora == 'CLARO') ? 'selected' : ''; ?>>CLARO</option>
            <option value="OUTROS" <?php echo ($filtroOperadora == 'OUTROS') ? 'selected' : ''; ?>>OUTROS</option>
        </select>
        <input type="text" name="vendedor" placeholder="Vendedor" value="<?php echo htmlspecialchars($filtroVendedor); ?>">
        <input type="text" name="consultor" placeholder="Consultor" value="<?php echo htmlspecialchars($filtroConsultor); ?>">
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="fas fa-search"></i> Filtrar
        </button>
        <a href="index.php?page=relatorios" class="btn btn-secondary btn-sm">
            <i class="fas fa-times"></i> Limpar
        </a>
    </form>
    <table id="tabelaClientes">
        <thead>
            <tr>
                <th>Nome</th>
                <th>CPF</th>
                <th>Contato</th>
                <th>Operadora</th>
                <th>Vendedor</th>
                <th>Consultor</th>
                <th>Status</th>
                <th>Cadastro</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($clientesRelatorio as $c): ?>
            <tr>
                <td><?php echo htmlspecialchars($c['nome']); ?></td>
                <td><?php echo htmlspecialchars($c['cpf']); ?></td>
                <td><?php echo htmlspecialchars($c['contato']); ?></td>
                <td><?php echo htmlspecialchars($c['operadora']); ?></td>
                <td><?php echo htmlspecialchars($c['vendedor']); ?></td>
                <td><?php echo htmlspecialchars($c['consultor']); ?></td>
                <td><?php echo htmlspecialchars($c['status']); ?></td>
                <td><?php echo date('d/m/Y', strtotime($c['data_cadastro'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="table-container mt-20">
    <div class="table-header">
        <h3><i class="fas fa-dollar-sign"></i> Relatório Financeiro</h3>
        <div>
            <button onclick="exportarTabela('tabelaFinanceiro', 'financeiro')" class="btn btn-success btn-sm">
                <i class="fas fa-file-excel"></i> Excel
            </button>
            <button onclick="exportarTabela('tabelaFinanceiro', 'financeiro', 'pdf')" class="btn btn-danger btn-sm">
                <i class="fas fa-file-pdf"></i> PDF
            </button>
        </div>
    </div>
    <form method="GET" class="filters" style="padding: 0 20px 20px;">
        <input type="hidden" name="page" value="relatorios">
        <select name="periodo">
            <option value="todos" <?php echo ($filtroPeriodo == 'todos') ? 'selected' : ''; ?>>Todos Períodos</option>
            <option value="mes" <?php echo ($filtroPeriodo == 'mes') ? 'selected' : ''; ?>>Mês Atual</option>
            <option value="ano" <?php echo ($filtroPeriodo == 'ano') ? 'selected' : ''; ?>>Ano Atual</option>
        </select>
        <select name="status_fin">
            <option value="">Todos Status</option>
            <option value="Pago" <?php echo ($filtroStatusFin == 'Pago') ? 'selected' : ''; ?>>Pago</option>
            <option value="Pendente" <?php echo ($filtroStatusFin == 'Pendente') ? 'selected' : ''; ?>>Pendente</option>
            <option value="Cancelado" <?php echo ($filtroStatusFin == 'Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">
            <i class="fas fa-search"></i> Filtrar
        </button>
        <a href="index.php?page=relatorios" class="btn btn-secondary btn-sm">
            <i class="fas fa-times"></i> Limpar
        </a>
    </form>
    <table id="tabelaFinanceiro">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Descrição</th>
                <th>Valor</th>
                <th>Vencimento</th>
                <th>Pagamento</th>
                <th>Forma Pgto</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($financeiroRelatorio as $f): ?>
            <tr>
                <td><?php echo htmlspecialchars($f['cliente_nome']); ?></td>
                <td><?php echo htmlspecialchars($f['descricao']); ?></td>
                <td>R$ <?php echo number_format($f['valor'], 2, ',', '.'); ?></td>
                <td><?php echo date('d/m/Y', strtotime($f['data_vencimento'])); ?></td>
                <td><?php echo $f['data_pagamento'] ? date('d/m/Y', strtotime($f['data_pagamento'])) : '-'; ?></td>
                <td><?php echo isset($f['forma_pagamento']) ? htmlspecialchars($f['forma_pagamento']) : '-'; ?></td>
                <td><?php echo htmlspecialchars($f['status']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
