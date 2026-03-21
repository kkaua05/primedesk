<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

try {
    // Sanitizar CPF (remover formatação)
    $cpf = isset($data['cpf']) ? preg_replace('/[^0-9]/', '', $data['cpf']) : '';
    $id = isset($data['id']) && !empty($data['id']) ? $data['id'] : null;
    
    // Validar se CPF já existe
    if (!empty($cpf)) {
        if ($id) {
            // Modo edição: verificar se CPF já existe para OUTRO cliente
            $stmt = $conn->prepare("SELECT id FROM clientes WHERE cpf = ? AND id != ?");
            $stmt->execute([$cpf, $id]);
        } else {
            // Modo cadastro: verificar se CPF já existe
            $stmt = $conn->prepare("SELECT id FROM clientes WHERE cpf = ?");
            $stmt->execute([$cpf]);
        }
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                "status" => "error", 
                "message" => "Já existe um cliente cadastrado com este CPF!"
            ]);
            exit;
        }
    }
    
    // Validar se e-mail já existe (opcional)
    $email = isset($data['email']) ? trim($data['email']) : '';
    if (!empty($email)) {
        if ($id) {
            $stmt = $conn->prepare("SELECT id FROM clientes WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
        } else {
            $stmt = $conn->prepare("SELECT id FROM clientes WHERE email = ?");
            $stmt->execute([$email]);
        }
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                "status" => "error", 
                "message" => "Já existe um cliente cadastrado com este e-mail!"
            ]);
            exit;
        }
    }
    
    if ($id) {
        // Atualizar
        $query = "UPDATE clientes SET 
                  nome = :nome, 
                  cpf = :cpf, 
                  contato = :contato, 
                  email = :email, 
                  nome_mae = :nome_mae, 
                  operadora = :operadora, 
                  vendedor = :vendedor, 
                  consultor = :consultor, 
                  observacoes = :obs,
                  status = :status
                  WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $mensagem = "Cliente atualizado com sucesso!";
    } else {
        // Criar
        $query = "INSERT INTO clientes (nome, cpf, contato, email, nome_mae, operadora, vendedor, consultor, observacoes, status) 
                  VALUES (:nome, :cpf, :contato, :email, :nome_mae, :operadora, :vendedor, :consultor, :obs, :status)";
        $stmt = $conn->prepare($query);
        $mensagem = "Cliente cadastrado com sucesso!";
    }

    $stmt->bindParam(":nome", $data['nome']);
    $stmt->bindParam(":cpf", $data['cpf']);
    $stmt->bindParam(":contato", $data['contato']);
    $stmt->bindParam(":email", $data['email']);
    $stmt->bindParam(":nome_mae", $data['nome_mae']);
    $stmt->bindParam(":operadora", $data['operadora']);
    $stmt->bindParam(":vendedor", $data['vendedor']);
    $stmt->bindParam(":consultor", $data['consultor']);
    $stmt->bindParam(":obs", $data['observacoes']);
    $stmt->bindParam(":status", $data['status']);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success", 
            "message" => $mensagem,
            "redirect" => "index.php?page=clientes"
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Erro ao salvar cliente."]);
    }
} catch (PDOException $e) {
    // Verificar se é erro de duplicação (código 23000)
    if ($e->getCode() == 23000) {
        echo json_encode([
            "status" => "error", 
            "message" => "Já existe um cliente cadastrado com este CPF!"
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Erro: " . $e->getMessage()]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>