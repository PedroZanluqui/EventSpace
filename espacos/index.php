<?php
require_once __DIR__ . "/../includes/funcoes.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["acao_busca"])) {
    $acaoBusca = textoEntrada($_POST, "acao_busca");
    if ($acaoBusca === "salvar") {
        $paramsSalvar = [
            "tipo"       => textoEntrada($_POST, "tipo"),
            "data"       => textoEntrada($_POST, "data"),
            "pessoas"    => inteiroEntrada($_POST, "pessoas") ?: "",
            "comodidade" => textoEntrada($_POST, "comodidade"),
            "ordem"      => textoEntrada($_POST, "ordem"),
        ];
        $paramsSalvar = array_filter($paramsSalvar, fn($v) => $v !== "");
        if ($paramsSalvar) {
            $partes = [];
            if (!empty($paramsSalvar["tipo"])) $partes[] = $paramsSalvar["tipo"];
            if (!empty($paramsSalvar["comodidade"])) $partes[] = $paramsSalvar["comodidade"];
            if (!empty($paramsSalvar["pessoas"])) $partes[] = $paramsSalvar["pessoas"] . "+ pessoas";
            if (!empty($paramsSalvar["data"])) $partes[] = dataBR($paramsSalvar["data"]);
            $tituloBusca = implode(" · ", $partes) ?: "Busca personalizada";
            $_SESSION["buscas_salvas"][] = [
                "id"     => uniqid(),
                "titulo" => $tituloBusca,
                "params" => $paramsSalvar
            ];
            definirToast("Filtro de busca salvo em suas buscas frequentes!");
        }
        header("Location: " . url("espacos/index.php?" . http_build_query($paramsSalvar)));
        exit;
    } elseif ($acaoBusca === "remover") {
        $idRem = textoEntrada($_POST, "id_busca");
        $_SESSION["buscas_salvas"] = array_values(array_filter(
            $_SESSION["buscas_salvas"] ?? [],
            fn($b) => $b["id"] !== $idRem
        ));
        definirToast("Busca salva removida.");
        header("Location: " . url("espacos/index.php"));
        exit;
    }
}

$tipo       = textoEntrada($_GET, "tipo");
$data       = textoEntrada($_GET, "data");
$pessoas    = inteiroEntrada($_GET, "pessoas");
$comodidade = textoEntrada($_GET, "comodidade");
$ordem      = textoEntrada($_GET, "ordem") ?: "nome_asc";

$sql    = "SELECT * FROM espacos WHERE disponivel = 1";
$tipos  = "";
$params = [];

if ($tipo !== "") {
    $sql .= " AND tipo = ?";
    $tipos .= "s";
    $params[] = $tipo;
}
if ($pessoas > 0) {
    $sql .= " AND capacidade >= ?";
    $tipos .= "i";
    $params[] = $pessoas;
}
if ($comodidade !== "") {
    $sql .= " AND comodidades LIKE ?";
    $tipos .= "s";
    $params[] = "%" . $comodidade . "%";
}
if ($data !== "") {
    $sql .= " AND id NOT IN (SELECT espaco_id FROM reservas WHERE data_evento = ? AND status='APROVADA')";
    $tipos .= "s";
    $params[] = $data;
}

$mapaOrdem = [
    "nome_asc"   => "nome ASC",
    "preco_asc"  => "preco ASC",
    "preco_desc" => "preco DESC",
    "cap_desc"   => "capacidade DESC",
    "cap_asc"    => "capacidade ASC",
];
$sql .= " ORDER BY " . ($mapaOrdem[$ordem] ?? "nome ASC");

$st = $conn->prepare($sql);
if ($params) $st->bind_param($tipos, ...$params);
$st->execute();
$lista = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$todosTipos = $conn->query("SELECT DISTINCT tipo FROM espacos WHERE disponivel = 1 ORDER BY tipo")->fetch_all(MYSQLI_ASSOC);
$todasComodidades = ["Wi-Fi", "Ar-condicionado", "Estacionamento", "Projetor", "Som", "Cozinha", "Iluminação", "Acessibilidade"];
$favIds = idsFavoritos($conn);
$buscasSalvas = $_SESSION["buscas_salvas"] ?? [];

$queryContexto = [];
if ($data !== "") $queryContexto["data"] = $data;
if ($pessoas > 0) $queryContexto["pessoas"] = $pessoas;
$sufixoContexto = $queryContexto ? "&" . http_build_query($queryContexto) : "";

$filtrosAtivos = [];
if ($tipo !== "") {
    $q = $_GET; unset($q["tipo"]);
    $filtrosAtivos[] = ["rotulo" => "Tipo de espaço: $tipo", "url" => url("espacos/index.php?" . http_build_query($q))];
}
if ($data !== "") {
    $q = $_GET; unset($q["data"]);
    $filtrosAtivos[] = ["rotulo" => "Data: " . dataBR($data), "url" => url("espacos/index.php?" . http_build_query($q))];
}
if ($pessoas > 0) {
    $q = $_GET; unset($q["pessoas"]);
    $filtrosAtivos[] = ["rotulo" => "Mínimo de $pessoas pessoas", "url" => url("espacos/index.php?" . http_build_query($q))];
}
if ($comodidade !== "") {
    $q = $_GET; unset($q["comodidade"]);
    $filtrosAtivos[] = ["rotulo" => "Comodidade: $comodidade", "url" => url("espacos/index.php?" . http_build_query($q))];
}

cabecalho("Espaços disponíveis", "espacos");
?>
<section>
  <div class="rotulo">Catálogo e resultados</div>
  <h1 class="menor">Espaços disponíveis</h1>
  <p class="nota">Filtre por tipo de espaço, data, capacidade e comodidades. Você pode selecionar até <strong>3 espaços</strong> para comparar lado a lado.</p>

  <form class="busca busca-completa" method="get">
    <label>Tipo de espaço
      <select name="tipo">
        <option value="">Todos os tipos</option>
        <?php foreach ($todosTipos as $t): ?>
          <option value="<?= e($t["tipo"]) ?>" <?= $t["tipo"] === $tipo ? "selected" : "" ?>><?= e($t["tipo"]) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Data pretendida
      <input type="date" name="data" min="<?= date('Y-m-d') ?>" value="<?= e($data) ?>">
    </label>
    <label>Quantidade de pessoas
      <input type="number" name="pessoas" min="1" value="<?= $pessoas ?: "" ?>" placeholder="Ex.: 50">
    </label>
    <label>Comodidades
      <select name="comodidade">
        <option value="">Todas as comodidades</option>
        <?php foreach ($todasComodidades as $c): ?>
          <option value="<?= e($c) ?>" <?= $c === $comodidade ? "selected" : "" ?>><?= e($c) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Ordenar por
      <select name="ordem">
        <option value="nome_asc" <?= $ordem === "nome_asc" ? "selected" : "" ?>>Nome (A–Z)</option>
        <option value="preco_asc" <?= $ordem === "preco_asc" ? "selected" : "" ?>>Menor preço por dia</option>
        <option value="preco_desc" <?= $ordem === "preco_desc" ? "selected" : "" ?>>Maior preço por dia</option>
        <option value="cap_desc" <?= $ordem === "cap_desc" ? "selected" : "" ?>>Maior capacidade</option>
        <option value="cap_asc" <?= $ordem === "cap_asc" ? "selected" : "" ?>>Menor capacidade</option>
      </select>
    </label>
    <button type="submit" class="btn-primario" data-loading-text="Filtrando...">Aplicar filtros</button>
  </form>

  <?php if (!empty($filtrosAtivos)): ?>
    <div class="barra-filtros-ativos" aria-label="Filtros ativos">
      <div class="chips-ativos">
        <span class="rotulo-inline">Filtros ativos:</span>
        <?php foreach ($filtrosAtivos as $fa): ?>
          <a class="chip-removivel" href="<?= e($fa["url"]) ?>" title="Remover este filtro">
            <?= e($fa["rotulo"]) ?> <span aria-hidden="true">×</span>
          </a>
        <?php endforeach; ?>
        <a class="link-limpar" href="<?= url('espacos/index.php') ?>">Limpar filtros</a>
      </div>

      <form method="post" class="form-salvar-busca">
        <input type="hidden" name="acao_busca" value="salvar">
        <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
        <input type="hidden" name="data" value="<?= e($data) ?>">
        <input type="hidden" name="pessoas" value="<?= $pessoas ?: "" ?>">
        <input type="hidden" name="comodidade" value="<?= e($comodidade) ?>">
        <input type="hidden" name="ordem" value="<?= e($ordem) ?>">
        <button type="submit" class="botao secundario pequeno">🔖 Salvar estes filtros</button>
      </form>
    </div>
  <?php endif; ?>

  <?php if (!empty($buscasSalvas)): ?>
    <div class="buscas-frequentes">
      <span class="rotulo-inline">Filtros frequentes salvos:</span>
      <?php foreach ($buscasSalvas as $bs): ?>
        <span class="chip-salvo">
          <a href="<?= url('espacos/index.php?' . http_build_query($bs['params'])) ?>"><?= e($bs['titulo']) ?></a>
          <form method="post" class="inline-zero">
            <input type="hidden" name="acao_busca" value="remover">
            <input type="hidden" name="id_busca" value="<?= e($bs['id']) ?>">
            <button type="submit" class="btn-remover-chip" title="Excluir busca salva">×</button>
          </form>
        </span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!$lista): ?>
    <div class="estado-vazio-erro">
      <h2>Nenhum espaço encontrado</h2>
      <p>Não encontramos espaços disponíveis com a combinação de filtros selecionada<?= $data ? " para o dia <strong>" . dataBR($data) . "</strong>" : "" ?>.</p>
      <div class="acoes-erro">
        <a class="botao" href="<?= url('espacos/index.php') ?>">Limpar filtros e ver todos</a>
        <?php if ($data !== ""):
            $proxDia = date('Y-m-d', strtotime($data . ' +1 day'));
            $qProx = $_GET; $qProx['data'] = $proxDia;
        ?>
          <a class="botao secundario" href="<?= url('espacos/index.php?' . http_build_query($qProx)) ?>">Tentar dia seguinte (<?= dataBR($proxDia) ?>)</a>
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>
    <p class="contagem-resultados">Exibindo <strong><?= count($lista) ?></strong> <?= count($lista) === 1 ? 'espaço encontrado' : 'espaços encontrados' ?>.</p>

    <div class="grade">
      <?php foreach ($lista as $e):
        $ehFav = in_array((int)$e["id"], $favIds);
      ?>
        <article class="card"
          data-id="<?= (int)$e["id"] ?>"
          data-nome="<?= e($e["nome"]) ?>"
          data-tipo="<?= e($e["tipo"]) ?>"
          data-capacidade="<?= e(capacidadeTexto($e["capacidade"])) ?>"
          data-preco="<?= e(moeda($e["preco"]) . '/dia') ?>"
          data-comodidades="<?= e($e["comodidades"]) ?>"
          data-url="<?= e(url('reservas/solicitar.php?id=' . $e["id"] . $sufixoContexto)) ?>">

          <div class="card-topo">
            <span class="tag-tipo"><?= e($e["tipo"]) ?></span>
            <form method="post" action="<?= url('favoritos.php') ?>" class="form-fav">
              <input type="hidden" name="espaco_id" value="<?= (int)$e["id"] ?>">
              <input type="hidden" name="retorno" value="<?= e($_SERVER['REQUEST_URI'] ?? url('espacos/index.php')) ?>">
              <button type="submit" class="btn-favorito <?= $ehFav ? 'ativo' : '' ?>" title="<?= $ehFav ? 'Remover dos favoritos' : 'Adicionar aos favoritos' ?>">
                <?= $ehFav ? '★ Salvo' : '☆ Favoritar' ?>
              </button>
            </form>
          </div>

          <strong class="card-titulo">
            <a href="<?= url('espacos/detalhes.php?id=' . $e["id"] . $sufixoContexto) ?>"><?= e($e["nome"]) ?></a>
          </strong>

          <span class="info"><?= capacidadeTexto($e["capacidade"]) ?></span>

          <span class="status-disponibilidade disp-livre">
            <?php if ($data !== ""): ?>
              ● Disponível em <?= dataBR($data) ?>
            <?php else: ?>
              ● Disponível para reserva (escolha uma data)
            <?php endif; ?>
          </span>

          <?= renderChipsComodidades($e["comodidades"]) ?>

          <label class="check-comparar">
            <input type="checkbox" class="seletor-comparar" value="<?= (int)$e["id"] ?>">
            <span>Comparar espaço (até 3)</span>
          </label>

          <div class="card-rodape">
            <span class="preco"><?= moeda($e["preco"]) ?><small>/dia</small></span>
            <div class="card-acoes">
              <a class="botao secundario pequeno" href="<?= url('espacos/detalhes.php?id=' . $e["id"] . $sufixoContexto) ?>">Ver detalhes</a>
              <a class="botao pequeno" href="<?= url('reservas/solicitar.php?id=' . $e["id"] . $sufixoContexto) ?>">Solicitar reserva</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<div id="barra-comparacao" class="barra-comparacao oculto" role="region" aria-label="Comparador de espaços">
  <div class="barra-comparacao-interno">
    <div>
      <strong>Comparar espaços selecionados (<span id="contador-comparar">0</span>/3)</strong>
      <span id="nomes-comparar" class="nota"></span>
    </div>
    <div class="acoes-comparar">
      <button type="button" class="botao pequeno" id="btn-abrir-comparador">Comparar lado a lado</button>
      <button type="button" class="botao secundario pequeno" id="btn-limpar-comparador">Limpar seleção</button>
    </div>
  </div>
</div>

<div id="modal-comparacao" class="modal-fundo oculto" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-comp">
  <div class="modal-caixa largo">
    <div class="modal-cabecalho">
      <h2 id="titulo-modal-comp">Comparação lado a lado</h2>
      <button type="button" class="fechar-modal" id="btn-fechar-comparador" aria-label="Fechar comparação">×</button>
    </div>
    <div id="conteudo-comparacao" class="tabela-responsiva"></div>
  </div>
</div>

<?php rodape(); ?>
