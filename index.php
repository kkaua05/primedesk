<?php
// Iniciar sessão apenas se não existir
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir verificação de sessão (contém as funções)
require_once 'includes/verificar_sessao.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Verificar permissão da página
if (!verificarPermissao($page)) {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Acesso Negado!',
            text: 'Você não tem permissão para acessar esta página.',
            confirmButtonText: 'Voltar'
        }).then(function() {
            window.location.href = 'index.php?page=dashboard';
        });
    </script>";
    exit;
}

include 'includes/header.php';

switch($page) {
    case 'dashboard':
        include 'pages/dashboard.php';
        break;
    case 'clientes':
        include 'pages/clientes.php';
        break;
    case 'cadastrar_cliente':
        include 'pages/cadastrar_cliente.php';
        break;
    case 'editar_cliente':
        include 'pages/editar_cliente.php';
        break;
    case 'visualizar_cliente':
        include 'pages/visualizar_cliente.php';
        break;
    case 'financeiro_clientes':
        include 'pages/financeiro_clientes.php';
        break;
    case 'financeiro_mensal':
        include 'pages/financeiro_mensal.php';
        break;
    case 'relatorios':
        include 'pages/relatorios.php';
        break;
    default:
        include 'pages/dashboard.php';

     case 'agenda':
        include 'pages/agenda.php';
    break;
}

include 'includes/footer.php';
?>