<?php
require_once 'config/database.php';
require_once 'includes/verificar_sessao.php';

$database = new Database();
$conn = $database->getConnection();

// Filtros
$filtroNome = isset($_GET['nome']) ? $_GET['nome'] : '';
$filtroOperadora = isset($_GET['operadora']) ? $_GET['operadora'] : '';
$filtroVendedor = isset($_GET['vendedor']) ? $_GET['vendedor'] : '';

$query = "SELECT * FROM clientes WHERE 1=1";
$params = [];

if ($filtroNome) {
    $query .= " AND nome LIKE :nome";
    $params[':nome'] = "%$filtroNome%";
}
if ($filtroOperadora) {
    $query .= " AND operadora = :operadora";
    $params[':operadora'] = $filtroOperadora;
}
if ($filtroVendedor) {
    $query .= " AND vendedor LIKE :vendedor";
    $params[':vendedor'] = "%$filtroVendedor%";
}

$query .= " ORDER BY id DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$clientes = $stmt->fetchAll();
?>
<div class="page-header">
    <h2><i class="fas fa-users"></i> Gestão de Clientes</h2>
    <a href="index.php?page=cadastrar_cliente" class="btn btn-primary">
        <i class="fas fa-plus"></i> Novo Cliente
    </a>
</div>

<div class="table-container">
    <div class="table-header">
        <h3>Lista de Clientes</h3>
        <form method="GET" class="filters">
            <input type="hidden" name="page" value="clientes">
            <input type="text" name="nome" placeholder="Buscar por nome" value="<?php echo htmlspecialchars($filtroNome); ?>">
            <select name="operadora">
                <option value="">Todas Operadoras</option>
                <option value="VIVO" <?php echo ($filtroOperadora == 'VIVO') ? 'selected' : ''; ?>>VIVO</option>
                <option value="TIM" <?php echo ($filtroOperadora == 'TIM') ? 'selected' : ''; ?>>TIM</option>
                <option value="CLARO" <?php echo ($filtroOperadora == 'CLARO') ? 'selected' : ''; ?>>CLARO</option>
                <option value="OUTROS" <?php echo ($filtroOperadora == 'OUTROS') ? 'selected' : ''; ?>>OUTROS</option>
            </select>
            <input type="text" name="vendedor" placeholder="Vendedor" value="<?php echo htmlspecialchars($filtroVendedor); ?>">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="fas fa-search"></i> Filtrar
            </button>
            <a href="index.php?page=clientes" class="btn btn-secondary btn-sm">
                <i class="fas fa-times"></i> Limpar
            </a>
        </form>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>CPF</th>
                <th>Contato</th>
                <th>Operadora</th>
                <th>Vendedor</th>
                <th>Consultor</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($clientes) > 0): ?>
                <?php foreach($clientes as $c): ?>
                <tr>
                    <td><?php echo $c['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($c['nome']); ?></strong></td>
                    <td><?php echo htmlspecialchars($c['cpf']); ?></td>
                    <td><?php echo htmlspecialchars($c['contato']); ?></td>
                    <td><span class="badge bg-warning"><?php echo htmlspecialchars($c['operadora']); ?></span></td>
                    <td><?php echo htmlspecialchars($c['vendedor']); ?></td>
                    <td><?php echo htmlspecialchars($c['consultor']); ?></td>
                    <td>
                        <span class="badge <?php echo ($c['status'] == 'Ativo') ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo $c['status']; ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="index.php?page=visualizar_cliente&id=<?php echo $c['id']; ?>" 
                               class="btn btn-sm btn-info" 
                               title="Visualizar Detalhes"
                               style="background: #3b82f6; color: white;">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="index.php?page=editar_cliente&id=<?php echo $c['id']; ?>" 
                               class="btn btn-sm btn-primary" 
                               title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="index.php?page=financeiro_clientes&id=<?php echo $c['id']; ?>" 
                               class="btn btn-sm btn-success" 
                               title="Financeiro">
                                <i class="fas fa-dollar-sign"></i>
                            </a>
                            <button onclick="deletarCliente(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars($c['nome']); ?>')" 
                                    class="btn btn-sm btn-danger" 
                                    title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center">Nenhum cliente encontrado</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.btn-group {
    display: inline-flex;
    gap: 4px;
}
.btn-group .btn {
    padding: 6px 10px;
}
</style>
