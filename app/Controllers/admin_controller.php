<?php
declare(strict_types=1);

function parsearPrazoFuturo(string $bruto): ?string
{
    if ($bruto === '') {
        return null;
    }
    $ts = strtotime($bruto);
    if ($ts === false || $ts <= time()) {
        return null;
    }
    return date('Y-m-d H:i:s', $ts);
}

function adminPainel(mysqli $conn): void
{
    exigeLogin('administrador');
    processarReservasAutomaticas($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['acao']) && isset($_POST['id'])) {
            $rid  = inteiroEntrada($_POST, 'id');
            $acao = textoEntrada($_POST, 'acao');
            $r    = buscarReservaPorId($conn, $rid);

            if ($r) {
                $statusAnterior = (string)$r['status'];
                $motivoAnterior = (string)($r['motivo_recusa'] ?? '');
                $prazoAnterior  = (string)($r['prazo_resolucao'] ?? '');

                if ($acao === 'aprovar') {
                    if (reservaTemConflito($conn, (int)$r['espaco_id'], (string)$r['data_evento'], (string)$r['hora_inicio'], (string)$r['hora_fim'], $rid)) {
                        definirToast("Não foi possível aprovar a solicitação #$rid: já existe uma reserva aprovada nesse mesmo horário e espaço.", 'erro');
                    } else {
                        aprovarReserva($conn, $rid);
                        definirToast(
                            "Solicitação #$rid aprovada com sucesso! O cliente será notificado.",
                            'sucesso',
                            [
                                'url'    => url('admin'),
                                'campos' => [
                                    'desfazer_status_id' => $rid,
                                    'status_anterior'    => $statusAnterior,
                                    'motivo_anterior'    => $motivoAnterior,
                                    'prazo_anterior'     => $prazoAnterior,
                                ],
                            ]
                        );
                    }
                } elseif ($acao === 'rejeitar') {
                    $motivo = textoEntrada($_POST, 'motivo_recusa');
                    if ($motivo === '') {
                        definirToast('Informe o motivo da recusa antes de confirmar a decisão.', 'erro');
                        redirecionar('admin');
                    }

                    $concederPrazo = textoEntrada($_POST, 'conceder_prazo') === '1';
                    $prazoSql = null;
                    if ($concederPrazo) {
                        $prazoSql = parsearPrazoFuturo(textoEntrada($_POST, 'prazo_resolucao'));
                        if ($prazoSql === null) {
                            definirToast('Informe um prazo válido e futuro para que o cliente possa resolver as pendências.', 'erro');
                            redirecionar('admin');
                        }
                    }

                    recusarReserva($conn, $rid, $motivo, $prazoSql);
                    $msgToast = $prazoSql !== null
                        ? "Solicitação #$rid recusada com pendência (prazo até " . dataHoraBR($prazoSql) . '). O cliente foi notificado.'
                        : "Solicitação #$rid recusada definitivamente. O cliente será notificado.";

                    definirToast(
                        $msgToast,
                        'sucesso',
                        [
                            'url'    => url('admin'),
                            'campos' => [
                                'desfazer_status_id' => $rid,
                                'status_anterior'    => $statusAnterior,
                                'motivo_anterior'    => $motivoAnterior,
                                'prazo_anterior'     => $prazoAnterior,
                            ],
                        ]
                    );
                }
            }
            redirecionar('admin');
        }

        if (isset($_POST['desfazer_status_id'])) {
            $rid            = inteiroEntrada($_POST, 'desfazer_status_id');
            $stAnt          = textoEntrada($_POST, 'status_anterior') ?: 'PENDENTE';
            $motivoAnterior = textoEntrada($_POST, 'motivo_anterior');
            $prazoAnterior  = textoEntrada($_POST, 'prazo_anterior');

            if (restaurarStatusReservaAdmin($conn, $rid, $stAnt, $motivoAnterior, $prazoAnterior)) {
                definirToast("Ação desfeita! A solicitação #$rid voltou ao status '$stAnt'.");
            }
            redirecionar('admin');
        }

        if (isset($_POST['acao_lote']) && !empty($_POST['ids_selecionados']) && is_array($_POST['ids_selecionados'])) {
            $acaoLote   = textoEntrada($_POST, 'acao_lote');
            $ids        = array_map('intval', $_POST['ids_selecionados']);
            $aprovadas  = 0;
            $conflitos  = 0;
            $rejeitadas = 0;

            $motivoLote = '';
            $prazoLote  = null;
            if ($acaoLote === 'rejeitar_lote') {
                $motivoLote = textoEntrada($_POST, 'motivo_recusa');
                if ($motivoLote === '') {
                    definirToast('Informe o motivo da recusa para aplicar a recusa em lote.', 'erro');
                    redirecionar('admin');
                }
                if (textoEntrada($_POST, 'conceder_prazo') === '1') {
                    $prazoLote = parsearPrazoFuturo(textoEntrada($_POST, 'prazo_resolucao'));
                    if ($prazoLote === null) {
                        definirToast('Informe um prazo válido e futuro para a recusa com pendência em lote.', 'erro');
                        redirecionar('admin');
                    }
                }
            }

            foreach ($ids as $rid) {
                $r = buscarReservaPorId($conn, $rid);
                if (!$r || $r['status'] !== 'PENDENTE') {
                    continue;
                }
                if ($acaoLote === 'aprovar_lote') {
                    if (reservaTemConflito($conn, (int)$r['espaco_id'], (string)$r['data_evento'], (string)$r['hora_inicio'], (string)$r['hora_fim'], $rid)) {
                        $conflitos++;
                    } else {
                        aprovarReserva($conn, $rid);
                        $aprovadas++;
                    }
                } elseif ($acaoLote === 'rejeitar_lote') {
                    recusarReserva($conn, $rid, $motivoLote, $prazoLote);
                    $rejeitadas++;
                }
            }

            if ($acaoLote === 'aprovar_lote') {
                $msg = "$aprovadas solicitação(ões) aprovada(s) em lote.";
                if ($conflitos > 0) $msg .= " ($conflitos ignorada(s) por conflito de agenda).";
                definirToast($msg, $conflitos > 0 && $aprovadas === 0 ? 'erro' : 'sucesso');
            } else {
                $msg = "$rejeitadas solicitação(ões) recusada(s) em lote.";
                if ($prazoLote !== null) $msg .= ' Prazo de resolução até ' . dataHoraBR($prazoLote) . '.';
                definirToast($msg);
            }
            redirecionar('admin');
        }

        if (isset($_POST['alternar_espaco'])) {
            $eid = inteiroEntrada($_POST, 'alternar_espaco');
            if ($eid > 0) {
                alternarDisponibilidadeEspaco($conn, $eid);
                definirToast(
                    'Status de disponibilidade do espaço atualizado.',
                    'sucesso',
                    [
                        'url'    => url('admin'),
                        'campos' => ['alternar_espaco' => $eid],
                    ]
                );
            }
            redirecionar('admin');
        }

        redirecionar('admin');
    }

    $filtroEspaco = inteiroEntrada($_GET, 'espaco_id');
    $filtroData   = textoEntrada($_GET, 'data');

    $pendentes = listarPendentesAdmin($conn, $filtroEspaco, $filtroData);
    foreach ($pendentes as &$pend) {
        $pend['tem_conflito'] = reservaTemConflito(
            $conn,
            (int)$pend['espaco_id'],
            (string)$pend['data_evento'],
            (string)$pend['hora_inicio'],
            (string)$pend['hora_fim'],
            (int)$pend['id']
        );
    }
    unset($pend);

    $aguardandoAjuste = listarAguardandoCorrecaoAdmin($conn, $filtroEspaco, $filtroData);
    $agenda           = listarAgendaAdmin($conn, $filtroEspaco, $filtroData);
    $historicoRecente = listarHistoricoRecusasAdmin($conn, $filtroEspaco, $filtroData, 15);
    $espacos          = listarTodosEspacos($conn);

    renderizarView($conn, 'admin/painel', [
        'filtroEspaco'     => $filtroEspaco,
        'filtroData'       => $filtroData,
        'pendentes'        => $pendentes,
        'aguardandoAjuste' => $aguardandoAjuste,
        'agenda'           => $agenda,
        'historicoRecente' => $historicoRecente,
        'espacos'          => $espacos,
        'prazoPadraoIso'   => date('Y-m-d\TH:i', strtotime('+48 hours')),
    ], 'Painel administrativo', 'admin');
}

function adminEspacos(mysqli $conn): void
{
    exigeLogin('administrador');

    $comodidadesSugeridas = [
        'Wi-Fi',
        'Ar-condicionado',
        'Estacionamento',
        'Projetor',
        'Som',
        'Cozinha',
        'Iluminação',
        'Banheiros',
        'Acessibilidade',
        'Área externa',
        'Café e Água',
    ];
    $tiposSugeridos = [
        'Salão de festas',
        'Auditório',
        'Área externa',
        'Sala de reunião',
        'Espaço corporativo',
        'Estúdio / Coworking',
    ];

    $espacoAtual = [
        'id'                  => 0,
        'nome'                => '',
        'tipo'                => '',
        'descricao'           => '',
        'comodidades'         => '',
        'capacidade'          => '',
        'preco'               => '',
        'disponivel'          => 1,
        'responsavel_nome'    => 'Coordenação EventSpace',
        'responsavel_contato' => 'contato@eventspace.com.br · (11) 99999-0000',
        'imagem_url'          => '',
        'imagem_credito'      => '',
        'galeria_imagens'     => '',
        'endereco'            => '',
        'cidade'              => 'São Paulo',
        'estado'              => 'SP',
        'cep'                 => '',
    ];
    $erros = [];
    $modoFormulario = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['acao_rapida'])) {
            $acaoRapida = textoEntrada($_POST, 'acao_rapida');
            $eid        = inteiroEntrada($_POST, 'espaco_id');

            if ($acaoRapida === 'alternar_status' && $eid > 0) {
                alternarDisponibilidadeEspaco($conn, $eid);
                definirToast(
                    "Status de disponibilidade do espaço #$eid atualizado.",
                    'sucesso',
                    [
                        'url'    => url('admin/espacos'),
                        'campos' => ['acao_rapida' => 'alternar_status', 'espaco_id' => $eid],
                    ]
                );
                redirecionar('admin/espacos');
            }

            if ($acaoRapida === 'excluir' && $eid > 0) {
                $res = excluirOuArquivarEspaco($conn, $eid);
                if ($res['acao'] === 'arquivado') {
                    definirToast(
                        "Este espaço possui {$res['qtd_reservas']} solicitação(ões) no histórico e não pode ser apagado definitivamente. Ele foi ARQUIVADO para preservar os registros.",
                        'sucesso',
                        [
                            'url'    => url('admin/espacos'),
                            'campos' => ['acao_rapida' => 'alternar_status', 'espaco_id' => $eid],
                        ]
                    );
                } else {
                    definirToast("Espaço #$eid excluído permanentemente do catálogo.");
                }
                redirecionar('admin/espacos');
            }
        }

        if (isset($_POST['salvar_espaco'])) {
            $espacoAtual = [
                'id'                  => inteiroEntrada($_POST, 'id'),
                'nome'                => textoEntrada($_POST, 'nome'),
                'tipo'                => textoEntrada($_POST, 'tipo'),
                'descricao'           => textoEntrada($_POST, 'descricao'),
                'capacidade'          => inteiroEntrada($_POST, 'capacidade'),
                'disponivel'          => isset($_POST['disponivel']) ? 1 : 0,
                'responsavel_nome'    => textoEntrada($_POST, 'responsavel_nome') ?: 'Coordenação EventSpace',
                'responsavel_contato' => textoEntrada($_POST, 'responsavel_contato') ?: 'contato@eventspace.com.br · (11) 99999-0000',
                'imagem_url'          => textoEntrada($_POST, 'imagem_url'),
                'imagem_credito'      => textoEntrada($_POST, 'imagem_credito'),
                'galeria_imagens'     => textoEntrada($_POST, 'galeria_imagens'),
                'endereco'            => textoEntrada($_POST, 'endereco'),
                'cidade'              => textoEntrada($_POST, 'cidade') ?: 'São Paulo',
                'estado'              => strtoupper(substr(textoEntrada($_POST, 'estado') ?: 'SP', 0, 2)),
                'cep'                 => textoEntrada($_POST, 'cep'),
            ];

            $precoBruto = str_replace(',', '.', textoEntrada($_POST, 'preco'));
            $espacoAtual['preco'] = is_numeric($precoBruto) ? (float)$precoBruto : -1;

            $comodidadesMarcadas = isset($_POST['comodidades_check']) && is_array($_POST['comodidades_check'])
                ? array_map('trim', $_POST['comodidades_check'])
                : [];
            $comodidadesExtras = array_filter(array_map('trim', explode(',', textoEntrada($_POST, 'comodidades_extra'))));
            $todasCom = array_values(array_unique(array_merge($comodidadesMarcadas, $comodidadesExtras)));
            $espacoAtual['comodidades'] = implode(', ', $todasCom);

            $erros = validarDadosEspaco($espacoAtual);

            $galeriaLimpa = [];
            if ($espacoAtual['galeria_imagens'] !== '') {
                $dec = json_decode($espacoAtual['galeria_imagens'], true);
                if (is_array($dec)) {
                    foreach ($dec as $gUrl) {
                        if (is_string($gUrl) && validarUrlImagem($gUrl)) {
                            $galeriaLimpa[] = trim($gUrl);
                        }
                    }
                }
            }
            $espacoAtual['galeria_imagens'] = !empty($galeriaLimpa)
                ? json_encode(array_values(array_unique($galeriaLimpa)), JSON_UNESCAPED_SLASHES)
                : '';

            if (empty($erros)) {
                $ehEdicao = $espacoAtual['id'] > 0;
                salvarEspaco($conn, $espacoAtual);
                definirToast(
                    $ehEdicao
                        ? 'Espaço "' . $espacoAtual['nome'] . '" atualizado com sucesso!'
                        : 'Novo espaço "' . $espacoAtual['nome'] . '" cadastrado no catálogo!'
                );
                redirecionar('admin/espacos');
            }

            $modoFormulario = true;
        }
    } else {
        $editarId       = inteiroEntrada($_GET, 'editar');
        $modoFormulario = isset($_GET['novo']) || $editarId > 0;

        if ($editarId > 0) {
            $encontrado = buscarEspacoPorId($conn, $editarId);
            if ($encontrado) {
                $espacoAtual = $encontrado;
            } else {
                definirToast('Espaço não encontrado.', 'erro');
                redirecionar('admin/espacos');
            }
        }
    }

    $comodidadesAtuaisLista = array_filter(array_map('trim', explode(',', (string)$espacoAtual['comodidades'])));
    $comodidadesExtrasLista = array_diff($comodidadesAtuaisLista, $comodidadesSugeridas);
    $espacos                = listarEspacosComContagemReservas($conn);

    $galeriaLista = [];
    if (!empty($espacoAtual['galeria_imagens'])) {
        $dec = json_decode((string)$espacoAtual['galeria_imagens'], true);
        if (is_array($dec)) {
            $galeriaLista = array_values(array_filter($dec, 'is_string'));
        }
    }

    renderizarView($conn, 'admin/espacos', [
        'comodidadesSugeridas'   => $comodidadesSugeridas,
        'tiposSugeridos'         => $tiposSugeridos,
        'modoFormulario'         => $modoFormulario,
        'espacoAtual'            => $espacoAtual,
        'erros'                  => $erros,
        'comodidadesAtuaisLista' => $comodidadesAtuaisLista,
        'comodidadesExtrasLista' => $comodidadesExtrasLista,
        'galeriaLista'           => $galeriaLista,
        'espacos'                => $espacos,
        'mapaPreviewUrl'         => urlMapaGoogleEmbed($espacoAtual),
    ], 'Gerenciar espaços', 'admin_espacos');
}
