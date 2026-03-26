<?php
require_once 'config/database.php';
require_once 'includes/verificar_sessao.php';

$database = new Database();
$conn = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : 0;

// Dados do cliente
$stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    echo "<script>
    Swal.fire({
        icon: 'error',
        title: 'Erro!',
        text: 'Cliente não encontrado!'
    }).then(function() {
        window.location.href = 'index.php?page=clientes';
    });
    </script>";
    exit;
}

// Métricas Financeiras
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM financeiro WHERE cliente_id = ?");
$stmt->execute([$id]);
$totalServicos = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro WHERE cliente_id = ? AND status = 'Pago'");
$stmt->execute([$id]);
$totalPago = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro WHERE cliente_id = ? AND status = 'Pendente'");
$stmt->execute([$id]);
$totalPendente = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro WHERE cliente_id = ? AND status = 'Cancelado'");
$stmt->execute([$id]);
$totalCancelado = $stmt->fetch()['total'];

// Últimos lançamentos financeiros
$stmt = $conn->prepare("SELECT * FROM financeiro WHERE cliente_id = ? ORDER BY data_vencimento DESC LIMIT 5");
$stmt->execute([$id]);
$ultimosLancamentos = $stmt->fetchAll();

// Tarefas/Agendamentos relacionados
$stmt = $conn->prepare("SELECT * FROM agenda WHERE cliente_id = ? ORDER BY data_inicio DESC LIMIT 5");
$stmt->execute([$id]);
$tarefasCliente = $stmt->fetchAll();

// Receita mensal (últimos 6 meses)
$stmt = $conn->prepare("SELECT DATE_FORMAT(data_pagamento, '%Y-%m') as mes, SUM(valor) as total 
    FROM financeiro 
    WHERE cliente_id = ? AND status = 'Pago'
    GROUP BY mes 
    ORDER BY mes DESC 
    LIMIT 6");
$stmt->execute([$id]);
$receitaMensal = array_reverse($stmt->fetchAll());
?>

<div class="client-header">
    <div class="client-header-content">
        <div class="client-avatar-large">
            <?php echo strtoupper(substr($cliente['nome'], 0, 2)); ?>
        </div>
        <div class="client-info-header">
            <h1><?php echo htmlspecialchars($cliente['nome']); ?></h1>
            <div class="client-meta">
                <span class="badge <?php echo ($cliente['status'] == 'Ativo') ? 'bg-success' : 'bg-danger'; ?>" style="font-size: 14px;">
                    <i class="fas fa-circle"></i> <?php echo $cliente['status']; ?>
                </span>
                <span class="badge bg-warning" style="font-size: 14px;">
                    <i class="fas fa-signal"></i> <?php echo htmlspecialchars($cliente['operadora']); ?>
                </span>
                <span style="color: var(--text-light); margin-left: 15px;">
                    <i class="fas fa-calendar"></i> Cliente desde <?php echo date('d/m/Y', strtotime($cliente['data_cadastro'])); ?>
                </span>
            </div>
        </div>
    </div>
    <div class="client-actions">
        <a href="index.php?page=editar_cliente&id=<?php echo $cliente['id']; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Editar
        </a>
        <a href="index.php?page=financeiro_clientes&id=<?php echo $cliente['id']; ?>" class="btn btn-success">
            <i class="fas fa-dollar-sign"></i> Financeiro
        </a>
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="fas fa-print"></i> Imprimir
        </button>
        <a href="index.php?page=clientes" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<!-- Cards de Métricas -->
<div class="metrics-grid">
    <div class="metric-card primary">
        <div class="metric-icon">
            <i class="fas fa-hashtag"></i>
        </div>
        <div class="metric-content">
            <h4>ID do Cliente</h4>
            <div class="metric-value">#<?php echo $cliente['id']; ?></div>
        </div>
    </div>
    
    <div class="metric-card success">
        <div class="metric-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="metric-content">
            <h4>Total de Serviços</h4>
            <div class="metric-value"><?php echo number_format($totalServicos); ?></div>
        </div>
    </div>
    
    <div class="metric-card success">
        <div class="metric-icon">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="metric-content">
            <h4>Total Pago</h4>
            <div class="metric-value">R$ <?php echo number_format($totalPago, 2, ',', '.'); ?></div>
        </div>
    </div>
    
    <div class="metric-card warning">
        <div class="metric-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="metric-content">
            <h4>Total Pendente</h4>
            <div class="metric-value">R$ <?php echo number_format($totalPendente, 2, ',', '.'); ?></div>
        </div>
    </div>
    
    <div class="metric-card danger">
        <div class="metric-icon">
            <i class="fas fa-times-circle"></i>
        </div>
        <div class="metric-content">
            <h4>Total Cancelado</h4>
            <div class="metric-value">R$ <?php echo number_format($totalCancelado, 2, ',', '.'); ?></div>
        </div>
    </div>
</div>

<div class="content-grid">
    <!-- Informações Pessoais -->
    <div class="card card-large">
        <div class="card-header">
            <h3><i class="fas fa-user"></i> Informações Pessoais</h3>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <label><i class="fas fa-user"></i> Nome Completo</label>
                    <div class="info-value"><?php echo htmlspecialchars($cliente['nome']); ?></div>
                </div>
                
                <div class="info-item">
                    <label><i class="fas fa-id-card"></i> CPF</label>
                    <div class="info-value"><?php echo htmlspecialchars($cliente['cpf']); ?></div>
                </div>
                
                <div class="info-item">
                    <label><i class="fas fa-phone"></i> Contato</label>
                    <div class="info-value">
                        <?php if($cliente['contato']): ?>
                            <a href="tel:<?php echo preg_replace('/[^0-9]/', '', $cliente['contato']); ?>">
                                <?php echo htmlspecialchars($cliente['contato']); ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">Não informado</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <label><i class="fas fa-envelope"></i> E-mail</label>
                    <div class="info-value">
                        <?php if($cliente['email']): ?>
                            <a href="mailto:<?php echo htmlspecialchars($cliente['email']); ?>">
                                <?php echo htmlspecialchars($cliente['email']); ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">Não informado</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <label><i class="fas fa-female"></i> Nome da Mãe</label>
                    <div class="info-value">
                        <?php echo $cliente['nome_mae'] ? htmlspecialchars($cliente['nome_mae']) : '<span class="text-muted">Não informado</span>'; ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <label><i class="fas fa-user-tie"></i> Vendedor</label>
                    <div class="info-value">
                        <?php echo $cliente['vendedor'] ? htmlspecialchars($cliente['vendedor']) : '<span class="text-muted">Não informado</span>'; ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <label><i class="fas fa-headset"></i> Consultor</label>
                    <div class="info-value">
                        <?php echo $cliente['consultor'] ? htmlspecialchars($cliente['consultor']) : '<span class="text-muted">Não informado</span>'; ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <label><i class="fas fa-calendar"></i> Data de Cadastro</label>
                    <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($cliente['data_cadastro'])); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Observações -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-sticky-note"></i> Observações</h3>
        </div>
        <div class="card-body">
            <?php if($cliente['observacoes']): ?>
                <div class="observations-text">
                    <?php echo nl2br(htmlspecialchars($cliente['observacoes'])); ?>
                </div>
            <?php else: ?>
                <p class="text-muted text-center">Nenhuma observação registrada</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="content-grid">
    <!-- Gráfico de Receita Mensal -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-line"></i> Receita Mensal</h3>
        </div>
        <div class="card-body">
            <?php if(count($receitaMensal) > 0): ?>
                <canvas id="receitaChart" height="250"></canvas>
            <?php else: ?>
                <p class="text-muted text-center">Nenhum pagamento registrado</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Últimas Tarefas -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-tasks"></i> Últimas Tarefas</h3>
            <a href="index.php?page=agenda" class="btn btn-sm btn-outline-primary">Ver Todas</a>
        </div>
        <div class="card-body">
            <?php if(count($tarefasCliente) > 0): ?>
                <div class="task-list">
                    <?php foreach($tarefasCliente as $tarefa): ?>
                        <div class="task-item">
                            <div class="task-priority <?php echo strtolower($tarefa['prioridade']); ?>"></div>
                            <div class="task-content">
                                <div class="task-title"><?php echo htmlspecialchars($tarefa['titulo']); ?></div>
                                <div class="task-meta">
                                    <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($tarefa['data_inicio'])); ?></span>
                                    <span class="badge <?php 
                                        echo $tarefa['status'] == 'Concluido' ? 'bg-success' : 
                                            ($tarefa['status'] == 'Pendente' ? 'bg-warning' : 'bg-danger');
                                    ?>">
                                        <?php echo $tarefa['status']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted text-center">Nenhuma tarefa registrada</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Últimos Lançamentos Financeiros -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-receipt"></i> Últimos Lançamentos Financeiros</h3>
        <a href="index.php?page=financeiro_clientes&id=<?php echo $cliente['id']; ?>" class="btn btn-sm btn-primary">
            Ver Todos <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if(count($ultimosLancamentos) > 0): ?>
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th>Vencimento</th>
                        <th>Pagamento</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($ultimosLancamentos as $f): ?>
                    <tr>
                        <td><?php echo $f['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($f['descricao']); ?></strong></td>
                        <td class="text-success">R$ <?php echo number_format($f['valor'], 2, ',', '.'); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($f['data_vencimento'])); ?></td>
                        <td><?php echo $f['data_pagamento'] ? date('d/m/Y', strtotime($f['data_pagamento'])) : '-'; ?></td>
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
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state" style="padding: 40px;">
                <i class="fas fa-inbox" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 15px;"></i>
                <p style="color: #64748b; margin: 0;">Nenhum lançamento financeiro encontrado</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- CSS Avançado -->
<style>
.client-header {
    background: linear-gradient(135deg, #272c42 0%, #282750 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.client-header-content {
    display: flex;
    align-items: center;
    gap: 20px;
}

.client-avatar-large {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: 700;
    border: 3px solid rgba(255, 255, 255, 0.3);
}

.client-info-header h1 {
    margin: 0 0 10px 0;
    font-size: 2rem;
    font-weight: 700;
}

.client-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.client-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.metric-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s;
}

.metric-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.metric-card.primary { border-left: 4px solid #3b82f6; }
.metric-card.success { border-left: 4px solid #10b981; }
.metric-card.warning { border-left: 4px solid #f59e0b; }
.metric-card.danger { border-left: 4px solid #ef4444; }

.metric-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.metric-card.primary .metric-icon { background: #eff6ff; color: #3b82f6; }
.metric-card.success .metric-icon { background: #ecfdf5; color: #10b981; }
.metric-card.warning .metric-icon { background: #fffbeb; color: #f59e0b; }
.metric-card.danger .metric-icon { background: #fef2f2; color: #ef4444; }

.metric-content h4 {
    margin: 0 0 5px 0;
    font-size: 13px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.metric-value {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
}

.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 30px;
}

.card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}

.card-large {
    grid-column: span 2;
}

.card-header {
    padding: 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    margin: 0;
    font-size: 16px;
    color: #1e293b;
}

.card-body {
    padding: 20px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.info-item label {
    display: block;
    font-size: 13px;
    color: #64748b;
    margin-bottom: 5px;
    font-weight: 500;
}

.info-item label i {
    margin-right: 5px;
    color: #3b82f6;
}

.info-value {
    font-size: 15px;
    color: #1e293b;
    font-weight: 600;
}

.info-value a {
    color: #3b82f6;
    text-decoration: none;
}

.info-value a:hover {
    text-decoration: underline;
}

.observations-text {
    background: #f8fafc;
    padding: 15px;
    border-radius: 8px;
    border-left: 4px solid #3b82f6;
    line-height: 1.6;
    color: #475569;
}

.task-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.task-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
    transition: all 0.2s;
}

.task-item:hover {
    background: #f1f5f9;
}

.task-priority {
    width: 4px;
    height: 40px;
    border-radius: 2px;
}

.task-priority.urgente { background: #ef4444; }
.task-priority.alta { background: #f59e0b; }
.task-priority.media { background: #3b82f6; }
.task-priority.baixa { background: #10b981; }

.task-content {
    flex: 1;
}

.task-title {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 5px;
}

.task-meta {
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 13px;
    color: #64748b;
}

.table-custom {
    width: 100%;
    border-collapse: collapse;
}

.table-custom th,
.table-custom td {
    padding: 12px 20px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.table-custom th {
    background: #f8fafc;
    font-weight: 600;
    color: #64748b;
    font-size: 13px;
    text-transform: uppercase;
}

.table-custom tr:hover {
    background: #f8fafc;
}

.empty-state {
    text-align: center;
    padding: 40px;
}

.text-muted {
    color: #94a3b8;
}

.text-center {
    text-align: center;
}

@media (max-width: 768px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
    
    .card-large {
        grid-column: span 1;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .client-header {
        flex-direction: column;
        text-align: center;
    }
    
    .client-header-content {
        flex-direction: column;
    }
    
    .client-actions {
        justify-content: center;
    }
}

@media print {
    .client-actions, .btn {
        display: none !important;
    }
    
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        page-break-inside: avoid;
    }
}
</style>

<!-- Scripts -->
<?php if(count($receitaMensal) > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const receitaCtx = document.getElementById('receitaChart').getContext('2d');
new Chart(receitaCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_map(function($r) {
            $meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
            $data = DateTime::createFromFormat('Y-m', $r['mes']);
            return $meses[(int)$data->format('n') - 1] . '/' . $data->format('y');
        }, $receitaMensal)); ?>,
        datasets: [{
            label: 'Receita (R$)',
            data: <?php echo json_encode(array_column($receitaMensal, 'total')); ?>,
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderColor: 'rgba(59, 130, 246, 1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: 'rgba(59, 130, 246, 1)',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'R$ ' + context.parsed.y.toFixed(2).replace('.', ',');
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'R$ ' + value.toLocaleString('pt-BR');
                    }
                }
            }
        }
    }
});
</script>
<?php endif; ?>
