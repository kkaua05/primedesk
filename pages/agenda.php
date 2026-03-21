<?php
require_once 'config/database.php';
require_once 'includes/verificar_sessao.php';

$database = new Database();
$conn = $database->getConnection();

$usuario_id = $_SESSION['usuario_id'];
$mes = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
$ano = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');

// Dias do mês
$primeiro_dia = date('w', mktime(0, 0, 0, $mes, 1, $ano));
$total_dias = date('t', mktime(0, 0, 0, $mes, 1, $ano));

// Nomes dos meses
$nomes_meses = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

// Buscar tarefas do mês
$stmt = $conn->prepare("SELECT a.*, c.nome as cliente_nome 
                        FROM agenda a 
                        LEFT JOIN clientes c ON a.cliente_id = c.id 
                        WHERE a.usuario_id = ? 
                        AND MONTH(a.data_inicio) = ? 
                        AND YEAR(a.data_inicio) = ?
                        ORDER BY a.data_inicio, a.hora_inicio");
$stmt->execute([$usuario_id, $mes, $ano]);
$tarefas_mes = $stmt->fetchAll();

// Agrupar tarefas por dia
$tarefas_por_dia = [];
foreach ($tarefas_mes as $tarefa) {
    $dia = (int)date('j', strtotime($tarefa['data_inicio']));
    if (!isset($tarefas_por_dia[$dia])) {
        $tarefas_por_dia[$dia] = [];
    }
    $tarefas_por_dia[$dia][] = $tarefa;
}

// Contadores
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM agenda WHERE usuario_id = ? AND status = 'Pendente'");
$stmt->execute([$usuario_id]);
$tarefas_pendentes = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM agenda WHERE usuario_id = ? AND status = 'Concluido'");
$stmt->execute([$usuario_id]);
$tarefas_concluidas = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM agenda WHERE usuario_id = ? AND prioridade = 'Urgente' AND status != 'Concluido'");
$stmt->execute([$usuario_id]);
$tarefas_urgentes = $stmt->fetch()['total'];

// Tarefas de hoje
$hoje = date('Y-m-d');
$stmt = $conn->prepare("SELECT * FROM agenda WHERE usuario_id = ? AND data_inicio = ? ORDER BY hora_inicio");
$stmt->execute([$usuario_id, $hoje]);
$tarefas_hoje = $stmt->fetchAll();
?>

<div class="page-header">
    <div>
        <h2><i class="fas fa-calendar-alt"></i> Agenda & Tarefas</h2>
        <p style="color: var(--text-light); margin-top: 5px;">
            Gerencie suas tarefas e compromissos
        </p>
    </div>
    <div>
        <button onclick="abrirModalTarefa()" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nova Tarefa
        </button>
    </div>
</div>

<!-- Cards de Resumo -->
<div class="cards-grid">
    <div class="card">
        <h3><i class="fas fa-tasks"></i> Tarefas Pendentes</h3>
        <div class="value"><?php echo $tarefas_pendentes; ?></div>
    </div>
    <div class="card success">
        <h3><i class="fas fa-check-circle"></i> Concluídas</h3>
        <div class="value"><?php echo $tarefas_concluidas; ?></div>
    </div>
    <div class="card danger">
        <h3><i class="fas fa-exclamation-triangle"></i> Urgentes</h3>
        <div class="value"><?php echo $tarefas_urgentes; ?></div>
    </div>
    <div class="card warning">
        <h3><i class="fas fa-calendar-day"></i> Hoje</h3>
        <div class="value"><?php echo count($tarefas_hoje); ?></div>
    </div>
</div>

<!-- Tarefas de Hoje -->
<?php if(count($tarefas_hoje) > 0): ?>
<div class="table-container" style="margin-bottom: 20px;">
    <div class="table-header">
        <h3><i class="fas fa-calendar-day"></i> Tarefas de Hoje</h3>
    </div>
    <div style="padding: 15px;">
        <?php foreach($tarefas_hoje as $t): ?>
        <div style="display: flex; align-items: center; gap: 15px; padding: 12px; 
                    background: #f8fafc; border-radius: 8px; margin-bottom: 10px;
                    border-left: 4px solid <?php 
                        echo $t['prioridade'] == 'Urgente' ? '#ef4444' : 
                             ($t['prioridade'] == 'Alta' ? '#f59e0b' : 
                             ($t['prioridade'] == 'Media' ? '#3b82f6' : '#10b981')); 
                    ?>;">
            <div style="flex: 1;">
                <strong><?php echo htmlspecialchars($t['titulo']); ?></strong>
                <?php if($t['hora_inicio']): ?>
                <span style="color: var(--text-light); margin-left: 10px;">
                    <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($t['hora_inicio'])); ?>
                </span>
                <?php endif; ?>
                <?php if(isset($t['cliente_nome']) && $t['cliente_nome']): ?>
                <span style="color: var(--text-light); margin-left: 10px;">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($t['cliente_nome']); ?>
                </span>
                <?php endif; ?>
            </div>
            <span class="badge <?php 
                echo $t['status'] == 'Concluido' ? 'bg-success' : 
                     ($t['status'] == 'Pendente' ? 'bg-warning' : 'bg-danger'); 
            ?>">
                <?php echo $t['status']; ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Calendário -->
<div class="table-container">
    <div class="table-header">
        <h3>
            <i class="fas fa-calendar"></i> 
            <?php echo $nomes_meses[$mes]; ?> de <?php echo $ano; ?>
        </h3>
        <div style="display: flex; gap: 10px;">
            <a href="index.php?page=agenda&mes=<?php echo $mes > 1 ? $mes - 1 : 12; ?>&ano=<?php echo $mes > 1 ? $ano : $ano - 1; ?>" 
               class="btn btn-sm btn-secondary">
                <i class="fas fa-chevron-left"></i> Mês Anterior
            </a>
            <a href="index.php?page=agenda" class="btn btn-sm btn-primary">
                <i class="fas fa-calendar"></i> Mês Atual
            </a>
            <a href="index.php?page=agenda&mes=<?php echo $mes < 12 ? $mes + 1 : 1; ?>&ano=<?php echo $mes < 12 ? $ano : $ano + 1; ?>" 
               class="btn btn-sm btn-secondary">
                Próximo Mês <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>
    <div style="padding: 20px;">
        <div class="calendar-grid">
            <div class="calendar-header">
                <div>Dom</div>
                <div>Seg</div>
                <div>Ter</div>
                <div>Qua</div>
                <div>Qui</div>
                <div>Sex</div>
                <div>Sáb</div>
            </div>
            <div class="calendar-body">
                <?php
                // Dias vazios antes do primeiro dia
                for ($i = 0; $i < $primeiro_dia; $i++) {
                    echo '<div class="calendar-day empty"></div>';
                }
                
                // Dias do mês
                for ($dia = 1; $dia <= $total_dias; $dia++) {
                    $data_completa = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
                    $eh_hoje = ($data_completa == date('Y-m-d'));
                    $tem_tarefas = isset($tarefas_por_dia[$dia]) && count($tarefas_por_dia[$dia]) > 0;
                    
                    echo '<div class="calendar-day ' . ($eh_hoje ? 'today' : '') . '">';
                    echo '<div class="calendar-day-number">' . $dia . '</div>';
                    
                    if ($tem_tarefas) {
                        echo '<div class="calendar-tasks">';
                        $count = 0;
                        foreach ($tarefas_por_dia[$dia] as $tarefa) {
                            if ($count >= 3) {
                                echo '<div class="calendar-task-more">+' . (count($tarefas_por_dia[$dia]) - 3) . '</div>';
                                break;
                            }
                            $cor = $tarefa['prioridade'] == 'Urgente' ? '#ef4444' : 
                                   ($tarefa['prioridade'] == 'Alta' ? '#f59e0b' : 
                                   ($tarefa['prioridade'] == 'Media' ? '#3b82f6' : '#10b981'));
                            echo '<div class="calendar-task" style="background: ' . $cor . '20; border-left: 3px solid ' . $cor . ';">';
                            echo '<small>' . htmlspecialchars($tarefa['titulo']) . '</small>';
                            echo '</div>';
                            $count++;
                        }
                        echo '</div>';
                    }
                    
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Tarefa -->
<div id="modalTarefa" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3 id="modalTarefaTitle">Nova Tarefa</h3>
            <span class="close" onclick="fecharModalTarefa()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formTarefa">
                <input type="hidden" id="tarefa_id" name="id">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Título *</label>
                        <input type="text" id="tarefa_titulo" name="titulo" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Categoria</label>
                        <select id="tarefa_categoria" name="categoria" class="form-control">
                            <option value="Tarefa">Tarefa</option>
                            <option value="Reuniao">Reunião</option>
                            <option value="Ligacao">Ligação</option>
                            <option value="Email">E-mail</option>
                            <option value="Outro">Outro</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Descrição</label>
                    <textarea id="tarefa_descricao" name="descricao" class="form-control" rows="3"></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Data de Início *</label>
                        <input type="date" id="tarefa_data_inicio" name="data_inicio" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Data de Fim</label>
                        <input type="date" id="tarefa_data_fim" name="data_fim" class="form-control">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Hora de Início</label>
                        <input type="time" id="tarefa_hora_inicio" name="hora_inicio" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Hora de Fim</label>
                        <input type="time" id="tarefa_hora_fim" name="hora_fim" class="form-control">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Prioridade</label>
                        <select id="tarefa_prioridade" name="prioridade" class="form-control">
                            <option value="Baixa">Baixa</option>
                            <option value="Media" selected>Média</option>
                            <option value="Alta">Alta</option>
                            <option value="Urgente">Urgente</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select id="tarefa_status" name="status" class="form-control">
                            <option value="Pendente">Pendente</option>
                            <option value="Em Progresso">Em Progresso</option>
                            <option value="Concluido">Concluído</option>
                            <option value="Cancelado">Cancelado</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Cliente (Opcional)</label>
                        <select id="tarefa_cliente_id" name="cliente_id" class="form-control">
                            <option value="">Nenhum</option>
                            <?php
                            $stmt = $conn->query("SELECT id, nome FROM clientes ORDER BY nome");
                            while ($cliente = $stmt->fetch()) {
                                echo '<option value="' . $cliente['id'] . '">' . htmlspecialchars($cliente['nome']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="tarefa_lembrete" name="lembrete" value="1">
                        Ativar lembrete
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Tarefa
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="fecharModalTarefa()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.calendar-grid {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
}

.calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: #f1f5f9;
    border-bottom: 1px solid #e2e8f0;
}

.calendar-header div {
    padding: 12px;
    text-align: center;
    font-weight: 600;
    color: #64748b;
    font-size: 0.85rem;
}

.calendar-body {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: #ffffff;
}

.calendar-day {
    min-height: 100px;
    padding: 8px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    cursor: pointer;
    transition: all 0.2s;
}

.calendar-day:hover {
    background: #f8fafc;
}

.calendar-day.empty {
    background: #f8fafc;
    cursor: default;
}

.calendar-day.today {
    background: #eff6ff;
    border-color: #3b82f6;
}

.calendar-day-number {
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 8px;
}

.calendar-day.today .calendar-day-number {
    background: #3b82f6;
    color: white;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.calendar-tasks {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.calendar-task {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
}

.calendar-task:hover {
    opacity: 0.8;
}

.calendar-task-more {
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
}

@media (max-width: 768px) {
    .calendar-day {
        min-height: 60px;
    }
    
    .calendar-task {
        display: none;
    }
    
    .calendar-task-more {
        display: block !important;
    }
}
</style>

<script>
// Abrir Modal de Tarefa
function abrirModalTarefa(id = null) {
    const modal = document.getElementById('modalTarefa');
    const form = document.getElementById('formTarefa');
    const title = document.getElementById('modalTarefaTitle');
    
    form.reset();
    
    if (id) {
        title.innerText = 'Editar Tarefa';
        // Carregar dados da tarefa via AJAX
        fetch('actions/carregar_tarefa.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const t = data.data;
                    document.getElementById('tarefa_id').value = t.id;
                    document.getElementById('tarefa_titulo').value = t.titulo;
                    document.getElementById('tarefa_categoria').value = t.categoria;
                    document.getElementById('tarefa_descricao').value = t.descricao || '';
                    document.getElementById('tarefa_data_inicio').value = t.data_inicio;
                    document.getElementById('tarefa_data_fim').value = t.data_fim || '';
                    document.getElementById('tarefa_hora_inicio').value = t.hora_inicio || '';
                    document.getElementById('tarefa_hora_fim').value = t.hora_fim || '';
                    document.getElementById('tarefa_prioridade').value = t.prioridade;
                    document.getElementById('tarefa_status').value = t.status;
                    document.getElementById('tarefa_cliente_id').value = t.cliente_id || '';
                    document.getElementById('tarefa_lembrete').checked = t.lembrete == 1;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                Swal.fire('Erro!', 'Não foi possível carregar a tarefa.', 'error');
            });
    } else {
        title.innerText = 'Nova Tarefa';
        document.getElementById('tarefa_data_inicio').value = '<?php echo date('Y-m-d'); ?>';
    }
    
    modal.style.display = 'block';
}

// Fechar Modal de Tarefa
function fecharModalTarefa() {
    document.getElementById('modalTarefa').style.display = 'none';
    document.getElementById('formTarefa').reset();
}

// Salvar Tarefa
document.getElementById('formTarefa').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    // Definir valores padrão para campos vazios
    if (!data.lembrete) data.lembrete = '0';
    if (!data.cliente_id) data.cliente_id = '';
    if (!data.data_fim) data.data_fim = null;
    if (!data.hora_inicio) data.hora_inicio = null;
    if (!data.hora_fim) data.hora_fim = null;
    
    // Mostrar loading
    Swal.fire({
        title: 'Salvando...',
        text: 'Aguarde enquanto salvamos a tarefa.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch('actions/salvar_tarefa.php', {
        method: 'POST',
        headers: { 
            'Content-Type': 'application/json' 
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(res => {
        Swal.close();
        if (res.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: res.message,
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                fecharModalTarefa();
                window.location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: res.message || 'Erro ao salvar tarefa.'
            });
        }
    })
    .catch(error => {
        Swal.close();
        console.error('Erro completo:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: 'Erro na comunicação com o servidor. Detalhes: ' + error.message
        });
    });
});

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const modal = document.getElementById('modalTarefa');
    if (event.target == modal) {
        fecharModalTarefa();
    }
};
</script>