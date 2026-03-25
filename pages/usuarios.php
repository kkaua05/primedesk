<?php
require_once 'config/database.php';
require_once 'includes/verificar_sessao.php';

// Apenas administradores
if ($_SESSION['usuario_nivel'] !== 'Administrador') {
    echo "<script>
    Swal.fire({
        icon: 'error',
        title: 'Acesso Negado!',
        text: 'Você não tem permissão para acessar esta página.'
    }).then(function() {
        window.location.href = 'index.php?page=dashboard';
    });
    </script>";
    exit;
}

$database = new Database();
$conn = $database->getConnection();

// Buscar todos os usuários
$stmt = $conn->query("SELECT * FROM usuarios ORDER BY id DESC");
$usuarios = $stmt->fetchAll();
?>
<div class="page-header">
    <h2><i class="fas fa-users-cog"></i> Gerenciar Usuários</h2>
    <button onclick="abrirModalUsuario()" class="btn btn-primary">
        <i class="fas fa-plus"></i> Novo Usuário
    </button>
</div>

<div class="table-container">
    <div class="table-header">
        <h3>Lista de Usuários</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Nível</th>
                <th>Status</th>
                <th>Último Acesso</th>
                <th>Cadastro</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($usuarios as $u): ?>
            <tr>
                <td><?php echo $u['id']; ?></td>
                <td><?php echo htmlspecialchars($u['nome']); ?></td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td>
                    <span class="badge <?php echo ($u['nivel'] == 'Administrador') ? 'bg-danger' : 'bg-warning'; ?>">
                        <?php echo $u['nivel']; ?>
                    </span>
                </td>
                <td>
                    <span class="badge <?php echo ($u['status'] == 'Ativo') ? 'bg-success' : 'bg-danger'; ?>">
                        <?php echo $u['status']; ?>
                    </span>
                </td>
                <td><?php echo $u['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acesso'])) : 'Nunca'; ?></td>
                <td><?php echo date('d/m/Y', strtotime($u['data_cadastro'])); ?></td>
                <td>
                    <button onclick="editarUsuario(<?php echo $u['id']; ?>)" 
                            class="btn btn-sm btn-primary" 
                            title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="alternarStatusUsuario(<?php echo $u['id']; ?>, '<?php echo $u['status']; ?>')" 
                            class="btn btn-sm <?php echo ($u['status'] == 'Ativo') ? 'btn-warning' : 'btn-success'; ?>" 
                            title="<?php echo ($u['status'] == 'Ativo') ? 'Desativar' : 'Ativar'; ?>">
                        <i class="fas fa-<?php echo ($u['status'] == 'Ativo') ? 'ban' : 'check'; ?>"></i>
                    </button>
                    <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
                    <button onclick="deletarUsuario(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['nome']); ?>')" 
                            class="btn btn-sm btn-danger" 
                            title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Usuário -->
<div id="modalUsuario" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Novo Usuário</h3>
            <span class="close" onclick="fecharModalUsuario()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="formUsuario">
                <input type="hidden" id="usuario_id" name="id">
                
                <div class="form-group">
                    <label>Nome Completo *</label>
                    <input type="text" id="usuario_nome" name="nome" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>E-mail *</label>
                    <input type="email" id="usuario_email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Senha <?php echo '<span id="senha_obrigatoria">*</span>'; ?></label>
                    <input type="password" id="usuario_senha" name="senha" class="form-control" minlength="6">
                    <small style="color: var(--text-light);">Mínimo 6 caracteres</small>
                </div>
                
                <div class="form-group">
                    <label>Nível de Acesso *</label>
                    <select id="usuario_nivel" name="nivel" class="form-control" required>
                        <option value="Funcionario">Funcionário</option>
                        <option value="Administrador">Administrador</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select id="usuario_status" name="status" class="form-control">
                        <option value="Ativo">Ativo</option>
                        <option value="Inativo">Inativo</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Usuário
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="fecharModalUsuario()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Abrir Modal para Novo Usuário
function abrirModalUsuario() {
    const modal = document.getElementById('modalUsuario');
    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('formUsuario');
    
    modalTitle.innerText = 'Novo Usuário';
    form.reset();
    document.getElementById('usuario_id').value = '';
    document.getElementById('usuario_senha').required = true;
    document.getElementById('senha_obrigatoria').style.display = 'inline';
    
    modal.style.display = 'block';
}

// Fechar Modal
function fecharModalUsuario() {
    const modal = document.getElementById('modalUsuario');
    modal.style.display = 'none';
    document.getElementById('formUsuario').reset();
}

// Editar Usuário
function editarUsuario(id) {
    const modal = document.getElementById('modalUsuario');
    const modalTitle = document.getElementById('modalTitle');
    
    modalTitle.innerText = 'Editar Usuário';
    
    fetch('actions/editar_usuario.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const u = data.data;
                document.getElementById('usuario_id').value = u.id;
                document.getElementById('usuario_nome').value = u.nome;
                document.getElementById('usuario_email').value = u.email;
                document.getElementById('usuario_nivel').value = u.nivel;
                document.getElementById('usuario_status').value = u.status;
                document.getElementById('usuario_senha').required = false;
                document.getElementById('senha_obrigatoria').style.display = 'none';
                
                modal.style.display = 'block';
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: data.message || 'Não foi possível carregar os dados do usuário.'
                });
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: 'Erro na comunicação com o servidor.'
            });
        });
}

// Alternar Status do Usuário
function alternarStatusUsuario(id, statusAtual) {
    const novoStatus = statusAtual === 'Ativo' ? 'Inativo' : 'Ativo';
    const acao = statusAtual === 'Ativo' ? 'desativar' : 'ativar';
    
    Swal.fire({
        title: 'Confirmar ' + acao.charAt(0).toUpperCase() + acao.slice(1) + '?',
        text: 'Deseja realmente ' + acao + ' este usuário?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: statusAtual === 'Ativo' ? '#f59e0b' : '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sim, ' + acao,
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then(function(result) {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Processando...',
                text: 'Aguarde.',
                allowOutsideClick: false,
                didOpen: function() {
                    Swal.showLoading();
                }
            });
            
            fetch('actions/alternar_status_usuario.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: id,
                    status: novoStatus
                })
            })
            .then(response => response.json())
            .then(res => {
                Swal.close();
                if (res.status === 'success') {
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
                    text: 'Erro na comunicação com o servidor.'
                });
            });
        }
    });
}

// Deletar Usuário
function deletarUsuario(id, nome) {
    Swal.fire({
        title: 'Tem certeza?',
        html: 'Deseja realmente excluir o usuário "' + nome + '"?<br><br><strong>Esta ação não pode ser desfeita!</strong>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar',
        reverseButtons: true
    }).then(function(result) {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Excluindo...',
                text: 'Aguarde.',
                allowOutsideClick: false,
                didOpen: function() {
                    Swal.showLoading();
                }
            });
            
            fetch('actions/deletar_usuario.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(res => {
                Swal.close();
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Excluído!',
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
                    text: 'Erro na comunicação com o servidor.'
                });
            });
        }
    });
}

// Salvar Usuário
document.getElementById('formUsuario').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    // Validações
    if (!data.nome || data.nome.trim() === '') {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Nome é obrigatório!'
        });
        return;
    }
    
    if (!data.email || data.email.trim() === '') {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'E-mail é obrigatório!'
        });
        return;
    }
    
    // Verificar se é cadastro novo e senha está vazia
    if (!data.id && (!data.senha || data.senha.length < 6)) {
        Swal.fire({
            icon: 'warning',
            title: 'Atenção!',
            text: 'Senha deve ter no mínimo 6 caracteres!'
        });
        return;
    }
    
    // Se for edição e senha estiver vazia, não enviar
    if (data.id && (!data.senha || data.senha.trim() === '')) {
        delete data.senha;
    }
    
    Swal.fire({
        title: 'Salvando...',
        text: 'Aguarde enquanto salvamos os dados.',
        allowOutsideClick: false,
        didOpen: function() {
            Swal.showLoading();
        }
    });
    
    fetch('actions/salvar_usuario.php', {
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
            fecharModalUsuario();
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
            text: 'Erro na comunicação com o servidor.'
        });
    });
});

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const modal = document.getElementById('modalUsuario');
    if (event.target == modal) {
        fecharModalUsuario();
    }
};

// Fechar modal com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('modalUsuario');
        if (modal && modal.style.display === 'block') {
            fecharModalUsuario();
        }
    }
});
</script>
