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
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM clientes");
    $totalClientes = $stmt->fetch()['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM clientes WHERE status = 'Ativo'");
    $clientesAtivos = $stmt->fetch()['total'];
    
    $stmt = $conn->query("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro 
                          WHERE MONTH(data_pagamento) = MONTH(CURRENT_DATE()) 
                          AND YEAR(data_pagamento) = YEAR(CURRENT_DATE()) 
                          AND status = 'Pago'");
    $receitaMes = number_format($stmt->fetch()['total'], 2, ',', '.');
    
    $stmt = $conn->query("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro WHERE status = 'Pendente'");
    $valorPendente = number_format($stmt->fetch()['total'], 2, ',', '.');
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM financeiro");
    $totalServicos = $stmt->fetch()['total'];
    
    echo json_encode([
        "status" => "success",
        "totalClientes" => number_format($totalClientes),
        "clientesAtivos" => number_format($clientesAtivos),
        "receitaMes" => $receitaMes,
        "valorPendente" => $valorPendente,
        "totalServicos" => number_format($totalServicos)
    ]);
    
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>