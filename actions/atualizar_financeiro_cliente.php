<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(["status" => "error", "message" => "Não autorizado"]);
    exit;
}

$cliente_id = isset($_GET['id']) ? $_GET['id'] : 0;

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $stmt = $conn->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro WHERE cliente_id = ? AND status = 'Pago'");
    $stmt->execute([$cliente_id]);
    $totalPago = number_format($stmt->fetch()['total'], 2, ',', '.');
    
    $stmt = $conn->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro WHERE cliente_id = ? AND status = 'Pendente'");
    $stmt->execute([$cliente_id]);
    $totalPendente = number_format($stmt->fetch()['total'], 2, ',', '.');
    
    $stmt = $conn->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro WHERE cliente_id = ? AND status = 'Cancelado'");
    $stmt->execute([$cliente_id]);
    $totalCancelado = number_format($stmt->fetch()['total'], 2, ',', '.');
    
    $stmt = $conn->prepare("SELECT * FROM financeiro WHERE cliente_id = ? ORDER BY data_vencimento DESC");
    $stmt->execute([$cliente_id]);
    $financeiro = $stmt->fetchAll();
    
    echo json_encode([
        "status" => "success",
        "totalPago" => $totalPago,
        "totalPendente" => $totalPendente,
        "totalCancelado" => $totalCancelado,
        "financeiro" => $financeiro
    ]);
    
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>