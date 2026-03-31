<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : 0;

try {
    // Buscar todas as parcelas relacionadas
    $stmt = $conn->prepare("SELECT * FROM financeiro 
        WHERE parcela_pai_id = ? OR id = ?
        ORDER BY parcela_atual ASC");
    $stmt->execute([$id, $id]);
    $parcelas = $stmt->fetchAll();
    
    if (count($parcelas) > 0) {
        echo json_encode([
            "status" => "success",
            "parcelas" => $parcelas
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Nenhuma parcela encontrada"
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>