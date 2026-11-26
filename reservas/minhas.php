<?php
require_once __DIR__ . "/../includes/funcoes.php";
exigeLogin("cliente");
$u = usuario();
atualizaConcluidas($conn);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["cancelar"])) {
        $rid = inteiroEntrada($_POST, "cancelar");
        $stAnt = $conn->prepare("SELECT status FROM reservas WHERE id = ? AND cliente_id = ?");
        $stAnt->bind_param("ii", $rid, $u["id"]);
        $stAnt->execute();
        $ant = $stAnt->get_result()->fetch_assoc();

        if ($ant && in_array($ant["status"], ["PENDENTE", "APROVADA"])) {
            $statusAntigo = $ant["status"];
            $st = $conn->prepare("UPDATE reservas SET status='CANCELADA', data_atualizacao=NOW() WHERE id = ? AND cliente_id = ?");
            $st->bind_param("ii", $rid, $u["id"]);
            $st->execute();

            definirToast(
                "Solicitação #$rid cancelada.",
                "sucesso",
                [
                    "url" => url("reservas/minhas.php"),
                    "campos" => ["desfazer_cancelamento" => $rid, "status_anterior" => $statusAntigo]
                ]
            );
        } else {
            definirToast("Esta solicitação não pode ser cancelada.", "erro");
        }
        header("Location: " . url("reservas/minhas.php"));
        exit;
    }

    if (isset($_POST["desfazer_cancelamento"])) {
        $rid = inteiroEntrada($_POST, "desfazer_cancelamento");
        $statusRestaurar = textoEntrada($_POST, "status_anterior") === "APROVADA" ? "APROVADA" : "PENDENTE";
        $st = $conn->prepare("UPDATE reservas SET status = ? WHERE id = ? AND cliente_id = ? AND status = 'CANCELADA'");
        $st->bind_param("sii", $statusRestaurar, $rid, $u["id"]);
        $st->execute();
        definirToast("Cancelamento desfeito! A solicitação #$rid voltou ao status anterior.");
        header("Location: " . url("reservas/minhas.php"));
        exit;
    }
}

$aba = textoEntrada($_GET, "aba") === "historico" ? "historico" : "ativas";

$st = $conn->prepare("SELECT r.*, e.nome AS espaco, e.tipo AS tipo_espaco, e.responsavel_nome, e.responsavel_contato
                      FROM reservas r
                      JOIN espacos e ON e.id = r.espaco_id
                      WHERE r.cliente_id = ?
                      ORDER BY r.data_evento DESC, r.id DESC");
$st->bind_param("i", $u["id"]);
$st->execute();
$todas = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$ativas = array_values(array_filter($todas, fn($r) => in_array($r["status"], ["PENDENTE", "APROVADA"])));
$hist   = array_values(array_filter($todas, fn($r) => !in_array($r["status"], ["PENDENTE", "APROVADA"])));

cabecalho("Minhas solicitações", "solicitudes");
?>
<section class="largo">
  <div class="cabecalho-secao">
    <div>
      <div class="rotulo">Acompanhamento de pedidos</div>
      <h1 class="menor">Minhas solicitações</h1>
      <p class="nota">Acompanhe o status de análise de cada pedido, edite solicitações que estão aguardando aprovação ou reserve novamente a partir do seu histórico.</p>
    </div>
    <a class="botao" href="<?= url('espacos/index.php') ?>">+ Solicitar reserva</a>
  </div>

  <div class="abas-navegacao" role="tablist">
    <a role="tab" aria-selected="<?= $aba === 'ativas' ? 'true' : 'false' ?>" class="aba-item <?= $aba === 'ativas' ? 'ativa' : '' ?>" href="<?= url('reservas/minhas.php?aba=ativas') ?>">
      Solicitações em andamento (<?= count($ativas) ?>)
    </a>
    <a role="tab" aria-selected="<?= $aba === 'historico' ? 'true' : 'false' ?>" class="aba-item <?= $aba === 'historico' ? 'ativa' : '' ?>" href="<?= url('reservas/minhas.php?aba=historico') ?>">
      Histórico (<?= count($hist) ?>)
    </a>
  </div>

  <?php if ($aba === "ativas"): ?>
    <?php if (!$ativas): ?>
      <div class="estado-vazio-erro">
        <h2>Você não possui solicitações em andamento</h2>
        <p>Explore nosso catálogo de espaços para solicitar uma nova reserva para o seu evento.</p>
        <a class="botao" href="<?= url('espacos/index.php') ?>">Buscar espaços disponíveis</a>
      </div>
    <?php else: ?>
      <div class="tabela-responsiva">
        <table>
          <thead>
            <tr>
              <th>Protocolo</th>
              <th>Espaço</th>
              <th>Data e Horário</th>
              <th>Tipo de evento</th>
              <th>Capacidade solicitada</th>
              <th>Status e Prazo</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($ativas as $r): ?>
            <tr>
              <td><strong>#<?= (int)$r["id"] ?></strong></td>
              <td>
                <a href="<?= url('espacos/detalhes.php?id=' . (int)$r['espaco_id']) ?>"><strong><?= e($r["espaco"]) ?></strong></a><br>
                <small class="nota"><?= e($r["tipo_espaco"]) ?></small>
              </td>
              <td>
                <strong><?= dataBR($r["data_evento"]) ?></strong><br>
                <small class="nota"><?= hora($r["hora_inicio"]) ?> às <?= hora($r["hora_fim"]) ?></small>
              </td>
              <td><?= e($r["tipo_evento"]) ?></td>
              <td><?= (int)$r["qtd_pessoas"] ?> pessoas</td>
              <td><?= renderStatusBadge($r["status"], $r["data_atualizacao"]) ?></td>
              <td>
                <div class="acoes-linha">
                  <?php if ($r["status"] === "PENDENTE"): ?>
                    <a class="botao secundario pequeno" href="<?= url('reservas/solicitar.php?editar=' . (int)$r['id']) ?>">Editar solicitação</a>
                  <?php endif; ?>
                  <form method="post" onsubmit="return confirm('Tem certeza de que deseja cancelar a solicitação #<?= (int)$r['id'] ?> para <?= e($r['espaco']) ?>?')">
                    <button type="submit" class="botao perigo pequeno" name="cancelar" value="<?= (int)$r["id"] ?>" data-loading-text="Cancelando...">
                      Cancelar reserva
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

  <?php else: ?>
    <?php if (!$hist): ?>
      <div class="estado-vazio-erro">
        <h2>Nenhuma solicitação no histórico</h2>
        <p>Solicitações concluídas, recusadas ou canceladas aparecerão aqui.</p>
      </div>
    <?php else: ?>
      <div class="tabela-responsiva">
        <table>
          <thead>
            <tr>
              <th>Protocolo</th>
              <th>Espaço</th>
              <th>Data e Horário</th>
              <th>Tipo de evento</th>
              <th>Quantidade de pessoas</th>
              <th>Status final</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($hist as $r): ?>
            <tr>
              <td>#<?= (int)$r["id"] ?></td>
              <td>
                <a href="<?= url('espacos/detalhes.php?id=' . (int)$r['espaco_id']) ?>"><strong><?= e($r["espaco"]) ?></strong></a><br>
                <small class="nota"><?= e($r["tipo_espaco"]) ?></small>
              </td>
              <td>
                <?= dataBR($r["data_evento"]) ?><br>
                <small class="nota"><?= hora($r["hora_inicio"]) ?> às <?= hora($r["hora_fim"]) ?></small>
              </td>
              <td><?= e($r["tipo_evento"]) ?></td>
              <td><?= (int)$r["qtd_pessoas"] ?> pessoas</td>
              <td><?= renderStatusBadge($r["status"], $r["data_atualizacao"]) ?></td>
              <td>
                <a class="botao secundario pequeno" href="<?= url('reservas/solicitar.php?repetir=' . (int)$r['id']) ?>">
                  ↻ Reservar novamente
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</section>
<?php rodape(); ?>
