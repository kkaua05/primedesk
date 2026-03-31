<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Não autorizado"
    ]);
    exit;
}

require_once '../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);
    
    $id = $data['id'] ?? 0;
    
    if (!$id) {
        throw new Exception("ID da parcela não fornecido");
    }
    
    // Atualizar parcela para paga
    $stmt = $conn->prepare("UPDATE financeiro SET 
                            status = 'Pago', 
                            data_pagamento = CURDATE() 
                            WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "Parcela marcada como paga com sucesso!"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Parcela não encontrada"
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>