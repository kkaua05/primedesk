<?php
require_once 'config/database.php';
require_once 'includes/verificar_sessao.php';

$database = new Database();
$conn = $database->getConnection();

// Total de clientes
$stmt = $conn->query("SELECT COUNT(*) as total FROM clientes");
$totalClientes = $stmt->fetch()['total'];

// Clientes ativos
$stmt = $conn->query("SELECT COUNT(*) as total FROM clientes WHERE status = 'Ativo'");
$clientesAtivos = $stmt->fetch()['total'];

// Receita do mês atual (pagamentos recebidos este mês)
$stmt = $conn->query("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro 
                      WHERE MONTH(data_pagamento) = MONTH(CURRENT_DATE()) 
                      AND YEAR(data_pagamento) = YEAR(CURRENT_DATE()) 
                      AND status = 'Pago'");
$receitaMes = $stmt->fetch()['total'];

// Valor pendente (todos os pendentes independente do mês)
$stmt = $conn->query("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro WHERE status = 'Pendente'");
$valorPendente = $stmt->fetch()['total'];

// Total de serviços
$stmt = $conn->query("SELECT COUNT(*) as total FROM financeiro");
$totalServicos = $stmt->fetch()['total'];

// Dados para gráfico de receita mensal (últimos 12 meses)
$stmt = $conn->query("SELECT MONTH(data_pagamento) as mes, SUM(valor) as total 
                      FROM financeiro 
                      WHERE status = 'Pago' 
                      AND YEAR(data_pagamento) = YEAR(CURRENT_DATE())
                      GROUP BY MONTH(data_pagamento)
                      ORDER BY mes");
$receitaMensal = $stmt->fetchAll();

// Dados para gráfico de operadoras
$stmt = $conn->query("SELECT operadora, COUNT(*) as count FROM clientes GROUP BY operadora");
$operadoras = $stmt->fetchAll();

// Últimos pagamentos recebidos
$stmt = $conn->query("SELECT f.*, c.nome as cliente_nome 
                      FROM financeiro f 
                      INNER JOIN clientes c ON f.cliente_id = c.id 
                      WHERE f.status = 'Pago' 
                      ORDER BY f.data_pagamento DESC 
                      LIMIT 5");
$ultimosPagamentos = $stmt->fetchAll();
?>

<div class="page-header">
    <h2><i class="fas fa-home"></i> Dashboard</h2>
    <span class="badge bg-success">Sistema Online</span>
    <button onclick="atualizarDashboard()" class="btn btn-sm btn-primary">
        <i class="fas fa-sync-alt"></i> Atualizar
    </button>
</div>

<div class="cards-grid">
    <div class="card">
        <h3><i class="fas fa-users"></i> Total de Clientes</h3>
        <div class="value" id="totalClientes"><?php echo number_format($totalClientes); ?></div>
    </div>
    <div class="card success">
        <h3><i class="fas fa-check-circle"></i> Clientes Ativos</h3>
        <div class="value" id="clientesAtivos"><?php echo number_format($clientesAtivos); ?></div>
    </div>
    <div class="card success">
        <h3><i class="fas fa-dollar-sign"></i> Receita do Mês</h3>
        <div class="value" id="receitaMes">R$ <?php echo number_format($receitaMes, 2, ',', '.'); ?></div>
    </div>
    <div class="card warning">
        <h3><i class="fas fa-clock"></i> Valor Pendente</h3>
        <div class="value" id="valorPendente">R$ <?php echo number_format($valorPendente, 2, ',', '.'); ?></div>
    </div>
    <div class="card">
        <h3><i class="fas fa-briefcase"></i> Total de Serviços</h3>
        <div class="value" id="totalServicos"><?php echo number_format($totalServicos); ?></div>
    </div>
</div>

<!-- Últimos Pagamentos -->
<?php if(count($ultimosPagamentos) > 0): ?>
<div class="table-container" style="margin-bottom: 20px;">
    <div class="table-header">
        <h3><i class="fas fa-receipt"></i> Últimos Pagamentos Recebidos</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Descrição</th>
                <th>Valor</th>
                <th>Data Pagamento</th>
                <th>Forma Pgto</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($ultimosPagamentos as $p): ?>
            <tr>
                <td><?php echo htmlspecialchars($p['cliente_nome']); ?></td>
                <td><?php echo htmlspecialchars($p['descricao']); ?></td>
                <td>R$ <?php echo number_format($p['valor'], 2, ',', '.'); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($p['data_pagamento'])); ?></td>
                <td>
                    <?php if(isset($p['forma_pagamento'])): ?>
                    <span class="badge" style="background-color: #e0e7ff; color: #3730a3;">
                        <?php echo htmlspecialchars($p['forma_pagamento']); ?>
                    </span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="charts-grid">
    <div class="chart-container">
        <h4><i class="fas fa-chart-bar"></i> Receita Mensal</h4>
        <canvas id="receitaChart"></canvas>
    </div>
    <div class="chart-container">
        <h4><i class="fas fa-chart-pie"></i> Clientes por Operadora</h4>
        <canvas id="operadoraChart"></canvas>
    </div>
</div>

<script>
// Gráfico de Receita Mensal
const receitaData = {
    labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
    datasets: [{
        label: 'Receita (R$)',
        data: [<?php 
            $meses = array_fill(0, 12, 0);
            foreach($receitaMensal as $r) {
                $meses[$r['mes'] - 1] = $r['total'];
            }
            echo implode(',', $meses);
        ?>],
        backgroundColor: 'rgba(37, 99, 235, 0.2)',
        borderColor: 'rgba(37, 99, 235, 1)',
        borderWidth: 2,
        tension: 0.4
    }]
};

new Chart(document.getElementById('receitaChart'), {
    type: 'line',
    data: receitaData,
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Gráfico de Operadoras
const operadoraData = {
    labels: [<?php 
        $labels = [];
        foreach($operadoras as $o) {
            $labels[] = "'" . $o['operadora'] . "'";
        }
        echo implode(',', $labels);
    ?>],
    datasets: [{
        data: [<?php 
            $values = [];
            foreach($operadoras as $o) {
                $values[] = $o['count'];
            }
            echo implode(',', $values);
        ?>],
        backgroundColor: [
            'rgba(0, 60, 255, 0.8)',
            'rgba(98, 0, 255, 0.8)',
            'rgba(245, 11, 11, 0.8)',
            'rgba(107, 114, 128, 0.8)'
        ]
    }]
};

new Chart(document.getElementById('operadoraChart'), {
    type: 'doughnut',
    data: operadoraData,
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Atualizar Dashboard em tempo real (a cada 30 segundos)
function atualizarDashboard() {
    fetch('actions/atualizar_dashboard.php')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('totalClientes').innerText = data.totalClientes;
                document.getElementById('clientesAtivos').innerText = data.clientesAtivos;
                document.getElementById('receitaMes').innerText = 'R$ ' + data.receitaMes;
                document.getElementById('valorPendente').innerText = 'R$ ' + data.valorPendente;
                document.getElementById('totalServicos').innerText = data.totalServicos;
                
                Swal.fire({
                    icon: 'success',
                    title: 'Atualizado!',
                    text: 'Dashboard atualizado com sucesso',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        })
        .catch(error => {
            console.error('Erro:', error);
        });
}

// Auto-atualizar a cada 30 segundos
setInterval(atualizarDashboard, 30000);
</script>