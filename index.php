<?php
declare(strict_types=1);

date_default_timezone_set('America/Sao_Paulo');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/app/helpers.php';

definirBaseUrl(__DIR__);

require_once __DIR__ . '/app/Models/usuario_model.php';
require_once __DIR__ . '/app/Models/espaco_model.php';
require_once __DIR__ . '/app/Models/favorito_model.php';
require_once __DIR__ . '/app/Models/reserva_model.php';

require_once __DIR__ . '/app/Controllers/home_controller.php';
require_once __DIR__ . '/app/Controllers/auth_controller.php';
require_once __DIR__ . '/app/Controllers/espaco_controller.php';
require_once __DIR__ . '/app/Controllers/reserva_controller.php';
require_once __DIR__ . '/app/Controllers/admin_controller.php';

function obterRotaAtual(): string
{
    if (!empty($_GET['rota']) && is_string($_GET['rota'])) {
        return '/' . trim($_GET['rota'], '/') ?: '/';
    }

    $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = rtrim(baseUrl(), '/');
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    $limpo = '/' . trim($uri, '/');
    return $limpo === '//' ? '/' : $limpo;
}

$rota = obterRotaAtual();

if (preg_match('#^/espacos/(\d+)$#', $rota, $m)) {
    espacosDetalhes($conn, (int)$m[1]);
    exit;
}

switch ($rota) {
    case '/':
    case '/inicio':
    case '/index.php':
        homeIndex($conn);
        break;

    case '/ajuda':
    case '/ajuda.php':
        ajudaIndex($conn);
        break;

    case '/favoritos':
    case '/favoritos/alternar':
    case '/favoritos.php':
        favoritosIndex($conn);
        break;

    case '/login':
    case '/auth/login.php':
        authLogin($conn);
        break;

    case '/cadastro':
    case '/auth/cadastro.php':
        authCadastro($conn);
        break;

    case '/logout':
    case '/auth/logout.php':
        authLogout();
        break;

    case '/espacos':
    case '/espacos/busca':
    case '/espacos/index.php':
        espacosIndex($conn);
        break;

    case '/espacos/detalhes':
    case '/espacos/detalhes.php':
        espacosDetalhes($conn);
        break;

    case '/reservas':
    case '/reservas/index.php':
    case '/reservas/minhas':
    case '/reservas/minhas.php':
    case '/reservas/cancelar':
    case '/reservas/desfazer-cancelamento':
        reservasMinhas($conn);
        break;

    case '/reservas/solicitar':
    case '/reservas/solicitar.php':
        reservasSolicitar($conn);
        break;

    case '/admin':
    case '/admin/index.php':
    case '/admin/reservas/decidir':
    case '/admin/reservas/desfazer':
    case '/admin/reservas/lote':
    case '/admin/espacos/alternar-painel':
        adminPainel($conn);
        break;

    case '/admin/espacos':
    case '/admin/espacos.php':
    case '/admin/espacos/salvar':
    case '/admin/espacos/acao':
        adminEspacos($conn);
        break;

    case '/api/fotos':
        apiBuscarFotos();
        break;

    case '/api/espacos/hidratar-foto':
        apiHidratarFoto($conn);
        break;

    default:
        http_response_code(404);
        if (str_starts_with($rota, '/api/')) {
            responderJson(['erro' => 'Endpoint não encontrado.'], 404);
        }
        $titulo        = 'Página não encontrada';
        $itemMenuAtivo = '';
        $notificacoes  = [];
        $qtdFavs       = 0;
        $usuarioAtual  = usuarioLogado();
        $conteudo      = '<section class="estado-vazio-erro">'
            . '<div class="rotulo">Erro 404</div>'
            . '<h1>Página não encontrada</h1>'
            . '<p class="nota">A rota solicitada (<code>' . e($rota) . '</code>) não existe no EventSpace.</p>'
            . '<div class="acoes-erro"><a class="botao" href="' . url('') . '">Voltar para o início</a>'
            . '<a class="botao secundario" href="' . url('espacos') . '">Explorar espaços</a></div>'
            . '</section>';
        require __DIR__ . '/app/Views/layout.php';
        break;
}
