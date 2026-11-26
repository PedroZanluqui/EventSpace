<?php
require_once __DIR__ . "/includes/funcoes.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["espaco_id"])) {
    $eid = inteiroEntrada($_POST, "espaco_id");
    $adicionado = alternarFavorito($conn, $eid);
    definirToast($adicionado ? "Espaço salvo na sua lista de Favoritos!" : "Espaço removido dos Favoritos.");
    $retorno = textoEntrada($_POST, "retorno") ?: url("favoritos.php");
    header("Location: " . $retorno);
    exit;
}

$favIds = idsFavoritos($conn);
$lista = [];
if (!empty($favIds)) {
    $idsSeguros = implode(",", array_map('intval', $favIds));
    $lista = $conn->query("SELECT * FROM espacos WHERE id IN ($idsSeguros) ORDER BY nome")->fetch_all(MYSQLI_ASSOC);
}

cabecalho("Meus espaços favoritos", "favoritos");
?>
<section>
  <div class="rotulo">Sua seleção pessoal</div>
  <h1 class="menor">Espaços favoritos</h1>
  <p class="nota">Guarde seus espaços preferidos para comparar comodidades e solicitar reservas rapidamente.</p>

  <?php if (!$lista): ?>
    <div class="estado-vazio-erro">
      <h2>Você ainda não salvou nenhum espaço favorito</h2>
      <p>Clique no botão <strong>"☆ Favoritar"</strong> nos cartões de espaços para guardá-los aqui.</p>
      <a class="botao" href="<?= url('espacos/index.php') ?>">Explorar espaços disponíveis</a>
    </div>
  <?php else: ?>
    <div class="grade">
      <?php foreach ($lista as $e): ?>
        <article class="card">
          <div class="card-topo">
            <span class="tag-tipo"><?= e($e["tipo"]) ?></span>
            <form method="post" action="<?= url('favoritos.php') ?>" class="form-fav">
              <input type="hidden" name="espaco_id" value="<?= (int)$e["id"] ?>">
              <input type="hidden" name="retorno" value="<?= url('favoritos.php') ?>">
              <button type="submit" class="btn-favorito ativo" title="Remover dos favoritos">★ Salvo</button>
            </form>
          </div>

          <strong class="card-titulo"><a href="<?= url('espacos/detalhes.php?id=' . (int)$e["id"]) ?>"><?= e($e["nome"]) ?></a></strong>
          <span class="info"><?= capacidadeTexto($e["capacidade"]) ?></span>
          <span class="status-disponibilidade <?= $e['disponivel'] ? 'disp-livre' : 'disp-ocupado' ?>">
            <?= $e["disponivel"] ? "● Disponível para reserva" : "● Espaço temporariamente inativo" ?>
          </span>

          <?= renderChipsComodidades($e["comodidades"]) ?>

          <div class="card-rodape">
            <span class="preco"><?= moeda($e["preco"]) ?><small>/dia</small></span>
            <div class="card-acoes">
              <a class="botao secundario pequeno" href="<?= url('espacos/detalhes.php?id=' . (int)$e["id"]) ?>">Ver detalhes</a>
              <?php if ($e["disponivel"]): ?>
                <a class="botao pequeno" href="<?= url('reservas/solicitar.php?id=' . (int)$e["id"]) ?>">Solicitar reserva</a>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<?php rodape(); ?>

