<nav class="trilha-navegacao" aria-label="Caminho de navegação">
  <a href="<?= url('espacos' . ($dataFiltro !== '' || $pessoasFiltro > 0 ? '?' . http_build_query(array_filter(['data' => $dataFiltro, 'pessoas' => $pessoasFiltro ?: ''])) : '')) ?>">← Voltar aos resultados</a>
</nav>

<div class="layout-detalhes">
  <section class="detalhe-principal">
    <div class="cabecalho-detalhe">
      <div>
        <span class="tag-tipo"><?= e($e['tipo']) ?></span>
        <h1 class="menor"><?= e($e['nome']) ?></h1>
        <p class="card-localizacao">📍 <?= e($enderecoCompleto) ?></p>
      </div>
      <form method="post" action="<?= url('favoritos') ?>">
        <input type="hidden" name="espaco_id" value="<?= (int)$e['id'] ?>">
        <input type="hidden" name="retorno" value="<?= e($_SERVER['REQUEST_URI'] ?? url('espacos/detalhes?id=' . (int)$e['id'])) ?>">
        <button type="submit" class="btn-favorito <?= $ehFav ? 'ativo' : '' ?>">
          <?= $ehFav ? '★ Salvo nos favoritos' : '☆ Salvar nos favoritos' ?>
        </button>
      </form>
    </div>

    <div class="galeria-espaco" aria-label="Galeria de fotos do espaço">
      <div class="galeria-principal">
        <img
          id="galeria-img-principal"
          src="<?= e($galeria[0]) ?>"
          alt="Foto principal de <?= e($e['nome']) ?>"
          <?php if (empty($e['imagem_url'])): ?>
            data-hydrate-espaco-id="<?= (int)$e['id'] ?>"
            data-hydrate-query="<?= e($e['tipo']) ?>"
            data-hydrate-gallery="1"
          <?php endif; ?>
        >
      </div>
      <?php if (!empty($e['imagem_credito'])): ?>
        <p class="foto-credito" id="galeria-credito">📷 <?= e($e['imagem_credito']) ?></p>
      <?php else: ?>
        <p class="foto-credito oculto" id="galeria-credito"></p>
      <?php endif; ?>

      <div class="galeria-miniaturas <?= count($galeria) <= 1 ? 'oculto' : '' ?>" id="galeria-miniaturas">
        <?php foreach ($galeria as $idx => $fotoUrl): ?>
          <button type="button" class="galeria-thumb <?= $idx === 0 ? 'ativa' : '' ?>" data-foto-url="<?= e($fotoUrl) ?>" aria-label="Visualizar foto <?= $idx + 1 ?>">
            <img src="<?= e($fotoUrl) ?>" alt="Miniatura <?= $idx + 1 ?> de <?= e($e['nome']) ?>" loading="lazy">
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <p class="lead largo-texto"><?= e($e['descricao']) ?></p>

    <div class="bloco-secao">
      <h2>Capacidade e Comodidades</h2>
      <p class="destaque-capacidade">👥 <strong><?= capacidadeTexto($e['capacidade']) ?></strong></p>
      <?= renderChipsComodidades($e['comodidades']) ?>
    </div>

    <div class="bloco-secao">
      <h2>Localização e Mapa (Google Maps)</h2>
      <p class="nota"><strong>Endereço:</strong> <?= e($enderecoCompleto) ?></p>

      <div class="mapa-container">
        <iframe
          class="mapa-iframe"
          src="<?= e($mapaEmbedUrl) ?>"
          loading="lazy"
          allowfullscreen
          referrerpolicy="no-referrer-when-downgrade"
          title="Localização de <?= e($e['nome']) ?> no Google Maps"
        ></iframe>
      </div>

      <div class="mapa-rodape-acoes">
        <a class="botao secundario pequeno" href="<?= e($mapaAbrirUrl) ?>" target="_blank" rel="noopener noreferrer">🗺️ Abrir no Google Maps</a>
        <a class="botao secundario pequeno" href="<?= e($mapaRotasUrl) ?>" target="_blank" rel="noopener noreferrer">🧭 Traçar rota até o local</a>
      </div>
    </div>

    <div class="bloco-secao">
      <h2>Calendário de disponibilidade (próximos 14 dias)</h2>
      <p class="nota">Clique em uma data livre para iniciar sua solicitação com a data já preenchida.</p>
      <div class="grade-calendario-mini">
        <?php
        for ($i = 0; $i < 14; $i++):
            $diaIso = date('Y-m-d', strtotime("+$i days"));
            $diaBr  = date('d/m', strtotime($diaIso));
            $sem    = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'][(int)date('w', strtotime($diaIso))];
            $horariosOcup = $datasOcupadasMap[$diaIso] ?? [];
            $temOcupacao  = !empty($horariosOcup);
            $selecionado  = ($dataFiltro === $diaIso);
            $linkDia = url('reservas/solicitar?' . http_build_query([
                'id'      => $e['id'],
                'data'    => $diaIso,
                'pessoas' => $pessoasFiltro ?: '',
            ]));
        ?>
          <a href="<?= e($linkDia) ?>" class="dia-calendario <?= $temOcupacao ? 'dia-parcial' : 'dia-livre' ?> <?= $selecionado ? 'dia-selecionado' : '' ?>" title="<?= $temOcupacao ? 'Horários reservados: ' . implode(', ', $horariosOcup) : 'Dia totalmente livre — clique para solicitar reserva' ?>">
            <span class="dia-semana"><?= $sem ?></span>
            <strong class="dia-data"><?= $diaBr ?></strong>
            <span class="dia-status"><?= $temOcupacao ? 'Parcial' : 'Livre' ?></span>
          </a>
        <?php endfor; ?>
      </div>

      <?php if (!empty($ocupadas)): ?>
        <div class="lista-ocupadas-box">
          <strong>Períodos já reservados neste espaço:</strong>
          <ul class="lista">
            <?php foreach ($ocupadas as $o): ?>
              <li><strong><?= dataBR($o['data_evento']) ?></strong> das <?= hora($o['hora_inicio']) ?> às <?= hora($o['hora_fim']) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>

    <div class="bloco-secao">
      <h2>Política de cancelamento e resolução de pendências</h2>
      <ul class="lista-regras">
        <li><strong>Prazo de análise:</strong> Toda solicitação é avaliada pelo administrador em até <strong>2 dias úteis</strong>. Enquanto estiver com status <em>Aguardando aprovação</em>, o espaço ainda não está bloqueado definitivamente.</li>
        <li><strong>Pendências e cancelamento automático:</strong> Caso o administrador identifique alguma pendência, informará o motivo e definirá um prazo para ajuste. Se o prazo expirar sem correção, a solicitação é cancelada automaticamente.</li>
        <li><strong>Edição e cancelamento gratuitos:</strong> Você pode editar os dados ou cancelar sua solicitação a qualquer momento enquanto estiver em análise, ou cancelar gratuitamente até <strong>48 horas antes</strong> do início do evento aprovado.</li>
      </ul>
    </div>

    <div class="bloco-secao caixa-responsavel">
      <h2>Dúvidas sobre este espaço?</h2>
      <p>Fale diretamente com o responsável pelo espaço antes de enviar sua solicitação:</p>
      <p>
        <strong>Responsável:</strong> <?= e($e['responsavel_nome'] ?? 'Coordenação EventSpace') ?><br>
        <strong>Contato direto:</strong> <?= e($e['responsavel_contato'] ?? 'contato@eventspace.com.br · (11) 99999-0000') ?>
      </p>
      <a class="botao secundario pequeno" href="mailto:contato@eventspace.com.br?subject=<?= rawurlencode('Dúvida sobre o espaço ' . $e['nome']) ?>">✉️ Falar com o responsável</a>
    </div>
  </section>

  <aside class="caixa-fixa-reserva" aria-label="Resumo e ação de reserva">
    <img src="<?= e($galeria[0]) ?>" alt="Miniatura de <?= e($e['nome']) ?>" class="sidebar-thumb-espaco">
    <div class="preco-destaque">
      <span class="rotulo">Valor da diária</span>
      <strong><?= moeda($e['preco']) ?><small>/dia</small></strong>
    </div>

    <div class="status-bloco-lateral">
      <?php if (!$e['disponivel']): ?>
        <span class="status-disponibilidade disp-ocupado">● Espaço temporariamente inativo</span>
      <?php elseif ($dataFiltro !== ''): ?>
        <?php if (empty($datasOcupadasMap[$dataFiltro])): ?>
          <span class="status-disponibilidade disp-livre">● Disponível em <?= dataBR($dataFiltro) ?></span>
        <?php else: ?>
          <span class="status-disponibilidade disp-parcial">● Horários parciais em <?= dataBR($dataFiltro) ?> (<?= implode(', ', $datasOcupadasMap[$dataFiltro]) ?> ocupados)</span>
        <?php endif; ?>
      <?php else: ?>
        <span class="status-disponibilidade disp-livre">● Disponível para solicitações</span>
      <?php endif; ?>
    </div>

    <ul class="resumo-curto">
      <li><span>Tipo de espaço:</span> <strong><?= e($e['tipo']) ?></strong></li>
      <li><span>Capacidade máxima:</span> <strong>até <?= (int)$e['capacidade'] ?> pessoas</strong></li>
      <li><span>Cidade/UF:</span> <strong><?= e(($e['cidade'] ?? 'São Paulo') . '/' . ($e['estado'] ?? 'SP')) ?></strong></li>
      <li><span>Prazo de resposta:</span> <strong>até 2 dias úteis</strong></li>
    </ul>

    <?php if ($e['disponivel']): ?>
      <a class="botao largura-total" href="<?= url('reservas/solicitar?' . http_build_query($queryReserva)) ?>">Solicitar reserva</a>
    <?php else: ?>
      <button type="button" class="botao largura-total" disabled>Espaço indisponível</button>
    <?php endif; ?>

    <p class="nota-centro">Você poderá revisar todos os dados antes de enviar.</p>
  </aside>
</div>
