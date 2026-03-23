        </div>
    </div>
    <div id="modalFinanceiro" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Novo Lançamento Financeiro</h3>
                <span class="close" onclick="fecharModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="formFinanceiro">
                    <input type="hidden" id="fin_id" name="id">
                    <input type="hidden" id="fin_cliente_id" name="cliente_id">
                    <div class="form-group">
                        <label>Descrição do Serviço</label>
                        <input type="text" id="fin_descricao" name="descricao" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Valor (R$)</label>
                        <input type="number" id="fin_valor" name="valor" class="form-control" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Data de Vencimento</label>
                        <input type="date" id="fin_vencimento" name="data_vencimento" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Data de Pagamento</label>
                        <input type="date" id="fin_pagamento" name="data_pagamento" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Forma de Pagamento</label>
                        <select id="fin_forma_pagamento" name="forma_pagamento" class="form-control" required>
                            <option value="Pix">Pix</option>
                            <option value="Boleto">Boleto</option>
                            <option value="Especie">Espécie</option>
                            <option value="Permuta">Permuta</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select id="fin_status" name="status" class="form-control" required>
                            <option value="Pendente">Pendente</option>
                            <option value="Pago">Pago</option>
                            <option value="Cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Salvar</button>
                        <button type="button" class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="assets/js/script.js"></script>
</body>
</html>
