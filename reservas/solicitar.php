<?php
require_once __DIR__ . "/../includes/funcoes.php";
exigeLogin("cliente");
$u = usuario();

if (isset($_GET["ok"])) {
    $rid = inteiroEntrada($_GET, "ok");
    $editado = isset($_GET["editado"]);
    $st = $conn->prepare("SELECT r.*, e.nome AS espaco, e.tipo AS tipo_espaco, e.preco, e.responsavel_nome, e.responsavel_contato
                          FROM reservas r
                          JOIN espacos e ON e.id = r.espaco_id
                          WHERE r.id = ? AND r.cliente_id = ?");
    $st->bind_param("ii", $rid, $u["id"]);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    if (!$r) { header("Location: " . url("reservas/minhas.php")); exit; }

    cabecalho("Solicitação registrada", "solicitudes");
    ?>
    <nav class="barra-progresso-etapas" aria-label="Progresso da solicitação">
      <div class="passo-barra concluido"><span class="num-passo">✓</span> <span>01. Dados do evento</span></div>
      <div class="passo-barra concluido"><span class="num-passo">✓</span> <span>02. Revisão e política</span></div>
      <div class="passo-barra atual"><span class="num-passo">03</span> <span>03. Solicitação enviada</span></div>
    </nav>

    <section class="caixa largo-medio">
      <div class="rotulo">Etapa 03 de 03 — Confirmação do protocolo</div>
      <h1 class="menor"><?= $editado ? "Solicitação atualizada com sucesso!" : "Solicitação enviada com sucesso!" ?></h1>

      <div class="msg">
        <strong>O que acontece agora?</strong><br>
        Sua solicitação está com o status <strong>Aguardando aprovação</strong> e será analisada pelo administrador em até <strong>2 dias úteis</strong>.
        Você receberá uma notificação no topo do sistema assim que ela for aprovada ou recusada.
      </div>

      <dl class="dados">
        <dt>Protocolo</dt><dd><strong>#<?= (int)$r["id"] ?></strong></dd>
        <dt>Status atual</dt><dd><?= renderStatusBadge($r["status"], $r["data_atualizacao"]) ?></dd>
        <dt>Espaço e Tipo</dt><dd><?= e($r["espaco"]) ?> (<?= e($r["tipo_espaco"]) ?>)</dd>
        <dt>Data e Horário</dt><dd><?= dataBR($r["data_evento"]) ?>, das <?= hora($r["hora_inicio"]) ?> às <?= hora($r["hora_fim"]) ?></dd>
        <dt>Tipo de evento</dt><dd><?= e($r["tipo_evento"]) ?></dd>
        <dt>Quantidade de pessoas</dt><dd><?= (int)$r["qtd_pessoas"] ?> pessoas</dd>
        <dt>Valor estimado</dt><dd><?= moeda($r["preco"]) ?>/dia</dd>
        <dt>Responsável pelo espaço</dt><dd><?= e($r["responsavel_nome"]) ?> · <?= e($r["responsavel_contato"]) ?></dd>
      </dl>

      <div class="barra-acoes-form">
        <a class="botao" href="<?= url('reservas/minhas.php') ?>">Acompanhar em Minhas solicitações</a>
        <a class="botao secundario" href="<?= url('reservas/solicitar.php?editar=' . (int)$r['id']) ?>">Editar esta solicitação</a>
      </div>
    </section>
    <?php
    rodape();
    exit;
}
$editarId = isset($_POST["editar_id"]) ? inteiroEntrada($_POST, "editar_id") : inteiroEntrada($_GET, "editar");
$repetirId = inteiroEntrada($_GET, "repetir");
$reservaExistente = null;

if ($editarId > 0) {
    $st = $conn->prepare("SELECT * FROM reservas WHERE id = ? AND cliente_id = ? AND status = 'PENDENTE'");
    $st->bind_param("ii", $editarId, $u["id"]);
    $st->execute();
    $reservaExistente = $st->get_result()->fetch_assoc();
    if (!$reservaExistente) {
        definirToast("Somente solicitações que estão aguardando aprovação podem ser editadas.", "erro");
        header("Location: " . url("reservas/minhas.php"));
        exit;
    }
} elseif ($repetirId > 0) {
    $st = $conn->prepare("SELECT * FROM reservas WHERE id = ? AND cliente_id = ?");
    $st->bind_param("ii", $repetirId, $u["id"]);
    $st->execute();
    $reservaExistente = $st->get_result()->fetch_assoc();
}

$espaco_id = isset($_POST["espaco_id"])
    ? inteiroEntrada($_POST, "espaco_id")
    : ($reservaExistente ? (int)$reservaExistente["espaco_id"] : inteiroEntrada($_GET, "id"));

$st = $conn->prepare("SELECT * FROM espacos WHERE id = ? AND disponivel = 1");
$st->bind_param("i", $espaco_id);
$st->execute();
$e = $st->get_result()->fetch_assoc();
if (!$e) {
    definirToast("Espaço não encontrado ou indisponível no momento.", "erro");
    header("Location: " . url("espacos/index.php"));
    exit;
}
$stOcup = $conn->prepare("SELECT data_evento, hora_inicio, hora_fim FROM reservas
                          WHERE espaco_id = ? AND status='APROVADA' AND data_evento >= CURDATE() AND id <> ?
                          ORDER BY data_evento, hora_inicio");
$stOcup->bind_param("ii", $e["id"], $editarId);
$stOcup->execute();
$ocupadas = $stOcup->get_result()->fetch_all(MYSQLI_ASSOC);
$datasOcupadasMap = [];
foreach ($ocupadas as $o) {
    $datasOcupadasMap[$o["data_evento"]][] = hora($o["hora_inicio"]) . "–" . hora($o["hora_fim"]);
}

$etapa = textoEntrada($_POST, "etapa");

$d = [
    "data"    => $_SERVER["REQUEST_METHOD"] === "POST"
        ? textoEntrada($_POST, "data")
        : (textoEntrada($_GET, "data") ?: ($editarId > 0 && $reservaExistente ? $reservaExistente["data_evento"] : "")),
    "ini"     => $_SERVER["REQUEST_METHOD"] === "POST"
        ? textoEntrada($_POST, "ini")
        : ($reservaExistente ? hora($reservaExistente["hora_inicio"]) : "14:00"),
    "fim"     => $_SERVER["REQUEST_METHOD"] === "POST"
        ? textoEntrada($_POST, "fim")
        : ($reservaExistente ? hora($reservaExistente["hora_fim"]) : "22:00"),
    "tipo"    => $_SERVER["REQUEST_METHOD"] === "POST"
        ? textoEntrada($_POST, "tipo")
        : ($reservaExistente ? $reservaExistente["tipo_evento"] : ""),
    "pessoas" => $_SERVER["REQUEST_METHOD"] === "POST"
        ? inteiroEntrada($_POST, "pessoas")
        : (inteiroEntrada($_GET, "pessoas") ?: ($reservaExistente ? (int)$reservaExistente["qtd_pessoas"] : 0)),
    "obs"     => $_SERVER["REQUEST_METHOD"] === "POST"
        ? textoEntrada($_POST, "obs")
        : ($reservaExistente ? (string)$reservaExistente["observacoes"] : ""),
    "aceite"  => isset($_POST["aceite_politica"]) ? 1 : 0,
];

$errosCampos = [];
$conflitoHorario = false;
$datasSugeridas = [];

function validarCamposSolicitacao(mysqli $conn, array $e, array $d, int $ignorarId, bool $exigirAceite, bool &$conflito): array {
    $erros = [];
    if ($d["data"] === "") {
        $erros["data"] = "Informe a data do evento. Selecione um dia a partir de hoje (" . date("d/m/Y") . ").";
    } elseif ($d["data"] < date("Y-m-d")) {
        $erros["data"] = "A data não pode estar no passado. Escolha uma data a partir de " . date("d/m/Y") . ".";
    }

    if ($d["ini"] === "") {
        $erros["ini"] = "Informe o horário de início do evento.";
    }
    if ($d["fim"] === "") {
        $erros["fim"] = "Informe o horário de término do evento.";
    }
    if ($d["ini"] !== "" && $d["fim"] !== "" && $d["ini"] >= $d["fim"]) {
        $erros["fim"] = "O horário de término (" . $d["fim"] . ") deve ser maior que o horário de início (" . $d["ini"] . ").";
    }

    if ($d["tipo"] === "") {
        $erros["tipo"] = "Selecione ou informe o tipo de evento (ex.: Casamento, Workshop, Aniversário).";
    }

    if ($d["pessoas"] < 1) {
        $erros["pessoas"] = "Informe uma quantidade válida (mínimo de 1 pessoa).";
    } elseif ($d["pessoas"] > (int)$e["capacidade"]) {
        $erros["pessoas"] = "A quantidade informada (" . $d["pessoas"] . " pessoas) ultrapassa o limite máximo deste espaço (" . (int)$e["capacidade"] . " pessoas). Reduza o número de convidados ou escolha um espaço maior.";
    }

    if (empty($erros["data"]) && empty($erros["ini"]) && empty($erros["fim"])) {
        if (temConflito($conn, $e["id"], $d["data"], $d["ini"], $d["fim"], $ignorarId)) {
            $conflito = true;
            $erros["conflito"] = "Este espaço já possui uma reserva aprovada em " . dataBR($d["data"]) . " que conflita com o horário " . $d["ini"] . "–" . $d["fim"] . ".";
        }
    }

    if ($exigirAceite && !$d["aceite"]) {
        $erros["aceite"] = "Para enviar a solicitação, marque a caixa confirmando que leu e concorda com a política de cancelamento e o prazo de análise.";
    }

    return $erros;
}

if ($etapa === "voltar_edicao") {
    $etapa = "";
}

if ($etapa === "revisar") {
    $errosCampos = validarCamposSolicitacao($conn, $e, $d, $editarId, false, $conflitoHorario);
    if (!empty($errosCampos)) {
        if ($conflitoHorario) {
            $datasSugeridas = sugerirProximasDatas($conn, $e["id"], $d["data"], 3);
        }
        $etapa = "";
    }
} elseif ($etapa === "confirmar") {
    $errosCampos = validarCamposSolicitacao($conn, $e, $d, $editarId, true, $conflitoHorario);
    if (!empty($errosCampos)) {
        if ($conflitoHorario) {
            $datasSugeridas = sugerirProximasDatas($conn, $e["id"], $d["data"], 3);
            $etapa = "";
        } else {
            $etapa = isset($errosCampos["aceite"]) && count($errosCampos) === 1 ? "revisar" : "";
        }
    } else {
        if ($editarId > 0) {
            $st = $conn->prepare("UPDATE reservas
                SET data_evento=?, hora_inicio=?, hora_fim=?, tipo_evento=?, qtd_pessoas=?, observacoes=?
                WHERE id=? AND cliente_id=? AND status='PENDENTE'");
            $st->bind_param("ssssisii", $d["data"], $d["ini"], $d["fim"], $d["tipo"], $d["pessoas"], $d["obs"], $editarId, $u["id"]);
            $st->execute();
            definirToast("Solicitação #$editarId atualizada com sucesso!");
            header("Location: " . url("reservas/solicitar.php?ok=" . $editarId . "&editado=1"));
            exit;
        } else {
            $st = $conn->prepare("INSERT INTO reservas
                (espaco_id, cliente_id, data_evento, hora_inicio, hora_fim, tipo_evento, qtd_pessoas, observacoes, notificado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $st->bind_param("iissssis", $e["id"], $u["id"], $d["data"], $d["ini"], $d["fim"], $d["tipo"], $d["pessoas"], $d["obs"]);
            $st->execute();
            $novoId = $conn->insert_id;
            definirToast("Solicitação #$novoId enviada para análise do administrador!");
            header("Location: " . url("reservas/solicitar.php?ok=" . $novoId));
            exit;
        }
    }
}

$tiposEventoSugeridos = [
    "Aniversário / Confraternização",
    "Casamento / Cerimônia",
    "Workshop / Treinamento",
    "Reunião Executiva",
    "Palestra / Congresso",
    "Formatura / Jantar",
    "Outro evento"
];

cabecalho($editarId > 0 ? "Editar solicitação #$editarId" : "Solicitar reserva", "solicitudes");
?>

<nav class="barra-progresso-etapas" aria-label="Progresso da solicitação">
  <?php if ($etapa === "revisar"): ?>
    <form method="post" class="inline-zero">
      <input type="hidden" name="etapa" value="voltar_edicao">
      <input type="hidden" name="espaco_id" value="<?= (int)$e["id"] ?>">
      <input type="hidden" name="editar_id" value="<?= (int)$editarId ?>">
      <input type="hidden" name="data" value="<?= e($d["data"]) ?>">
      <input type="hidden" name="ini" value="<?= e($d["ini"]) ?>">
      <input type="hidden" name="fim" value="<?= e($d["fim"]) ?>">
      <input type="hidden" name="tipo" value="<?= e($d["tipo"]) ?>">
      <input type="hidden" name="pessoas" value="<?= (int)$d["pessoas"] ?>">
      <input type="hidden" name="obs" value="<?= e($d["obs"]) ?>">
      <button type="submit" class="passo-barra concluido clicavel" title="Clique para voltar e editar os dados do evento">
        <span class="num-passo">✓</span> <span>01. Dados do evento (Editar)</span>
      </button>
    </form>
    <div class="passo-barra atual"><span class="num-passo">02</span> <span>02. Revisão e política</span></div>
    <div class="passo-barra"><span class="num-passo">03</span> <span>03. Solicitação enviada</span></div>
  <?php else: ?>
    <div class="passo-barra atual"><span class="num-passo">01</span> <span>01. Dados do evento</span></div>
    <div class="passo-barra"><span class="num-passo">02</span> <span>02. Revisão e política</span></div>
    <div class="passo-barra"><span class="num-passo">03</span> <span>03. Solicitação enviada</span></div>
  <?php endif; ?>
</nav>

<div class="layout-detalhes">
  <section class="detalhe-principal">
    <?php if ($etapa === "revisar"): ?>
      <div class="rotulo">Etapa 02 de 03 — Conferência final</div>
      <h1 class="menor">Revise sua solicitação</h1>
      <p class="nota">Confira atentamente os dados abaixo antes de enviar sua solicitação para análise.</p>

      <?php if (!empty($errosCampos["aceite"])): ?>
        <div class="msg erro" role="alert"><?= e($errosCampos["aceite"]) ?></div>
      <?php endif; ?>

      <dl class="dados">
        <dt>Espaço escolhido</dt><dd><strong><?= e($e["nome"]) ?></strong> (<?= e($e["tipo"]) ?>)</dd>
        <dt>Data do evento</dt><dd><?= dataBR($d["data"]) ?></dd>
        <dt>Horário</dt><dd>Das <?= e($d["ini"]) ?> às <?= e($d["fim"]) ?></dd>
        <dt>Tipo de evento</dt><dd><?= e($d["tipo"]) ?></dd>
        <dt>Quantidade de pessoas</dt><dd><?= (int)$d["pessoas"] ?> pessoas (limite do espaço: até <?= (int)$e["capacidade"] ?> pessoas)</dd>
        <dt>Observações</dt><dd><?= $d["obs"] !== "" ? e($d["obs"]) : "Nenhuma observação informada" ?></dd>
        <dt>Valor da diária</dt><dd><strong><?= moeda($e["preco"]) ?>/dia</strong></dd>
        <dt>Prazo de resposta</dt><dd>Análise pelo administrador em até <strong>2 dias úteis</strong></dd>
      </dl>

      <form method="post" class="form-revisao">
        <input type="hidden" name="etapa" value="confirmar">
        <input type="hidden" name="espaco_id" value="<?= (int)$e["id"] ?>">
        <input type="hidden" name="editar_id" value="<?= (int)$editarId ?>">
        <input type="hidden" name="data" value="<?= e($d["data"]) ?>">
        <input type="hidden" name="ini" value="<?= e($d["ini"]) ?>">
        <input type="hidden" name="fim" value="<?= e($d["fim"]) ?>">
        <input type="hidden" name="tipo" value="<?= e($d["tipo"]) ?>">
        <input type="hidden" name="pessoas" value="<?= (int)$d["pessoas"] ?>">
        <input type="hidden" name="obs" value="<?= e($d["obs"]) ?>">

        <div class="caixa-politica <?= !empty($errosCampos['aceite']) ? 'campo-invalido-box' : '' ?>">
          <h3>Política de aprovação e cancelamento</h3>
          <p>
            1. Ao enviar, seu pedido fica com status <strong>Aguardando aprovação</strong> e passa pela análise do administrador em até <strong>2 dias úteis</strong>.<br>
            2. Enquanto estiver em análise, você pode editar ou cancelar gratuitamente a qualquer momento em <em>Minhas solicitações</em>.<br>
            3. Após aprovada, o cancelamento gratuito pode ser feito até <strong>48 horas antes</strong> da data do evento.
          </p>
          <label class="label-checkbox">
            <input type="checkbox" name="aceite_politica" value="1" required <?= $d["aceite"] ? "checked" : "" ?>>
            <span>Li e concordo com a política de cancelamento e estou ciente de que a solicitação depende de aprovação do administrador. <span class="obrigatorio">*</span></span>
          </label>
          <?php if (!empty($errosCampos["aceite"])): ?>
            <span class="erro-campo"><?= e($errosCampos["aceite"]) ?></span>
          <?php endif; ?>
        </div>

        <div class="barra-acoes-form">
          <button type="submit" class="btn-primario" data-loading-text="Enviando solicitação...">
            <?= $editarId > 0 ? "Salvar alterações da solicitação" : "Enviar solicitação de reserva" ?>
          </button>
          <button type="submit" name="etapa" value="voltar_edicao" class="botao secundario" formnovalidate>
            ← Voltar e editar dados
          </button>
          <a class="link-cancelar" href="<?= url('espacos/detalhes.php?id=' . (int)$e['id']) ?>" onclick="return confirm('Deseja cancelar o preenchimento desta solicitação?')">Cancelar</a>
        </div>
      </form>

    <?php else: ?>
      <div class="rotulo">Etapa 01 de 03 — Preenchimento</div>
      <h1 class="menor"><?= $editarId > 0 ? "Editar solicitação #$editarId" : "Solicitar reserva" ?></h1>
      <p class="nota">Os campos marcados com <span class="obrigatorio">*</span> são obrigatórios. Após enviar, sua solicitação será analisada em até <strong>2 dias úteis</strong>.</p>

      <?php if ($conflitoHorario): ?>
        <div class="caixa-indisponivel" role="alert">
          <strong>⚠️ Espaço indisponível nesse período</strong>
          <p><?= e($errosCampos["conflito"]) ?></p>
          <?php if (!empty($datasSugeridas)): ?>
            <p><strong>Sugestão de datas livres próximas para <?= e($e["nome"]) ?>:</strong></p>
            <div class="chips-sugestao-datas">
              <?php foreach ($datasSugeridas as $ds): ?>
                <button type="button" class="chip-data-sugerida" onclick="document.getElementById('campo-data').value='<?= e($ds) ?>'; document.getElementById('campo-data').focus();">
                  📅 Usar <?= dataBR($ds) ?>
                </button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php elseif (!empty($errosCampos)): ?>
        <div class="msg erro" role="alert">
          <strong>Verifique os campos destacados em vermelho abaixo para continuar.</strong>
        </div>
      <?php endif; ?>

      <form method="post" id="form-solicitar" novalidate>
        <input type="hidden" name="etapa" value="revisar">
        <input type="hidden" name="espaco_id" value="<?= (int)$e["id"] ?>">
        <input type="hidden" name="editar_id" value="<?= (int)$editarId ?>">

        <div class="grupo-campo <?= !empty($errosCampos['data']) || !empty($errosCampos['conflito']) ? 'tem-erro' : '' ?>">
          <label for="campo-data">
            Data do evento <span class="obrigatorio">*</span>
            <span class="dica-campo">Selecione uma data disponível no calendário (bloqueado para datas passadas).</span>
          </label>
          <input type="date" id="campo-data" name="data" min="<?= date("Y-m-d") ?>" value="<?= e($d["data"]) ?>" required>
          <?php if (!empty($errosCampos["data"])): ?>
            <span class="erro-campo"><?= e($errosCampos["data"]) ?></span>
          <?php endif; ?>
        </div>

        <div class="linha">
          <div class="grupo-campo <?= !empty($errosCampos['ini']) ? 'tem-erro' : '' ?>">
            <label for="campo-ini">
              Horário de início <span class="obrigatorio">*</span>
              <span class="dica-campo">Início da utilização do espaço</span>
            </label>
            <input type="time" id="campo-ini" name="ini" value="<?= e($d["ini"]) ?>" required>
            <?php if (!empty($errosCampos["ini"])): ?>
              <span class="erro-campo"><?= e($errosCampos["ini"]) ?></span>
            <?php endif; ?>
          </div>

          <div class="grupo-campo <?= !empty($errosCampos['fim']) ? 'tem-erro' : '' ?>">
            <label for="campo-fim">
              Horário de término <span class="obrigatorio">*</span>
              <span class="dica-campo">Deve ser posterior ao horário de início</span>
            </label>
            <input type="time" id="campo-fim" name="fim" value="<?= e($d["fim"]) ?>" required>
            <?php if (!empty($errosCampos["fim"])): ?>
              <span class="erro-campo"><?= e($errosCampos["fim"]) ?></span>
            <?php endif; ?>
          </div>
        </div>

        <div class="linha">
          <div class="grupo-campo <?= !empty($errosCampos['tipo']) ? 'tem-erro' : '' ?>">
            <label for="campo-tipo">
              Tipo de evento <span class="obrigatorio">*</span>
              <span class="dica-campo">Natureza do seu evento (diferente do tipo de espaço)</span>
            </label>
            <input type="text" id="campo-tipo" name="tipo" list="lista-tipos-evento" value="<?= e($d["tipo"]) ?>" placeholder="Selecione ou digite (ex.: Casamento)" required>
            <datalist id="lista-tipos-evento">
              <?php foreach ($tiposEventoSugeridos as $sug): ?>
                <option value="<?= e($sug) ?>">
              <?php endforeach; ?>
            </datalist>
            <?php if (!empty($errosCampos["tipo"])): ?>
              <span class="erro-campo"><?= e($errosCampos["tipo"]) ?></span>
            <?php endif; ?>
          </div>

          <div class="grupo-campo <?= !empty($errosCampos['pessoas']) ? 'tem-erro' : '' ?>">
            <label for="campo-pessoas">
              Quantidade de pessoas <span class="obrigatorio">*</span>
              <span class="dica-campo">Máximo permitido neste espaço: <strong><?= (int)$e["capacidade"] ?> pessoas</strong></span>
            </label>
            <input type="number" id="campo-pessoas" name="pessoas" min="1" max="<?= (int)$e["capacidade"] ?>" data-max-cap="<?= (int)$e["capacidade"] ?>" value="<?= $d["pessoas"] ?: "" ?>" placeholder="1 a <?= (int)$e["capacidade"] ?>" required>
            <span id="erro-pessoas-js" class="erro-campo <?= empty($errosCampos['pessoas']) ? 'oculto' : '' ?>">
              <?= e($errosCampos["pessoas"] ?? "") ?>
            </span>
          </div>
        </div>

        <div class="grupo-campo">
          <label for="campo-obs">
            Observações e necessidades adicionais <span class="opcional">(opcional)</span>
            <span class="dica-campo">Informe se precisará de disposição especial de mesas, equipamentos extras ou horário de montagem.</span>
          </label>
          <textarea id="campo-obs" name="obs" rows="3" placeholder="Ex.: Montagem da decoração 1 hora antes do início..."><?= e($d["obs"]) ?></textarea>
        </div>

        <div class="barra-acoes-form">
          <button type="submit" class="btn-primario" data-loading-text="Validando dados...">Revisar solicitação →</button>
          <a class="botao secundario" href="<?= url('espacos/detalhes.php?id=' . (int)$e['id']) ?>">← Voltar ao espaço</a>
          <a class="link-cancelar" href="<?= url('espacos/index.php') ?>">Cancelar</a>
        </div>
      </form>
    <?php endif; ?>
  </section>

  <aside class="caixa-fixa-reserva" aria-label="Resumo fixo do espaço selecionado">
    <div class="rotulo">Resumo do espaço</div>
    <strong class="titulo-resumo-fixo"><?= e($e["nome"]) ?></strong>
    <span class="tag-tipo"><?= e($e["tipo"]) ?></span>

    <div class="preco-destaque compacto">
      <strong><?= moeda($e["preco"]) ?><small>/dia</small></strong>
    </div>

    <ul class="resumo-curto">
      <li><span>Capacidade:</span> <strong>até <?= (int)$e["capacidade"] ?> pessoas</strong></li>
      <li><span>Prazo de análise:</span> <strong>até 2 dias úteis</strong></li>
    </ul>

    <?= renderChipsComodidades($e["comodidades"], 4) ?>

    <div class="mini-calendario-lateral">
      <strong>Disponibilidade nos próximos 10 dias</strong>
      <span class="nota">Clique em um dia para preencher a data:</span>
      <div class="grade-mini-lateral">
        <?php for ($i = 0; $i < 10; $i++):
            $diaIso = date("Y-m-d", strtotime("+$i days"));
            $diaBr  = date("d/m", strtotime($diaIso));
            $ocup   = $datasOcupadasMap[$diaIso] ?? [];
            $temOc  = !empty($ocup);
        ?>
          <button type="button"
            class="btn-dia-mini <?= $temOc ? 'parcial' : 'livre' ?> <?= $d['data'] === $diaIso ? 'ativo' : '' ?>"
            onclick="const c = document.getElementById('campo-data'); if(c){ c.value='<?= $diaIso ?>'; c.dispatchEvent(new Event('change')); }"
            title="<?= $temOc ? 'Ocupado em: ' . implode(', ', $ocup) : 'Dia livre' ?>">
            <span><?= $diaBr ?></span>
            <small><?= $temOc ? 'Parcial' : 'Livre' ?></small>
          </button>
        <?php endfor; ?>
      </div>
    </div>
  </aside>
</div>

<?php rodape(); ?>
