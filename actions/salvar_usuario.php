<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

try {
    $nome = trim($data['nome']);
    $email = trim($data['email']);
    $nivel = $data['nivel'] ?? 'Funcionario';
    $status = $data['status'] ?? 'Ativo';
    $id = isset($data['id']) && !empty($data['id']) ? $data['id'] : null;
    
    // Validações
    if (empty($nome)) {
        throw new Exception("Nome é obrigatório.");
    }
    
    if (empty($email)) {
        throw new Exception("E-mail é obrigatório.");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("E-mail inválido.");
    }
    
    // Verificar se e-mail já existe
    if ($id) {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
    } else {
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
    }
    
    if ($stmt->rowCount() > 0) {
        throw new Exception("Já existe um usuário cadastrado com este e-mail.");
    }
    
    if ($id) {
        // Atualizar usuário
        if (isset($data['senha']) && !empty($data['senha'])) {
            // Atualizar com nova senha
            $senha_hash = password_hash($data['senha'], PASSWORD_DEFAULT);
            $query = "UPDATE usuarios SET 
                      nome = :nome, 
                      email = :email, 
                      senha = :senha,
                      nivel = :nivel, 
                      status = :status 
                      WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(":senha", $senha_hash);
        } else {
            // Atualizar sem alterar senha
            $query = "UPDATE usuarios SET 
                      nome = :nome, 
                      email = :email, 
                      nivel = :nivel, 
                      status = :status 
                      WHERE id = :id";
            $stmt = $conn->prepare($query);
        }
        
        $stmt->bindParam(":id", $id);
        $mensagem = "Usuário atualizado com sucesso!";
    } else {
        // Criar novo usuário
        if (!isset($data['senha']) || empty($data['senha'])) {
            throw new Exception("Senha é obrigatória para novo usuário.");
        }
        
        if (strlen($data['senha']) < 6) {
            throw new Exception("A senha deve ter no mínimo 6 caracteres.");
        }
        
        $senha_hash = password_hash($data['senha'], PASSWORD_DEFAULT);
        
        $query = "INSERT INTO usuarios (nome, email, senha, nivel, status) 
                  VALUES (:nome, :email, :senha, :nivel, :status)";
        $stmt = $conn->prepare($query);
        $mensagem = "Usuário cadastrado com sucesso!";
    }
    
    $stmt->bindParam(":nome", $nome);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":nivel", $nivel);
    $stmt->bindParam(":status", $status);
    
    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => $mensagem
        ]);
    } else {
        throw new Exception("Erro ao salvar usuário.");
    }
    
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>