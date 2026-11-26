<nav class="barra-progresso-etapas" aria-label="Progresso da solicitação">
  <div class="passo-barra concluido"><span class="num-passo">✓</span> <span>01. Dados do evento</span></div>
  <div class="passo-barra concluido"><span class="num-passo">✓</span> <span>02. Revisão e política</span></div>
  <div class="passo-barra atual"><span class="num-passo">03</span> <span>03. Solicitação enviada</span></div>
</nav>

<section class="caixa largo-medio">
  <div class="rotulo">Etapa 03 de 03 — Confirmação do protocolo</div>
  <h1 class="menor">
    <?php if (!empty($reenviado)): ?>
      Pendências resolvidas e solicitação reenviada!
    <?php elseif (!empty($editado)): ?>
      Solicitação atualizada com sucesso!
    <?php else: ?>
      Solicitação enviada com sucesso!
    <?php endif; ?>
  </h1>

  <div class="msg">
    <strong>O que acontece agora?</strong><br>
    Sua solicitação está com o status <strong>Aguardando aprovação</strong> e será analisada pelo administrador em até <strong>2 dias úteis</strong>.
    Você receberá uma notificação no topo do sistema assim que ela for aprovada ou recusada.
  </div>

  <dl class="dados">
    <dt>Protocolo</dt><dd><strong>#<?= (int)$r['id'] ?></strong></dd>
    <dt>Status atual</dt><dd><?= renderStatusBadge($r['status'], $r['data_atualizacao'], true, $r) ?></dd>
    <dt>Espaço e Tipo</dt><dd><?= e($r['espaco']) ?> (<?= e($r['tipo_espaco']) ?>)</dd>
    <dt>Data e Horário</dt><dd><?= dataBR($r['data_evento']) ?>, das <?= hora($r['hora_inicio']) ?> às <?= hora($r['hora_fim']) ?></dd>
    <dt>Tipo de evento</dt><dd><?= e($r['tipo_evento']) ?></dd>
    <dt>Quantidade de pessoas</dt><dd><?= (int)$r['qtd_pessoas'] ?> pessoas</dd>
    <dt>Valor estimado</dt><dd><?= moeda($r['preco']) ?>/dia</dd>
    <dt>Responsável pelo espaço</dt><dd><?= e($r['responsavel_nome']) ?> · <?= e($r['responsavel_contato']) ?></dd>
  </dl>

  <div class="barra-acoes-form">
    <a class="botao" href="<?= url('reservas/minhas') ?>">Acompanhar em Minhas solicitações</a>
    <a class="botao secundario" href="<?= url('reservas/solicitar?editar=' . (int)$r['id']) ?>">Editar esta solicitação</a>
  </div>
</section>
