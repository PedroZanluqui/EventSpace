<?php
declare(strict_types=1);

function processarReservasAutomaticas(mysqli $conn): array
{
    $concluidas = 0;
    $autoCanceladas = 0;

    if ($conn->query("UPDATE reservas SET status = 'CONCLUIDA' WHERE status = 'APROVADA' AND data_evento < CURDATE()")) {
        $concluidas = (int)$conn->affected_rows;
    }

    $sqlAuto = "UPDATE reservas
                SET status = 'CANCELADA',
                    cancelada_automaticamente = 1,
                    motivo_cancelamento = CONCAT(
                        'Cancelada automaticamente: prazo para resolver pendências expirou em ',
                        DATE_FORMAT(prazo_resolucao, '%d/%m/%Y às %H:%i'),
                        '.'
                    ),
                    data_atualizacao = NOW(),
                    notificado = 0
                WHERE status = 'REJEITADA'
                  AND prazo_resolucao IS NOT NULL
                  AND prazo_resolucao <= NOW()";

    if ($conn->query($sqlAuto)) {
        $autoCanceladas = (int)$conn->affected_rows;
    }

    return [
        'concluidas'      => $concluidas,
        'auto_canceladas' => $autoCanceladas,
    ];
}

function reservaTemConflito(mysqli $conn, int $espacoId, string $data, string $ini, string $fim, int $ignorarId = 0): bool
{
    $st = $conn->prepare(
        "SELECT COUNT(*) FROM reservas
         WHERE espaco_id = ? AND data_evento = ? AND status = 'APROVADA' AND id <> ?
           AND hora_inicio < ? AND hora_fim > ?"
    );
    $st->bind_param('isiss', $espacoId, $data, $ignorarId, $fim, $ini);
    $st->execute();
    return (int)$st->get_result()->fetch_row()[0] > 0;
}

function validarSolicitacaoReserva(
    mysqli $conn,
    array $espaco,
    array $d,
    int $ignorarId,
    bool $exigirAceite,
    bool &$conflito
): array {
    $erros = [];
    $data = (string)($d['data'] ?? '');
    $ini  = (string)($d['ini'] ?? '');
    $fim  = (string)($d['fim'] ?? '');
    $tipo = (string)($d['tipo'] ?? '');
    $pessoas = (int)($d['pessoas'] ?? 0);
    $aceite  = !empty($d['aceite']);

    if ($data === '') {
        $erros['data'] = 'Informe a data do evento. Selecione um dia a partir de hoje (' . date('d/m/Y') . ').';
    } elseif ($data < date('Y-m-d')) {
        $erros['data'] = 'A data não pode estar no passado. Escolha uma data a partir de ' . date('d/m/Y') . '.';
    }

    if ($ini === '') {
        $erros['ini'] = 'Informe o horário de início do evento.';
    }
    if ($fim === '') {
        $erros['fim'] = 'Informe o horário de término do evento.';
    }
    if ($ini !== '' && $fim !== '' && $ini >= $fim) {
        $erros['fim'] = 'O horário de término (' . $fim . ') deve ser maior que o horário de início (' . $ini . ').';
    }

    if ($tipo === '') {
        $erros['tipo'] = 'Selecione ou informe o tipo de evento (ex.: Casamento, Workshop, Aniversário).';
    }

    $cap = (int)($espaco['capacidade'] ?? 0);
    if ($pessoas < 1) {
        $erros['pessoas'] = 'Informe uma quantidade válida (mínimo de 1 pessoa).';
    } elseif ($pessoas > $cap) {
        $erros['pessoas'] = 'A quantidade informada (' . $pessoas . ' pessoas) ultrapassa o limite máximo deste espaço (' . $cap . ' pessoas). Reduza o número de convidados ou escolha um espaço maior.';
    }

    if (empty($erros['data']) && empty($erros['ini']) && empty($erros['fim'])) {
        if (reservaTemConflito($conn, (int)$espaco['id'], $data, $ini, $fim, $ignorarId)) {
            $conflito = true;
            $erros['conflito'] = 'Este espaço já possui uma reserva aprovada em ' . dataBR($data) . ' que conflita com o horário ' . $ini . '–' . $fim . '.';
        }
    }

    if ($exigirAceite && !$aceite) {
        $erros['aceite'] = 'Para enviar a solicitação, marque a caixa confirmando que leu e concorda com a política de cancelamento e o prazo de análise.';
    }

    return $erros;
}

function sugerirProximasDatas(mysqli $conn, int $espacoId, string $dataBase, int $qtd = 3): array
{
    $livres = [];
    $atual = max($dataBase ?: date('Y-m-d'), date('Y-m-d'));
    for ($i = 1; $i <= 20 && count($livres) < $qtd; $i++) {
        $candidata = date('Y-m-d', strtotime("$atual +$i days"));
        $st = $conn->prepare("SELECT COUNT(*) FROM reservas WHERE espaco_id = ? AND data_evento = ? AND status = 'APROVADA'");
        $st->bind_param('is', $espacoId, $candidata);
        $st->execute();
        if ((int)$st->get_result()->fetch_row()[0] === 0) {
            $livres[] = $candidata;
        }
    }
    return $livres;
}

function listarDatasOcupadasFuturas(mysqli $conn, int $espacoId, int $ignorarId = 0): array
{
    $st = $conn->prepare(
        "SELECT data_evento, hora_inicio, hora_fim
         FROM reservas
         WHERE espaco_id = ? AND status = 'APROVADA' AND data_evento >= CURDATE() AND id <> ?
         ORDER BY data_evento, hora_inicio"
    );
    $st->bind_param('ii', $espacoId, $ignorarId);
    $st->execute();
    return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

function buscarReservaPorId(mysqli $conn, int $id): ?array
{
    $st = $conn->prepare('SELECT * FROM reservas WHERE id = ? LIMIT 1');
    $st->bind_param('i', $id);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    return $row ?: null;
}

function buscarReservaDetalhadaCliente(mysqli $conn, int $id, int $clienteId): ?array
{
    $st = $conn->prepare(
        'SELECT r.*, e.nome AS espaco, e.tipo AS tipo_espaco, e.preco, e.responsavel_nome, e.responsavel_contato
         FROM reservas r
         JOIN espacos e ON e.id = r.espaco_id
         WHERE r.id = ? AND r.cliente_id = ?
         LIMIT 1'
    );
    $st->bind_param('ii', $id, $clienteId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    return $row ?: null;
}

function buscarReservaEditavelCliente(mysqli $conn, int $id, int $clienteId): ?array
{
    $st = $conn->prepare(
        "SELECT * FROM reservas
         WHERE id = ? AND cliente_id = ?
           AND (
               status = 'PENDENTE'
               OR (status = 'REJEITADA' AND prazo_resolucao IS NOT NULL AND prazo_resolucao > NOW())
           )
         LIMIT 1"
    );
    $st->bind_param('ii', $id, $clienteId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    return $row ?: null;
}

function buscarReservaParaRepetirCliente(mysqli $conn, int $id, int $clienteId): ?array
{
    $st = $conn->prepare('SELECT * FROM reservas WHERE id = ? AND cliente_id = ? LIMIT 1');
    $st->bind_param('ii', $id, $clienteId);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    return $row ?: null;
}

function listarReservasCliente(mysqli $conn, int $clienteId): array
{
    $st = $conn->prepare(
        'SELECT r.*, e.nome AS espaco, e.tipo AS tipo_espaco, e.responsavel_nome, e.responsavel_contato, e.imagem_url
         FROM reservas r
         JOIN espacos e ON e.id = r.espaco_id
         WHERE r.cliente_id = ?
         ORDER BY r.data_evento DESC, r.id DESC'
    );
    $st->bind_param('i', $clienteId);
    $st->execute();
    return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

function criarReserva(mysqli $conn, int $espacoId, int $clienteId, array $d): int
{
    $st = $conn->prepare(
        'INSERT INTO reservas (
            espaco_id, cliente_id, data_evento, hora_inicio, hora_fim,
            tipo_evento, qtd_pessoas, observacoes, notificado
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)'
    );
    $pessoas = (int)$d['pessoas'];
    $st->bind_param(
        'iissssis',
        $espacoId,
        $clienteId,
        $d['data'],
        $d['ini'],
        $d['fim'],
        $d['tipo'],
        $pessoas,
        $d['obs']
    );
    $st->execute();
    return (int)$conn->insert_id;
}

function atualizarReservaCliente(mysqli $conn, int $id, int $clienteId, array $d, bool $resolvendoPendencia = false): bool
{
    $pessoas = (int)$d['pessoas'];
    if ($resolvendoPendencia) {
        $st = $conn->prepare(
            "UPDATE reservas
             SET data_evento = ?, hora_inicio = ?, hora_fim = ?, tipo_evento = ?, qtd_pessoas = ?, observacoes = ?,
                 status = 'PENDENTE', prazo_resolucao = NULL, reenviada = 1, data_atualizacao = NOW(), notificado = 1
             WHERE id = ? AND cliente_id = ?
               AND status = 'REJEITADA' AND prazo_resolucao IS NOT NULL AND prazo_resolucao > NOW()"
        );
        $st->bind_param(
            'ssssisii',
            $d['data'],
            $d['ini'],
            $d['fim'],
            $d['tipo'],
            $pessoas,
            $d['obs'],
            $id,
            $clienteId
        );
        $st->execute();
        return $st->affected_rows > 0;
    }

    $st = $conn->prepare(
        "UPDATE reservas
         SET data_evento = ?, hora_inicio = ?, hora_fim = ?, tipo_evento = ?, qtd_pessoas = ?, observacoes = ?,
             data_atualizacao = NOW()
         WHERE id = ? AND cliente_id = ? AND status = 'PENDENTE'"
    );
    $st->bind_param(
        'ssssisii',
        $d['data'],
        $d['ini'],
        $d['fim'],
        $d['tipo'],
        $pessoas,
        $d['obs'],
        $id,
        $clienteId
    );
    return $st->execute();
}

function cancelarReservaCliente(mysqli $conn, int $id, int $clienteId): ?array
{
    $stAnt = $conn->prepare('SELECT status, motivo_recusa, prazo_resolucao FROM reservas WHERE id = ? AND cliente_id = ? LIMIT 1');
    $stAnt->bind_param('ii', $id, $clienteId);
    $stAnt->execute();
    $ant = $stAnt->get_result()->fetch_assoc();

    if (!$ant) {
        return null;
    }
    $podeCancelar = in_array($ant['status'], ['PENDENTE', 'APROVADA'], true)
        || ($ant['status'] === 'REJEITADA' && !empty($ant['prazo_resolucao']) && strtotime((string)$ant['prazo_resolucao']) > time());

    if (!$podeCancelar) {
        return null;
    }

    $motivoManual = 'Solicitação cancelada pelo cliente.';
    $st = $conn->prepare(
        "UPDATE reservas
         SET status = 'CANCELADA', cancelada_automaticamente = 0, motivo_cancelamento = ?,
             prazo_resolucao = NULL, data_atualizacao = NOW()
         WHERE id = ? AND cliente_id = ?"
    );
    $st->bind_param('sii', $motivoManual, $id, $clienteId);
    $st->execute();

    return $ant;
}

function desfazerCancelamentoCliente(mysqli $conn, int $id, int $clienteId, string $statusAnterior): bool
{
    if (!in_array($statusAnterior, ['PENDENTE', 'APROVADA'], true)) {
        $statusAnterior = 'PENDENTE';
    }
    $st = $conn->prepare(
        "UPDATE reservas
         SET status = ?, cancelada_automaticamente = 0, motivo_cancelamento = NULL, data_atualizacao = NOW()
         WHERE id = ? AND cliente_id = ? AND status = 'CANCELADA' AND cancelada_automaticamente = 0"
    );
    $st->bind_param('sii', $statusAnterior, $id, $clienteId);
    $st->execute();
    return $st->affected_rows > 0;
}

function aprovarReserva(mysqli $conn, int $id): bool
{
    $st = $conn->prepare(
        "UPDATE reservas
         SET status = 'APROVADA', motivo_recusa = NULL, prazo_resolucao = NULL,
             reenviada = 0, data_atualizacao = NOW(), notificado = 0
         WHERE id = ?"
    );
    $st->bind_param('i', $id);
    return $st->execute();
}

function recusarReserva(mysqli $conn, int $id, string $motivoRecusa, ?string $prazoResolucao = null): bool
{
    $st = $conn->prepare(
        "UPDATE reservas
         SET status = 'REJEITADA', motivo_recusa = ?, prazo_resolucao = ?,
             reenviada = 0, data_atualizacao = NOW(), notificado = 0
         WHERE id = ?"
    );
    $st->bind_param('ssi', $motivoRecusa, $prazoResolucao, $id);
    return $st->execute();
}

function restaurarStatusReservaAdmin(
    mysqli $conn,
    int $id,
    string $statusAnterior,
    ?string $motivoAnterior = null,
    ?string $prazoAnterior = null
): bool {
    if (!in_array($statusAnterior, ['PENDENTE', 'APROVADA', 'REJEITADA'], true)) {
        return false;
    }
    $prazoParam = ($prazoAnterior !== null && $prazoAnterior !== '') ? $prazoAnterior : null;
    $motivoParam = ($motivoAnterior !== null && $motivoAnterior !== '') ? $motivoAnterior : null;

    $st = $conn->prepare(
        'UPDATE reservas
         SET status = ?, motivo_recusa = ?, prazo_resolucao = ?, data_atualizacao = NOW(), notificado = 1
         WHERE id = ?'
    );
    $st->bind_param('sssi', $statusAnterior, $motivoParam, $prazoParam, $id);
    return $st->execute();
}

function consultarReservasAdmin(
    mysqli $conn,
    string $condicaoFixa,
    int $espacoId = 0,
    string $data = '',
    string $orderBy = 'r.data_evento ASC, r.hora_inicio ASC'
): array {
    $sql = "SELECT r.*, e.nome AS espaco, e.tipo AS tipo_espaco, e.capacidade,
                   u.nome AS cliente, u.email AS cliente_email
            FROM reservas r
            JOIN espacos e ON e.id = r.espaco_id
            JOIN usuarios u ON u.id = r.cliente_id
            WHERE ($condicaoFixa)";
    $tipos = '';
    $params = [];

    if ($espacoId > 0) {
        $sql .= ' AND r.espaco_id = ?';
        $tipos .= 'i';
        $params[] = $espacoId;
    }
    if ($data !== '') {
        $sql .= ' AND r.data_evento = ?';
        $tipos .= 's';
        $params[] = $data;
    }
    $sql .= ' ORDER BY ' . $orderBy;

    $st = $conn->prepare($sql);
    if (!empty($params)) {
        $st->bind_param($tipos, ...$params);
    }
    $st->execute();
    return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

function listarPendentesAdmin(mysqli $conn, int $espacoId = 0, string $data = ''): array
{
    return consultarReservasAdmin($conn, "r.status = 'PENDENTE'", $espacoId, $data);
}

function listarAguardandoCorrecaoAdmin(mysqli $conn, int $espacoId = 0, string $data = ''): array
{
    return consultarReservasAdmin(
        $conn,
        "r.status = 'REJEITADA' AND r.prazo_resolucao IS NOT NULL AND r.prazo_resolucao > NOW()",
        $espacoId,
        $data,
        'r.prazo_resolucao ASC'
    );
}

function listarAgendaAdmin(mysqli $conn, int $espacoId = 0, string $data = ''): array
{
    return consultarReservasAdmin(
        $conn,
        "r.status = 'APROVADA' AND r.data_evento >= CURDATE()",
        $espacoId,
        $data
    );
}

function listarHistoricoRecusasAdmin(mysqli $conn, int $espacoId = 0, string $data = '', int $limite = 15): array
{
    $cond = "(r.status = 'REJEITADA' AND (r.prazo_resolucao IS NULL OR r.prazo_resolucao <= NOW())) OR r.status = 'CANCELADA'";
    $rows = consultarReservasAdmin($conn, $cond, $espacoId, $data, 'COALESCE(r.data_atualizacao, r.data_solicitacao) DESC');
    return array_slice($rows, 0, $limite);
}

function obterNotificacoesCliente(mysqli $conn, int $clienteId): array
{
    $st = $conn->prepare(
        "SELECT r.id, r.status, r.data_evento, r.data_atualizacao, r.motivo_recusa,
                r.prazo_resolucao, r.cancelada_automaticamente, r.motivo_cancelamento,
                e.nome AS espaco
         FROM reservas r
         JOIN espacos e ON e.id = r.espaco_id
         WHERE r.cliente_id = ?
           AND r.notificado = 0
           AND (
               r.status IN ('APROVADA', 'REJEITADA')
               OR (r.status = 'CANCELADA' AND r.cancelada_automaticamente = 1)
           )
         ORDER BY r.data_atualizacao DESC"
    );
    $st->bind_param('i', $clienteId);
    $st->execute();
    return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

function marcarNotificacaoLida(mysqli $conn, int $reservaId, int $clienteId): void
{
    $st = $conn->prepare('UPDATE reservas SET notificado = 1 WHERE id = ? AND cliente_id = ?');
    $st->bind_param('ii', $reservaId, $clienteId);
    $st->execute();
}
