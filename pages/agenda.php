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

// Buscar todos os clientes para o select
$stmt = $conn->query("SELECT id, nome FROM clientes WHERE status = 'Ativo' ORDER BY nome");
$clientes = $stmt->fetchAll();
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
        <div class="value" id="countPendentes"><?php echo $tarefas_pendentes; ?></div>
    </div>
    <div class="card success">
        <h3><i class="fas fa-check-circle"></i> Concluídas</h3>
        <div class="value" id="countConcluidas"><?php echo $tarefas_concluidas; ?></div>
    </div>
    <div class="card danger">
        <h3><i class="fas fa-exclamation-triangle"></i> Urgentes</h3>
        <div class="value" id="countUrgentes"><?php echo $tarefas_urgentes; ?></div>
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
            <button onclick="visualizarTarefa(<?php echo $t['id']; ?>)" class="btn btn-sm btn-info" title="Visualizar">
                <i class="fas fa-eye"></i>
            </button>
            <button onclick="editarTarefa(<?php echo $t['id']; ?>)" class="btn btn-sm btn-primary" title="Editar">
                <i class="fas fa-edit"></i>
            </button>
            <button onclick="deletarTarefa(<?php echo $t['id']; ?>)" class="btn btn-sm btn-danger" title="Excluir">
                <i class="fas fa-trash"></i>
            </button>
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
                    
                    echo '<div class="calendar-day ' . ($eh_hoje ? 'today' : '') . '" onclick="clicarDia(\'' . $data_completa . '\')">';
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
                            echo '<div class="calendar-task" style="background: ' . $cor . '20; border-left: 3px solid ' . $cor . ';" onclick="event.stopPropagation(); visualizarTarefa(' . $tarefa['id'] . ')">';
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
                            <?php foreach($clientes as $cliente): ?>
                                <option value="<?php echo $cliente['id']; ?>">
                                    <?php echo htmlspecialchars($cliente['nome']); ?>
                                </option>
                            <?php endforeach; ?>
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

<!-- Modal de Visualização -->
<div id="modalVisualizar" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3><i class="fas fa-eye"></i> Detalhes da Tarefa</h3>
            <span class="close" onclick="fecharModalVisualizar()">&times;</span>
        </div>
        <div class="modal-body" id="conteudoVisualizacao">
            <!-- Conteúdo será carregado via JavaScript -->
        </div>
        <div class="modal-footer" style="padding: 15px 20px; border-top: 1px solid #e2e8f0; display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" onclick="fecharModalVisualizar()">
                <i class="fas fa-times"></i> Fechar
            </button>
            <button type="button" class="btn btn-primary" id="btnEditarVisualizacao" onclick="editarTarefaDoModal()">
                <i class="fas fa-edit"></i> Editar
            </button>
            <button type="button" class="btn btn-danger" id="btnExcluirVisualizacao" onclick="deletarTarefaDoModal()">
                <i class="fas fa-trash"></i> Excluir
            </button>
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
    min-height: 120px;
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
    transition: all 0.2s;
}

.calendar-task:hover {
    opacity: 0.8;
    transform: translateX(2px);
}

.calendar-task-more {
    color: #64748b;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    text-align: center;
    padding: 2px;
}

.btn-info {
    background-color: #3b82f6;
    color: white;
}

.btn-info:hover {
    background-color: #2563eb;
}

.modal-footer {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding: 15px 20px;
    border-top: 1px solid #e2e8f0;
}

.visualizacao-item {
    margin-bottom: 15px;
}

.visualizacao-label {
    font-weight: 600;
    color: #64748b;
    font-size: 0.85rem;
    margin-bottom: 5px;
    text-transform: uppercase;
}

.visualizacao-value {
    color: #1e293b;
    font-size: 1rem;
    padding: 8px 12px;
    background: #f8fafc;
    border-radius: 6px;
}

@media (max-width: 768px) {
    .calendar-day {
        min-height: 80px;
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
let tarefaAtualId = null;

// Abrir Modal de Tarefa
function abrirModalTarefa(id = null, data = null) {
    const modal = document.getElementById('modalTarefa');
    const form = document.getElementById('formTarefa');
    const title = document.getElementById('modalTarefaTitle');
    
    form.reset();
    
    if (id) {
        title.innerHTML = '<i class="fas fa-edit"></i> Editar Tarefa';
        carregarTarefa(id);
    } else {
        title.innerHTML = '<i class="fas fa-plus"></i> Nova Tarefa';
        document.getElementById('tarefa_data_inicio').value = data || '<?php echo date('Y-m-d'); ?>';
    }
    
    modal.style.display = 'block';
}

// Fechar Modal de Tarefa
function fecharModalTarefa() {
    document.getElementById('modalTarefa').style.display = 'none';
    document.getElementById('formTarefa').reset();
    tarefaAtualId = null;
}

// Carregar dados da tarefa
function carregarTarefa(id) {
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
                tarefaAtualId = id;
            } else {
                Swal.fire('Erro!', 'Não foi possível carregar a tarefa.', 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            Swal.fire('Erro!', 'Erro na comunicação com o servidor.', 'error');
        });
}

// Editar Tarefa
function editarTarefa(id) {
    abrirModalTarefa(id);
}

// Visualizar Tarefa
function visualizarTarefa(id) {
    const modal = document.getElementById('modalVisualizar');
    const conteudo = document.getElementById('conteudoVisualizacao');
    
    fetch('actions/carregar_tarefa.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const t = data.data;
                tarefaAtualId = id;
                
                const prioridades = {
                    'Baixa': '<span class="badge" style="background: #10b981; color: white;">Baixa</span>',
                    'Media': '<span class="badge" style="background: #3b82f6; color: white;">Média</span>',
                    'Alta': '<span class="badge" style="background: #f59e0b; color: white;">Alta</span>',
                    'Urgente': '<span class="badge" style="background: #ef4444; color: white;">Urgente</span>'
                };
                
                const status = {
                    'Pendente': '<span class="badge bg-warning">Pendente</span>',
                    'Em Progresso': '<span class="badge" style="background: #3b82f6; color: white;">Em Progresso</span>',
                    'Concluido': '<span class="badge bg-success">Concluído</span>',
                    'Cancelado': '<span class="badge bg-danger">Cancelado</span>'
                };
                
                conteudo.innerHTML = `
                    <div class="visualizacao-item">
                        <div class="visualizacao-label"><i class="fas fa-heading"></i> Título</div>
                        <div class="visualizacao-value">${t.titulo}</div>
                    </div>
                    <div class="visualizacao-item">
                        <div class="visualizacao-label"><i class="fas fa-align-left"></i> Descrição</div>
                        <div class="visualizacao-value">${t.descricao || 'Nenhuma descrição'}</div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="visualizacao-item">
                            <div class="visualizacao-label"><i class="fas fa-calendar"></i> Data de Início</div>
                            <div class="visualizacao-value">${formatarData(t.data_inicio)}</div>
                        </div>
                        <div class="visualizacao-item">
                            <div class="visualizacao-label"><i class="fas fa-calendar-check"></i> Data de Fim</div>
                            <div class="visualizacao-value">${t.data_fim ? formatarData(t.data_fim) : 'Não definida'}</div>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="visualizacao-item">
                            <div class="visualizacao-label"><i class="fas fa-clock"></i> Hora de Início</div>
                            <div class="visualizacao-value">${t.hora_inicio ? formatarHora(t.hora_inicio) : 'Não definida'}</div>
                        </div>
                        <div class="visualizacao-item">
                            <div class="visualizacao-label"><i class="fas fa-clock"></i> Hora de Fim</div>
                            <div class="visualizacao-value">${t.hora_fim ? formatarHora(t.hora_fim) : 'Não definida'}</div>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <div class="visualizacao-item">
                            <div class="visualizacao-label"><i class="fas fa-flag"></i> Prioridade</div>
                            <div class="visualizacao-value">${prioridades[t.prioridade]}</div>
                        </div>
                        <div class="visualizacao-item">
                            <div class="visualizacao-label"><i class="fas fa-info-circle"></i> Status</div>
                            <div class="visualizacao-value">${status[t.status]}</div>
                        </div>
                        <div class="visualizacao-item">
                            <div class="visualizacao-label"><i class="fas fa-folder"></i> Categoria</div>
                            <div class="visualizacao-value">${t.categoria}</div>
                        </div>
                    </div>
                    ${t.cliente_nome ? `
                    <div class="visualizacao-item">
                        <div class="visualizacao-label"><i class="fas fa-user"></i> Cliente</div>
                        <div class="visualizacao-value">${t.cliente_nome}</div>
                    </div>
                    ` : ''}
                `;
                
                modal.style.display = 'block';
            } else {
                Swal.fire('Erro!', 'Não foi possível carregar a tarefa.', 'error');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            Swal.fire('Erro!', 'Erro na comunicação com o servidor.', 'error');
        });
}

// Fechar Modal de Visualização
function fecharModalVisualizar() {
    document.getElementById('modalVisualizar').style.display = 'none';
    tarefaAtualId = null;
}

// Editar Tarefa do Modal de Visualização
function editarTarefaDoModal() {
    fecharModalVisualizar();
    if (tarefaAtualId) {
        abrirModalTarefa(tarefaAtualId);
    }
}

// Deletar Tarefa do Modal de Visualização
function deletarTarefaDoModal() {
    if (tarefaAtualId) {
        deletarTarefa(tarefaAtualId);
        fecharModalVisualizar();
    }
}

// Deletar Tarefa
function deletarTarefa(id) {
    Swal.fire({
        title: 'Tem certeza?',
        text: "Deseja realmente excluir esta tarefa?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Excluindo...',
                text: 'Aguarde.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('actions/deletar_tarefa.php', {
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
                        title: 'Excluído!',
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

// Clicar no dia do calendário
function clicarDia(data) {
    abrirModalTarefa(null, data);
}

// Formatar data
function formatarData(data) {
    if (!data) return '-';
    const partes = data.split('-');
    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

// Formatar hora
function formatarHora(hora) {
    if (!hora) return '-';
    return hora.substring(0, 5);
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const modalTarefa = document.getElementById('modalTarefa');
    const modalVisualizar = document.getElementById('modalVisualizar');
    if (event.target == modalTarefa) {
        fecharModalTarefa();
    }
    if (event.target == modalVisualizar) {
        fecharModalVisualizar();
    }
};

// Fechar modal com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        fecharModalTarefa();
        fecharModalVisualizar();
    }
});
</script>
