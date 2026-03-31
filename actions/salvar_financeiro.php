<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

try {
    $cliente_id = $data['cliente_id'];
    $descricao = trim($data['descricao']);
    $valor_total = floatval($data['valor']);
    $data_vencimento = $data['data_vencimento'];
    $data_pagamento = !empty($data['data_pagamento']) ? $data['data_pagamento'] : null;
    $forma_pagamento = $data['forma_pagamento'];
    $status = $data['status'];
    
    // Verificar se é parcelado
    $tipo_lancamento = isset($data['tipo_lancamento']) ? $data['tipo_lancamento'] : 'unico';
    $total_parcelas = isset($data['total_parcelas']) ? intval($data['total_parcelas']) : 1;
    
    // Validações
    if (empty($descricao)) {
        throw new Exception("Descrição é obrigatória.");
    }
    if ($valor_total <= 0) {
        throw new Exception("Valor deve ser maior que zero.");
    }
    if (empty($data_vencimento)) {
        throw new Exception("Data de vencimento é obrigatória.");
    }
    
    // Calcular valor da parcela
    $valor_parcela = $valor_total / $total_parcelas;
    
    // Se for edição, excluir parcelas existentes primeiro
    if (isset($data['id']) && !empty($data['id'])) {
        $id = $data['id'];
        // Verificar se tem parcelas filhas
        $stmt = $conn->prepare("SELECT id FROM financeiro WHERE parcela_pai_id = ?");
        $stmt->execute([$id]);
        $parcelas_filhas = $stmt->fetchAll();
        
        foreach ($parcelas_filhas as $filha) {
            $stmt = $conn->prepare("DELETE FROM financeiro WHERE id = ?");
            $stmt->execute([$filha['id']]);
        }
        
        // Atualizar parcela pai
        $query = "UPDATE financeiro SET
            descricao = :desc,
            valor = :val,
            valor_original = :val_orig,
            data_vencimento = :venc,
            data_pagamento = :pag,
            forma_pagamento = :forma,
            status = :status,
            total_parcelas = :total_parc,
            tipo_lancamento = :tipo
            WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $parcela_pai_id = $id;
    } else {
        // Criar nova parcela pai
        $query = "INSERT INTO financeiro (cliente_id, descricao, valor, valor_original, data_vencimento, 
            data_pagamento, forma_pagamento, status, parcela_atual, total_parcelas, parcela_pai_id, tipo_lancamento)
            VALUES (:cid, :desc, :val, :val_orig, :venc, :pag, :forma, :status, 1, :total_parc, NULL, :tipo)";
        $stmt = $conn->prepare($query);
        $mensagem = "Lançamento salvo com sucesso!";
    }
    
    $stmt->bindParam(":cid", $cliente_id);
    $stmt->bindParam(":desc", $descricao);
    $stmt->bindParam(":val", $valor_parcela);
    $stmt->bindParam(":val_orig", $valor_total);
    $stmt->bindParam(":venc", $data_vencimento);
    $stmt->bindParam(":pag", $data_pagamento);
    $stmt->bindParam(":forma", $forma_pagamento);
    $stmt->bindParam(":status", $status);
    $stmt->bindParam(":total_parc", $total_parcelas);
    $stmt->bindParam(":tipo", $tipo_lancamento);
    
    if (!$stmt->execute()) {
        throw new Exception("Erro ao salvar parcela principal.");
    }
    
    // Se não tinha ID, pegar o ID inserido
    if (!isset($id)) {
        $id = $conn->lastInsertId();
        $parcela_pai_id = $id;
        $mensagem = "Lançamento parcelado em {$total_parcelas}x criado com sucesso!";
    }
    
    // Criar parcelas adicionais se necessário
    if ($total_parcelas > 1) {
        $data_venc_base = new DateTime($data_vencimento);
        
        for ($i = 2; $i <= $total_parcelas; $i++) {
            // Adicionar mês para cada parcela
            $data_venc_parcela = clone $data_venc_base;
            $data_venc_parcela->modify("+".($i-1)." months");
            
            $query_parcela = "INSERT INTO financeiro (cliente_id, descricao, valor, valor_original, 
                data_vencimento, forma_pagamento, status, parcela_atual, total_parcelas, parcela_pai_id, tipo_lancamento)
                VALUES (:cid, :desc, :val, :val_orig, :venc, :forma, 'Pendente', :parcela, :total_parc, :pai_id, :tipo)";
            
            $stmt_parcela = $conn->prepare($query_parcela);
            $descricao_parcela = $descricao . " (" . $i . "/" . $total_parcelas . ")";
            
            $stmt_parcela->bindParam(":cid", $cliente_id);
            $stmt_parcela->bindParam(":desc", $descricao_parcela);
            $stmt_parcela->bindParam(":val", $valor_parcela);
            $stmt_parcela->bindParam(":val_orig", $valor_total);
            $stmt_parcela->bindParam(":venc", $data_venc_parcela->format('Y-m-d'));
            $stmt_parcela->bindParam(":forma", $forma_pagamento);
            $stmt_parcela->bindParam(":parcela", $i);
            $stmt_parcela->bindParam(":total_parc", $total_parcelas);
            $stmt_parcela->bindParam(":pai_id", $parcela_pai_id);
            $stmt_parcela->bindParam(":tipo", $tipo_lancamento);
            
            if (!$stmt_parcela->execute()) {
                throw new Exception("Erro ao criar parcela " . $i);
            }
        }
    }
    
    echo json_encode([
        "status" => "success",
        "message" => $mensagem,
        "redirect" => "index.php?page=financeiro_clientes&id=" . $cliente_id
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
