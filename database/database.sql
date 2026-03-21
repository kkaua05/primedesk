-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS rtcom_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rtcom_db;

-- Tabela de Clientes
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    cpf VARCHAR(14),
    contato VARCHAR(20),
    email VARCHAR(255),
    nome_mae VARCHAR(255),
    operadora ENUM('TIM', 'VIVO', 'CLARO', 'OUTROS') DEFAULT 'OUTROS',
    senha VARCHAR(255),
    vendedor VARCHAR(100),
    consultor VARCHAR(100),
    observacoes TEXT,
    status ENUM('Ativo', 'Inativo') DEFAULT 'Ativo',
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela Financeira
CREATE TABLE IF NOT EXISTS financeiro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE DEFAULT NULL,
    status ENUM('Pago', 'Pendente', 'Cancelado') DEFAULT 'Pendente',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inserir dados de exemplo
INSERT INTO clientes (nome, cpf, contato, email, nome_mae, operadora, vendedor, consultor, status) VALUES
('João Silva', '123.456.789-00', '(11) 99999-9999', 'joao@email.com', 'Maria Silva', 'VIVO', 'Carlos', 'Ana', 'Ativo'),
('Maria Oliveira', '987.654.321-00', '(11) 98888-8888', 'maria@email.com', 'Josefa Oliveira', 'CLARO', 'Pedro', 'Ana', 'Ativo'),
('Roberto Santos', '111.222.333-44', '(21) 97777-7777', 'roberto@email.com', 'Clara Santos', 'TIM', 'Carlos', 'Bruno', 'Inativo'),
('Fernanda Costa', '222.333.444-55', '(31) 96666-6666', 'fernanda@email.com', 'Lucia Costa', 'VIVO', 'Pedro', 'Ana', 'Ativo'),
('Carlos Mendes', '333.444.555-66', '(41) 95555-5555', 'carlos@email.com', 'Rita Mendes', 'TIM', 'Carlos', 'Bruno', 'Ativo');

INSERT INTO financeiro (cliente_id, descricao, valor, data_vencimento, data_pagamento, status) VALUES
(1, 'Instalação Fibra', 150.00, '2024-01-01', '2024-01-05', 'Pago'),
(1, 'Mensalidade Janeiro', 89.90, '2024-01-10', '2024-01-10', 'Pago'),
(1, 'Mensalidade Fevereiro', 89.90, '2024-02-10', NULL, 'Pendente'),
(2, 'Mensalidade Janeiro', 89.90, '2024-01-10', '2024-01-12', 'Pago'),
(2, 'Mensalidade Fevereiro', 89.90, '2024-02-10', NULL, 'Pendente'),
(3, 'Manutenção', 200.00, '2024-01-15', NULL, 'Cancelado'),
(4, 'Instalação', 120.00, '2024-01-20', '2024-01-22', 'Pago'),
(4, 'Mensalidade Fevereiro', 99.90, '2024-02-10', NULL, 'Pendente'),
(5, 'Mensalidade Janeiro', 79.90, '2024-01-10', '2024-01-10', 'Pago'),
(5, 'Mensalidade Fevereiro', 79.90, '2024-02-10', NULL, 'Pendente');

-- Adicionar coluna forma_pagamento na tabela financeiro
ALTER TABLE financeiro 
ADD COLUMN forma_pagamento ENUM('Pix', 'Boleto', 'Especie', 'Permuta') DEFAULT 'Pix' 
AFTER status;

-- Se a tabela já existir com dados, atualizar os registros existentes
UPDATE financeiro SET forma_pagamento = 'Pix' WHERE forma_pagamento IS NULL;

-- Tabela de Usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel ENUM('Administrador', 'Funcionario') DEFAULT 'Funcionario',
    status ENUM('Ativo', 'Inativo') DEFAULT 'Ativo',
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Usuário Administrador (senha: admin123)
INSERT INTO usuarios (nome, email, senha, nivel, status) VALUES
('Administrador Principal', 'admin@rtcom.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'Ativo');

-- Usuário Funcionário (senha: func123)
INSERT INTO usuarios (nome, email, senha, nivel, status) VALUES
('Funcionário Teste', 'funcionario@rtcom.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Funcionario', 'Ativo');

-- Nota: As senhas acima são hash de 'password' usando bcrypt
-- Para gerar novas senhas, use: password_hash('sua_senha', PASSWORD_DEFAULT)

-- Tabela de Agenda/Tarefas
CREATE TABLE IF NOT EXISTS agenda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    data_inicio DATE NOT NULL,
    data_fim DATE DEFAULT NULL,
    hora_inicio TIME DEFAULT NULL,
    hora_fim TIME DEFAULT NULL,
    prioridade ENUM('Baixa', 'Media', 'Alta', 'Urgente') DEFAULT 'Media',
    status ENUM('Pendente', 'Em Progresso', 'Concluido', 'Cancelado') DEFAULT 'Pendente',
    categoria ENUM('Tarefa', 'Reuniao', 'Ligacao', 'Email', 'Outro') DEFAULT 'Tarefa',
    cliente_id INT DEFAULT NULL,
    lembrete TINYINT(1) DEFAULT 0,
    data_lembrete DATETIME DEFAULT NULL,
    notificado TINYINT(1) DEFAULT 0,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_conclusao DATETIME DEFAULT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela de Notificações
CREATE TABLE IF NOT EXISTS notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    tipo ENUM('info', 'sucesso', 'aviso', 'erro') DEFAULT 'info',
    lido TINYINT(1) DEFAULT 0,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_leitura DATETIME DEFAULT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índices para performance
CREATE INDEX idx_agenda_data ON agenda(data_inicio);
CREATE INDEX idx_agenda_usuario ON agenda(usuario_id);
CREATE INDEX idx_agenda_status ON agenda(status);
CREATE INDEX idx_notificacoes_usuario ON notificacoes(usuario_id, lido);