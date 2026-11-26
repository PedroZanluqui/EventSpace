<?php
require_once __DIR__ . "/../includes/funcoes.php";
exigeLogin("administrador");
atualizaConcluidas($conn);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["acao"]) && isset($_POST["id"])) {
        $rid = inteiroEntrada($_POST, "id");
        $acao = textoEntrada($_POST, "acao");
        $st = $conn->prepare("SELECT * FROM reservas WHERE id = ?");
        $st->bind_param("i", $rid);
        $st->execute();
        $r = $st->get_result()->fetch_assoc();

        if ($r) {
            $statusAnterior = $r["status"];
            if ($acao === "aprovar") {
                if (temConflito($conn, $r["espaco_id"], $r["data_evento"], $r["hora_inicio"], $r["hora_fim"], $rid)) {
                    definirToast("Não foi possível aprovar a solicitação #$rid: já existe uma reserva aprovada nesse mesmo horário e espaço.", "erro");
                } else {
                    $conn->query("UPDATE reservas SET status='APROVADA', data_atualizacao=NOW(), notificado=0 WHERE id = $rid");
                    definirToast(
                        "Solicitação #$rid aprovada com sucesso! O cliente será notificado.",
                        "sucesso",
                        [
                            "url" => url("admin/index.php"),
                            "campos" => ["desfazer_status_id" => $rid, "status_anterior" => $statusAnterior]
                        ]
                    );
                }
            } elseif ($acao === "rejeitar") {
                $conn->query("UPDATE reservas SET status='REJEITADA', data_atualizacao=NOW(), notificado=0 WHERE id = $rid");
                definirToast(
                    "Solicitação #$rid recusada. O cliente será notificado.",
                    "sucesso",
                    [
                        "url" => url("admin/index.php"),
                        "campos" => ["desfazer_status_id" => $rid, "status_anterior" => $statusAnterior]
                    ]
                );
            }
        }
        header("Location: " . url("admin/index.php"));
        exit;
    }

    if (isset($_POST["desfazer_status_id"])) {
        $rid = inteiroEntrada($_POST, "desfazer_status_id");
        $stAnt = textoEntrada($_POST, "status_anterior") ?: "PENDENTE";
        if (in_array($stAnt, ["PENDENTE", "APROVADA", "REJEITADA"])) {
            $st = $conn->prepare("UPDATE reservas SET status=?, data_atualizacao=NOW(), notificado=1 WHERE id=?");
            $st->bind_param("si", $stAnt, $rid);
            $st->execute();
            definirToast("Ação desfeita! A solicitação #$rid voltou ao status '$stAnt'.");
        }
        header("Location: " . url("admin/index.php"));
        exit;
    }

    if (isset($_POST["acao_lote"]) && !empty($_POST["ids_selecionados"]) && is_array($_POST["ids_selecionados"])) {
        $acaoLote = textoEntrada($_POST, "acao_lote");
        $ids = array_map('intval', $_POST["ids_selecionados"]);
        $aprovadas = 0;
        $conflitos = 0;
        $rejeitadas = 0;

        foreach ($ids as $rid) {
            $st = $conn->prepare("SELECT * FROM reservas WHERE id = ? AND status = 'PENDENTE'");
            $st->bind_param("i", $rid);
            $st->execute();
            $r = $st->get_result()->fetch_assoc();
            if (!$r) continue;

            if ($acaoLote === "aprovar_lote") {
                if (temConflito($conn, $r["espaco_id"], $r["data_evento"], $r["hora_inicio"], $r["hora_fim"], $rid)) {
                    $conflitos++;
                } else {
                    $conn->query("UPDATE reservas SET status='APROVADA', data_atualizacao=NOW(), notificado=0 WHERE id = $rid");
                    $aprovadas++;
                }
            } elseif ($acaoLote === "rejeitar_lote") {
                $conn->query("UPDATE reservas SET status='REJEITADA', data_atualizacao=NOW(), notificado=0 WHERE id = $rid");
                $rejeitadas++;
            }
        }

        if ($acaoLote === "aprovar_lote") {
            $msg = "$aprovadas solicitação(ões) aprovada(s) em lote.";
            if ($conflitos > 0) $msg .= " ($conflitos ignorada(s) por conflito de agenda).";
            definirToast($msg, $conflitos > 0 && $aprovadas === 0 ? "erro" : "sucesso");
        } else {
            definirToast("$rejeitadas solicitação(ões) recusada(s) em lote.");
        }
        header("Location: " . url("admin/index.php"));
        exit;
    }

    if (isset($_POST["alternar_espaco"])) {
        $eid = inteiroEntrada($_POST, "alternar_espaco");
        $conn->query("UPDATE espacos SET disponivel = 1 - disponivel WHERE id = $eid");
        definirToast(
            "Status de disponibilidade do espaço atualizado.",
            "sucesso",
            [
                "url" => url("admin/index.php"),
                "campos" => ["alternar_espaco" => $eid]
            ]
        );
        header("Location: " . url("admin/index.php"));
        exit;
    }
}

$filtroEspaco = inteiroEntrada($_GET, "espaco_id");
$filtroData   = textoEntrada($_GET, "data");

$wherePend = "WHERE r.status='PENDENTE'";
$whereAg   = "WHERE r.status='APROVADA' AND r.data_evento >= CURDATE()";
$tiposBind = "";
$paramsBind = [];

if ($filtroEspaco > 0) {
    $wherePend .= " AND r.espaco_id = " . (int)$filtroEspaco;
    $whereAg   .= " AND r.espaco_id = " . (int)$filtroEspaco;
}
if ($filtroData !== "") {
    $dataEsc = $conn->real_escape_string($filtroData);
    $wherePend .= " AND r.data_evento = '$dataEsc'";
    $whereAg   .= " AND r.data_evento = '$dataEsc'";
}

$pendentes = $conn->query("SELECT r.*, e.nome AS espaco, e.tipo AS tipo_espaco, e.capacidade, u.nome AS cliente, u.email AS cliente_email
    FROM reservas r
    JOIN espacos e ON e.id = r.espaco_id
    JOIN usuarios u ON u.id = r.cliente_id
    $wherePend
    ORDER BY r.data_evento, r.hora_inicio")->fetch_all(MYSQLI_ASSOC);

$agenda = $conn->query("SELECT r.*, e.nome AS espaco, e.tipo AS tipo_espaco, e.capacidade, u.nome AS cliente, u.email AS cliente_email
    FROM reservas r
    JOIN espacos e ON e.id = r.espaco_id
    JOIN usuarios u ON u.id = r.cliente_id
    $whereAg
    ORDER BY r.data_evento, r.hora_inicio")->fetch_all(MYSQLI_ASSOC);

$espacos = $conn->query("SELECT * FROM espacos ORDER BY nome")->fetch_all(MYSQLI_ASSOC);

cabecalho("Painel administrativo", "admin");
?>
<section class="largo">
  <div class="cabecalho-secao">
    <div>
      <div class="rotulo">Gestão e aprovação de reservas</div>
      <h1 class="menor">Painel administrativo</h1>
      <p class="nota">Priorize a análise das solicitações pendentes e acompanhe os eventos confirmados na agenda.</p>
    </div>
    <div class="acoes-linha">
      <a class="botao" href="<?= url('admin/espacos.php?novo=1') ?>">+ Cadastrar novo espaço</a>
      <a class="botao secundario" href="<?= url('admin/espacos.php') ?>">Gerenciar espaços</a>
    </div>
  </div>

  <details class="caixa-criterios-admin" style="margin-bottom: 18px;">
    <summary>📋 Orientações e critérios para aprovação</summary>
    <div class="conteudo-criterios">
      <ul>
        <li><strong>1. Verificação de conflito:</strong> O sistema sinaliza automaticamente em vermelho caso duas solicitações disputem o mesmo horário.</li>
        <li><strong>2. Adequação de capacidade:</strong> Confira se a quantidade de pessoas informada é compatível com o formato do evento e comodidades do espaço.</li>
        <li><strong>3. Prazo de retorno:</strong> Responda às solicitações em até <strong>2 dias úteis</strong>. O cliente recebe uma notificação imediata ao aprovar ou recusar.</li>
      </ul>
    </div>
  </details>

  <form class="busca busca-admin" method="get">
    <label>Filtrar por espaço
      <select name="espaco_id">
        <option value="0">Todos os espaços</option>
        <?php foreach ($espacos as $esp): ?>
          <option value="<?= (int)$esp["id"] ?>" <?= $filtroEspaco === (int)$esp["id"] ? "selected" : "" ?>>
            <?= e($esp["nome"]) ?> (<?= e($esp["tipo"]) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Filtrar por data do evento
      <input type="date" name="data" value="<?= e($filtroData) ?>">
    </label>
    <div class="acoes-filtro-admin">
      <button type="submit" class="btn-primario pequeno" data-loading-text="Filtrando...">Filtrar painel</button>
      <?php if ($filtroEspaco > 0 || $filtroData !== ""): ?>
        <a class="botao secundario pequeno" href="<?= url('admin/index.php') ?>">Limpar filtros</a>
      <?php endif; ?>
    </div>
  </form>

  <div class="bloco-painel-destaque">
    <div class="cabecalho-bloco">
      <h2>Solicitações aguardando aprovação (<?= count($pendentes) ?>)</h2>
      <?php if ($pendentes): ?>
        <div class="acoes-lote-topo">
          <button type="submit" form="form-lote" name="acao_lote" value="aprovar_lote" class="botao pequeno" onclick="return confirm('Aprovar todas as solicitações selecionadas que não possuam conflito?')">
            ✓ Aprovar selecionadas
          </button>
          <button type="submit" form="form-lote" name="acao_lote" value="rejeitar_lote" class="botao perigo pequeno" onclick="return confirm('Recusar todas as solicitações selecionadas?')">
            ✕ Recusar selecionadas
          </button>
        </div>
      <?php endif; ?>
    </div>

    <?php if (!$pendentes): ?>
      <p class="vazio">Nenhuma solicitação aguardando aprovação com os filtros atuais.</p>
    <?php else: ?>
      <form id="form-lote" method="post"></form>
      <div class="tabela-responsiva">
        <table>
          <thead>
            <tr>
              <th style="width: 36px;">
                <input type="checkbox" id="selecionar-todas" title="Selecionar todas as solicitações">
              </th>
              <th>Protocolo</th>
              <th>Espaço</th>
              <th>Data e Horário</th>
              <th>Cliente</th>
              <th>Tipo de evento</th>
              <th>Pessoas</th>
              <th>Conflito</th>
              <th>Ações de decisão</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($pendentes as $r):
              $conflitoAprovada = temConflito($conn, $r["espaco_id"], $r["data_evento"], $r["hora_inicio"], $r["hora_fim"], $r["id"]);
          ?>
            <tr class="<?= $conflitoAprovada ? 'linha-conflito' : '' ?>">
              <td>
                <input type="checkbox" name="ids_selecionados[]" value="<?= (int)$r["id"] ?>" form="form-lote" class="check-item-lote">
              </td>
              <td><strong>#<?= (int)$r["id"] ?></strong></td>
              <td>
                <strong><?= e($r["espaco"]) ?></strong><br>
                <small class="nota"><?= e($r["tipo_espaco"]) ?></small>
              </td>
              <td>
                <strong><?= dataBR($r["data_evento"]) ?></strong><br>
                <small class="nota"><?= hora($r["hora_inicio"]) ?> às <?= hora($r["hora_fim"]) ?></small>
              </td>
              <td>
                <?= e($r["cliente"]) ?><br>
                <small class="nota"><?= e($r["cliente_email"]) ?></small>
              </td>
              <td><?= e($r["tipo_evento"]) ?></td>
              <td><?= (int)$r["qtd_pessoas"] ?> / <?= (int)$r["capacidade"] ?></td>
              <td>
                <?php if ($conflitoAprovada): ?>
                  <span class="alerta-conflito-tag" title="Já existe uma reserva aprovada neste horário">⚠️ Conflito de agenda</span>
                <?php else: ?>
                  <span class="ok-conflito-tag">✓ Horário livre</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="acoes-linha">
                  <button type="button" class="botao secundario pequeno btn-abrir-detalhe-reserva"
                    data-id="<?= (int)$r['id'] ?>"
                    data-espaco="<?= e($r['espaco'] . ' (' . $r['tipo_espaco'] . ')') ?>"
                    data-cliente="<?= e($r['cliente'] . ' (' . $r['cliente_email'] . ')') ?>"
                    data-data="<?= dataBR($r['data_evento']) ?>"
                    data-horario="<?= hora($r['hora_inicio']) ?> às <?= hora($r['hora_fim']) ?>"
                    data-evento="<?= e($r['tipo_evento']) ?>"
                    data-pessoas="<?= (int)$r['qtd_pessoas'] ?> pessoas (capacidade até <?= (int)$r['capacidade'] ?>)"
                    data-obs="<?= e($r['observacoes'] ?: 'Nenhuma observação informada') ?>"
                    data-status="<?= e($r['status']) ?>">
                    Ver detalhes
                  </button>

                  <form method="post" class="inline" onsubmit="return confirm('Confirmar APROVAÇÃO da solicitação #<?= (int)$r['id'] ?> para <?= e($r['cliente']) ?>?')">
                    <input type="hidden" name="id" value="<?= (int)$r["id"] ?>">
                    <button type="submit" class="botao pequeno" name="acao" value="aprovar" <?= $conflitoAprovada ? 'disabled title="Há conflito com outra reserva aprovada"' : '' ?> data-loading-text="Aprovando...">
                      Aprovar
                    </button>
                  </form>

                  <form method="post" class="inline" onsubmit="return confirm('Confirmar RECUSA da solicitação #<?= (int)$r['id'] ?> de <?= e($r['cliente']) ?>?')">
                    <input type="hidden" name="id" value="<?= (int)$r["id"] ?>">
                    <button type="submit" class="botao perigo pequeno" name="acao" value="rejeitar" data-loading-text="Recusando...">
                      Recusar
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="bloco-painel-destaque">
    <h2>Agenda de eventos aprovados (<?= count($agenda) ?>)</h2>
    <p class="nota">Clique em qualquer evento da agenda para inspecionar os detalhes completos da solicitação ou alterar a decisão caso necessário.</p>
    <?php if (!$agenda): ?>
      <p class="vazio">Nenhum evento aprovado agendado para o filtro selecionado.</p>
    <?php else: ?>
      <div class="tabela-responsiva">
        <table>
          <thead>
            <tr>
              <th>Data</th>
              <th>Horário</th>
              <th>Espaço</th>
              <th>Cliente</th>
              <th>Tipo de evento</th>
              <th>Quantidade de pessoas</th>
              <th>Ação</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($agenda as $r): ?>
            <tr class="linha-agenda-clicavel">
              <td><strong><?= dataBR($r["data_evento"]) ?></strong></td>
              <td><?= hora($r["hora_inicio"]) ?> às <?= hora($r["hora_fim"]) ?></td>
              <td><?= e($r["espaco"]) ?></td>
              <td><?= e($r["cliente"]) ?></td>
              <td><?= e($r["tipo_evento"]) ?></td>
              <td><?= (int)$r["qtd_pessoas"] ?> pessoas</td>
              <td>
                <button type="button" class="botao secundario pequeno btn-abrir-detalhe-reserva"
                  data-id="<?= (int)$r['id'] ?>"
                  data-espaco="<?= e($r['espaco'] . ' (' . $r['tipo_espaco'] . ')') ?>"
                  data-cliente="<?= e($r['cliente'] . ' (' . $r['cliente_email'] . ')') ?>"
                  data-data="<?= dataBR($r['data_evento']) ?>"
                  data-horario="<?= hora($r['hora_inicio']) ?> às <?= hora($r['hora_fim']) ?>"
                  data-evento="<?= e($r['tipo_evento']) ?>"
                  data-pessoas="<?= (int)$r['qtd_pessoas'] ?> pessoas (capacidade até <?= (int)$r['capacidade'] ?>)"
                  data-obs="<?= e($r['observacoes'] ?: 'Nenhuma observação informada') ?>"
                  data-status="<?= e($r['status']) ?>">
                  Abrir solicitação #<?= (int)$r["id"] ?>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <details class="bloco-secundario-admin" open>
    <summary><strong>Gerenciar catálogo de espaços e comodidades (<?= count($espacos) ?> espaços)</strong></summary>
    <div class="cabecalho-bloco" style="margin-top: 14px;">
      <span class="nota">Cadastre novos espaços, edite dados e comodidades ou altere a disponibilidade.</span>
      <div class="acoes-linha">
        <a class="botao pequeno" href="<?= url('admin/espacos.php?novo=1') ?>">+ Cadastrar novo espaço</a>
        <a class="botao secundario pequeno" href="<?= url('admin/espacos.php') ?>">Ver gerenciamento completo</a>
      </div>
    </div>
    <div class="tabela-responsiva" style="margin-top: 12px;">
      <table>
        <thead>
          <tr>
            <th>Nome do espaço</th>
            <th>Tipo de espaço</th>
            <th>Comodidades oferecidas</th>
            <th>Capacidade</th>
            <th>Preço da diária</th>
            <th>Status</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($espacos as $s): ?>
          <tr>
            <td><strong><?= e($s["nome"]) ?></strong></td>
            <td><?= e($s["tipo"]) ?></td>
            <td><?= renderChipsComodidades($s["comodidades"], 3) ?></td>
            <td><?= capacidadeTexto($s["capacidade"]) ?></td>
            <td><?= moeda($s["preco"]) ?>/dia</td>
            <td>
              <span class="selo-status <?= $s["disponivel"] ? 'status-APROVADA' : 'status-CANCELADA' ?>">
                <?= $s["disponivel"] ? "Ativo no catálogo" : "Arquivado (Inativo)" ?>
              </span>
            </td>
            <td>
              <div class="acoes-linha">
                <a class="botao secundario pequeno" href="<?= url('admin/espacos.php?editar=' . (int)$s['id']) ?>">Editar</a>
                <form method="post" class="inline" onsubmit="return confirm('Deseja <?= $s['disponivel'] ? 'arquivar (ocultar de novas buscas)' : 'reativar' ?> o espaço <?= e($s['nome']) ?>?')">
                  <button type="submit" class="botao secundario pequeno" name="alternar_espaco" value="<?= (int)$s["id"] ?>">
                    <?= $s["disponivel"] ? "Arquivar" : "Reativar" ?>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </details>
</section>

<div id="modal-detalhe-reserva" class="modal-fundo oculto" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-res">
  <div class="modal-caixa">
    <div class="modal-cabecalho">
      <h2 id="titulo-modal-res">Detalhes da solicitação</h2>
      <button type="button" class="fechar-modal" id="btn-fechar-modal-res" aria-label="Fechar janela">×</button>
    </div>
    <dl class="dados" id="corpo-modal-res"></dl>
    <div class="barra-acoes-form" id="acoes-modal-res"></div>
  </div>
</div>

<?php rodape(); ?>
