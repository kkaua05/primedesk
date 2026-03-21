<?php
// Script para gerar hash de senha
// Acesse: http://localhost/rtcom-consultoria/gerar_hash_senha.php

$senha = 'password';
$hash = password_hash($senha, PASSWORD_DEFAULT);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Gerar Hash de Senha</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f1f5f9;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #2563eb; }
        .hash-box {
            background: #f8fafc;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #2563eb;
            margin: 20px 0;
            word-break: break-all;
        }
        .sql-box {
            background: #1e293b;
            color: #10b981;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            margin: 20px 0;
            white-space: pre-wrap;
        }
        .info {
            background: #fef3c7;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #f59e0b;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔐 Gerador de Hash de Senha</h1>
        
        <div class='info'>
            <strong>Senha original:</strong> password<br>
            <strong>Hash gerado:</strong>
        </div>
        
        <div class='hash-box'>
            <strong>$hash</strong>
        </div>
        
        <h3>📋 SQL para inserir/atualizar usuários:</h3>
        <div class='sql-box'>
-- Apagar usuários existentes (OPCIONAL)
DELETE FROM usuarios WHERE email IN ('admin@rtcom.com', 'funcionario@rtcom.com');

-- Inserir Administrador
INSERT INTO usuarios (nome, email, senha, nivel, status) VALUES
('Administrador Principal', 'admin@rtcom.com', '$hash', 'Administrador', 'Ativo');

-- Inserir Funcionário
INSERT INTO usuarios (nome, email, senha, nivel, status) VALUES
('Funcionário Teste', 'funcionario@rtcom.com', '$hash', 'Funcionario', 'Ativo');
        </div>
        
        <h3>🔑 Credenciais de Acesso:</h3>
        <ul>
            <li><strong>Administrador:</strong> admin@rtcom.com / password</li>
            <li><strong>Funcionário:</strong> funcionario@rtcom.com / password</li>
        </ul>
        
        <p style='color: #64748b; margin-top: 30px;'>
            <em>Copie o SQL acima e execute no phpMyAdmin.</em>
        </p>
    </div>
</body>
</html>";
?>