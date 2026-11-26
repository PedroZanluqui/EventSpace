<?php
declare(strict_types=1);

function validarDadosEspaco(array $dados): array
{
    $erros = [];
    if (trim((string)($dados['nome'] ?? '')) === '') {
        $erros['nome'] = 'Informe o nome do espaço (em português e sem abreviações).';
    }
    if (trim((string)($dados['tipo'] ?? '')) === '') {
        $erros['tipo'] = 'Selecione ou informe o tipo de espaço (ex.: Salão de festas, Auditório).';
    }
    if (trim((string)($dados['descricao'] ?? '')) === '') {
        $erros['descricao'] = 'Informe uma descrição clara sobre a estrutura do espaço.';
    }
    if ((int)($dados['capacidade'] ?? 0) < 1) {
        $erros['capacidade'] = 'A capacidade máxima deve ser de pelo menos 1 pessoa.';
    }
    if ((float)($dados['preco'] ?? 0) <= 0) {
        $erros['preco'] = 'Informe um valor de diária válido maior que zero (ex.: 2500.00).';
    }
    if (trim((string)($dados['comodidades'] ?? '')) === '') {
        $erros['comodidades'] = 'Selecione pelo menos uma comodidade oferecida pelo espaço.';
    }
    $imgUrl = trim((string)($dados['imagem_url'] ?? ''));
    if ($imgUrl !== '' && !validarUrlImagem($imgUrl)) {
        $erros['imagem_url'] = 'Informe uma URL de imagem válida iniciando com http:// ou https://.';
    }
    if (trim((string)($dados['endereco'] ?? '')) === '') {
        $erros['endereco'] = 'Informe o endereço do local para exibição no Google Maps.';
    }
    return $erros;
}

function listarTiposEspacos(mysqli $conn): array
{
    $res = $conn->query('SELECT DISTINCT tipo FROM espacos WHERE disponivel = 1 ORDER BY tipo');
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function listarEspacosDestaque(mysqli $conn, int $limite = 3): array
{
    $st = $conn->prepare('SELECT * FROM espacos WHERE disponivel = 1 ORDER BY capacidade DESC LIMIT ?');
    $st->bind_param('i', $limite);
    $st->execute();
    return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

function buscarEspacosFiltrados(mysqli $conn, array $filtros): array
{
    $tipo       = trim((string)($filtros['tipo'] ?? ''));
    $data       = trim((string)($filtros['data'] ?? ''));
    $pessoas    = (int)($filtros['pessoas'] ?? 0);
    $comodidade = trim((string)($filtros['comodidade'] ?? ''));
    $ordem      = trim((string)($filtros['ordem'] ?? 'nome_asc'));

    $sql = 'SELECT * FROM espacos WHERE disponivel = 1';
    $tipos = '';
    $params = [];

    if ($tipo !== '') {
        $sql .= ' AND tipo = ?';
        $tipos .= 's';
        $params[] = $tipo;
    }
    if ($pessoas > 0) {
        $sql .= ' AND capacidade >= ?';
        $tipos .= 'i';
        $params[] = $pessoas;
    }
    if ($comodidade !== '') {
        $sql .= ' AND comodidades LIKE ?';
        $tipos .= 's';
        $params[] = '%' . $comodidade . '%';
    }
    if ($data !== '') {
        $sql .= " AND id NOT IN (SELECT espaco_id FROM reservas WHERE data_evento = ? AND status = 'APROVADA')";
        $tipos .= 's';
        $params[] = $data;
    }

    $mapaOrdem = [
        'nome_asc'   => 'nome ASC',
        'preco_asc'  => 'preco ASC',
        'preco_desc' => 'preco DESC',
        'cap_desc'   => 'capacidade DESC',
        'cap_asc'    => 'capacidade ASC',
    ];
    $sql .= ' ORDER BY ' . ($mapaOrdem[$ordem] ?? 'nome ASC');

    $st = $conn->prepare($sql);
    if (!empty($params)) {
        $st->bind_param($tipos, ...$params);
    }
    $st->execute();
    return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

function listarTodosEspacos(mysqli $conn): array
{
    $res = $conn->query('SELECT * FROM espacos ORDER BY nome');
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function listarEspacosComContagemReservas(mysqli $conn): array
{
    $sql = 'SELECT e.*, (SELECT COUNT(*) FROM reservas r WHERE r.espaco_id = e.id) AS total_reservas
            FROM espacos e
            ORDER BY e.nome';
    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

function buscarEspacoPorId(mysqli $conn, int $id, bool $apenasDisponivel = false): ?array
{
    $sql = $apenasDisponivel
        ? 'SELECT * FROM espacos WHERE id = ? AND disponivel = 1 LIMIT 1'
        : 'SELECT * FROM espacos WHERE id = ? LIMIT 1';
    $st = $conn->prepare($sql);
    $st->bind_param('i', $id);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    return $row ?: null;
}

function buscarEspacosPorIds(mysqli $conn, array $ids): array
{
    $idsLimpos = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($v) => $v > 0)));
    if (empty($idsLimpos)) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($idsLimpos), '?'));
    $tipos = str_repeat('i', count($idsLimpos));
    $st = $conn->prepare("SELECT * FROM espacos WHERE id IN ($placeholders) ORDER BY nome");
    $st->bind_param($tipos, ...$idsLimpos);
    $st->execute();
    return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

function salvarEspaco(mysqli $conn, array $dados): int
{
    $id                 = (int)($dados['id'] ?? 0);
    $nome               = (string)$dados['nome'];
    $tipo               = (string)$dados['tipo'];
    $descricao          = (string)$dados['descricao'];
    $comodidades        = (string)$dados['comodidades'];
    $capacidade         = (int)$dados['capacidade'];
    $preco              = (float)$dados['preco'];
    $disponivel         = (int)$dados['disponivel'];
    $responsavelNome    = (string)$dados['responsavel_nome'];
    $responsavelContato = (string)$dados['responsavel_contato'];
    $imagemUrl          = ($dados['imagem_url'] ?? '') !== '' ? (string)$dados['imagem_url'] : null;
    $imagemCredito      = ($dados['imagem_credito'] ?? '') !== '' ? (string)$dados['imagem_credito'] : null;
    $galeriaImagens     = ($dados['galeria_imagens'] ?? '') !== '' ? (string)$dados['galeria_imagens'] : null;
    $endereco           = ($dados['endereco'] ?? '') !== '' ? (string)$dados['endereco'] : null;
    $cidade             = ($dados['cidade'] ?? '') !== '' ? (string)$dados['cidade'] : 'São Paulo';
    $estado             = ($dados['estado'] ?? '') !== '' ? strtoupper((string)$dados['estado']) : 'SP';
    $cep                = ($dados['cep'] ?? '') !== '' ? (string)$dados['cep'] : null;

    if ($id > 0) {
        $st = $conn->prepare(
            'UPDATE espacos
             SET nome = ?, tipo = ?, descricao = ?, comodidades = ?, capacidade = ?, preco = ?, disponivel = ?,
                 responsavel_nome = ?, responsavel_contato = ?,
                 imagem_url = ?, imagem_credito = ?, galeria_imagens = ?,
                 endereco = ?, cidade = ?, estado = ?, cep = ?
             WHERE id = ?'
        );
        $st->bind_param(
            'ssssidisssssssssi',
            $nome,
            $tipo,
            $descricao,
            $comodidades,
            $capacidade,
            $preco,
            $disponivel,
            $responsavelNome,
            $responsavelContato,
            $imagemUrl,
            $imagemCredito,
            $galeriaImagens,
            $endereco,
            $cidade,
            $estado,
            $cep,
            $id
        );
        $st->execute();
        return $id;
    }

    $st = $conn->prepare(
        'INSERT INTO espacos (
            nome, tipo, descricao, comodidades, capacidade, preco, disponivel,
            responsavel_nome, responsavel_contato,
            imagem_url, imagem_credito, galeria_imagens,
            endereco, cidade, estado, cep
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $st->bind_param(
        'ssssidisssssssss',
        $nome,
        $tipo,
        $descricao,
        $comodidades,
        $capacidade,
        $preco,
        $disponivel,
        $responsavelNome,
        $responsavelContato,
        $imagemUrl,
        $imagemCredito,
        $galeriaImagens,
        $endereco,
        $cidade,
        $estado,
        $cep
    );
    $st->execute();
    return (int)$conn->insert_id;
}

function atualizarFotosEspaco(mysqli $conn, int $id, string $imagemUrl, ?string $credito, array $galeria = []): bool
{
    $galeriaJson = !empty($galeria) ? json_encode(array_values($galeria), JSON_UNESCAPED_SLASHES) : null;
    $st = $conn->prepare(
        'UPDATE espacos
         SET imagem_url = ?, imagem_credito = ?, galeria_imagens = COALESCE(galeria_imagens, ?)
         WHERE id = ?'
    );
    $st->bind_param('sssi', $imagemUrl, $credito, $galeriaJson, $id);
    return $st->execute();
}

function alternarDisponibilidadeEspaco(mysqli $conn, int $id): bool
{
    $st = $conn->prepare('UPDATE espacos SET disponivel = 1 - disponivel WHERE id = ?');
    $st->bind_param('i', $id);
    return $st->execute();
}

function excluirOuArquivarEspaco(mysqli $conn, int $id): array
{
    $stCheck = $conn->prepare('SELECT COUNT(*) FROM reservas WHERE espaco_id = ?');
    $stCheck->bind_param('i', $id);
    $stCheck->execute();
    $qtd = (int)$stCheck->get_result()->fetch_row()[0];

    if ($qtd > 0) {
        $st = $conn->prepare('UPDATE espacos SET disponivel = 0 WHERE id = ?');
        $st->bind_param('i', $id);
        $st->execute();
        return ['acao' => 'arquivado', 'qtd_reservas' => $qtd];
    }

    $st = $conn->prepare('DELETE FROM espacos WHERE id = ?');
    $st->bind_param('i', $id);
    $st->execute();
    return ['acao' => 'excluido', 'qtd_reservas' => 0];
}
