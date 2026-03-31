-- Adicionar colunas para parcelamento na tabela financeiro
ALTER TABLE financeiro 
ADD COLUMN parcela_atual INT DEFAULT 1 AFTER forma_pagamento,
ADD COLUMN total_parcelas INT DEFAULT 1 AFTER parcela_atual,
ADD COLUMN parcela_pai_id INT DEFAULT NULL AFTER total_parcelas,
ADD COLUMN valor_original DECIMAL(10,2) DEFAULT NULL AFTER parcela_pai_id;

-- Criar índice para melhor performance
CREATE INDEX idx_parcela_pai ON financeiro(parcela_pai_id);
CREATE INDEX idx_parcela_atual ON financeiro(parcela_atual, total_parcelas);

-- Adicionar campo para identificar se é recorrente
ALTER TABLE financeiro 
ADD COLUMN tipo_lancamento ENUM('unico', 'parcelado', 'recorrente') DEFAULT 'unico' AFTER valor_original;