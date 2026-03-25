<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? 0;

try {
    // Verificar se não está tentando excluir a si mesmo
    session_start();
    if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $id) {
        throw new Exception("Você não pode excluir seu próprio usuário.");
    }
    
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "Usuário excluído com sucesso!"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Usuário não encontrado."
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>