<?php
require_once 'config/database.php';
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

// Total de serviços
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM financeiro WHERE cliente_id = ?");
$stmt->execute([$id]);
$totalServicos = $stmt->fetch()['total'];

// Valor total pago
$stmt = $conn->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro WHERE cliente_id = ? AND status = 'Pago'");
$stmt->execute([$id]);
$totalPago = $stmt->fetch()['total'];

// Valor total pendente
$stmt = $conn->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro WHERE cliente_id = ? AND status = 'Pendente'");
$stmt->execute([$id]);
$totalPendente = $stmt->fetch()['total'];

// Últimos lançamentos
$stmt = $conn->prepare("SELECT * FROM financeiro WHERE cliente_id = ? ORDER BY data_vencimento DESC LIMIT 5");
$stmt->execute([$id]);
$ultimosLancamentos = $stmt->fetchAll();
?>

<div class="page-header">
    <div>
        <h2><i class="fas fa-user-circle"></i> Detalhes do Cliente</h2>
        <p style="color: var(--text-light); margin-top: 5px;">
            Visualizando informações completas do cliente
        </p>
    </div>
    <div>
        <a href="index.php?page=clientes" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <a href="index.php?page=editar_cliente&id=<?php echo $cliente['id']; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Editar
        </a>
        <a href="index.php?page=financeiro_clientes&id=<?php echo $cliente['id']; ?>" class="btn btn-success">
            <i class="fas fa-dollar-sign"></i> Financeiro
        </a>
    </div>
</div>

<!-- Cards de Resumo -->
<div class="cards-grid">
    <div class="card">
        <h3><i class="fas fa-hashtag"></i> ID do Cliente</h3>
        <div class="value"><?php echo $cliente['id']; ?></div>
    </div>
    <div class="card success">
        <h3><i class="fas fa-check-circle"></i> Status</h3>
        <div class="value">
            <span class="badge <?php echo ($cliente['status'] == 'Ativo') ? 'bg-success' : 'bg-danger'; ?>" style="font-size: 1rem;">
                <?php echo $cliente['status']; ?>
            </span>
        </div>
    </div>
    <div class="card">
        <h3><i class="fas fa-briefcase"></i> Total de Serviços</h3>
        <div class="value"><?php echo $totalServicos; ?></div>
    </div>
    <div class="card success">
        <h3><i class="fas fa-dollar-sign"></i> Total Pago</h3>
        <div class="value">R$ <?php echo number_format($totalPago, 2, ',', '.'); ?></div>
    </div>
    <div class="card warning">
        <h3><i class="fas fa-clock"></i> Total Pendente</h3>
        <div class="value">R$ <?php echo number_format($totalPendente, 2, ',', '.'); ?></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    <!-- Informações Pessoais -->
    <div class="table-container">
        <div class="table-header">
            <h3><i class="fas fa-user"></i> Informações Pessoais</h3>
        </div>
        <div style="padding: 20px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div>
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Nome Completo</label>
                        <p style="font-weight: 600; color: var(--text-color); font-size: 1.1rem;"><?php echo htmlspecialchars($cliente['nome']); ?></p>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> CPF</label>
                        <p style="font-weight: 600; color: var(--text-color);"><?php echo htmlspecialchars($cliente['cpf']); ?></p>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Contato</label>
                        <p style="font-weight: 600; color: var(--text-color);"><?php echo htmlspecialchars($cliente['contato']); ?></p>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> E-mail</label>
                        <p style="font-weight: 600; color: var(--text-color);"><?php echo htmlspecialchars($cliente['email']); ?></p>
                    </div>
                </div>
                <div>
                    <div class="form-group">
                        <label><i class="fas fa-female"></i> Nome da Mãe</label>
                        <p style="font-weight: 600; color: var(--text-color);"><?php echo htmlspecialchars($cliente['nome_mae']); ?></p>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-signal"></i> Operadora</label>
                        <p><span class="badge bg-warning"><?php echo htmlspecialchars($cliente['operadora']); ?></span></p>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user-tie"></i> Vendedor</label>
                        <p style="font-weight: 600; color: var(--text-color);"><?php echo htmlspecialchars($cliente['vendedor']); ?></p>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-headset"></i> Consultor</label>
                        <p style="font-weight: 600; color: var(--text-color);"><?php echo htmlspecialchars($cliente['consultor']); ?></p>
                    </div>
                </div>
            </div>
            <div class="form-group" style="margin-top: 15px;">
                <label><i class="fas fa-calendar"></i> Data de Cadastro</label>
                <p style="font-weight: 600; color: var(--text-color);"><?php echo date('d/m/Y H:i', strtotime($cliente['data_cadastro'])); ?></p>
            </div>
        </div>
    </div>

    <!-- Observações -->
    <div class="table-container">
        <div class="table-header">
            <h3><i class="fas fa-sticky-note"></i> Observações</h3>
        </div>
        <div style="padding: 20px;">
            <p style="color: var(--text-color); line-height: 1.6; white-space: pre-wrap;">
                <?php echo htmlspecialchars($cliente['observacoes']); ?>
            </p>
        </div>
    </div>
</div>

<!-- Últimos Lançamentos Financeiros -->
<div class="table-container">
    <div class="table-header">
        <h3><i class="fas fa-history"></i> Últimos Lançamentos Financeiros</h3>
        <a href="index.php?page=financeiro_clientes&id=<?php echo $cliente['id']; ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-list"></i> Ver Todos
        </a>
    </div>
    <table>
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
            <?php if(count($ultimosLancamentos) > 0): ?>
                <?php foreach($ultimosLancamentos as $f): ?>
                <tr>
                    <td><?php echo $f['id']; ?></td>
                    <td><?php echo htmlspecialchars($f['descricao']); ?></td>
                    <td>R$ <?php echo number_format($f['valor'], 2, ',', '.'); ?></td>
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
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">Nenhum lançamento encontrado</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Botão de Impressão -->
<div style="margin-top: 20px; text-align: center;">
    <button onclick="window.print()" class="btn btn-secondary">
        <i class="fas fa-print"></i> Imprimir Ficha do Cliente
    </button>
</div>

<style>
@media print {
    .sidebar, .top-bar, .page-header .btn, button {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
    }
    .table-container, .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        page-break-inside: avoid;
    }
}
</style>