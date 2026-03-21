<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(["status" => "error", "message" => "Não autorizado"]);
    exit;
}

$mes = isset($_GET['mes']) ? $_GET['mes'] : date('m');
$ano = isset($_GET['ano']) ? $_GET['ano'] : date('Y');

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare("SELECT COALESCE(SUM(f.valor), 0) as total 
                            FROM financeiro f 
                            WHERE MONTH(f.data_pagamento) = ? 
                            AND YEAR(f.data_pagamento) = ? 
                            AND f.status = 'Pago'");
    $stmt->execute([$mes, $ano]);
    $receitaTotal = number_format($stmt->fetch()['total'], 2, ',', '.');
    
    $stmt = $conn->prepare("SELECT COALESCE(SUM(valor), 0) as total 
                            FROM financeiro 
                            WHERE MONTH(data_vencimento) = ? 
                            AND YEAR(data_vencimento) = ? 
                            AND status = 'Pendente'");
    $stmt->execute([$mes, $ano]);
    $pendenteTotal = number_format($stmt->fetch()['total'], 2, ',', '.');
    
    $stmt = $conn->prepare("SELECT COALESCE(SUM(valor), 0) as total 
                            FROM financeiro 
                            WHERE MONTH(data_vencimento) = ? 
                            AND YEAR(data_vencimento) = ? 
                            AND status = 'Cancelado'");
    $stmt->execute([$mes, $ano]);
    $canceladoTotal = number_format($stmt->fetch()['total'], 2, ',', '.');
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total 
                            FROM financeiro 
                            WHERE MONTH(data_vencimento) = ? 
                            AND YEAR(data_vencimento) = ? 
                            AND status = 'Pago'");
    $stmt->execute([$mes, $ano]);
    $pagosCount = $stmt->fetch()['total'];
    
    echo json_encode([
        "status" => "success",
        "receitaTotal" => $receitaTotal,
        "pendenteTotal" => $pendenteTotal,
        "canceladoTotal" => $canceladoTotal,
        "pagosCount" => $pagosCount
    ]);
    
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>