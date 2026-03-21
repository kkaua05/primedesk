<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? 0;

try {
    $stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "status" => "success", 
            "message" => "Cliente excluído com sucesso!",
            "redirect" => "index.php?page=clientes"
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Cliente não encontrado."]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>