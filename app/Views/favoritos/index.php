<section>
  <div class="rotulo">Sua seleção pessoal</div>
  <h1 class="menor">Espaços favoritos</h1>
  <p class="nota">Guarde seus espaços preferidos para comparar comodidades e solicitar reservas rapidamente.</p>

  <?php if (empty($lista)): ?>
    <div class="estado-vazio-erro">
      <h2>Você ainda não salvou nenhum espaço favorito</h2>
      <p>Clique no botão <strong>"☆ Favoritar"</strong> nos cartões de espaços para guardá-los aqui.</p>
      <a class="botao" href="<?= url('espacos') ?>">Explorar espaços disponíveis</a>
    </div>
  <?php else: ?>
    <div class="grade">
      <?php foreach ($lista as $e):
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
              <input type="hidden" name="retorno" value="<?= url('favoritos') ?>">
              <button type="submit" class="btn-favorito ativo" title="Remover dos favoritos">★ Salvo</button>
            </form>
          </div>

          <strong class="card-titulo"><a href="<?= url('espacos/detalhes?id=' . (int)$e['id']) ?>"><?= e($e['nome']) ?></a></strong>
          <span class="card-localizacao">📍 <?= e(enderecoCurto($e)) ?></span>
          <span class="info"><?= capacidadeTexto($e['capacidade']) ?></span>
          <span class="status-disponibilidade <?= $e['disponivel'] ? 'disp-livre' : 'disp-ocupado' ?>">
            <?= $e['disponivel'] ? '● Disponível para reserva' : '● Espaço temporariamente inativo' ?>
          </span>

          <?= renderChipsComodidades($e['comodidades']) ?>

          <div class="card-rodape">
            <span class="preco"><?= moeda($e['preco']) ?><small>/dia</small></span>
            <div class="card-acoes">
              <a class="botao secundario pequeno" href="<?= url('espacos/detalhes?id=' . (int)$e['id']) ?>">Ver detalhes</a>
              <?php if ($e['disponivel']): ?>
                <a class="botao pequeno" href="<?= url('reservas/solicitar?id=' . (int)$e['id']) ?>">Solicitar reserva</a>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
