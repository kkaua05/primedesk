<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? 0;
$status = $data['status'] ?? 'Ativo';

try {
    // Verificar se não está tentando desativar a si mesmo
    session_start();
    if (isset($_SESSION['usuario_id']) && $_SESSION['usuario_id'] == $id && $status == 'Inativo') {
        throw new Exception("Você não pode desativar seu próprio usuário.");
    }
    
    $stmt = $conn->prepare("UPDATE usuarios SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    
    if ($stmt->rowCount() > 0) {
        $acao = $status == 'Ativo' ? 'ativado' : 'desativado';
        echo json_encode([
            "status" => "success",
            "message" => "Usuário " . $acao . " com sucesso!"
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