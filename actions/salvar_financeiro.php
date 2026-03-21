<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

try {
    if (isset($data['id']) && !empty($data['id'])) {
        // Atualizar
        $query = "UPDATE financeiro SET 
                  descricao = :desc, 
                  valor = :val, 
                  data_vencimento = :venc, 
                  data_pagamento = :pag, 
                  forma_pagamento = :forma,
                  status = :status 
                  WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id", $data['id']);
    } else {
        // Criar
        $query = "INSERT INTO financeiro (cliente_id, descricao, valor, data_vencimento, data_pagamento, forma_pagamento, status) 
                  VALUES (:cid, :desc, :val, :venc, :pag, :forma, :status)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":cid", $data['cliente_id']);
    }

    $stmt->bindParam(":desc", $data['descricao']);
    $stmt->bindParam(":val", $data['valor']);
    $stmt->bindParam(":venc", $data['data_vencimento']);
    $stmt->bindParam(":pag", $data['data_pagamento']);
    $stmt->bindParam(":forma", $data['forma_pagamento']);
    $stmt->bindParam(":status", $data['status']);

    if ($stmt->execute()) {
        $cliente_id = isset($data['cliente_id']) ? $data['cliente_id'] : 0;
        echo json_encode([
            "status" => "success", 
            "message" => "Lançamento salvo com sucesso!",
            "redirect" => "index.php?page=financeiro_clientes&id=" . $cliente_id
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Erro ao salvar lançamento."]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>