<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : 0;

try {
    $stmt = $conn->prepare("SELECT id, nome, email, nivel, status FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    
    if ($data) {
        echo json_encode([
            "status" => "success",
            "data" => $data
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Usuário não encontrado"
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>