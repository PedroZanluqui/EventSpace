<section>
  <div class="rotulo">Catálogo e resultados</div>
  <h1 class="menor">Espaços disponíveis</h1>
  <p class="nota">Filtre por tipo de espaço, data, capacidade e comodidades. Você pode selecionar até <strong>3 espaços</strong> para comparar lado a lado.</p>

  <form class="busca busca-completa" method="get" action="<?= url('espacos') ?>">
    <label>Tipo de espaço
      <select name="tipo">
        <option value="">Todos os tipos</option>
        <?php foreach ($todosTipos as $t): ?>
          <option value="<?= e($t['tipo']) ?>" <?= $t['tipo'] === $tipo ? 'selected' : '' ?>><?= e($t['tipo']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Data pretendida
      <input type="date" name="data" min="<?= date('Y-m-d') ?>" value="<?= e($data) ?>">
    </label>
    <label>Quantidade de pessoas
      <input type="number" name="pessoas" min="1" value="<?= $pessoas ?: '' ?>" placeholder="Ex.: 50">
    </label>
    <label>Comodidades
      <select name="comodidade">
        <option value="">Todas as comodidades</option>
        <?php foreach ($todasComodidades as $c): ?>
          <option value="<?= e($c) ?>" <?= $c === $comodidade ? 'selected' : '' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Ordenar por
      <select name="ordem">
        <option value="nome_asc" <?= $ordem === 'nome_asc' ? 'selected' : '' ?>>Nome (A–Z)</option>
        <option value="preco_asc" <?= $ordem === 'preco_asc' ? 'selected' : '' ?>>Menor preço por dia</option>
        <option value="preco_desc" <?= $ordem === 'preco_desc' ? 'selected' : '' ?>>Maior preço por dia</option>
        <option value="cap_desc" <?= $ordem === 'cap_desc' ? 'selected' : '' ?>>Maior capacidade</option>
        <option value="cap_asc" <?= $ordem === 'cap_asc' ? 'selected' : '' ?>>Menor capacidade</option>
      </select>
    </label>
    <div class="busca-acao">
      <button type="submit" class="btn-primario" data-loading-text="Filtrando...">Aplicar filtros</button>
    </div>
  </form>

  <?php if (!empty($filtrosAtivos)): ?>
    <div class="barra-filtros-ativos" aria-label="Filtros ativos">
      <div class="chips-ativos">
        <span class="rotulo-inline">Filtros ativos:</span>
        <?php foreach ($filtrosAtivos as $fa): ?>
          <a class="chip-removivel" href="<?= e($fa['url']) ?>" title="Remover este filtro">
            <?= e($fa['rotulo']) ?> <span aria-hidden="true">×</span>
          </a>
        <?php endforeach; ?>
        <a class="link-limpar" href="<?= url('espacos') ?>">Limpar filtros</a>
      </div>

      <form method="post" action="<?= url('espacos') ?>" class="form-salvar-busca">
        <input type="hidden" name="acao_busca" value="salvar">
        <input type="hidden" name="tipo" value="<?= e($tipo) ?>">
        <input type="hidden" name="data" value="<?= e($data) ?>">
        <input type="hidden" name="pessoas" value="<?= $pessoas ?: '' ?>">
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
          <a href="<?= url('espacos?' . http_build_query($bs['params'])) ?>"><?= e($bs['titulo']) ?></a>
          <form method="post" action="<?= url('espacos') ?>" class="inline-zero">
            <input type="hidden" name="acao_busca" value="remover">
            <input type="hidden" name="id_busca" value="<?= e($bs['id']) ?>">
            <button type="submit" class="btn-remover-chip" title="Excluir busca salva">×</button>
          </form>
        </span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (empty($lista)): ?>
    <div class="estado-vazio-erro">
      <h2>Nenhum espaço encontrado</h2>
      <p>Não encontramos espaços disponíveis com a combinação de filtros selecionada<?= $data ? ' para o dia <strong>' . dataBR($data) . '</strong>' : '' ?>.</p>
      <div class="acoes-erro">
        <a class="botao" href="<?= url('espacos') ?>">Limpar filtros e ver todos</a>
        <?php if ($data !== ''):
            $proxDia = date('Y-m-d', strtotime($data . ' +1 day'));
            $qProx = $_GET;
            unset($qProx['rota']);
            $qProx['data'] = $proxDia;
        ?>
          <a class="botao secundario" href="<?= url('espacos?' . http_build_query($qProx)) ?>">Tentar dia seguinte (<?= dataBR($proxDia) ?>)</a>
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>
    <p class="contagem-resultados">Exibindo <strong><?= count($lista) ?></strong> <?= count($lista) === 1 ? 'espaço encontrado' : 'espaços encontrados' ?>.</p>

    <div class="grade">
      <?php foreach ($lista as $e):
        $ehFav = in_array((int)$e['id'], $favIds, true);
        $imgUrl = imagemEspaco($e);
        $precisaHidratar = empty($e['imagem_url']);
        $endCurto = enderecoCurto($e);
      ?>
        <article class="card"
          data-id="<?= (int)$e['id'] ?>"
          data-nome="<?= e($e['nome']) ?>"
          data-tipo="<?= e($e['tipo']) ?>"
          data-capacidade="<?= e(capacidadeTexto($e['capacidade'])) ?>"
          data-preco="<?= e(moeda($e['preco']) . '/dia') ?>"
          data-comodidades="<?= e($e['comodidades']) ?>"
          data-endereco="<?= e($endCurto) ?>"
          data-imagem="<?= e($imgUrl) ?>"
          data-url="<?= e(url('reservas/solicitar?id=' . (int)$e['id'] . $sufixoContexto)) ?>">

          <a href="<?= url('espacos/detalhes?id=' . (int)$e['id'] . $sufixoContexto) ?>" class="card-midia">
            <img
              src="<?= e($imgUrl) ?>"
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
              <input type="hidden" name="retorno" value="<?= e($_SERVER['REQUEST_URI'] ?? url('espacos')) ?>">
              <button type="submit" class="btn-favorito <?= $ehFav ? 'ativo' : '' ?>" title="<?= $ehFav ? 'Remover dos favoritos' : 'Adicionar aos favoritos' ?>">
                <?= $ehFav ? '★ Salvo' : '☆ Favoritar' ?>
              </button>
            </form>
          </div>

          <strong class="card-titulo">
            <a href="<?= url('espacos/detalhes?id=' . (int)$e['id'] . $sufixoContexto) ?>"><?= e($e['nome']) ?></a>
          </strong>

          <span class="card-localizacao">📍 <?= e($endCurto) ?></span>
          <span class="info"><?= capacidadeTexto($e['capacidade']) ?></span>

          <span class="status-disponibilidade disp-livre">
            <?php if ($data !== ''): ?>
              ● Disponível em <?= dataBR($data) ?>
            <?php else: ?>
              ● Disponível para reserva (escolha uma data)
            <?php endif; ?>
          </span>

          <?= renderChipsComodidades($e['comodidades']) ?>

          <label class="check-comparar">
            <input type="checkbox" class="seletor-comparar" value="<?= (int)$e['id'] ?>">
            <span>Comparar espaço (até 3)</span>
          </label>

          <div class="card-rodape">
            <span class="preco"><?= moeda($e['preco']) ?><small>/dia</small></span>
            <div class="card-acoes">
              <a class="botao secundario pequeno" href="<?= url('espacos/detalhes?id=' . (int)$e['id'] . $sufixoContexto) ?>">Ver detalhes</a>
              <a class="botao pequeno" href="<?= url('reservas/solicitar?id=' . (int)$e['id'] . $sufixoContexto) ?>">Solicitar reserva</a>
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
