<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(["status" => "error", "message" => "Não autorizado"]);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

$id = isset($_GET['id']) ? $_GET['id'] : 0;
$usuario_id = $_SESSION['usuario_id'];

try {
    $stmt = $conn->prepare("SELECT * FROM agenda WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);
    $data = $stmt->fetch();
    
    if ($data) {
        echo json_encode([
            "status" => "success",
            "data" => $data
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Tarefa não encontrada"]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>