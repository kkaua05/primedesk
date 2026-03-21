<?php
$cliente = null;
$modoEdicao = false;
?>

<div class="page-header">
    <h2><i class="fas fa-user-plus"></i> Cadastrar Cliente</h2>
    <a href="index.php?page=clientes" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Voltar
    </a>
</div>

<div class="table-container">
    <div class="table-header">
        <h3>Informações do Cliente</h3>
    </div>
    <form id="formCliente" style="padding: 20px;">
        <input type="hidden" name="id" id="cliente_id" value="">
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div class="form-group">
                <label>Nome Completo *</label>
                <input type="text" name="nome" id="nome" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>CPF *</label>
                <input type="text" name="cpf" id="cpf" class="form-control" placeholder="000.000.000-00" maxlength="14" required>
                <small style="color: var(--text-light);">CPF único - não será permitido cadastro duplicado</small>
            </div>
            
            <div class="form-group">
                <label>Contato</label>
                <input type="text" name="contato" id="contato" class="form-control" placeholder="(00) 00000-0000" maxlength="15">
            </div>
            
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="email@exemplo.com">
            </div>
            
            <div class="form-group">
                <label>Nome da Mãe</label>
                <input type="text" name="nome_mae" id="nome_mae" class="form-control">
            </div>
            
            <div class="form-group">
                <label>Operadora *</label>
                <select name="operadora" id="operadora" class="form-control" required>
                    <option value="">Selecione...</option>
                    <option value="VIVO">VIVO</option>
                    <option value="TIM">TIM</option>
                    <option value="CLARO">CLARO</option>
                    <option value="OUTROS">OUTROS</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Vendedor</label>
                <input type="text" name="vendedor" id="vendedor" class="form-control">
            </div>
            
            <div class="form-group">
                <label>Consultor</label>
                <input type="text" name="consultor" id="consultor" class="form-control">
            </div>
            
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="Ativo">Ativo</option>
                    <option value="Inativo">Inativo</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label>Observações</label>
            <textarea name="observacoes" id="observacoes" class="form-control" rows="4" placeholder="Histórico, observações, anotações..."></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Salvar Cliente
            </button>
            <a href="index.php?page=clientes" class="btn btn-secondary">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </div>
    </form>
</div>

<script>
// Máscara para CPF
document.getElementById('cpf').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    e.target.value = value;
});

// Máscara para telefone
document.getElementById('contato').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
    value = value.replace(/(\d)(\d{4})$/, '$1-$2');
    e.target.value = value;
});
</script>