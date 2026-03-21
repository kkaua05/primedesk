-- Script para identificar CPFs duplicados
SELECT cpf, COUNT(*) as quantidade
FROM clientes
WHERE cpf IS NOT NULL AND cpf != ''
GROUP BY cpf
HAVING COUNT(*) > 1;

-- Script para remover duplicados (mantendo o menor ID)
DELETE c1 FROM clientes c1
INNER JOIN clientes c2 
WHERE c1.id > c2.id 
AND c1.cpf = c2.cpf 
AND c1.cpf IS NOT NULL 
AND c1.cpf != '';

-- Adicionar índice único após limpeza
ALTER TABLE clientes 
ADD UNIQUE INDEX idx_cpf_unico (cpf);

-- Verificar se o índice foi criado
SHOW INDEX FROM clientes WHERE Key_name = 'idx_cpf_unico';