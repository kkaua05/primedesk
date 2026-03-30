<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Não autorizado. Faça login novamente."
    ]);
    exit;
}

require_once '../config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);
    
    if (!isset($data['id']) || empty($data['id'])) {
        throw new Exception("ID da tarefa não fornecido.");
    }
    
    $id = $data['id'];
    
    // Verificar se a tarefa existe (compartilhado - qualquer usuário pode deletar)
    $stmt = $conn->prepare("SELECT id FROM agenda WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() == 0) {
        throw new Exception("Tarefa não encontrada.");
    }
    
    // Excluir a tarefa
    $stmt = $conn->prepare("DELETE FROM agenda WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode([
        "status" => "success",
        "message" => "Tarefa excluída com sucesso!"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
