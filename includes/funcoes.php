<?php
session_start();
require_once __DIR__ . "/../config/conexao.php";

$docRoot = rtrim(str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: ''), '/');
$appRoot = rtrim(str_replace('\\', '/', realpath(__DIR__ . '/..') ?: ''), '/');
$baseUrl = ($docRoot !== '' && str_starts_with($appRoot, $docRoot)) ? substr($appRoot, strlen($docRoot)) : '';
if (!defined('BASE_URL')) {
    define('BASE_URL', $baseUrl);
}

function url($path = '') {
    $path = ltrim($path, '/');
    return BASE_URL . ($path !== '' ? '/' . $path : '');
}

function e($s): string {
    return htmlspecialchars(is_scalar($s) ? (string)$s : "", ENT_QUOTES, "UTF-8");
}

function textoEntrada(array $dados, string $chave): string {
    $valor = $dados[$chave] ?? "";
    return is_scalar($valor) ? trim((string)$valor) : "";
}

function inteiroEntrada(array $dados, string $chave): int {
    $valor = $dados[$chave] ?? 0;
    return is_scalar($valor) ? (int)$valor : 0;
}

function moeda($v) { return "R$ " . number_format((float)$v, 2, ",", "."); }
function dataBR($d) { return $d ? date("d/m/Y", strtotime($d)) : "—"; }
function dataHoraBR($d) { return $d ? date("d/m/Y \à\s H:i", strtotime($d)) : ""; }
function hora($h) { return substr((string)$h, 0, 5); }
function capacidadeTexto($cap) { return "Capacidade: até " . (int)$cap . " pessoas"; }

function usuario() { return $_SESSION["usuario"] ?? null; }

function exigeLogin($tipo = null) {
    $u = usuario();
    if (!$u) { header("Location: " . url("auth/login.php")); exit; }
    if ($tipo && $u["tipo"] !== $tipo) { header("Location: " . url("index.php")); exit; }
}

function garantirEsquemaHeuristica(mysqli $conn) {
    static $verificado = false;
    if ($verificado) return;
    $verificado = true;

    $colsEsp = [];
    $res = $conn->query("SHOW COLUMNS FROM espacos");
    if ($res) {
        while ($row = $res->fetch_assoc()) $colsEsp[$row['Field']] = true;
        if (!isset($colsEsp['responsavel_nome'])) {
            $conn->query("ALTER TABLE espacos ADD COLUMN responsavel_nome VARCHAR(100) DEFAULT 'Coordenação EventSpace'");
        }
        if (!isset($colsEsp['responsavel_contato'])) {
            $conn->query("ALTER TABLE espacos ADD COLUMN responsavel_contato VARCHAR(100) DEFAULT 'contato@eventspace.com.br · (11) 99999-0000'");
        }
    }

    $conn->query("UPDATE espacos SET nome = 'Grande Salão Imperial' WHERE nome = 'Grand Hall'");
    $conn->query("UPDATE espacos SET nome = 'Jardim Eventos' WHERE nome = 'Garden Eventos'");
    $conn->query("UPDATE espacos SET nome = 'Sala Executiva Central' WHERE nome = 'Meeting Room'");

    $colsRes = [];
    $resR = $conn->query("SHOW COLUMNS FROM reservas");
    if ($resR) {
        while ($row = $resR->fetch_assoc()) $colsRes[$row['Field']] = true;
        if (!isset($colsRes['data_atualizacao'])) {
            $conn->query("ALTER TABLE reservas ADD COLUMN data_atualizacao DATETIME NULL DEFAULT NULL");
        }
        if (!isset($colsRes['notificado'])) {
            $conn->query("ALTER TABLE reservas ADD COLUMN notificado TINYINT(1) NOT NULL DEFAULT 1");
        }
    }

    $conn->query("CREATE TABLE IF NOT EXISTS favoritos (
        usuario_id INT NOT NULL,
        espaco_id INT NOT NULL,
        PRIMARY KEY (usuario_id, espaco_id)
    )");
}

garantirEsquemaHeuristica($conn);

function atualizaConcluidas($conn) {
    $conn->query("UPDATE reservas SET status='CONCLUIDA'
                  WHERE status='APROVADA' AND data_evento < CURDATE()");
}

function temConflito($conn, $espaco_id, $data, $ini, $fim, $ignorarId = 0) {
    $st = $conn->prepare("SELECT COUNT(*) FROM reservas
        WHERE espaco_id=? AND data_evento=? AND status='APROVADA' AND id<>?
          AND hora_inicio < ? AND hora_fim > ?");
    $st->bind_param("isiss", $espaco_id, $data, $ignorarId, $fim, $ini);
    $st->execute();
    return $st->get_result()->fetch_row()[0] > 0;
}

function sugerirProximasDatas($conn, $espaco_id, $dataBase, $qtd = 3): array {
    $livres = [];
    $atual = max($dataBase ?: date("Y-m-d"), date("Y-m-d"));
    for ($i = 1; $i <= 20 && count($livres) < $qtd; $i++) {
        $candidata = date("Y-m-d", strtotime("$atual +$i days"));
        $st = $conn->prepare("SELECT COUNT(*) FROM reservas WHERE espaco_id=? AND data_evento=? AND status='APROVADA'");
        $st->bind_param("is", $espaco_id, $candidata);
        $st->execute();
        if ($st->get_result()->fetch_row()[0] == 0) {
            $livres[] = $candidata;
        }
    }
    return $livres;
}

function renderStatusBadge(string $status, ?string $dataAtualizacao = null, bool $exibirExplicacao = true): string {
    $mapa = [
        'PENDENTE'  => [
            'rotulo' => 'Aguardando aprovação',
            'explicacao' => 'Espaço ainda não garantido · Prazo de análise: até 2 dias úteis'
        ],
        'APROVADA'  => [
            'rotulo' => 'Reserva aprovada',
            'explicacao' => 'Espaço confirmado e reservado para o seu evento'
        ],
        'REJEITADA' => [
            'rotulo' => 'Solicitação recusada',
            'explicacao' => 'Não foi possível atender no período solicitado'
        ],
        'CANCELADA' => [
            'rotulo' => 'Cancelada',
            'explicacao' => 'Solicitação cancelada pelo cliente'
        ],
        'CONCLUIDA' => [
            'rotulo' => 'Concluída',
            'explicacao' => 'Evento já realizado'
        ],
    ];
    $info = $mapa[$status] ?? ['rotulo' => $status, 'explicacao' => ''];
    $html = '<div class="bloco-status">';
    $html .= '<span class="selo-status status-' . e($status) . '">' . e($info['rotulo']) . '</span>';
    if ($exibirExplicacao && $info['explicacao']) {
        $html .= '<small class="explicacao-status">' . e($info['explicacao']);
        if ($dataAtualizacao) {
            $html .= ' · Atualizado em ' . dataHoraBR($dataAtualizacao);
        }
        $html .= '</small>';
    }
    $html .= '</div>';
    return $html;
}

function iconeComodidade(string $nome): string {
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

function renderChipsComodidades(?string $comodidadesStr, int $limite = 0): string {
    if (!$comodidadesStr) return '';
    $itens = array_filter(array_map('trim', explode(',', $comodidadesStr)));
    if ($limite > 0) $itens = array_slice($itens, 0, $limite);
    $out = '<div class="comodidades-lista">';
    foreach ($itens as $item) {
        $out .= '<span class="chip-comodidade">' . iconeComodidade($item) . ' ' . e($item) . '</span>';
    }
    $out .= '</div>';
    return $out;
}

function idsFavoritos($conn): array {
    $u = usuario();
    if ($u) {
        $st = $conn->prepare("SELECT espaco_id FROM favoritos WHERE usuario_id = ?");
        $st->bind_param("i", $u["id"]);
        $st->execute();
        $res = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        return array_map(fn($r) => (int)$r["espaco_id"], $res);
    }
    return $_SESSION["favoritos_visitante"] ?? [];
}

function alternarFavorito($conn, int $espacoId): bool {
    $u = usuario();
    if ($u) {
        $st = $conn->prepare("SELECT 1 FROM favoritos WHERE usuario_id = ? AND espaco_id = ?");
        $st->bind_param("ii", $u["id"], $espacoId);
        $st->execute();
        if ($st->get_result()->num_rows > 0) {
            $del = $conn->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND espaco_id = ?");
            $del->bind_param("ii", $u["id"], $espacoId);
            $del->execute();
            return false;
        } else {
            $ins = $conn->prepare("INSERT IGNORE INTO favoritos (usuario_id, espaco_id) VALUES (?, ?)");
            $ins->bind_param("ii", $u["id"], $espacoId);
            $ins->execute();
            return true;
        }
    } else {
        $favs = $_SESSION["favoritos_visitante"] ?? [];
        if (in_array($espacoId, $favs)) {
            $_SESSION["favoritos_visitante"] = array_values(array_diff($favs, [$espacoId]));
            return false;
        } else {
            $favs[] = $espacoId;
            $_SESSION["favoritos_visitante"] = array_unique($favs);
            return true;
        }
    }
}

function definirToast(string $mensagem, string $tipo = 'sucesso', ?array $desfazer = null) {
    $_SESSION['toast_msg'] = [
        'texto' => $mensagem,
        'tipo' => $tipo,
        'desfazer' => $desfazer
    ];
}

function obterNotificacoesPendentesCliente(mysqli $conn): array {
    $u = usuario();
    if (!$u || $u['tipo'] !== 'cliente') return [];
    $st = $conn->prepare("SELECT r.id, r.status, r.data_evento, r.data_atualizacao, e.nome AS espaco
                          FROM reservas r
                          JOIN espacos e ON e.id = r.espaco_id
                          WHERE r.cliente_id = ? AND r.notificado = 0 AND r.status IN ('APROVADA', 'REJEITADA')
                          ORDER BY r.data_atualizacao DESC");
    $st->bind_param("i", $u['id']);
    $st->execute();
    return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

function cabecalho($titulo, $itemMenuAtivo = '') {
    global $conn;
    $u = usuario();
    $uri = $_SERVER['SCRIPT_NAME'] ?? '';
    if ($itemMenuAtivo === '') {
        if (str_contains($uri, '/admin/espacos')) $itemMenuAtivo = 'admin_espacos';
        elseif (str_contains($uri, '/admin/')) $itemMenuAtivo = 'admin';
        elseif (str_contains($uri, '/espacos/')) $itemMenuAtivo = 'espacos';
        elseif (str_contains($uri, '/reservas/')) $itemMenuAtivo = 'solicitudes';
        elseif (str_contains($uri, '/ajuda.php')) $itemMenuAtivo = 'ajuda';
        elseif (str_contains($uri, '/favoritos.php')) $itemMenuAtivo = 'favoritos';
        elseif (str_contains($uri, '/auth/login')) $itemMenuAtivo = 'entrar';
        elseif (str_contains($uri, '/auth/cadastro')) $itemMenuAtivo = 'cadastrar';
        else $itemMenuAtivo = 'inicio';
    }

    if (isset($_GET['dispensar_notif']) && $u) {
        $nid = (int)$_GET['dispensar_notif'];
        $st = $conn->prepare("UPDATE reservas SET notificado = 1 WHERE id = ? AND cliente_id = ?");
        $st->bind_param("ii", $nid, $u['id']);
        $st->execute();
    }

    $notificacoes = obterNotificacoesPendentesCliente($conn);
    $qtdFavs = count(idsFavoritos($conn));
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>EventSpace — <?= e($titulo) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
  <script src="<?= url('assets/js/app.js') ?>" defer></script>
</head>
<body>
  <header class="topo">
    <a class="logo" href="<?= url('index.php') ?>">EventSpace</a>
    <nav aria-label="Navegação principal">
      <?php if ($u && $u["tipo"] === "administrador"): ?>
        <a href="<?= url('admin/index.php') ?>" class="<?= $itemMenuAtivo === 'admin' ? 'ativo' : '' ?>">Painel administrativo</a>
        <a href="<?= url('admin/espacos.php') ?>" class="<?= $itemMenuAtivo === 'admin_espacos' ? 'ativo' : '' ?>">Gerenciar espaços</a>
        <a href="<?= url('espacos/index.php') ?>" class="<?= $itemMenuAtivo === 'espacos' ? 'ativo' : '' ?>">Catálogo público</a>
        <a href="<?= url('ajuda.php') ?>" class="<?= $itemMenuAtivo === 'ajuda' ? 'ativo' : '' ?>">Ajuda</a>
      <?php else: ?>
        <a href="<?= url('index.php') ?>" class="<?= $itemMenuAtivo === 'inicio' ? 'ativo' : '' ?>">Início</a>
        <a href="<?= url('espacos/index.php') ?>" class="<?= $itemMenuAtivo === 'espacos' ? 'ativo' : '' ?>">Espaços</a>
        <?php if ($u): ?>
          <a href="<?= url('reservas/minhas.php') ?>" class="<?= $itemMenuAtivo === 'solicitudes' ? 'ativo' : '' ?>">
            Minhas solicitações
            <?php if (count($notificacoes) > 0): ?>
              <span class="contador-alerta" title="Atualizações nas suas solicitações"><?= count($notificacoes) ?></span>
            <?php endif; ?>
          </a>
        <?php endif; ?>
        <a href="<?= url('favoritos.php') ?>" class="<?= $itemMenuAtivo === 'favoritos' ? 'ativo' : '' ?>">
          Favoritos<?php if ($qtdFavs > 0): ?> (<?= $qtdFavs ?>)<?php endif; ?>
        </a>
        <a href="<?= url('ajuda.php') ?>" class="<?= $itemMenuAtivo === 'ajuda' ? 'ativo' : '' ?>">Ajuda</a>
      <?php endif; ?>

      <div class="area-usuario">
        <?php if ($u): ?>
          <span class="identificacao-usuario">
            <span class="perfil-tag"><?= $u["tipo"] === "administrador" ? "Administrador" : "Cliente" ?></span>
            <strong><?= e($u["nome"]) ?></strong>
          </span>
          <a class="link-sair" href="<?= url('auth/logout.php') ?>">Sair</a>
        <?php else: ?>
          <a href="<?= url('auth/login.php') ?>" class="<?= $itemMenuAtivo === 'entrar' ? 'ativo' : '' ?>">Entrar</a>
          <a href="<?= url('auth/cadastro.php') ?>" class="botao-menu <?= $itemMenuAtivo === 'cadastrar' ? 'ativo' : '' ?>">Cadastrar</a>
        <?php endif; ?>
      </div>
    </nav>
  </header>

  <?php if (!empty($_SESSION['toast_msg'])):
      $t = $_SESSION['toast_msg'];
      unset($_SESSION['toast_msg']);
  ?>
    <div class="toast-flutuante toast-<?= e($t['tipo']) ?>" role="status" aria-live="polite">
      <div class="toast-conteudo">
        <span><?= e($t['texto']) ?></span>
        <?php if (!empty($t['desfazer'])): ?>
          <form method="post" action="<?= e($t['desfazer']['url']) ?>" class="form-desfazer">
            <?php foreach ($t['desfazer']['campos'] as $k => $v): ?>
              <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
            <?php endforeach; ?>
            <button type="submit" class="btn-desfazer">Desfazer ação</button>
          </form>
        <?php endif; ?>
      </div>
      <button type="button" class="fechar-toast" onclick="this.parentElement.remove()" aria-label="Fechar aviso">×</button>
    </div>
  <?php endif; ?>

  <?php if (!empty($notificacoes)): ?>
    <section class="painel-notificacoes" aria-label="Notificações de solicitações">
      <?php foreach ($notificacoes as $n): ?>
        <div class="alerta-notificacao status-borda-<?= e($n['status']) ?>">
          <div>
            <strong><?= $n['status'] === 'APROVADA' ? '🎉 Sua solicitação foi aprovada!' : '⚠️ Atualização na sua solicitação' ?></strong>
            <p>
              O pedido <strong>#<?= (int)$n['id'] ?></strong> para <strong><?= e($n['espaco']) ?></strong>
              (evento em <?= dataBR($n['data_evento']) ?>) mudou para
              <strong><?= $n['status'] === 'APROVADA' ? 'Reserva aprovada' : 'Solicitação recusada' ?></strong>
              <?php if ($n['data_atualizacao']): ?>em <?= dataHoraBR($n['data_atualizacao']) ?><?php endif; ?>.
            </p>
          </div>
          <div class="acoes-notificacao">
            <a class="botao pequeno" href="<?= url('reservas/minhas.php?dispensar_notif=' . (int)$n['id']) ?>">Ver solicitação</a>
            <a class="botao pequeno secundario" href="?dispensar_notif=<?= (int)$n['id'] ?>">Marcar como lida</a>
          </div>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <main class="pagina">
<?php
}

function rodape() {
    ?>
  </main>
  <footer class="rodape">
    <div class="rodape-interno">
      <div>
        <strong>EventSpace</strong>
        <p class="nota">Plataforma de solicitação e gestão de espaços para eventos.</p>
      </div>
      <div class="rodape-links">
        <a href="<?= url('index.php') ?>">Início</a>
        <a href="<?= url('espacos/index.php') ?>">Espaços</a>
        <a href="<?= url('favoritos.php') ?>">Favoritos</a>
        <a href="<?= url('ajuda.php') ?>">Ajuda e Política de Cancelamento</a>
      </div>
    </div>
  </footer>
</body>
</html>
<?php
}
