<?php
declare(strict_types=1);

function definirBaseUrl(string $rootPath): void
{
    if (defined('BASE_URL')) {
        return;
    }
    $docRoot = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: ''), '/');
    $appRoot = rtrim(str_replace('\\', '/', realpath($rootPath) ?: ''), '/');
    $baseUrl = ($docRoot !== '' && $appRoot !== '' && str_starts_with($appRoot, $docRoot))
        ? substr($appRoot, strlen($docRoot))
        : '';
    define('BASE_URL', $baseUrl);
}

function baseUrl(): string
{
    return defined('BASE_URL') ? (string)constant('BASE_URL') : '';
}

function url(string $path = ''): string
{
    $base = baseUrl();
    $path = ltrim($path, '/');
    return $base . ($path !== '' ? '/' . $path : ($base !== '' ? '/' : '/'));
}

function asset(string $path): string
{
    return baseUrl() . '/assets/' . ltrim($path, '/');
}

function redirecionar(string $caminho): never
{
    if (str_starts_with($caminho, '/') || str_starts_with($caminho, 'http://') || str_starts_with($caminho, 'https://')) {
        header('Location: ' . $caminho);
    } else {
        header('Location: ' . url($caminho));
    }
    exit;
}

function responderJson(array $dados, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function e(mixed $s): string
{
    return htmlspecialchars(is_scalar($s) ? (string)$s : '', ENT_QUOTES, 'UTF-8');
}

function textoEntrada(array $dados, string $chave): string
{
    $valor = $dados[$chave] ?? '';
    return is_scalar($valor) ? trim((string)$valor) : '';
}

function inteiroEntrada(array $dados, string $chave): int
{
    $valor = $dados[$chave] ?? 0;
    return is_scalar($valor) ? (int)$valor : 0;
}

function moeda(mixed $v): string
{
    return 'R$ ' . number_format((float)$v, 2, ',', '.');
}

function dataBR(?string $d): string
{
    if (!$d) {
        return '—';
    }
    $ts = strtotime($d);
    return $ts !== false ? date('d/m/Y', $ts) : '—';
}

function dataHoraBR(?string $d): string
{
    if (!$d) {
        return '';
    }
    $ts = strtotime($d);
    return $ts !== false ? date('d/m/Y \à\s H:i', $ts) : '';
}

function dataHoraIso(?string $d): string
{
    if (!$d) {
        return '';
    }
    $ts = strtotime($d);
    return $ts !== false ? date('c', $ts) : '';
}

function hora(?string $h): string
{
    return substr((string)$h, 0, 5);
}

function capacidadeTexto(mixed $cap): string
{
    return 'Capacidade: até ' . (int)$cap . ' pessoas';
}

function usuarioLogado(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function exigeLogin(?string $tipo = null): void
{
    $u = usuarioLogado();
    if (!$u) {
        redirecionar('login');
    }
    if ($tipo !== null && ($u['tipo'] ?? '') !== $tipo) {
        redirecionar('');
    }
}

function definirToast(string $mensagem, string $tipo = 'sucesso', ?array $desfazer = null): void
{
    $_SESSION['toast_msg'] = [
        'texto'    => $mensagem,
        'tipo'     => $tipo,
        'desfazer' => $desfazer,
    ];
}

function temPrazoAtivo(array $reserva): bool
{
    if (($reserva['status'] ?? '') !== 'REJEITADA' || empty($reserva['prazo_resolucao'])) {
        return false;
    }
    $ts = strtotime((string)$reserva['prazo_resolucao']);
    return $ts !== false && $ts > time();
}

function tempoRestanteTexto(?string $prazo): string
{
    if (!$prazo) {
        return '';
    }
    $ts = strtotime($prazo);
    if ($ts === false) {
        return '';
    }
    $diff = $ts - time();
    if ($diff <= 0) {
        return 'Expirado';
    }
    $dias = intdiv($diff, 86400);
    $horas = intdiv($diff % 86400, 3600);
    $minutos = intdiv($diff % 3600, 60);

    if ($dias > 0) {
        return $dias . 'd ' . $horas . 'h';
    }
    if ($horas > 0) {
        return $horas . 'h ' . $minutos . 'min';
    }
    return max(1, $minutos) . ' min';
}

function renderStatusBadge(
    string $status,
    ?string $dataAtualizacao = null,
    bool $exibirExplicacao = true,
    array $reserva = []
): string {
    $classeExtra = 'status-' . $status;
    $reenviada = !empty($reserva['reenviada']);
    $prazoAtivo = temPrazoAtivo($reserva);
    $canceladaAuto = !empty($reserva['cancelada_automaticamente']);

    if ($status === 'REJEITADA' && $prazoAtivo) {
        $classeExtra = 'status-PENDENCIA';
        $rotulo = 'Recusada com pendência';
        $explicacao = 'Resolva até ' . dataHoraBR((string)$reserva['prazo_resolucao']) . ' ou será cancelada automaticamente';
    } elseif ($status === 'REJEITADA') {
        $rotulo = 'Solicitação recusada';
        $explicacao = 'Não foi possível atender a solicitação no período informado';
    } elseif ($status === 'CANCELADA' && $canceladaAuto) {
        $classeExtra = 'status-CANCELADA-AUTO';
        $rotulo = 'Cancelada automaticamente';
        $explicacao = 'Prazo para resolver pendências expirou';
    } elseif ($status === 'CANCELADA') {
        $rotulo = 'Cancelada';
        $explicacao = 'Solicitação cancelada pelo cliente';
    } elseif ($status === 'APROVADA') {
        $rotulo = 'Reserva aprovada';
        $explicacao = 'Espaço confirmado e reservado para o seu evento';
    } elseif ($status === 'CONCLUIDA') {
        $rotulo = 'Concluída';
        $explicacao = 'Evento já realizado';
    } elseif ($status === 'PENDENTE') {
        $rotulo = 'Aguardando aprovação';
        $explicacao = 'Espaço ainda não garantido · Prazo de análise: até 2 dias úteis';
    } else {
        $rotulo = $status;
        $explicacao = '';
    }

    $html = '<div class="bloco-status">';
    $html .= '<div class="selos-linha">';
    $html .= '<span class="selo-status ' . e($classeExtra) . '">' . e($rotulo) . '</span>';
    if ($status === 'PENDENTE' && $reenviada) {
        $html .= '<span class="tag-reenviada" title="O cliente corrigiu as pendências e reenviou a solicitação">🔄 Reenviada</span>';
    }
    $html .= '</div>';

    if ($exibirExplicacao && $explicacao !== '') {
        $html .= '<small class="explicacao-status">' . e($explicacao);
        if ($dataAtualizacao) {
            $html .= ' · Atualizado em ' . dataHoraBR($dataAtualizacao);
        }
        $html .= '</small>';
    }
    $html .= '</div>';

    return $html;
}

function iconeComodidade(string $nome): string
{
    $n = mb_strtolower(trim($nome), 'UTF-8');
    if (str_contains($n, 'wi-fi') || str_contains($n, 'wifi')) return '📶';
    if (str_contains($n, 'ar-condicionado') || str_contains($n, 'climatiz')) return '❄️';
    if (str_contains($n, 'estacionamento')) return '🚗';
    if (str_contains($n, 'cozinha') || str_contains($n, 'café')) return '🍽️';
    if (str_contains($n, 'projetor') || str_contains($n, 'telão')) return '📽️';
    if (str_contains($n, 'som')) return '🔊';
    if (str_contains($n, 'iluminação')) return '💡';
    if (str_contains($n, 'banheiro')) return '🚻';
    if (str_contains($n, 'acessibilidade')) return '♿';
    if (str_contains($n, 'externa') || str_contains($n, 'jardim')) return '🌿';
    return '✔️';
}

function renderChipsComodidades(?string $comodidadesStr, int $limite = 0): string
{
    if (!$comodidadesStr) {
        return '';
    }
    $itens = array_filter(array_map('trim', explode(',', $comodidadesStr)));
    if ($limite > 0) {
        $itens = array_slice($itens, 0, $limite);
    }
    $out = '<div class="comodidades-lista">';
    foreach ($itens as $item) {
        $out .= '<span class="chip-comodidade">' . iconeComodidade($item) . ' ' . e($item) . '</span>';
    }
    $out .= '</div>';
    return $out;
}

function validarUrlImagem(string $url): bool
{
    $u = trim($url);
    if ($u === '') {
        return false;
    }
    if (str_starts_with($u, 'assets/img/') && !str_contains($u, '..')) {
        return true;
    }
    if (!str_starts_with($u, 'https://') && !str_starts_with($u, 'http://')) {
        return false;
    }
    return filter_var($u, FILTER_VALIDATE_URL) !== false;
}

function imagemEspaco(array $espaco): string
{
    $url = trim((string)($espaco['imagem_url'] ?? ''));
    if ($url !== '' && validarUrlImagem($url)) {
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            return url($url);
        }
        return $url;
    }
    return asset('img/espaco-placeholder.svg');
}

function galeriaEspaco(array $espaco): array
{
    $fotos = [];
    $principal = trim((string)($espaco['imagem_url'] ?? ''));
    if ($principal !== '' && validarUrlImagem($principal)) {
        $fotos[] = $principal;
    }
    $json = trim((string)($espaco['galeria_imagens'] ?? ''));
    if ($json !== '') {
        $decodificado = json_decode($json, true);
        if (is_array($decodificado)) {
            foreach ($decodificado as $item) {
                if (is_string($item)) {
                    $u = trim($item);
                    if ($u !== '' && validarUrlImagem($u) && !in_array($u, $fotos, true)) {
                        $fotos[] = $u;
                    }
                }
            }
        }
    }
    if (empty($fotos)) {
        $fotos[] = asset('img/espaco-placeholder.svg');
    }
    return $fotos;
}

function termoFotoEspaco(string $termo): string
{
    $t = mb_strtolower(trim($termo), 'UTF-8');
    $mapa = [
        'salão de festas'    => 'banquet hall event venue interior',
        'salao de festas'    => 'banquet hall event venue interior',
        'auditório'          => 'modern auditorium conference hall stage',
        'auditorio'          => 'modern auditorium conference hall stage',
        'área externa'       => 'garden wedding event venue outdoor',
        'area externa'       => 'garden wedding event venue outdoor',
        'jardim'             => 'garden wedding event venue outdoor',
        'sala de reunião'    => 'executive conference meeting room',
        'sala de reuniao'    => 'executive conference meeting room',
        'espaço corporativo' => 'corporate event venue modern hall',
        'espaco corporativo' => 'corporate event venue modern hall',
        'estúdio'            => 'creative studio coworking space',
        'estudio'            => 'creative studio coworking space',
    ];
    foreach ($mapa as $chave => $consulta) {
        if (str_contains($t, $chave)) {
            return $consulta;
        }
    }
    return $termo !== '' ? $termo : 'modern event venue interior';
}

function buscarFotosApi(string $termo, int $limite = 8): array
{
    $consulta = termoFotoEspaco($termo);
    $limite = max(1, min(12, $limite));

    if (!function_exists('curl_init')) {
        return ['provedor' => 'Wikimedia Commons API', 'termo_usado' => $consulta, 'fotos' => []];
    }

    $url = 'https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrnamespace=6'
        . '&gsrsearch=' . rawurlencode($consulta . ' filetype:bitmap')
        . '&gsrlimit=15&prop=imageinfo&iiprop=url|extmetadata|mime|size&iiurlwidth=1200&format=json';

    $ch = curl_init($url);
    if ($ch === false) {
        return ['provedor' => 'Wikimedia Commons API', 'termo_usado' => $consulta, 'fotos' => []];
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT        => 7,
        CURLOPT_USERAGENT      => 'EventSpace/1.0 (Local Venue App)',
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    if (defined('CURLSSLOPT_NATIVE_CA')) {
        curl_setopt($ch, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
    }

    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if (!is_string($body) || $body === '' || $code < 200 || $code >= 300) {
        return ['provedor' => 'Wikimedia Commons API', 'termo_usado' => $consulta, 'fotos' => []];
    }

    $json = json_decode($body, true);
    $pages = $json['query']['pages'] ?? [];
    if (!is_array($pages)) {
        return ['provedor' => 'Wikimedia Commons API', 'termo_usado' => $consulta, 'fotos' => []];
    }

    $fotos = [];
    foreach ($pages as $page) {
        $info = $page['imageinfo'][0] ?? null;
        if (!is_array($info)) {
            continue;
        }
        $mime = (string)($info['mime'] ?? '');
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            continue;
        }
        $w = (int)($info['width'] ?? 0);
        $h = (int)($info['height'] ?? 0);
        if ($w < 600 || $w < $h) {
            continue;
        }
        $imgUrl = (string)($info['thumburl'] ?? $info['url'] ?? '');
        if (!validarUrlImagem($imgUrl)) {
            continue;
        }
        $autorRaw = (string)($info['extmetadata']['Artist']['value'] ?? 'Wikimedia Commons');
        $autor = trim(strip_tags($autorRaw));
        if (mb_strlen($autor, 'UTF-8') > 60) {
            $autor = mb_substr($autor, 0, 57, 'UTF-8') . '...';
        }
        $fotos[] = [
            'url'     => $imgUrl,
            'thumb'   => $imgUrl,
            'alt'     => trim(str_replace('File:', '', (string)($page['title'] ?? $consulta))),
            'credito' => ($autor !== '' ? $autor : 'Acervo livre') . ' · Wikimedia Commons',
        ];
        if (count($fotos) >= $limite) {
            break;
        }
    }

    return [
        'provedor'    => 'Wikimedia Commons API',
        'termo_usado' => $consulta,
        'fotos'       => $fotos,
    ];
}

function enderecoCurto(array $espaco): string
{
    $partes = array_filter([
        trim((string)($espaco['endereco'] ?? '')),
        trim((string)($espaco['cidade'] ?? '')),
        trim((string)($espaco['estado'] ?? '')),
    ]);
    if (count($partes) >= 3) {
        return $partes[0] . ' — ' . $partes[1] . '/' . $partes[2];
    }
    return implode(' · ', $partes) ?: 'Localização sob consulta';
}

function enderecoCompleto(array $espaco): string
{
    $endereco = trim((string)($espaco['endereco'] ?? ''));
    $cidade   = trim((string)($espaco['cidade'] ?? ''));
    $estado   = trim((string)($espaco['estado'] ?? ''));
    $cep      = trim((string)($espaco['cep'] ?? ''));

    $partes = [];
    if ($endereco !== '') $partes[] = $endereco;
    if ($cidade !== '' && $estado !== '') {
        $partes[] = $cidade . ' - ' . $estado;
    } elseif ($cidade !== '') {
        $partes[] = $cidade;
    }
    if ($cep !== '') $partes[] = $cep;

    if (empty($partes)) {
        $nome = trim((string)($espaco['nome'] ?? ''));
        return $nome !== '' ? $nome . ', São Paulo - SP, Brasil' : 'São Paulo - SP, Brasil';
    }
    $partes[] = 'Brasil';
    return implode(', ', $partes);
}

function urlMapaGoogleEmbed(array $espaco): string
{
    return 'https://maps.google.com/maps?q=' . rawurlencode(enderecoCompleto($espaco)) . '&z=15&hl=pt-BR&output=embed';
}

function urlMapaGoogleAbrir(array $espaco): string
{
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(enderecoCompleto($espaco));
}

function urlMapaGoogleRotas(array $espaco): string
{
    return 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode(enderecoCompleto($espaco));
}

function renderizarView(
    mysqli $conn,
    string $view,
    array $dados = [],
    string $titulo = '',
    string $itemMenuAtivo = ''
): void {
    processarReservasAutomaticas($conn);

    $u = usuarioLogado();
    if (isset($_GET['dispensar_notif']) && $u && ($u['tipo'] ?? '') === 'cliente') {
        marcarNotificacaoLida($conn, (int)$_GET['dispensar_notif'], (int)$u['id']);
    }

    $notificacoes = [];
    if ($u && ($u['tipo'] ?? '') === 'cliente') {
        $notificacoes = obterNotificacoesCliente($conn, (int)$u['id']);
    }

    $qtdFavs = count(idsFavoritosUsuario($conn, $u));

    $caminhoView = __DIR__ . '/Views/' . ltrim($view, '/') . '.php';
    if (!is_file($caminhoView)) {
        die('View não encontrada: ' . e($view));
    }

    extract($dados, EXTR_SKIP);

    ob_start();
    require $caminhoView;
    $conteudo = (string)ob_get_clean();

    $usuarioAtual = $u;
    require __DIR__ . '/Views/layout.php';
}
