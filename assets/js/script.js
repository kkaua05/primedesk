/**
 * PrimeDesk - Sistema de Gestão
 * Script JavaScript Principal
 */

// Toggle Sidebar Mobile
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
}

// Fechar Modal Financeiro
function fecharModal() {
    const modal = document.getElementById('modalFinanceiro');
    if (modal) {
        modal.style.display = 'none';
        const form = document.getElementById('formFinanceiro');
        if (form) {
            form.reset();
        }
    }
}

// Abrir Modal para Novo Lançamento Financeiro
function abrirModalFinanceiro() {
    const modal = document.getElementById('modalFinanceiro');
    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('formFinanceiro');
    if (modal && modalTitle && form) {
        modalTitle.innerText = 'Novo Lançamento Financeiro';
        document.getElementById('fin_id').value = '';
        form.reset();
        modal.style.display = 'block';
    }
}

// Editar Lançamento Financeiro
function editarFinanceiro(id) {
    fetch('actions/editar_financeiro.php?id=' + id)
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.status === 'success') {
                const modal = document.getElementById('modalFinanceiro');
                const modalTitle = document.getElementById('modalTitle');
                if (modal && modalTitle) {
                    modalTitle.innerText = 'Editar Lançamento';
                    document.getElementById('fin_id').value = data.data.id;
                    document.getElementById('fin_descricao').value = data.data.descricao;
                    document.getElementById('fin_valor').value = data.data.valor;
                    document.getElementById('fin_vencimento').value = data.data.data_vencimento;
                    document.getElementById('fin_pagamento').value = data.data.data_pagamento || '';
                    document.getElementById('fin_forma_pagamento').value = data.data.forma_pagamento || 'Pix';
                    document.getElementById('fin_status').value = data.data.status;
                    modal.style.display = 'block';
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: data.message || 'Não foi possível carregar os dados do lançamento.'
                });
            }
        })
        .catch(function(error) {
            console.error('Erro ao editar financeiro:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: 'Erro na comunicação com o servidor.'
            });
        });
}

// Salvar Cliente
const formCliente = document.getElementById('formCliente');
if (formCliente) {
    formCliente.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(formCliente);
        const data = Object.fromEntries(formData.entries());
        
        // Validar CPF
        if (!data.cpf || data.cpf.trim() === '' || data.cpf === '000.000.000-00') {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção!',
                text: 'CPF é obrigatório e deve ser válido!',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        // Validar Nome
        if (!data.nome || data.nome.trim() === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção!',
                text: 'Nome completo é obrigatório!',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        // Validar Operadora
        if (!data.operadora || data.operadora === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção!',
                text: 'Selecione uma operadora!',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        // Mostrar loading
        Swal.fire({
            title: 'Salvando...',
            text: 'Aguarde enquanto salvamos os dados.',
            allowOutsideClick: false,
            didOpen: function() {
                Swal.showLoading();
            }
        });
        
        fetch('actions/salvar_cliente.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(res) {
            Swal.close();
            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(function() {
                    window.location.href = res.redirect || 'index.php?page=clientes';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: res.message || 'Ocorreu um erro ao salvar o cliente.',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(function(error) {
            Swal.close();
            console.error('Erro:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: 'Erro na comunicação com o servidor. Verifique sua conexão.',
                confirmButtonText: 'OK'
            });
        });
    });
}

// Salvar Financeiro
const formFinanceiro = document.getElementById('formFinanceiro');
if (formFinanceiro) {
    formFinanceiro.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(formFinanceiro);
        const data = Object.fromEntries(formData.entries());
        
        // Validar campos obrigatórios
        if (!data.descricao || data.descricao.trim() === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção!',
                text: 'Descrição do serviço é obrigatória!',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        if (!data.valor || parseFloat(data.valor) <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção!',
                text: 'Valor deve ser maior que zero!',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        if (!data.data_vencimento || data.data_vencimento === '') {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção!',
                text: 'Data de vencimento é obrigatória!',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        // Mostrar loading
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
        .then(function(response) {
            return response.json();
        })
        .then(function(res) {
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
                    window.location.href = res.redirect || window.location.href;
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: res.message || 'Ocorreu um erro ao salvar o lançamento.',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(function(error) {
            Swal.close();
            console.error('Erro:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: 'Erro na comunicação com o servidor. Verifique sua conexão.',
                confirmButtonText: 'OK'
            });
        });
    });
}

// Deletar Cliente
function deletarCliente(id, nome) {
    Swal.fire({
        title: 'Tem certeza?',
        html: 'Deseja realmente excluir o cliente "' + nome + '"?<br>Esta ação não pode ser desfeita!',
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
            fetch('actions/deletar_cliente.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id })
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(res) {
                Swal.close();
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Excluído!',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(function() {
                        window.location.href = res.redirect || 'index.php?page=clientes';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: res.message || 'Não foi possível excluir o cliente.',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(function(error) {
                Swal.close();
                console.error('Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Erro na comunicação com o servidor.',
                    confirmButtonText: 'OK'
                });
            });
        }
    });
}

// Deletar Financeiro
function deletarFinanceiro(id) {
    const clienteIdField = document.getElementById('fin_cliente_id');
    const clienteId = clienteIdField ? clienteIdField.value : 0;
    Swal.fire({
        title: 'Tem certeza?',
        text: 'Deseja realmente excluir este lançamento financeiro?',
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
            fetch('actions/deletar_financeiro.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: id,
                    cliente_id: clienteId
                })
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(res) {
                Swal.close();
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Excluído!',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(function() {
                        window.location.href = res.redirect || window.location.href;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: res.message || 'Não foi possível excluir o lançamento.',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(function(error) {
                Swal.close();
                console.error('Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Erro na comunicação com o servidor.',
                    confirmButtonText: 'OK'
                });
            });
        }
    });
}

// Exportar Tabela
function exportarTabela(tabelaId, nome, tipo) {
    if (!tipo) tipo = 'excel';
    const tabela = document.getElementById(tabelaId);
    
    if (!tabela) {
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: 'Tabela não encontrada para exportação.',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    if (tipo === 'excel' || tipo === 'csv') {
        let csv = [];
        const rows = tabela.querySelectorAll('tr');
        
        for (let i = 0; i < rows.length; i++) {
            const row = [];
            const cols = rows[i].querySelectorAll('td, th');
            
            for (let j = 0; j < cols.length; j++) {
                let text = cols[j].innerText.replace(/[\r\n]+/g, ' ').trim();
                row.push('"' + text.replace(/"/g, '""') + '"');
            }
            
            csv.push(row.join(','));
        }
        
        const csvFile = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const downloadLink = document.createElement('a');
        const dataAtual = new Date().toISOString().split('T')[0];
        downloadLink.download = nome + '_' + dataAtual + '.csv';
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.click();
        
        Swal.fire({
            icon: 'success',
            title: 'Exportado!',
            text: 'Arquivo CSV gerado com sucesso!',
            timer: 2000,
            showConfirmButton: false
        });
    } else if (tipo === 'pdf') {
        Swal.fire({
            icon: 'info',
            title: 'Exportar PDF',
            html: 'Para exportar em PDF, utilize a impressão do navegador:<br><br>' +
                   '<strong>Ctrl + P</strong> (Windows)<br>' +
                   '<strong>Cmd + P</strong> (Mac)<br><br>' +
                   'E selecione "Salvar como PDF".',
            confirmButtonText: 'Entendi',
            timer: 5000
        }).then(function() {
            window.print();
        });
    }
}

// Quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    // Máscara para CPF
    const cpfInput = document.getElementById('cpf');
    if (cpfInput) {
        cpfInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
        });
    }
    
    // Máscara para Telefone/Celular
    const contatoInput = document.getElementById('contato');
    if (contatoInput) {
        contatoInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            if (value.length > 10) {
                value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
            } else if (value.length > 5) {
                value = value.replace(/^(\d{2})(\d{4})(\d{4}).*/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})(\d{0,5}).*/, '($1) $2');
            } else {
                value = value.replace(/^(\d{0,2}).*/, '($1');
            }
            e.target.value = value;
        });
    }
    
    // Fechar modal ao clicar fora
    window.onclick = function(event) {
        const modal = document.getElementById('modalFinanceiro');
        if (modal && event.target === modal) {
            fecharModal();
        }
    };
    
    // Fechar modal com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('modalFinanceiro');
            if (modal && modal.style.display === 'block') {
                fecharModal();
            }
        }
    });
});

console.log('PrimeDesk - Sistema Carregado');
