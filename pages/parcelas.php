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
$query = "SELECT f.*, c.nome as cliente_nome, c.operadora,
          (SELECT COUNT(*) FROM financeiro WHERE parcela_pai_id = f.id OR id = f.parcela_pai_id) as total_parcelas_rel
          FROM financeiro f
          INNER JOIN clientes c ON f.cliente_id = c.id
          WHERE f.tipo_lancamento = 'parcelado' OR f.total_parcelas > 1";

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
        <input type="hidden" name="page" value="parcelas">
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
                <a href="index.php?page=parcelas" class="btn btn-secondary" style="margin-left: 10px;">
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
                <th>Ações</th>
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
                    
                    // Verificar se é parcela pai ou filha
                    $ehParcelaPai = ($p['parcela_atual'] == 1 || $p['parcela_pai_id'] == null);
                    $totalParc = $p['total_parcelas_rel'] > 0 ? $p['total_parcelas_rel'] : $p['total_parcelas'];
                    ?>
                <tr class="<?php echo $diasAtraso > 0 ? 'bg-danger-light' : ''; ?>">
                    <td><?php echo $p['id']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($p['cliente_nome']); ?></strong>
                        <br><small style="color: var(--text-light);"><?php echo htmlspecialchars($p['operadora']); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($p['descricao']); ?></td>
                    <td>
                        <?php if($ehParcelaPai && $p['parcela_pai_id'] == null): ?>
                            <span class="badge bg-info">
                                <i class="fas fa-layer-group"></i> <?php echo $p['parcela_atual']; ?>/<?php echo $totalParc; ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-secondary">
                                <?php echo $p['parcela_atual']; ?>/<?php echo $totalParc; ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-success">
                        <strong>R$ <?php echo number_format($p['valor'], 2, ',', '.'); ?></strong>
                    </td>
                    <td>
                        <?php echo date('d/m/Y', strtotime($p['data_vencimento'])); ?>
                        <?php if($diasAtraso > 0): ?>
                            <br><small style="color: #ef4444; font-weight: 600;">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo $diasAtraso; ?> dias atraso
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
                    <td>
                        <button onclick="verParcelas(<?php echo $p['id']; ?>)" 
                                class="btn btn-sm btn-info" 
                                title="Ver Todas as Parcelas">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php if($p['status'] == 'Pendente'): ?>
                            <button onclick="marcarComoPaga(<?php echo $p['id']; ?>)" 
                                    class="btn btn-sm btn-success" 
                                    title="Marcar como Paga">
                                <i class="fas fa-check"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center">
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

<!-- Modal para Ver Parcelas -->
<div id="modalParcelas" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3><i class="fas fa-layer-group"></i> Detalhamento das Parcelas</h3>
            <span class="close" onclick="fecharModalParcelas()">&times;</span>
        </div>
        <div class="modal-body" id="conteudoParcelas">
            <!-- Conteúdo carregado via AJAX -->
        </div>
    </div>
</div>

<style>
.bg-danger-light {
    background-color: #fef2f2 !important;
}
.bg-secondary {
    background-color: #64748b !important;
    color: white !important;
}
.text-success {
    color: #10b981 !important;
}
.badge-info {
    background-color: #3b82f6 !important;
    color: white !important;
}
</style>

<script>
// Ver Parcelas
function verParcelas(id) {
    fetch('actions/ver_parcelas.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                let html = '<div style="padding: 20px;">';
                html += '<table class="table">';
                html += '<thead><tr>';
                html += '<th>Parcela</th>';
                html += '<th>Descrição</th>';
                html += '<th>Vencimento</th>';
                html += '<th>Valor</th>';
                html += '<th>Status</th>';
                html += '<th>Pagamento</th>';
                html += '</tr></thead><tbody>';
                
                data.parcelas.forEach(function(p) {
                    const statusClass = p.status === 'Pago' ? 'bg-success' : 
                                       (p.status === 'Cancelado' ? 'bg-danger' : 'bg-warning');
                    const dataPagamento = p.data_pagamento ? 
                        new Date(p.data_pagamento).toLocaleDateString('pt-BR') : '-';
                    
                    html += '<tr>';
                    html += '<td><span class="badge bg-primary">' + p.parcela_atual + '/' + p.total_parcelas + '</span></td>';
                    html += '<td>' + p.descricao + '</td>';
                    html += '<td>' + new Date(p.data_vencimento).toLocaleDateString('pt-BR') + '</td>';
                    html += '<td>R$ ' + parseFloat(p.valor).toFixed(2).replace('.', ',') + '</td>';
                    html += '<td><span class="badge ' + statusClass + '">' + p.status + '</span></td>';
                    html += '<td>' + dataPagamento + '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                html += '</div>';
                
                document.getElementById('conteudoParcelas').innerHTML = html;
                document.getElementById('modalParcelas').style.display = 'block';
            } else {
                Swal.fire('Erro!', 'Não foi possível carregar as parcelas.', 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            Swal.fire('Erro!', 'Erro na comunicação com o servidor.', 'error');
        });
}

// Marcar como Paga
function marcarComoPaga(id) {
    Swal.fire({
        title: 'Confirmar Pagamento',
        text: 'Deseja marcar esta parcela como paga?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sim, marcar como paga',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Processando...',
                text: 'Aguarde.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('actions/marcar_parcela_paga.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Erro!', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Erro:', error);
                Swal.fire('Erro!', 'Erro na comunicação com o servidor.', 'error');
            });
        }
    });
}

// Fechar Modal Parcelas
function fecharModalParcelas() {
    document.getElementById('modalParcelas').style.display = 'none';
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const modal = document.getElementById('modalParcelas');
    if (event.target == modal) {
        fecharModalParcelas();
    }
};
</script>