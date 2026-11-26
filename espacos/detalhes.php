<?php
require_once __DIR__ . "/../includes/funcoes.php";
$id = inteiroEntrada($_GET, "id");
$st = $conn->prepare("SELECT * FROM espacos WHERE id = ?");
$st->bind_param("i", $id);
$st->execute();
$e = $st->get_result()->fetch_assoc();
if (!$e) { header("Location: " . url("espacos/index.php")); exit; }

$dataFiltro    = textoEntrada($_GET, "data");
$pessoasFiltro = inteiroEntrada($_GET, "pessoas");

$st = $conn->prepare("SELECT data_evento, hora_inicio, hora_fim FROM reservas
                      WHERE espaco_id = ? AND status='APROVADA' AND data_evento >= CURDATE()
                      ORDER BY data_evento, hora_inicio");
$st->bind_param("i", $id);
$st->execute();
$ocupadas = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$datasOcupadasMap = [];
foreach ($ocupadas as $o) {
    $datasOcupadasMap[$o["data_evento"]][] = hora($o["hora_inicio"]) . "–" . hora($o["hora_fim"]);
}

$favIds = idsFavoritos($conn);
$ehFav  = in_array((int)$e["id"], $favIds);

$queryReserva = ["id" => $e["id"]];
if ($dataFiltro !== "") $queryReserva["data"] = $dataFiltro;
if ($pessoasFiltro > 0) $queryReserva["pessoas"] = $pessoasFiltro;

cabecalho($e["nome"], "espacos");
?>
<nav class="trilha-navegacao" aria-label="Caminho de navegação">
  <a href="<?= url('espacos/index.php?' . http_build_query(array_filter(['data' => $dataFiltro, 'pessoas' => $pessoasFiltro ?: '']))) ?>">← Voltar aos resultados</a>
</nav>

<div class="layout-detalhes">
  <section class="detalhe-principal">
    <div class="cabecalho-detalhe">
      <div>
        <span class="tag-tipo"><?= e($e["tipo"]) ?></span>
        <h1 class="menor"><?= e($e["nome"]) ?></h1>
      </div>
      <form method="post" action="<?= url('favoritos.php') ?>">
        <input type="hidden" name="espaco_id" value="<?= (int)$e["id"] ?>">
        <input type="hidden" name="retorno" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
        <button type="submit" class="btn-favorito <?= $ehFav ? 'ativo' : '' ?>">
          <?= $ehFav ? '★ Salvo nos favoritos' : '☆ Salvar nos favoritos' ?>
        </button>
      </form>
    </div>

    <p class="lead largo-texto"><?= e($e["descricao"]) ?></p>

    <div class="bloco-secao">
      <h2>Capacidade e Comodidades</h2>
      <p class="destaque-capacidade">👥 <strong><?= capacidadeTexto($e["capacidade"]) ?></strong></p>
      <?= renderChipsComodidades($e["comodidades"]) ?>
    </div>

    <div class="bloco-secao">
      <h2>Calendário de disponibilidade (próximos 14 dias)</h2>
      <p class="nota">Clique em uma data livre para iniciar sua solicitação com a data já preenchida.</p>
      <div class="grade-calendario-mini">
        <?php
        for ($i = 0; $i < 14; $i++):
            $diaIso = date("Y-m-d", strtotime("+$i days"));
            $diaBr  = date("d/m", strtotime($diaIso));
            $sem    = ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"][(int)date("w", strtotime($diaIso))];
            $horariosOcup = $datasOcupadasMap[$diaIso] ?? [];
            $temOcupacao  = !empty($horariosOcup);
            $selecionado  = ($dataFiltro === $diaIso);
            $linkDia = url("reservas/solicitar.php?" . http_build_query([
                "id"      => $e["id"],
                "data"    => $diaIso,
                "pessoas" => $pessoasFiltro ?: ""
            ]));
        ?>
          <a href="<?= e($linkDia) ?>" class="dia-calendario <?= $temOcupacao ? 'dia-parcial' : 'dia-livre' ?> <?= $selecionado ? 'dia-selecionado' : '' ?>" title="<?= $temOcupacao ? 'Horários reservados: ' . implode(', ', $horariosOcup) : 'Dia totalmente livre — clique para solicitar reserva' ?>">
            <span class="dia-semana"><?= $sem ?></span>
            <strong class="dia-data"><?= $diaBr ?></strong>
            <span class="dia-status"><?= $temOcupacao ? 'Parcial' : 'Livre' ?></span>
          </a>
        <?php endfor; ?>
      </div>

      <?php if ($ocupadas): ?>
        <div class="lista-ocupadas-box">
          <strong>Períodos já reservados neste espaço:</strong>
          <ul class="lista">
            <?php foreach ($ocupadas as $o): ?>
              <li><strong><?= dataBR($o["data_evento"]) ?></strong> das <?= hora($o["hora_inicio"]) ?> às <?= hora($o["hora_fim"]) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>

    <div class="bloco-secao">
      <h2>Política de cancelamento e regras de aprovação</h2>
      <ul class="lista-regras">
        <li><strong>Prazo de análise:</strong> Toda solicitação é avaliada pelo administrador em até <strong>2 dias úteis</strong>. Enquanto estiver com status <em>Aguardando aprovação</em>, o espaço ainda não está bloqueado definitivamente.</li>
        <li><strong>Edição e cancelamento gratuitos:</strong> Você pode editar os dados ou cancelar sua solicitação a qualquer momento enquanto ela estiver <em>Aguardando aprovação</em>, ou cancelar gratuitamente até <strong>48 horas antes</strong> do início do evento aprovado.</li>
      </ul>
    </div>

    <div class="bloco-secao caixa-responsavel">
      <h2>Dúvidas sobre este espaço?</h2>
      <p>Fale diretamente com o responsável pelo espaço antes de enviar sua solicitação:</p>
      <p>
        <strong>Responsável:</strong> <?= e($e["responsavel_nome"] ?? "Coordenação EventSpace") ?><br>
        <strong>Contato direto:</strong> <?= e($e["responsavel_contato"] ?? "contato@eventspace.com.br · (11) 99999-0000") ?>
      </p>
      <a class="botao secundario pequeno" href="mailto:contato@eventspace.com.br?subject=<?= rawurlencode('Dúvida sobre o espaço ' . $e['nome']) ?>">✉️ Falar com o responsável</a>
    </div>
  </section>

  <aside class="caixa-fixa-reserva" aria-label="Resumo e ação de reserva">
    <div class="preco-destaque">
      <span class="rotulo">Valor da diária</span>
      <strong><?= moeda($e["preco"]) ?><small>/dia</small></strong>
    </div>

    <div class="status-bloco-lateral">
      <?php if (!$e["disponivel"]): ?>
        <span class="status-disponibilidade disp-ocupado">● Espaço temporariamente inativo</span>
      <?php elseif ($dataFiltro !== ""): ?>
        <?php if (empty($datasOcupadasMap[$dataFiltro])): ?>
          <span class="status-disponibilidade disp-livre">● Disponível em <?= dataBR($dataFiltro) ?></span>
        <?php else: ?>
          <span class="status-disponibilidade disp-parcial">● Horários parciais em <?= dataBR($dataFiltro) ?> (<?= implode(", ", $datasOcupadasMap[$dataFiltro]) ?> ocupados)</span>
        <?php endif; ?>
      <?php else: ?>
        <span class="status-disponibilidade disp-livre">● Disponível para solicitações</span>
      <?php endif; ?>
    </div>

    <ul class="resumo-curto">
      <li><span>Tipo de espaço:</span> <strong><?= e($e["tipo"]) ?></strong></li>
      <li><span>Capacidade máxima:</span> <strong>até <?= (int)$e["capacidade"] ?> pessoas</strong></li>
      <li><span>Prazo de resposta:</span> <strong>até 2 dias úteis</strong></li>
    </ul>

    <?php if ($e["disponivel"]): ?>
      <a class="botao largura-total" href="<?= url('reservas/solicitar.php?' . http_build_query($queryReserva)) ?>">Solicitar reserva</a>
    <?php else: ?>
      <button type="button" class="botao largura-total" disabled>Espaço indisponível</button>
    <?php endif; ?>

    <p class="nota-centro">Você poderá revisar todos os dados antes de enviar.</p>
  </aside>
</div>
<?php rodape(); ?>
