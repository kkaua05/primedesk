<?php
$usuarioNivel = $_SESSION['usuario_nivel'] ?? 'Funcionario';
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-chart-line"></i>
        <h2>PrimeDesk</h2>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="index.php?page=dashboard" class="<?php echo ($page == 'dashboard') ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="index.php?page=clientes" class="<?php echo in_array($page, ['clientes', 'cadastrar_cliente', 'editar_cliente', 'visualizar_cliente']) ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Clientes</span>
            </a>
        </li>
        <li>
            <a href="index.php?page=financeiro_mensal" class="<?php echo ($page == 'financeiro_mensal') ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>Financeiro Mensal</span>
            </a>
        </li>
        <li>
            <a href="index.php?page=agenda" class="<?php echo ($page == 'agenda') ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>Agenda</span>
            </a>
        </li>
        <?php if ($usuarioNivel === 'Administrador'): ?>
        <li>
            <a href="index.php?page=relatorios" class="<?php echo ($page == 'relatorios') ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i>
                <span>Relatórios</span>
            </a>
        </li>
        <li>
            <a href="index.php?page=usuarios" class="<?php echo ($page == 'usuarios') ? 'active' : ''; ?>">
                <i class="fas fa-users-cog"></i>
                <span>Usuários</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
    <div class="sidebar-footer">
        <p>&copy; 2026 PrimeDesk</p>
        <p style="font-size: 0.75rem; color: #64748b; margin-top: 5px;">
            <i class="fas fa-user-shield"></i> <?php echo $usuarioNivel; ?>
        </p>
    </div>
</div>
