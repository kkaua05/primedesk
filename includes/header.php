<?php
// Verificar se sessão já existe antes de iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Verificar timeout da sessão (30 minutos)
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 1800) {
    session_destroy();
    header('Location: login.php?timeout=1');
    exit;
}

// Atualizar tempo da sessão
$_SESSION['login_time'] = time();

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrimeDesk - Sistema de Gestão</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="top-bar">
            <div class="logo-mobile">
                <i class="fas fa-bars" onclick="toggleSidebar()"></i>
                <span>Rtcom Consultoria</span>
            </div>
            <div class="user-info">
                <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></span>
                <span class="badge <?php echo ($_SESSION['usuario_nivel'] === 'Administrador') ? 'badge-admin' : 'badge-funcionario'; ?>">
                    <?php echo strtoupper($_SESSION['usuario_nivel']); ?>
                </span>
                <span class="date"><i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i'); ?></span>
                <a href="logout.php" class="btn-sair" title="Sair do Sistema">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
        <div class="content-area">