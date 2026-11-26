<section class="hero">
  <div class="rotulo">Solicitação de espaços para eventos</div>
  <h1>Qual evento você está planejando?</h1>
  <p class="lead">Busque por tipo de espaço, data e quantidade de pessoas. Sua solicitação é analisada pelo administrador em até <strong>2 dias úteis</strong>.</p>

  <form class="busca" method="get" action="<?= url('espacos') ?>">
    <label>Tipo de espaço
      <select name="tipo">
        <option value="">Todos os tipos</option>
        <?php foreach ($tipos as $t): ?>
          <option value="<?= e($t['tipo']) ?>"><?= e($t['tipo']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Data do evento
      <input type="date" name="data" min="<?= date('Y-m-d') ?>">
    </label>
    <label>Quantidade de pessoas
      <input type="number" name="pessoas" min="1" placeholder="Ex.: 80">
    </label>
    <button type="submit" class="btn-primario" data-loading-text="Buscando...">Buscar espaços</button>
  </form>

  <?php if (!empty($buscasSalvas)): ?>
    <div class="buscas-frequentes">
      <span class="rotulo-inline">Suas buscas salvas:</span>
      <?php foreach ($buscasSalvas as $bs): ?>
        <a class="chip-filtro" href="<?= url('espacos?' . http_build_query($bs['params'])) ?>">
          🔍 <?= e($bs['titulo']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="como-funciona" aria-label="Como funciona o processo de reserva">
  <div class="como-funciona-cabecalho">
    <div class="rotulo">Passo a passo transparente</div>
    <h2>Como funciona sua solicitação</h2>
    <p class="nota">Acompanhe cada etapa desde a escolha do espaço até a confirmação definitiva na agenda.</p>
  </div>
  <div class="passos-grade">
    <div class="passo-item">
      <span class="passo-numero">01</span>
      <strong>Escolha o espaço e a data</strong>
      <p>Filtre por tipo de espaço, comodidades, data e pessoas, veja fotos e localização no mapa e compare até 3 opções.</p>
    </div>
    <div class="passo-item">
      <span class="passo-numero">02</span>
      <strong>Preencha e revise os dados</strong>
      <p>Informe o horário de início e término, o tipo de evento e aceite a política de cancelamento antes de enviar.</p>
    </div>
    <div class="passo-item">
      <span class="passo-numero">03</span>
      <strong>Análise e prazo para ajustes</strong>
      <p>Em até 2 dias úteis o pedido é aprovado ou retorna com motivo claro e prazo para você resolver pendências.</p>
    </div>
    <div class="passo-item">
      <span class="passo-numero">04</span>
      <strong>Confirmação garantida</strong>
      <p>Assim que aprovado, você recebe um aviso no topo do sistema e o espaço fica garantido na agenda oficial.</p>
    </div>
  </div>
</section>

<section class="secao-destaques">
  <div class="cabecalho-secao">
    <div>
      <div class="rotulo">Catálogo em destaque</div>
      <h2>Espaços mais procurados</h2>
    </div>
    <a class="botao secundario pequeno" href="<?= url('espacos') ?>">Ver todos os espaços →</a>
  </div>

  <div class="grade">
    <?php foreach ($destaques as $e):
      $ehFav = in_array((int)$e['id'], $favIds, true);
      $precisaHidratar = empty($e['imagem_url']);
    ?>
      <article class="card">
        <a href="<?= url('espacos/detalhes?id=' . (int)$e['id']) ?>" class="card-midia">
          <img
            src="<?= e(imagemEspaco($e)) ?>"
            alt="Foto de <?= e($e['nome']) ?>"
            class="card-imagem"
            loading="lazy"
            <?php if ($precisaHidratar): ?>
              data-hydrate-espaco-id="<?= (int)$e['id'] ?>"
              data-hydrate-query="<?= e($e['tipo']) ?>"
            <?php endif; ?>
          >
        </a>

        <div class="card-topo">
          <span class="tag-tipo"><?= e($e['tipo']) ?></span>
          <form method="post" action="<?= url('favoritos') ?>" class="form-fav">
            <input type="hidden" name="espaco_id" value="<?= (int)$e['id'] ?>">
            <input type="hidden" name="retorno" value="<?= e($_SERVER['REQUEST_URI'] ?? url('')) ?>">
            <button type="submit" class="btn-favorito <?= $ehFav ? 'ativo' : '' ?>" title="<?= $ehFav ? 'Remover dos favoritos' : 'Salvar nos favoritos' ?>" aria-label="Favoritar <?= e($e['nome']) ?>">
              <?= $ehFav ? '★ Salvo' : '☆ Favoritar' ?>
            </button>
          </form>
        </div>

        <strong class="card-titulo"><a href="<?= url('espacos/detalhes?id=' . (int)$e['id']) ?>"><?= e($e['nome']) ?></a></strong>
        <span class="card-localizacao">📍 <?= e(enderecoCurto($e)) ?></span>
        <span class="info"><?= capacidadeTexto($e['capacidade']) ?></span>
        <span class="status-disponibilidade disp-livre">● Disponível para novas datas</span>

        <?= renderChipsComodidades($e['comodidades'], 4) ?>

        <div class="card-rodape">
          <span class="preco"><?= moeda($e['preco']) ?><small>/dia</small></span>
          <div class="card-acoes">
            <a class="botao secundario pequeno" href="<?= url('espacos/detalhes?id=' . (int)$e['id']) ?>">Ver detalhes</a>
            <a class="botao pequeno" href="<?= url('reservas/solicitar?id=' . (int)$e['id']) ?>">Solicitar reserva</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
