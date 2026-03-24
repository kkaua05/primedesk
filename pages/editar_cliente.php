<?php
require_once 'config/database.php';
require_once 'includes/verificar_sessao.php';

$database = new Database();
$conn = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : 0;
$stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    echo "<script>
    Swal.fire({
        icon: 'error',
        title: 'Erro!',
        text: 'Cliente não encontrado!'
    }).then(() => {
        window.location.href = 'index.php?page=clientes';
    });
    </script>";
    exit;
}
?>
<div class="page-header">
    <h2><i class="fas fa-user-edit"></i> Editar Cliente</h2>
    <a href="index.php?page=clientes" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>
<div class="table-container">
    <div class="table-header">
        <h3>Informações do Cliente</h3>
    </div>
    <form id="formCliente" style="padding: 20px;">
        <input type="hidden" name="id" id="cliente_id" value="<?php echo $cliente['id']; ?>">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div class="form-group">
                <label>Nome Completo *</label>
                <input type="text" name="nome" id="nome" class="form-control" value="<?php echo htmlspecialchars($cliente['nome']); ?>" required>
            </div>
            <div class="form-group">
                <label>CPF *</label>
                <input type="text" name="cpf" id="cpf" class="form-control" value="<?php echo htmlspecialchars($cliente['cpf']); ?>" placeholder="000.000.000-00" maxlength="14" required>
            </div>
            <div class="form-group">
                <label>Contato</label>
                <input type="text" name="contato" id="contato" class="form-control" value="<?php echo htmlspecialchars($cliente['contato']); ?>" placeholder="(00) 00000-0000" maxlength="15">
            </div>
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($cliente['email']); ?>" placeholder="email@exemplo.com">
            </div>
            <div class="form-group">
                <label>Nome da Mãe</label>
                <input type="text" name="nome_mae" id="nome_mae" class="form-control" value="<?php echo htmlspecialchars($cliente['nome_mae']); ?>">
            </div>
            <div class="form-group">
                <label>Operadora *</label>
                <select name="operadora" id="operadora" class="form-control" required>
                    <option value="">Selecione...</option>
                    <option value="VIVO" <?php echo ($cliente['operadora'] == 'VIVO') ? 'selected' : ''; ?>>VIVO</option>
                    <option value="TIM" <?php echo ($cliente['operadora'] == 'TIM') ? 'selected' : ''; ?>>TIM</option>
                    <option value="CLARO" <?php echo ($cliente['operadora'] == 'CLARO') ? 'selected' : ''; ?>>CLARO</option>
                    <option value="OUTROS" <?php echo ($cliente['operadora'] == 'OUTROS') ? 'selected' : ''; ?>>OUTROS</option>
                </select>
            </div>
            <div class="form-group">
                <label>Vendedor</label>
                <input type="text" name="vendedor" id="vendedor" class="form-control" value="<?php echo htmlspecialchars($cliente['vendedor']); ?>">
            </div>
            <div class="form-group">
                <label>Consultor</label>
                <input type="text" name="consultor" id="consultor" class="form-control" value="<?php echo htmlspecialchars($cliente['consultor']); ?>">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="Ativo" <?php echo ($cliente['status'] == 'Ativo') ? 'selected' : ''; ?>>Ativo</option>
                    <option value="Inativo" <?php echo ($cliente['status'] == 'Inativo') ? 'selected' : ''; ?>>Inativo</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Observações</label>
            <textarea name="observacoes" id="observacoes" class="form-control" rows="4"><?php echo htmlspecialchars($cliente['observacoes']); ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Atualizar Cliente
            </button>
            <a href="index.php?page=clientes" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </div>
    </form>
</div>
<script>
document.getElementById('cpf').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    e.target.value = value;
});
document.getElementById('contato').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
    value = value.replace(/(\d)(\d{4})$/, '$1-$2');
    e.target.value = value;
});
</script>
