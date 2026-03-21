<?php
require_once 'config/database.php';
$database = new Database();
$conn = $database->getConnection();

// Apenas administradores
if ($_SESSION['usuario_nivel'] !== 'Administrador') {
    echo "<script>window.location.href = 'index.php?page=dashboard';</script>";
    exit;
}

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
                <td>
                    <button class="btn btn-sm btn-primary">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>