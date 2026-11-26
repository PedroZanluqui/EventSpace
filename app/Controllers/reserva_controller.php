<?php
declare(strict_types=1);

function reservasMinhas(mysqli $conn): void
{
    exigeLogin('cliente');
    $u = usuarioLogado();
    processarReservasAutomaticas($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['cancelar'])) {
            $rid = inteiroEntrada($_POST, 'cancelar');
            $ant = cancelarReservaCliente($conn, $rid, (int)$u['id']);
            if ($ant !== null) {
                $statusAntigo = (string)$ant['status'];
                $desfazer = in_array($statusAntigo, ['PENDENTE', 'APROVADA'], true)
                    ? [
                        'url'    => url('reservas/minhas'),
                        'campos' => ['desfazer_cancelamento' => $rid, 'status_anterior' => $statusAntigo],
                    ]
                    : null;
                definirToast("Solicitação #$rid cancelada.", 'sucesso', $desfazer);
            } else {
                definirToast('Esta solicitação não pode ser cancelada.', 'erro');
            }
            redirecionar('reservas/minhas');
        }

        if (isset($_POST['desfazer_cancelamento'])) {
            $rid = inteiroEntrada($_POST, 'desfazer_cancelamento');
            $statusRestaurar = textoEntrada($_POST, 'status_anterior') === 'APROVADA' ? 'APROVADA' : 'PENDENTE';
            if (desfazerCancelamentoCliente($conn, $rid, (int)$u['id'], $statusRestaurar)) {
                definirToast("Cancelamento desfeito! A solicitação #$rid voltou ao status anterior.");
            } else {
                definirToast('Não foi possível desfazer este cancelamento.', 'erro');
            }
            redirecionar('reservas/minhas');
        }

        redirecionar('reservas/minhas');
    }

    $todas = listarReservasCliente($conn, (int)$u['id']);

    $ativas = array_values(array_filter(
        $todas,
        static fn(array $r): bool => in_array($r['status'], ['PENDENTE', 'APROVADA'], true) || temPrazoAtivo($r)
    ));
    $hist = array_values(array_filter(
        $todas,
        static fn(array $r): bool => !in_array($r['status'], ['PENDENTE', 'APROVADA'], true) && !temPrazoAtivo($r)
    ));

    $aba = textoEntrada($_GET, 'aba') === 'historico' ? 'historico' : 'ativas';

    renderizarView($conn, 'reservas/minhas', [
        'aba'    => $aba,
        'ativas' => $ativas,
        'hist'   => $hist,
    ], 'Minhas solicitações', 'solicitudes');
}

function reservasSolicitar(mysqli $conn): void
{
    exigeLogin('cliente');
    $u = usuarioLogado();
    processarReservasAutomaticas($conn);

    if (isset($_GET['ok'])) {
        $rid       = inteiroEntrada($_GET, 'ok');
        $editado   = isset($_GET['editado']);
        $reenviado = isset($_GET['reenviado']);
        $r         = buscarReservaDetalhadaCliente($conn, $rid, (int)$u['id']);
        if (!$r) {
            redirecionar('reservas/minhas');
        }
        renderizarView($conn, 'reservas/confirmacao', [
            'r'         => $r,
            'editado'   => $editado,
            'reenviado' => $reenviado,
        ], 'Solicitação registrada', 'solicitudes');
        return;
    }

    $editarId            = isset($_POST['editar_id']) ? inteiroEntrada($_POST, 'editar_id') : inteiroEntrada($_GET, 'editar');
    $repetirId           = inteiroEntrada($_GET, 'repetir');
    $reservaExistente    = null;
    $resolvendoPendencia = false;

    if ($editarId > 0) {
        $reservaExistente = buscarReservaEditavelCliente($conn, $editarId, (int)$u['id']);
        if (!$reservaExistente) {
            $qualquer = buscarReservaDetalhadaCliente($conn, $editarId, (int)$u['id']);
            if ($qualquer && !empty($qualquer['cancelada_automaticamente'])) {
                definirToast(
                    "O prazo para resolver as pendências da solicitação #$editarId expirou e ela foi cancelada automaticamente.",
                    'erro'
                );
                redirecionar('reservas/minhas?aba=historico');
            }
            definirToast(
                'Somente solicitações em análise ou recusadas com prazo de pendência ativo podem ser editadas.',
                'erro'
            );
            redirecionar('reservas/minhas');
        }
        $resolvendoPendencia = ($reservaExistente['status'] === 'REJEITADA');
    } elseif ($repetirId > 0) {
        $reservaExistente = buscarReservaParaRepetirCliente($conn, $repetirId, (int)$u['id']);
    }

    $espacoId = isset($_POST['espaco_id'])
        ? inteiroEntrada($_POST, 'espaco_id')
        : ($reservaExistente ? (int)$reservaExistente['espaco_id'] : inteiroEntrada($_GET, 'id'));

    $e = buscarEspacoPorId($conn, $espacoId, true);
    if (!$e) {
        definirToast('Espaço não encontrado ou indisponível no momento.', 'erro');
        redirecionar('espacos');
    }

    $ocupadas = listarDatasOcupadasFuturas($conn, (int)$e['id'], $editarId);
    $datasOcupadasMap = [];
    foreach ($ocupadas as $o) {
        $datasOcupadasMap[$o['data_evento']][] = hora($o['hora_inicio']) . '–' . hora($o['hora_fim']);
    }

    $isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
    $etapa  = textoEntrada($_POST, 'etapa');

    $d = [
        'data'    => $isPost
            ? textoEntrada($_POST, 'data')
            : (textoEntrada($_GET, 'data') ?: ($editarId > 0 && $reservaExistente ? (string)$reservaExistente['data_evento'] : '')),
        'ini'     => $isPost
            ? textoEntrada($_POST, 'ini')
            : ($reservaExistente ? hora((string)$reservaExistente['hora_inicio']) : '14:00'),
        'fim'     => $isPost
            ? textoEntrada($_POST, 'fim')
            : ($reservaExistente ? hora((string)$reservaExistente['hora_fim']) : '22:00'),
        'tipo'    => $isPost
            ? textoEntrada($_POST, 'tipo')
            : ($reservaExistente ? (string)$reservaExistente['tipo_evento'] : ''),
        'pessoas' => $isPost
            ? inteiroEntrada($_POST, 'pessoas')
            : (inteiroEntrada($_GET, 'pessoas') ?: ($reservaExistente ? (int)$reservaExistente['qtd_pessoas'] : 0)),
        'obs'     => $isPost
            ? textoEntrada($_POST, 'obs')
            : ($reservaExistente ? (string)$reservaExistente['observacoes'] : ''),
        'aceite'  => isset($_POST['aceite_politica']) ? 1 : 0,
    ];

    $errosCampos     = [];
    $conflitoHorario = false;
    $datasSugeridas  = [];

    if ($etapa === 'voltar_edicao') {
        $etapa = '';
    }

    if ($etapa === 'revisar') {
        $errosCampos = validarSolicitacaoReserva($conn, $e, $d, $editarId, false, $conflitoHorario);
        if (!empty($errosCampos)) {
            if ($conflitoHorario) {
                $datasSugeridas = sugerirProximasDatas($conn, (int)$e['id'], $d['data'], 3);
            }
            $etapa = '';
        }
    } elseif ($etapa === 'confirmar') {
        $errosCampos = validarSolicitacaoReserva($conn, $e, $d, $editarId, true, $conflitoHorario);
        if (!empty($errosCampos)) {
            if ($conflitoHorario) {
                $datasSugeridas = sugerirProximasDatas($conn, (int)$e['id'], $d['data'], 3);
                $etapa = '';
            } else {
                $etapa = (isset($errosCampos['aceite']) && count($errosCampos) === 1) ? 'revisar' : '';
            }
        } else {
            if ($editarId > 0) {
                $okAtualizacao = atualizarReservaCliente($conn, $editarId, (int)$u['id'], $d, $resolvendoPendencia);
                if ($resolvendoPendencia && !$okAtualizacao) {
                    processarReservasAutomaticas($conn);
                    definirToast(
                        "O prazo para resolver as pendências da solicitação #$editarId expirou antes do envio e ela foi cancelada automaticamente.",
                        'erro'
                    );
                    redirecionar('reservas/minhas?aba=historico');
                }
                definirToast(
                    $resolvendoPendencia
                        ? "Pendências da solicitação #$editarId resolvidas e reenviadas para nova análise!"
                        : "Solicitação #$editarId atualizada com sucesso!"
                );
                $paramReenv = $resolvendoPendencia ? '&reenviado=1' : '&editado=1';
                redirecionar('reservas/solicitar?ok=' . $editarId . $paramReenv);
            } else {
                $novoId = criarReserva($conn, (int)$e['id'], (int)$u['id'], $d);
                definirToast("Solicitação #$novoId enviada para análise do administrador!");
                redirecionar('reservas/solicitar?ok=' . $novoId);
            }
        }
    }

    $tiposEventoSugeridos = [
        'Aniversário / Confraternização',
        'Casamento / Cerimônia',
        'Workshop / Treinamento',
        'Reunião Executiva',
        'Palestra / Congresso',
        'Formatura / Jantar',
        'Outro evento',
    ];

    $tituloPagina = $resolvendoPendencia
        ? "Resolver pendência #$editarId"
        : ($editarId > 0 ? "Editar solicitação #$editarId" : 'Solicitar reserva');

    renderizarView($conn, 'reservas/solicitar', [
        'e'                    => $e,
        'd'                    => $d,
        'etapa'                => $etapa,
        'editarId'             => $editarId,
        'reservaExistente'     => $reservaExistente,
        'resolvendoPendencia'  => $resolvendoPendencia,
        'datasOcupadasMap'     => $datasOcupadasMap,
        'errosCampos'          => $errosCampos,
        'conflitoHorario'      => $conflitoHorario,
        'datasSugeridas'       => $datasSugeridas,
        'tiposEventoSugeridos' => $tiposEventoSugeridos,
    ], $tituloPagina, 'solicitudes');
}
