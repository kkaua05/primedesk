<?php
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

// Função para verificar nível de acesso
function verificarNivel($nivelPermitido) {
    if (!isset($_SESSION['usuario_nivel'])) {
        return false;
    }
    if ($_SESSION['usuario_nivel'] === 'Administrador') {
        return true;
    }
    if (is_array($nivelPermitido)) {
        return in_array($_SESSION['usuario_nivel'], $nivelPermitido);
    }
    return $_SESSION['usuario_nivel'] === $nivelPermitido;
}

// Função para verificar permissão de página
// AGORA: Funcionário pode acessar TODAS as páginas exceto Usuários e Relatórios
function verificarPermissao($pagina) {
    if (!isset($_SESSION['usuario_nivel'])) {
        return false;
    }
    
    // Administrador tem acesso a tudo
    if ($_SESSION['usuario_nivel'] === 'Administrador') {
        return true;
    }
    
    // Funcionário tem acesso a quase tudo (compartilhado)
    $paginasFuncionario = [
        'dashboard', 
        'clientes', 
        'cadastrar_cliente', 
        'editar_cliente',
        'visualizar_cliente', 
        'financeiro_clientes', 
        'financeiro_mensal', 
        'agenda',
        'relatorios'  // Adicionado: funcionário pode ver relatórios
    ];
    
    return in_array($pagina, $paginasFuncionario);
}
?>
