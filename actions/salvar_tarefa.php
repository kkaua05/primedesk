<?php
session_start();
header('Content-Type: application/json');

// Verificar se está logado
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
    
    if (!$conn) {
        throw new Exception("Erro de conexão com o banco de dados.");
    }
    
    // Ler dados JSON
    $json = file_get_contents("php://input");
    if (empty($json)) {
        throw new Exception("Nenhum dado recebido.");
    }
    
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Erro ao decodificar JSON: " . json_last_error_msg());
    }
    
    $usuario_id = $_SESSION['usuario_id'];
    
    // Validar campos obrigatórios
    if (empty($data['titulo'])) {
        throw new Exception("O campo título é obrigatório.");
    }
    
    if (empty($data['data_inicio'])) {
        throw new Exception("A data de início é obrigatória.");
    }
    
    // Preparar dados
    $titulo = trim($data['titulo']);
    $descricao = isset($data['descricao']) ? trim($data['descricao']) : null;
    $categoria = isset($data['categoria']) ? $data['categoria'] : 'Tarefa';
    $data_inicio = $data['data_inicio'];
    $data_fim = isset($data['data_fim']) && !empty($data['data_fim']) ? $data['data_fim'] : null;
    $hora_inicio = isset($data['hora_inicio']) && !empty($data['hora_inicio']) ? $data['hora_inicio'] : null;
    $hora_fim = isset($data['hora_fim']) && !empty($data['hora_fim']) ? $data['hora_fim'] : null;
    $prioridade = isset($data['prioridade']) ? $data['prioridade'] : 'Media';
    $status = isset($data['status']) ? $data['status'] : 'Pendente';
    $cliente_id = isset($data['cliente_id']) && !empty($data['cliente_id']) ? $data['cliente_id'] : null;
    $lembrete = isset($data['lembrete']) && $data['lembrete'] == '1' ? 1 : 0;
    
    if (isset($data['id']) && !empty($data['id'])) {
        // Atualizar tarefa existente
        $query = "UPDATE agenda SET
            titulo = :titulo,
            descricao = :descricao,
            categoria = :categoria,
            data_inicio = :data_inicio,
            data_fim = :data_fim,
            hora_inicio = :hora_inicio,
            hora_fim = :hora_fim,
            prioridade = :prioridade,
            status = :status,
            cliente_id = :cliente_id,
            lembrete = :lembrete
            WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id", $data['id']);
        $mensagem = "Tarefa atualizada com sucesso!";
    } else {
        // Criar nova tarefa (compartilhada - todos podem ver)
        $query = "INSERT INTO agenda (usuario_id, titulo, descricao, categoria, data_inicio, data_fim,
            hora_inicio, hora_fim, prioridade, status, cliente_id, lembrete)
            VALUES (:usuario_id, :titulo, :descricao, :categoria, :data_inicio, :data_fim,
            :hora_inicio, :hora_fim, :prioridade, :status, :cliente_id, :lembrete)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":usuario_id", $usuario_id);
        $mensagem = "Tarefa criada com sucesso!";
    }
    
    $stmt->bindParam(":titulo", $titulo);
    $stmt->bindParam(":descricao", $descricao);
    $stmt->bindParam(":categoria", $categoria);
    $stmt->bindParam(":data_inicio", $data_inicio);
    $stmt->bindParam(":data_fim", $data_fim);
    $stmt->bindParam(":hora_inicio", $hora_inicio);
    $stmt->bindParam(":hora_fim", $hora_fim);
    $stmt->bindParam(":prioridade", $prioridade);
    $stmt->bindParam(":status", $status);
    $stmt->bindParam(":cliente_id", $cliente_id);
    $stmt->bindParam(":lembrete", $lembrete);
    
    if ($stmt->execute()) {
        // Criar notificação se for nova tarefa
        if (!isset($data['id'])) {
            try {
                $stmt_notif = $conn->prepare("INSERT INTO notificacoes (usuario_id, titulo, mensagem, tipo)
                    VALUES (?, ?, ?, ?)");
                $stmt_notif->execute([$usuario_id, 'Nova Tarefa', $titulo, 'info']);
            } catch (Exception $e) {
                // Ignorar erro de notificação
            }
        }
        
        echo json_encode([
            "status" => "success",
            "message" => $mensagem
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Erro ao executar query no banco de dados."
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
