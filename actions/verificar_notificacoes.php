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
$usuario_id = $_SESSION['usuario_id'];

try {
    // Buscar notificações não lidas
    $stmt = $conn->prepare("SELECT * FROM notificacoes 
                            WHERE usuario_id = ? AND lido = 0 
                            ORDER BY data_criacao DESC LIMIT 5");
    $stmt->execute([$usuario_id]);
    $notificacoes = $stmt->fetchAll();
    
    echo json_encode([
        "status" => "success",
        "novas" => count($notificacoes),
        "mensagens" => $notificacoes
    ]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>