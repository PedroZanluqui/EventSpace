<?php
declare(strict_types=1);

function espacosIndex(mysqli $conn): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $acaoBusca = textoEntrada($_POST, 'acao_busca');
        if ($acaoBusca === 'salvar') {
            $paramsSalvar = [
                'tipo'       => textoEntrada($_POST, 'tipo'),
                'data'       => textoEntrada($_POST, 'data'),
                'pessoas'    => inteiroEntrada($_POST, 'pessoas') ?: '',
                'comodidade' => textoEntrada($_POST, 'comodidade'),
                'ordem'      => textoEntrada($_POST, 'ordem'),
            ];
            $paramsSalvar = array_filter($paramsSalvar, static fn($v) => $v !== '');
            if ($paramsSalvar) {
                $partes = [];
                if (!empty($paramsSalvar['tipo'])) $partes[] = $paramsSalvar['tipo'];
                if (!empty($paramsSalvar['comodidade'])) $partes[] = $paramsSalvar['comodidade'];
                if (!empty($paramsSalvar['pessoas'])) $partes[] = $paramsSalvar['pessoas'] . '+ pessoas';
                if (!empty($paramsSalvar['data'])) $partes[] = dataBR((string)$paramsSalvar['data']);
                $tituloBusca = implode(' · ', $partes) ?: 'Busca personalizada';
                $_SESSION['buscas_salvas'][] = [
                    'id'     => uniqid('', true),
                    'titulo' => $tituloBusca,
                    'params' => $paramsSalvar,
                ];
                definirToast('Filtro de busca salvo em suas buscas frequentes!');
            }
            redirecionar('espacos' . ($paramsSalvar ? '?' . http_build_query($paramsSalvar) : ''));
        }

        if ($acaoBusca === 'remover') {
            $idRem = textoEntrada($_POST, 'id_busca');
            $_SESSION['buscas_salvas'] = array_values(array_filter(
                $_SESSION['buscas_salvas'] ?? [],
                static fn(array $b): bool => ($b['id'] ?? '') !== $idRem
            ));
            definirToast('Busca salva removida.');
        }

        redirecionar('espacos');
    }

    $tipo       = textoEntrada($_GET, 'tipo');
    $data       = textoEntrada($_GET, 'data');
    $pessoas    = inteiroEntrada($_GET, 'pessoas');
    $comodidade = textoEntrada($_GET, 'comodidade');
    $ordem      = textoEntrada($_GET, 'ordem') ?: 'nome_asc';

    $lista = buscarEspacosFiltrados($conn, [
        'tipo'       => $tipo,
        'data'       => $data,
        'pessoas'    => $pessoas,
        'comodidade' => $comodidade,
        'ordem'      => $ordem,
    ]);

    $todosTipos       = listarTiposEspacos($conn);
    $todasComodidades = ['Wi-Fi', 'Ar-condicionado', 'Estacionamento', 'Projetor', 'Som', 'Cozinha', 'Iluminação', 'Acessibilidade'];
    $favIds           = idsFavoritosUsuario($conn, usuarioLogado());
    $buscasSalvas     = $_SESSION['buscas_salvas'] ?? [];

    $queryContexto = [];
    if ($data !== '') $queryContexto['data'] = $data;
    if ($pessoas > 0) $queryContexto['pessoas'] = $pessoas;
    $sufixoContexto = $queryContexto ? '&' . http_build_query($queryContexto) : '';

    $filtrosAtivos = [];
    $parametrosAtuais = array_filter([
        'tipo'       => $tipo,
        'data'       => $data,
        'pessoas'    => $pessoas > 0 ? (string)$pessoas : '',
        'comodidade' => $comodidade,
        'ordem'      => $ordem,
    ], static fn($v) => $v !== '');

    if ($tipo !== '') {
        $q = $parametrosAtuais;
        unset($q['tipo']);
        $filtrosAtivos[] = ['rotulo' => "Tipo de espaço: $tipo", 'url' => url('espacos' . ($q ? '?' . http_build_query($q) : ''))];
    }
    if ($data !== '') {
        $q = $parametrosAtuais;
        unset($q['data']);
        $filtrosAtivos[] = ['rotulo' => 'Data: ' . dataBR($data), 'url' => url('espacos' . ($q ? '?' . http_build_query($q) : ''))];
    }
    if ($pessoas > 0) {
        $q = $parametrosAtuais;
        unset($q['pessoas']);
        $filtrosAtivos[] = ['rotulo' => "Mínimo de $pessoas pessoas", 'url' => url('espacos' . ($q ? '?' . http_build_query($q) : ''))];
    }
    if ($comodidade !== '') {
        $q = $parametrosAtuais;
        unset($q['comodidade']);
        $filtrosAtivos[] = ['rotulo' => "Comodidade: $comodidade", 'url' => url('espacos' . ($q ? '?' . http_build_query($q) : ''))];
    }

    renderizarView($conn, 'espacos/index', [
        'tipo'             => $tipo,
        'data'             => $data,
        'pessoas'          => $pessoas,
        'comodidade'       => $comodidade,
        'ordem'            => $ordem,
        'lista'            => $lista,
        'todosTipos'       => $todosTipos,
        'todasComodidades' => $todasComodidades,
        'favIds'           => $favIds,
        'buscasSalvas'     => $buscasSalvas,
        'sufixoContexto'   => $sufixoContexto,
        'filtrosAtivos'    => $filtrosAtivos,
    ], 'Espaços disponíveis', 'espacos');
}

function espacosDetalhes(mysqli $conn, ?int $idParam = null): void
{
    $id = $idParam !== null ? $idParam : inteiroEntrada($_GET, 'id');
    if ($id <= 0) {
        redirecionar('espacos');
    }

    $e = buscarEspacoPorId($conn, $id);
    if (!$e) {
        redirecionar('espacos');
    }

    $dataFiltro    = textoEntrada($_GET, 'data');
    $pessoasFiltro = inteiroEntrada($_GET, 'pessoas');

    $ocupadas = listarDatasOcupadasFuturas($conn, $id);
    $datasOcupadasMap = [];
    foreach ($ocupadas as $o) {
        $datasOcupadasMap[$o['data_evento']][] = hora($o['hora_inicio']) . '–' . hora($o['hora_fim']);
    }

    $favIds = idsFavoritosUsuario($conn, usuarioLogado());
    $ehFav  = in_array((int)$e['id'], $favIds, true);

    $queryReserva = ['id' => (int)$e['id']];
    if ($dataFiltro !== '') $queryReserva['data'] = $dataFiltro;
    if ($pessoasFiltro > 0) $queryReserva['pessoas'] = $pessoasFiltro;

    renderizarView($conn, 'espacos/detalhes', [
        'e'                => $e,
        'dataFiltro'       => $dataFiltro,
        'pessoasFiltro'    => $pessoasFiltro,
        'ocupadas'         => $ocupadas,
        'datasOcupadasMap' => $datasOcupadasMap,
        'ehFav'            => $ehFav,
        'queryReserva'     => $queryReserva,
        'galeria'          => galeriaEspaco($e),
        'enderecoCompleto' => enderecoCompleto($e),
        'mapaEmbedUrl'     => urlMapaGoogleEmbed($e),
        'mapaAbrirUrl'     => urlMapaGoogleAbrir($e),
        'mapaRotasUrl'     => urlMapaGoogleRotas($e),
    ], (string)$e['nome'], 'espacos');
}

function apiBuscarFotos(): void
{
    $u = usuarioLogado();
    if (!$u || ($u['tipo'] ?? '') !== 'administrador') {
        responderJson(['erro' => 'Acesso restrito a administradores.'], 403);
    }

    $termo  = textoEntrada($_GET, 'q');
    $limite = inteiroEntrada($_GET, 'limite') ?: 8;
    responderJson(buscarFotosApi($termo !== '' ? $termo : 'event venue', $limite));
}

function apiHidratarFoto(mysqli $conn): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderJson(['ok' => false, 'erro' => 'Método não permitido.'], 405);
    }

    $espacoId = inteiroEntrada($_POST, 'espaco_id');
    if ($espacoId <= 0) {
        responderJson(['ok' => false, 'erro' => 'Espaço inválido.'], 400);
    }

    $espaco = buscarEspacoPorId($conn, $espacoId);
    if (!$espaco) {
        responderJson(['ok' => false, 'erro' => 'Espaço não encontrado.'], 404);
    }

    if (!empty($espaco['imagem_url']) && validarUrlImagem((string)$espaco['imagem_url'])) {
        responderJson([
            'ok'             => true,
            'imagem_url'     => $espaco['imagem_url'],
            'imagem_credito' => $espaco['imagem_credito'] ?? '',
            'galeria'        => galeriaEspaco($espaco),
            'provedor'       => 'cache_banco',
        ]);
    }

    $urlCliente     = textoEntrada($_POST, 'fallback_url');
    $creditoCliente = textoEntrada($_POST, 'fallback_credito');
    $host           = strtolower((string)parse_url($urlCliente, PHP_URL_HOST));
    $hostValido     = $urlCliente !== '' && validarUrlImagem($urlCliente)
        && ($host === 'upload.wikimedia.org' || $host === 'commons.wikimedia.org');

    if ($hostValido) {
        atualizarFotosEspaco($conn, $espacoId, $urlCliente, $creditoCliente ?: 'Acervo Wikimedia Commons', [$urlCliente]);
        responderJson([
            'ok'             => true,
            'imagem_url'     => $urlCliente,
            'imagem_credito' => $creditoCliente ?: 'Acervo Wikimedia Commons',
            'galeria'        => [$urlCliente],
            'provedor'       => 'Wikimedia Commons API',
        ]);
    }

    $consulta  = termoFotoEspaco((string)($espaco['tipo'] ?: $espaco['nome'] ?: 'event venue'));
    $resultado = buscarFotosApi($consulta, 4);

    if (empty($resultado['fotos'])) {
        responderJson([
            'ok'       => false,
            'consulta' => $consulta,
            'provedor' => $resultado['provedor'],
        ]);
    }

    $capa        = $resultado['fotos'][0];
    $urlsGaleria = array_map(static fn(array $f): string => $f['url'], $resultado['fotos']);
    atualizarFotosEspaco($conn, $espacoId, $capa['url'], $capa['credito'], $urlsGaleria);

    responderJson([
        'ok'             => true,
        'imagem_url'     => $capa['url'],
        'imagem_credito' => $capa['credito'],
        'galeria'        => $urlsGaleria,
        'provedor'       => $resultado['provedor'],
    ]);
}
