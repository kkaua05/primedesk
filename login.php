<?php
session_start();

// Se já estiver logado, redireciona para dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    // CORREÇÃO: Usar filter_var em vez de FILTER_SANITIZE_EMAIL (depreciado)
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $senha = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($senha)) {
        $erro = 'Por favor, preencha todos os campos!';
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ? AND status = 'Ativo'");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();
            
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                // Login bem-sucedido
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];
                $_SESSION['usuario_nivel'] = $usuario['nivel'];
                $_SESSION['login_time'] = time();
                
                // Atualizar último acesso
                $stmt = $conn->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?");
                $stmt->execute([$usuario['id']]);
                
                header('Location: index.php');
                exit;
            } else {
                $erro = 'E-mail ou senha inválidos!';
            }
        } catch (Exception $e) {
            $erro = 'Erro ao conectar com o banco de dados.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="PrimeDesk - Sistema de Gestão">
    <title>Login | PrimeDesk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 48px 40px;
            width: 100%;
            max-width: 480px;
        }
        .login-header { text-align: center; margin-bottom: 40px; }
        .logo-container {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-container i { font-size: 2.5rem; color: white; }
        .login-header h1 { font-size: 28px; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
        .login-header p { color: #64748b; font-size: 15px; }
        .form-group { margin-bottom: 24px; }
        .form-group label { display: block; margin-bottom: 8px; color: #1e293b; font-weight: 600; font-size: 14px; }
        .input-wrapper { position: relative; }
        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        .form-control {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            background: #f8fafc;
        }
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4); }
        .alert {
            padding: 14px 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            background: #fee2e2;
            color: #dc2626;
            border: 2px solid #fca5a5;
        }
        .login-footer { text-align: center; margin-top: 32px; padding-top: 24px; border-top: 2px solid #e2e8f0; }
        .login-footer p { color: #64748b; font-size: 13px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <div class="logo-container">
                <i class="fas fa-chart-line"></i>
            </div>
            <h1>PrimeDesk</h1>
            <p>Sistema de Gestão</p>
        </div>
        <?php if ($erro): ?>
        <div class="alert">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo htmlspecialchars($erro); ?></span>
        </div>
        <?php endif; ?>
        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope" style="margin-right: 6px;"></i> E-mail</label>
                <div class="input-wrapper">
                    <input type="email" id="email" name="email" class="form-control" placeholder="seu@email.com" required autofocus>
                    <i class="fas fa-envelope"></i>
                </div>
            </div>
            <div class="form-group">
                <label for="senha"><i class="fas fa-lock" style="margin-right: 6px;"></i> Senha</label>
                <div class="input-wrapper">
                    <input type="password" id="senha" name="senha" class="form-control" placeholder="••••••••" required>
                    <i class="fas fa-lock"></i>
                </div>
            </div>
            <button type="submit" class="btn-login" id="btnLogin">
                <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i> Entrar no Sistema
            </button>
        </form>
        <div class="login-footer">
            <p>&copy; <?php echo date('Y'); ?> PrimeDesk. Todos os direitos reservados.</p>
        </div>
    </div>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('btnLogin');
            btn.innerHTML = '<span class="loading"></span> Autenticando...';
            btn.disabled = true;
        });
    </script>
</body>
</html>
