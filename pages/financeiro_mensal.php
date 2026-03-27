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

// Buscar todos os clientes para o select
$stmt = $conn->query("SELECT id, nome FROM clientes WHERE status = 'Ativo' ORDER BY nome");
$clientes = $stmt->fetchAll();
?>
<div class="page-header">
    <h2><i class="fas fa-chart-line"></i> Financeiro Mensal</h2>
    <div style="display: flex; gap: 10px;">
        <button onclick="abrirModalLancamento()" class="btn btn-primary">
            <i class="fas fa-plus"></i> Novo Lançamento
        </button>
        <button onclick="window.location.reload()" class="btn btn-secondary">
            <i class="fas fa-sync-alt"></i> Atualizar
        </button>
    </div>
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
                            <button onclick="abrirModalLancamento()" class="btn btn-primary" style="margin-top: 15px;">
                                <i class="fas fa-plus"></i> Adicionar Primeiro Lançamento
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal para Novo Lançamento -->
<div id="modalLancamento" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle"><i class="fas fa-plus-circle"></i> Novo Lançamento Financeiro</h3>
            <span class="close" onclick="fecharModalLancamento()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formLancamento">
                <input type="hidden" id="lancamento_id" name="id">
                
                <div class="form-group">
                    <label>Cliente * <i class="fas fa-user" style="color: var(--primary-color);"></i></label>
                    <select id="cliente_id" name="cliente_id" class="form-control" required>
                        <option value="">Selecione o cliente...</option>
                        <?php foreach($clientes as $cliente): ?>
                            <option value="<?php echo $cliente['id']; ?>">
                                <?php echo htmlspecialchars($cliente['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Descrição do Serviço * <i class="fas fa-align-left" style="color: var(--primary-color);"></i></label>
                    <input type="text" id="descricao" name="descricao" class="form-control" 
                           placeholder="Ex: Mensalidade, Instalação, Manutenção..." required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Valor (R$) * <i class="fas fa-dollar-sign" style="color: var(--primary-color);"></i></label>
                        <input type="number" id="valor" name="valor" class="form-control" 
                               step="0.01" min="0.01" placeholder="0,00" required>
                    </div>

                    <div class="form-group">
                        <label>Forma de Pagamento * <i class="fas fa-credit-card" style="color: var(--primary-color);"></i></label>
                        <select id="forma_pagamento" name="forma_pagamento" class="form-control" required>
                            <option value="">Selecione...</option>
                            <option value="Pix">💠 Pix</option>
                            <option value="Boleto">📄 Boleto</option>
                            <option value="Especie">💵 Espécie</option>
                            <option value="Cartao Credito">💳 Cartão de Crédito</option>
                            <option value="Cartao Debito">💳 Cartão de Débito</option>
                            <option value="Transferencia">🏦 Transferência</option>
                            <option value="Permuta">🔄 Permuta</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Data de Vencimento * <i class="fas fa-calendar" style="color: var(--primary-color);"></i></label>
                        <input type="date" id="data_vencimento" name="data_vencimento" 
                               class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Data de Pagamento <i class="fas fa-calendar-check" style="color: var(--primary-color);"></i></label>
                        <input type="date" id="data_pagamento" name="data_pagamento" 
                               class="form-control">
                        <small style="color: var(--text-light);">Deixe em branco se ainda não foi pago</small>
                    </div>
                </div>

                <div class="form-group">
                    <label>Status * <i class="fas fa-info-circle" style="color: var(--primary-color);"></i></label>
                    <select id="status" name="status" class="form-control" required onchange="verificarStatus()">
                        <option value="Pendente">Pendente</option>
                        <option value="Pago">Pago</option>
                        <option value="Cancelado">Cancelado</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Lançamento
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="fecharModalLancamento()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Definir data de vencimento padrão como hoje
document.addEventListener('DOMContentLoaded', function() {
    const hoje = new Date().toISOString().split('T')[0];
    const dataVencimentoInput = document.getElementById('data_vencimento');
    if (dataVencimentoInput && !dataVencimentoInput.value) {
        dataVencimentoInput.value = hoje;
    }
});

// Abrir Modal de Lançamento
function abrirModalLancamento() {
    const modal = document.getElementById('modalLancamento');
    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('formLancamento');
    
    modalTitle.innerHTML = '<i class="fas fa-plus-circle"></i> Novo Lançamento Financeiro';
    form.reset();
    document.getElementById('lancamento_id').value = '';
    document.getElementById('data_vencimento').value = new Date().toISOString().split('T')[0];
    document.getElementById('status').value = 'Pendente';
    
    modal.style.display = 'block';
}

// Fechar Modal
function fecharModalLancamento() {
    const modal = document.getElementById('modalLancamento');
    modal.style.display = 'none';
    document.getElementById('formLancamento').reset();
}

// Verificar status e habilitar/desabilitar data de pagamento
function verificarStatus() {
    const status = document.getElementById('status').value;
    const dataPagamentoInput = document.getElementById('data_pagamento');
    
    if (status === 'Pago' && !dataPagamentoInput.value) {
        dataPagamentoInput.value = new Date().toISOString().split('T')[0];
    } else if (status === 'Pendente' || status === 'Cancelado') {
        dataPagamentoInput.value = '';
    }
}

// Salvar Lançamento
document.getElementById('formLancamento').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    // Validações
    if (!data.cliente_id) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Selecione um cliente!'
        });
        return;
    }
    
    if (!data.descricao || data.descricao.trim() === '') {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Preencha a descrição do serviço!'
        });
        return;
    }
    
    if (!data.valor || parseFloat(data.valor) <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'O valor deve ser maior que zero!'
        });
        return;
    }
    
    if (!data.data_vencimento) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Selecione a data de vencimento!'
        });
        return;
    }
    
    // Mostrar loading
    Swal.fire({
        title: 'Salvando...',
        text: 'Aguarde enquanto salvamos o lançamento.',
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
            fecharModalLancamento();
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: res.message,
                timer: 2000,
                showConfirmButton: false
            }).then(function() {
                window.location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: res.message || 'Ocorreu um erro ao salvar o lançamento.'
            });
        }
    })
    .catch(error => {
        Swal.close();
        console.error('Erro:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: 'Erro na comunicação com o servidor.'
        });
    });
});

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const modal = document.getElementById('modalLancamento');
    if (event.target == modal) {
        fecharModalLancamento();
    }
};

// Fechar modal com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('modalLancamento');
        if (modal && modal.style.display === 'block') {
            fecharModalLancamento();
        }
    }
});
</script>

<style>
/* Estilo adicional para o modal */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    animation: fadeIn 0.3s;
}

.modal-content {
    background-color: #ffffff;
    margin: 3% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 700px;
    animation: slideIn 0.3s;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #1e293b;
    font-size: 1.3rem;
}

.close {
    font-size: 1.5rem;
    cursor: pointer;
    color: #64748b;
    transition: all 0.2s;
}

.close:hover {
    color: #ef4444;
}

.modal-body {
    padding: 25px;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>
