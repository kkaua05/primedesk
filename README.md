# PrimeDesk - Sistema de Gestão

Sistema completo de gerenciamento de clientes, financeiro e agenda.

## 🚀 Funcionalidades

- ✅ Gestão de Clientes (CRUD completo)
- ✅ Controle Financeiro (lançamentos, pagamentos, relatórios)
- ✅ Agenda e Tarefas (calendário, prioridades, lembretes)
- ✅ Dashboard com gráficos e indicadores
- ✅ Relatórios personalizáveis
- ✅ Controle de acesso por níveis (Admin/Funcionário)
- ✅ Interface responsiva e moderna

##  Requisitos

- PHP 8.0+
- MySQL 8.0+
- Apache/Nginx

## 🔧 Instalação

1. Clone o repositório:
```bash
git clone https://github.com/seu-usuario/primedesk.git

Configure o banco de dados:
Crie o banco: rtcom_db
Execute o arquivo database.sql

Edite config/database.php:

private $host = "localhost";
private $db_name = "rtcom_db";
private $username = "root";
private $password = "";

Acesse: http://localhost/primedesk

🔑 Acesso Padrão

Administrador:

Email: admin@rtcom.com
Senha: password

Funcionário:

Email: funcionario@rtcom.com
Senha: password

📁 Estrutura

primedesk/
├── config/           # Configurações do banco
├── includes/         # Header, footer, sidebar, sessão
├── actions/          # Processamento de formulários
├── pages/            # Páginas do sistema
├── assets/           # CSS e JavaScript
└── database.sql      # Script do banco de dados

🛠️ Tecnologias:
---------------------

PHP 8.0+ com PDO
MySQL
HTML5 + CSS3
JavaScript (ES6)
Chart.js (gráficos)
SweetAlert2 (alertas)
Font Awesome (ícones)


🔒 Segurança
-------------------

Senhas com hash bcrypt
Prepared Statements (SQL Injection)
Proteção XSS
Timeout de sessão (30 min)
Validação de dados


📝 Licença
MIT License


👨‍ Desenvolvedor
Kauã Ferreira - kauafesilva05@gmail.com


Sistema desenvolvido para gestão de consultorias e pequenas empresas.


**Pronto para copiar e colar!** 🎯
