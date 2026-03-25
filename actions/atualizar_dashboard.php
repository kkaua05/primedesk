<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(["status" => "error", "message" => "Não autorizado"]);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Total de clientes
    $stmt = $conn->query("SELECT COUNT(*) as total FROM clientes");
    $totalClientes = $stmt->fetch()['total'];
    
    // Clientes ativos
    $stmt = $conn->query("SELECT COUNT(*) as total FROM clientes WHERE status = 'Ativo'");
    $clientesAtivos = $stmt->fetch()['total'];
    
    // Receita do mês
    $stmt = $conn->query("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro
        WHERE MONTH(data_pagamento) = MONTH(CURRENT_DATE())
        AND YEAR(data_pagamento) = YEAR(CURRENT_DATE())
        AND status = 'Pago'");
    $receitaMes = number_format($stmt->fetch()['total'], 2, ',', '.');
    
    // Valor pendente
    $stmt = $conn->query("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro WHERE status = 'Pendente'");
    $valorPendente = number_format($stmt->fetch()['total'], 2, ',', '.');
    
    // Total de serviços
    $stmt = $conn->query("SELECT COUNT(*) as total FROM financeiro");
    $totalServicos = $stmt->fetch()['total'];
    
    // Receita mensal por mês
    $stmt = $conn->query("SELECT MONTH(data_pagamento) as mes, COALESCE(SUM(valor), 0) as total
        FROM financeiro
        WHERE status = 'Pago'
        AND YEAR(data_pagamento) = YEAR(CURRENT_DATE())
        GROUP BY MONTH(data_pagamento)
        ORDER BY mes");
    $receitaMensal = $stmt->fetchAll();
    
    echo json_encode([
        "status" => "success",
        "totalClientes" => $totalClientes,
        "clientesAtivos" => $clientesAtivos,
        "receitaMes" => $receitaMes,
        "valorPendente" => $valorPendente,
        "totalServicos" => $totalServicos,
        "receitaMensal" => $receitaMensal
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
