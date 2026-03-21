<?php
require_once 'config/database.php';
require_once 'includes/verificar_sessao.php';

$database = new Database();
$conn = $database->getConnection();

$cliente_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Dados do cliente
$stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$cliente_id]);
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

// Lançamentos financeiros
$stmt = $conn->prepare("SELECT * FROM financeiro WHERE cliente_id = ? ORDER BY data_vencimento DESC");
$stmt->execute([$cliente_id]);
$financeiro = $stmt->fetchAll();

// Totais
$stmt = $conn->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro WHERE cliente_id = ? AND status = 'Pago'");
$stmt->execute([$cliente_id]);
$totalPago = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro WHERE cliente_id = ? AND status = 'Pendente'");
$stmt->execute([$cliente_id]);
$totalPendente = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro WHERE cliente_id = ? AND status = 'Cancelado'");
$stmt->execute([$cliente_id]);
$totalCancelado = $stmt->fetch()['total'];
?>

<div class="page-header">
    <div>
        <h2><i class="fas fa-dollar-sign"></i> Financeiro do Cliente</h2>
        <p style="color: var(--text-light); margin-top: 5px;">
            <?php echo htmlspecialchars($cliente['nome']); ?> - <?php echo htmlspecialchars($cliente['operadora']); ?>
        </p>
    </div>
    <div>
        <a href="index.php?page=clientes" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
        <button onclick="abrirModalFinanceiro()" class="btn btn-primary">
            <i class="fas fa-plus"></i> Novo Lançamento
        </button>
    </div>
</div>

<div class="cards-grid">
    <div class="card success">
        <h3><i class="fas fa-check-circle"></i> Total Pago</h3>
        <div class="value" id="totalPago">R$ <?php echo number_format($totalPago, 2, ',', '.'); ?></div>
    </div>
    <div class="card warning">
        <h3><i class="fas fa-clock"></i> Total Pendente</h3>
        <div class="value" id="totalPendente">R$ <?php echo number_format($totalPendente, 2, ',', '.'); ?></div>
    </div>
    <div class="card danger">
        <h3><i class="fas fa-times-circle"></i> Total Cancelado</h3>
        <div class="value" id="totalCancelado">R$ <?php echo number_format($totalCancelado, 2, ',', '.'); ?></div>
    </div>
</div>

<div class="table-container">
    <div class="table-header">
        <h3>Lançamentos Financeiros</h3>
        <button onclick="atualizarFinanceiro()" class="btn btn-sm btn-primary">
            <i class="fas fa-sync-alt"></i> Atualizar
        </button>
    </div>
    <table id="tabelaFinanceiro">
        <thead>
            <tr>
                <th>ID</th>
                <th>Descrição</th>
                <th>Valor</th>
                <th>Vencimento</th>
                <th>Pagamento</th>
                <th>Forma Pgto</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody id="tbodyFinanceiro">
            <?php if(count($financeiro) > 0): ?>
                <?php foreach($financeiro as $f): ?>
                <tr>
                    <td><?php echo $f['id']; ?></td>
                    <td><?php echo htmlspecialchars($f['descricao']); ?></td>
                    <td>R$ <?php echo number_format($f['valor'], 2, ',', '.'); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($f['data_vencimento'])); ?></td>
                    <td><?php echo $f['data_pagamento'] ? date('d/m/Y H:i', strtotime($f['data_pagamento'])) : '-'; ?></td>
                    <td>
                        <?php if(isset($f['forma_pagamento']) && $f['forma_pagamento']): ?>
                        <span class="badge" style="background-color: #e0e7ff; color: #3730a3;">
                            <?php
                            $icones = [
                                'Pix' => 'fa-qrcode',
                                'Boleto' => 'fa-barcode',
                                'Especie' => 'fa-money-bill-wave',
                                'Permuta' => 'fa-exchange-alt'
                            ];
                            $icone = $icones[$f['forma_pagamento']] ?? 'fa-credit-card';
                            ?>
                            <i class="fas <?php echo $icone; ?>"></i> <?php echo htmlspecialchars($f['forma_pagamento']); ?>
                        </span>
                        <?php else: ?>
                        <span class="badge bg-warning">Não informado</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?php 
                            echo ($f['status'] == 'Pago') ? 'bg-success' : 
                                 (($f['status'] == 'Cancelado') ? 'bg-danger' : 'bg-warning'); 
                        ?>" id="status_<?php echo $f['id']; ?>">
                            <?php echo $f['status']; ?>
                        </span>
                    </td>
                    <td>
                        <button onclick="editarFinanceiro(<?php echo $f['id']; ?>)" 
                                class="btn btn-sm btn-primary" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deletarFinanceiro(<?php echo $f['id']; ?>)" 
                                class="btn btn-sm btn-danger" title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">Nenhum lançamento encontrado</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Financeiro -->
<div id="modalFinanceiro" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Novo Lançamento Financeiro</h3>
            <span class="close" onclick="fecharModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formFinanceiro">
                <input type="hidden" id="fin_id" name="id">
                <input type="hidden" id="fin_cliente_id" name="cliente_id" value="<?php echo $cliente_id; ?>">
                
                <div class="form-group">
                    <label>Descrição do Serviço *</label>
                    <input type="text" id="fin_descricao" name="descricao" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Valor (R$) *</label>
                    <input type="number" id="fin_valor" name="valor" class="form-control" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Data de Vencimento *</label>
                    <input type="date" id="fin_vencimento" name="data_vencimento" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Data de Pagamento</label>
                    <input type="date" id="fin_pagamento" name="data_pagamento" class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Forma de Pagamento *</label>
                    <select id="fin_forma_pagamento" name="forma_pagamento" class="form-control" required>
                        <option value="">Selecione...</option>
                        <option value="Pix">💠 Pix</option>
                        <option value="Boleto">📄 Boleto</option>
                        <option value="Especie">💵 Espécie (Dinheiro)</option>
                        <option value="Permuta">🔄 Permuta</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Status *</label>
                    <select id="fin_status" name="status" class="form-control" required>
                        <option value="Pendente">Pendente</option>
                        <option value="Pago">Pago</option>
                        <option value="Cancelado">Cancelado</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="fecharModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Definir o cliente_id
document.addEventListener('DOMContentLoaded', function() {
    const clienteIdField = document.getElementById('fin_cliente_id');
    if (clienteIdField) {
        clienteIdField.value = '<?php echo $cliente_id; ?>';
    }
});

// Abrir Modal Financeiro
function abrirModalFinanceiro() {
    document.getElementById('modalTitle').innerText = 'Novo Lançamento Financeiro';
    document.getElementById('fin_id').value = '';
    document.getElementById('formFinanceiro').reset();
    document.getElementById('modalFinanceiro').style.display = 'block';
}

// Fechar Modal
function fecharModal() {
    document.getElementById('modalFinanceiro').style.display = 'none';
    document.getElementById('formFinanceiro').reset();
}

// Salvar Financeiro
document.getElementById('formFinanceiro').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    Swal.fire({
        title: 'Salvando...',
        text: 'Aguarde enquanto salvamos os dados.',
        allowOutsideClick: false,
        didOpen: function() {
            Swal.showLoading();
        }
    });
    
    fetch('actions/salvar_financeiro.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json' 
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(res => {
        Swal.close();
        if (res.status === 'success') {
            fecharModal();
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: res.message,
                timer: 2000,
                showConfirmButton: false
            }).then(function() {
                atualizarFinanceiro();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: res.message
            });
        }
    })
    .catch(error => {
        Swal.close();
        console.error('Erro:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: 'Erro na comunicação com o servidor'
        });
    });
});

// Atualizar Financeiro em tempo real
function atualizarFinanceiro() {
    fetch('actions/atualizar_financeiro_cliente.php?id=<?php echo $cliente_id; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('totalPago').innerText = 'R$ ' + data.totalPago;
                document.getElementById('totalPendente').innerText = 'R$ ' + data.totalPendente;
                document.getElementById('totalCancelado').innerText = 'R$ ' + data.totalCancelado;
                
                // Atualizar tabela
                let html = '';
                if (data.financeiro.length > 0) {
                    data.financeiro.forEach(function(f) {
                        let badgeClass = f.status === 'Pago' ? 'bg-success' : (f.status === 'Cancelado' ? 'bg-danger' : 'bg-warning');
                        let formaPgto = f.forma_pagamento ? '<span class="badge" style="background-color: #e0e7ff; color: #3730a3;"><i class="fas fa-credit-card"></i> ' + f.forma_pagamento + '</span>' : '<span class="badge bg-warning">-</span>';
                        let dataPagamento = f.data_pagamento ? new Date(f.data_pagamento).toLocaleDateString('pt-BR') : '-';
                        
                        html += '<tr>';
                        html += '<td>' + f.id + '</td>';
                        html += '<td>' + f.descricao + '</td>';
                        html += '<td>R$ ' + parseFloat(f.valor).toFixed(2).replace('.', ',') + '</td>';
                        html += '<td>' + new Date(f.data_vencimento).toLocaleDateString('pt-BR') + '</td>';
                        html += '<td>' + dataPagamento + '</td>';
                        html += '<td>' + formaPgto + '</td>';
                        html += '<td><span class="badge ' + badgeClass + '">' + f.status + '</span></td>';
                        html += '<td>';
                        html += '<button onclick="editarFinanceiro(' + f.id + ')" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></button> ';
                        html += '<button onclick="deletarFinanceiro(' + f.id + ')" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>';
                        html += '</td>';
                        html += '</tr>';
                    });
                } else {
                    html = '<tr><td colspan="8" class="text-center">Nenhum lançamento encontrado</td></tr>';
                }
                document.getElementById('tbodyFinanceiro').innerHTML = html;
                
                Swal.fire({
                    icon: 'success',
                    title: 'Atualizado!',
                    text: 'Dados atualizados com sucesso',
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
setInterval(atualizarFinanceiro, 30000);

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const modal = document.getElementById('modalFinanceiro');
    if (event.target == modal) {
        fecharModal();
    }
};
</script>