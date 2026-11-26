<?php

if (!empty($resolvendoPendencia) && !empty($reservaExistente)): ?>
  <div class="caixa-pendencia-banner" role="region" aria-label="Pendência informada pelo administrador">
    <div class="rotulo">Ação necessária — Resolução de pendência #<?= (int)$editarId ?></div>
    <h2>Ajuste os dados e reenvie antes que o prazo expire</h2>
    <p><strong>Motivo informado pelo administrador:</strong> <?= e($reservaExistente['motivo_recusa'] ?: 'Ajuste os dados da solicitação.') ?></p>
    <p class="prazo-contador-linha">
      ⏳ <strong>Prazo limite:</strong> <?= dataHoraBR($reservaExistente['prazo_resolucao']) ?>
      <span class="badge-prazo-contador" data-countdown-iso="<?= e(dataHoraIso($reservaExistente['prazo_resolucao'])) ?>">
        Restam <?= e(tempoRestanteTexto($reservaExistente['prazo_resolucao'])) ?>
      </span>
      — se não for reenviada a tempo, esta solicitação será cancelada automaticamente.
    </p>
  </div>
<?php endif; ?>

<nav class="barra-progresso-etapas" aria-label="Progresso da solicitação">
  <?php if ($etapa === 'revisar'): ?>
    <form method="post" action="<?= url('reservas/solicitar') ?>" class="inline-zero">
      <input type="hidden" name="etapa" value="voltar_edicao">
      <input type="hidden" name="espaco_id" value="<?= (int)$e['id'] ?>">
      <input type="hidden" name="editar_id" value="<?= (int)$editarId ?>">
      <input type="hidden" name="data" value="<?= e($d['data']) ?>">
      <input type="hidden" name="ini" value="<?= e($d['ini']) ?>">
      <input type="hidden" name="fim" value="<?= e($d['fim']) ?>">
      <input type="hidden" name="tipo" value="<?= e($d['tipo']) ?>">
      <input type="hidden" name="pessoas" value="<?= (int)$d['pessoas'] ?>">
      <input type="hidden" name="obs" value="<?= e($d['obs']) ?>">
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
    <?php if ($etapa === 'revisar'): ?>
      <div class="rotulo">Etapa 02 de 03 — Conferência final</div>
      <h1 class="menor"><?= !empty($resolvendoPendencia) ? 'Revise e reenvie sua solicitação' : 'Revise sua solicitação' ?></h1>
      <p class="nota">Confira atentamente os dados abaixo antes de enviar sua solicitação para análise.</p>

      <?php if (!empty($errosCampos['aceite'])): ?>
        <div class="msg erro" role="alert"><?= e($errosCampos['aceite']) ?></div>
      <?php endif; ?>

      <dl class="dados">
        <dt>Espaço escolhido</dt><dd><strong><?= e($e['nome']) ?></strong> (<?= e($e['tipo']) ?>)</dd>
        <dt>Localização</dt><dd><?= e(enderecoCurto($e)) ?></dd>
        <dt>Data do evento</dt><dd><?= dataBR($d['data']) ?></dd>
        <dt>Horário</dt><dd>Das <?= e($d['ini']) ?> às <?= e($d['fim']) ?></dd>
        <dt>Tipo de evento</dt><dd><?= e($d['tipo']) ?></dd>
        <dt>Quantidade de pessoas</dt><dd><?= (int)$d['pessoas'] ?> pessoas (limite do espaço: até <?= (int)$e['capacidade'] ?> pessoas)</dd>
        <dt>Observações</dt><dd><?= $d['obs'] !== '' ? e($d['obs']) : 'Nenhuma observação informada' ?></dd>
        <dt>Valor da diária</dt><dd><strong><?= moeda($e['preco']) ?>/dia</strong></dd>
        <dt>Prazo de resposta</dt><dd>Análise pelo administrador em até <strong>2 dias úteis</strong></dd>
      </dl>

      <form method="post" action="<?= url('reservas/solicitar') ?>" class="form-revisao">
        <input type="hidden" name="etapa" value="confirmar">
        <input type="hidden" name="espaco_id" value="<?= (int)$e['id'] ?>">
        <input type="hidden" name="editar_id" value="<?= (int)$editarId ?>">
        <input type="hidden" name="data" value="<?= e($d['data']) ?>">
        <input type="hidden" name="ini" value="<?= e($d['ini']) ?>">
        <input type="hidden" name="fim" value="<?= e($d['fim']) ?>">
        <input type="hidden" name="tipo" value="<?= e($d['tipo']) ?>">
        <input type="hidden" name="pessoas" value="<?= (int)$d['pessoas'] ?>">
        <input type="hidden" name="obs" value="<?= e($d['obs']) ?>">

        <div class="caixa-politica <?= !empty($errosCampos['aceite']) ? 'campo-invalido-box' : '' ?>">
          <h3>Política de aprovação e cancelamento</h3>
          <p>
            1. Ao enviar, seu pedido fica com status <strong>Aguardando aprovação</strong> e passa pela análise do administrador em até <strong>2 dias úteis</strong>.<br>
            2. Caso o administrador aponte pendências com prazo, será necessário ajustar e reenviar dentro do período limite para evitar o cancelamento automático.<br>
            3. Após aprovada, o cancelamento gratuito pode ser feito até <strong>48 horas antes</strong> da data do evento.
          </p>
          <label class="label-checkbox">
            <input type="checkbox" name="aceite_politica" value="1" required <?= $d['aceite'] ? 'checked' : '' ?>>
            <span>Li e concordo com a política de cancelamento e estou ciente de que a solicitação depende de aprovação do administrador. <span class="obrigatorio">*</span></span>
          </label>
          <?php if (!empty($errosCampos['aceite'])): ?>
            <span class="erro-campo"><?= e($errosCampos['aceite']) ?></span>
          <?php endif; ?>
        </div>

        <div class="barra-acoes-form">
          <button type="submit" class="btn-primario" data-loading-text="Enviando solicitação...">
            <?= !empty($resolvendoPendencia) ? 'Reenviar solicitação corrigida' : ($editarId > 0 ? 'Salvar alterações da solicitação' : 'Enviar solicitação de reserva') ?>
          </button>
          <button type="submit" name="etapa" value="voltar_edicao" class="botao secundario" formnovalidate>
            ← Voltar e editar dados
          </button>
          <a class="link-cancelar" href="<?= url('espacos/detalhes?id=' . (int)$e['id']) ?>" onclick="return confirm('Deseja cancelar o preenchimento desta solicitação?')">Cancelar</a>
        </div>
      </form>

    <?php else: ?>
      <div class="rotulo">Etapa 01 de 03 — Preenchimento</div>
      <h1 class="menor">
        <?php if (!empty($resolvendoPendencia)): ?>
          Resolver pendência #<?= (int)$editarId ?>
        <?php elseif ($editarId > 0): ?>
          Editar solicitação #<?= (int)$editarId ?>
        <?php else: ?>
          Solicitar reserva
        <?php endif; ?>
      </h1>
      <p class="nota">Os campos marcados com <span class="obrigatorio">*</span> são obrigatórios. Após enviar, sua solicitação será analisada em até <strong>2 dias úteis</strong>.</p>

      <?php if ($conflitoHorario): ?>
        <div class="caixa-indisponivel" role="alert">
          <strong>⚠️ Espaço indisponível nesse período</strong>
          <p><?= e($errosCampos['conflito']) ?></p>
          <?php if (!empty($datasSugeridas)): ?>
            <p><strong>Sugestão de datas livres próximas para <?= e($e['nome']) ?>:</strong></p>
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

      <form method="post" action="<?= url('reservas/solicitar') ?>" id="form-solicitar" novalidate>
        <input type="hidden" name="etapa" value="revisar">
        <input type="hidden" name="espaco_id" value="<?= (int)$e['id'] ?>">
        <input type="hidden" name="editar_id" value="<?= (int)$editarId ?>">

        <div class="grupo-campo <?= !empty($errosCampos['data']) || !empty($errosCampos['conflito']) ? 'tem-erro' : '' ?>">
          <label for="campo-data">
            Data do evento <span class="obrigatorio">*</span>
            <span class="dica-campo">Selecione uma data disponível no calendário (bloqueado para datas passadas).</span>
          </label>
          <input type="date" id="campo-data" name="data" min="<?= date('Y-m-d') ?>" value="<?= e($d['data']) ?>" required>
          <?php if (!empty($errosCampos['data'])): ?>
            <span class="erro-campo"><?= e($errosCampos['data']) ?></span>
          <?php endif; ?>
        </div>

        <div class="linha">
          <div class="grupo-campo <?= !empty($errosCampos['ini']) ? 'tem-erro' : '' ?>">
            <label for="campo-ini">
              Horário de início <span class="obrigatorio">*</span>
              <span class="dica-campo">Início da utilização do espaço</span>
            </label>
            <input type="time" id="campo-ini" name="ini" value="<?= e($d['ini']) ?>" required>
            <?php if (!empty($errosCampos['ini'])): ?>
              <span class="erro-campo"><?= e($errosCampos['ini']) ?></span>
            <?php endif; ?>
          </div>

          <div class="grupo-campo <?= !empty($errosCampos['fim']) ? 'tem-erro' : '' ?>">
            <label for="campo-fim">
              Horário de término <span class="obrigatorio">*</span>
              <span class="dica-campo">Deve ser posterior ao horário de início</span>
            </label>
            <input type="time" id="campo-fim" name="fim" value="<?= e($d['fim']) ?>" required>
            <?php if (!empty($errosCampos['fim'])): ?>
              <span class="erro-campo"><?= e($errosCampos['fim']) ?></span>
            <?php endif; ?>
          </div>
        </div>

        <div class="linha">
          <div class="grupo-campo <?= !empty($errosCampos['tipo']) ? 'tem-erro' : '' ?>">
            <label for="campo-tipo">
              Tipo de evento <span class="obrigatorio">*</span>
              <span class="dica-campo">Natureza do seu evento (diferente do tipo de espaço)</span>
            </label>
            <input type="text" id="campo-tipo" name="tipo" list="lista-tipos-evento" value="<?= e($d['tipo']) ?>" placeholder="Selecione ou digite (ex.: Casamento)" required>
            <datalist id="lista-tipos-evento">
              <?php foreach ($tiposEventoSugeridos as $sug): ?>
                <option value="<?= e($sug) ?>">
              <?php endforeach; ?>
            </datalist>
            <?php if (!empty($errosCampos['tipo'])): ?>
              <span class="erro-campo"><?= e($errosCampos['tipo']) ?></span>
            <?php endif; ?>
          </div>

          <div class="grupo-campo <?= !empty($errosCampos['pessoas']) ? 'tem-erro' : '' ?>">
            <label for="campo-pessoas">
              Quantidade de pessoas <span class="obrigatorio">*</span>
              <span class="dica-campo">Máximo permitido neste espaço: <strong><?= (int)$e['capacidade'] ?> pessoas</strong></span>
            </label>
            <input type="number" id="campo-pessoas" name="pessoas" min="1" max="<?= (int)$e['capacidade'] ?>" data-max-cap="<?= (int)$e['capacidade'] ?>" value="<?= $d['pessoas'] ?: '' ?>" placeholder="1 a <?= (int)$e['capacidade'] ?>" required>
            <span id="erro-pessoas-js" class="erro-campo <?= empty($errosCampos['pessoas']) ? 'oculto' : '' ?>">
              <?= e($errosCampos['pessoas'] ?? '') ?>
            </span>
          </div>
        </div>

        <div class="grupo-campo">
          <label for="campo-obs">
            Observações e necessidades adicionais <span class="opcional">(opcional)</span>
            <span class="dica-campo">Informe se precisará de disposição especial de mesas, equipamentos extras ou responda ao apontamento do administrador.</span>
          </label>
          <textarea id="campo-obs" name="obs" rows="3" placeholder="Ex.: Montagem da decoração 1 hora antes do início..."><?= e($d['obs']) ?></textarea>
        </div>

        <div class="barra-acoes-form">
          <button type="submit" class="btn-primario" data-loading-text="Validando dados...">Revisar solicitação →</button>
          <a class="botao secundario" href="<?= url('espacos/detalhes?id=' . (int)$e['id']) ?>">← Voltar ao espaço</a>
          <a class="link-cancelar" href="<?= url('espacos') ?>">Cancelar</a>
        </div>
      </form>
    <?php endif; ?>
  </section>

  <aside class="caixa-fixa-reserva" aria-label="Resumo fixo do espaço selecionado">
    <img src="<?= e(imagemEspaco($e)) ?>" alt="Foto de <?= e($e['nome']) ?>" class="sidebar-thumb-espaco">
    <div class="rotulo">Resumo do espaço</div>
    <strong class="titulo-resumo-fixo"><?= e($e['nome']) ?></strong>
    <span class="tag-tipo"><?= e($e['tipo']) ?></span>
    <p class="card-localizacao" style="margin-top: 8px;">📍 <?= e(enderecoCurto($e)) ?></p>

    <div class="preco-destaque compacto">
      <strong><?= moeda($e['preco']) ?><small>/dia</small></strong>
    </div>

    <ul class="resumo-curto">
      <li><span>Capacidade:</span> <strong>até <?= (int)$e['capacidade'] ?> pessoas</strong></li>
      <li><span>Prazo de análise:</span> <strong>até 2 dias úteis</strong></li>
    </ul>

    <?= renderChipsComodidades($e['comodidades'], 4) ?>

    <div class="mini-calendario-lateral">
      <strong>Disponibilidade nos próximos 10 dias</strong>
      <span class="nota">Clique em um dia para preencher a data:</span>
      <div class="grade-mini-lateral">
        <?php for ($i = 0; $i < 10; $i++):
            $diaIso = date('Y-m-d', strtotime("+$i days"));
            $diaBr  = date('d/m', strtotime($diaIso));
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
