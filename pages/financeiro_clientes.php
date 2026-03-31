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

// Lançamentos financeiros (apenas parcelas pai ou únicos)
$stmt = $conn->prepare("SELECT * FROM financeiro 
    WHERE cliente_id = ? AND (parcela_pai_id IS NULL OR parcela_atual = 1)
    ORDER BY data_vencimento DESC");
$stmt->execute([$cliente_id]);
$financeiro = $stmt->fetchAll();

// Totais
$stmt = $conn->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro 
    WHERE cliente_id = ? AND status = 'Pago'");
$stmt->execute([$cliente_id]);
$totalPago = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro 
    WHERE cliente_id = ? AND status = 'Pendente'");
$stmt->execute([$cliente_id]);
$totalPendente = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro 
    WHERE cliente_id = ? AND status = 'Cancelado'");
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
                <th>Parcelas</th>
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
                    <?php
                    // Verificar se é parcelado
                    $eh_parcelado = ($f['total_parcelas'] > 1 || $f['tipo_lancamento'] == 'parcelado');
                    $total_parcelas = $f['total_parcelas'] ?? 1;
                    
                    // Contar quantas parcelas já foram pagas
                    if ($eh_parcelado) {
                        $stmt_parc = $conn->prepare("SELECT COUNT(*) as total, 
                            SUM(CASE WHEN status = 'Pago' THEN 1 ELSE 0 END) as pagas
                            FROM financeiro WHERE parcela_pai_id = ? OR id = ?");
                        $stmt_parc->execute([$f['id'], $f['id']]);
                        $info_parcelas = $stmt_parc->fetch();
                        $parcelas_pagas = $info_parcelas['pagas'];
                        $total_parc = $info_parcelas['total'];
                    }
                    ?>
                <tr>
                    <td><?php echo $f['id']; ?></td>
                    <td>
                        <?php echo htmlspecialchars($f['descricao']); ?>
                        <?php if($eh_parcelado): ?>
                            <br><small style="color: var(--text-light);">
                                <i class="fas fa-layer-group"></i> 
                                <?php echo $parcelas_pagas; ?>/<?php echo $total_parc; ?> pagas
                            </small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($eh_parcelado): ?>
                            <span class="badge" style="background: #3b82f6; color: white;">
                                <i class="fas fa-layer-group"></i> <?php echo $total_parcelas; ?>x
                            </span>
                            <br>
                            <button onclick="verParcelas(<?php echo $f['id']; ?>)" 
                                    class="btn btn-sm btn-link" style="margin-top: 5px;">
                                <i class="fas fa-eye"></i> Ver Parcelas
                            </button>
                        <?php else: ?>
                            <span class="badge bg-success">À vista</span>
                        <?php endif; ?>
                    </td>
                    <td>R$ <?php echo number_format($f['valor'], 2, ',', '.'); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($f['data_vencimento'])); ?></td>
                    <td><?php echo $f['data_pagamento'] ? date('d/m/Y H:i', strtotime($f['data_pagamento'])) : '-'; ?></td>
                    <td>
                        <?php if(isset($f['forma_pagamento']) && $f['forma_pagamento']): ?>
                            <span class="badge" style="background-color: #e0e7ff; color: #3730a3;">
                                <i class="fas fa-credit-card"></i> <?php echo htmlspecialchars($f['forma_pagamento']); ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-warning">-</span>
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
                    <td colspan="9" class="text-center">Nenhum lançamento encontrado</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Financeiro -->
<div id="modalFinanceiro" class="modal">
    <div class="modal-content" style="max-width: 700px;">
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
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Valor Total (R$) *</label>
                        <input type="number" id="fin_valor" name="valor" class="form-control" 
                               step="0.01" min="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Tipo de Lançamento *</label>
                        <select id="fin_tipo_lancamento" name="tipo_lancamento" class="form-control" required>
                            <option value="unico">À Vista</option>
                            <option value="parcelado">Parcelado</option>
                        </select>
                    </div>
                </div>
                
                <div id="div_parcelas" style="display: none;">
                    <div class="form-group">
                        <label>Número de Parcelas *</label>
                        <select id="fin_total_parcelas" name="total_parcelas" class="form-control">
                            <?php for($i = 2; $i <= 24; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?>x</option>
                            <?php endfor; ?>
                        </select>
                        <small style="color: var(--text-light);">
                            Valor da parcela: R$ <span id="valor_parcela">0,00</span>
                        </small>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Data de Vencimento da 1ª Parcela *</label>
                        <input type="date" id="fin_vencimento" name="data_vencimento" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Data de Pagamento</label>
                        <input type="date" id="fin_pagamento" name="data_pagamento" class="form-control">
                        <small style="color: var(--text-light);">Deixe em branco se pendente</small>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Forma de Pagamento *</label>
                        <select id="fin_forma_pagamento" name="forma_pagamento" class="form-control" required>
                            <option value="">Selecione...</option>
                            <option value="Pix">💠 Pix</option>
                            <option value="Boleto">📄 Boleto</option>
                            <option value="Especie">💵 Espécie (Dinheiro)</option>
                            <option value="Cartao Credito">💳 Cartão de Crédito</option>
                            <option value="Cartao Debito">💳 Cartão de Débito</option>
                            <option value="Transferencia">🏦 Transferência</option>
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

<script>
// Mostrar/esconder campo de parcelas
document.getElementById('fin_tipo_lancamento').addEventListener('change', function() {
    const divParcelas = document.getElementById('div_parcelas');
    if (this.value === 'parcelado') {
        divParcelas.style.display = 'block';
        calcularValorParcela();
    } else {
        divParcelas.style.display = 'none';
    }
});

// Calcular valor da parcela
document.getElementById('fin_valor').addEventListener('input', calcularValorParcela);
document.getElementById('fin_total_parcelas').addEventListener('change', calcularValorParcela);

function calcularValorParcela() {
    const valor = parseFloat(document.getElementById('fin_valor').value) || 0;
    const parcelas = parseInt(document.getElementById('fin_total_parcelas').value) || 1;
    const valorParcela = valor / parcelas;
    document.getElementById('valor_parcela').textContent = valorParcela.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Abrir Modal Financeiro
function abrirModalFinanceiro() {
    document.getElementById('modalTitle').innerText = 'Novo Lançamento Financeiro';
    document.getElementById('fin_id').value = '';
    document.getElementById('formFinanceiro').reset();
    document.getElementById('div_parcelas').style.display = 'none';
    document.getElementById('modalFinanceiro').style.display = 'block';
}

// Fechar Modal
function fecharModal() {
    document.getElementById('modalFinanceiro').style.display = 'none';
    document.getElementById('formFinanceiro').reset();
}

// Fechar Modal Parcelas
function fecharModalParcelas() {
    document.getElementById('modalParcelas').style.display = 'none';
}

// Ver Parcelas
function verParcelas(id) {
    fetch('actions/ver_parcelas.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                let html = '<table class="table"><thead><tr>';
                html += '<th>Parcela</th><th>Vencimento</th><th>Valor</th><th>Status</th><th>Pagamento</th>';
                html += '</tr></thead><tbody>';
                
                data.parcelas.forEach(function(p) {
                    const statusClass = p.status === 'Pago' ? 'bg-success' : 
                                       (p.status === 'Cancelado' ? 'bg-danger' : 'bg-warning');
                    html += '<tr>';
                    html += '<td>' + p.parcela_atual + '/' + p.total_parcelas + '</td>';
                    html += '<td>' + new Date(p.data_vencimento).toLocaleDateString('pt-BR') + '</td>';
                    html += '<td>R$ ' + parseFloat(p.valor).toFixed(2).replace('.', ',') + '</td>';
                    html += '<td><span class="badge ' + statusClass + '">' + p.status + '</span></td>';
                    html += '<td>' + (p.data_pagamento ? new Date(p.data_pagamento).toLocaleDateString('pt-BR') : '-') + '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                document.getElementById('conteudoParcelas').innerHTML = html;
                document.getElementById('modalParcelas').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            Swal.fire('Erro!', 'Não foi possível carregar as parcelas.', 'error');
        });
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
                // Recarregar página para atualizar tabela com parcelas
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Erro:', error);
        });
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const modal = document.getElementById('modalFinanceiro');
    const modalParc = document.getElementById('modalParcelas');
    if (event.target == modal) {
        fecharModal();
    }
    if (event.target == modalParc) {
        fecharModalParcelas();
    }
};
</script>
