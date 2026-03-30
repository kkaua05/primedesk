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

try {
    // Buscar tarefa com informações do usuário e cliente (compartilhado)
    $stmt = $conn->prepare("SELECT a.*, c.nome as cliente_nome, u.nome as nome_usuario 
                            FROM agenda a
                            LEFT JOIN clientes c ON a.cliente_id = c.id
                            LEFT JOIN usuarios u ON a.usuario_id = u.id
                            WHERE a.id = ?");
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
            "message" => "Tarefa não encontrada"
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
