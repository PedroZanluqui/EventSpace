<section class="largo">
  <div class="cabecalho-secao">
    <div>
      <div class="rotulo">Acompanhamento de pedidos</div>
      <h1 class="menor">Minhas solicitações</h1>
      <p class="nota">Acompanhe o status de análise, resolva eventuais pendências dentro do prazo ou reserve novamente a partir do seu histórico.</p>
    </div>
    <a class="botao" href="<?= url('espacos') ?>">+ Solicitar reserva</a>
  </div>

  <div class="abas-navegacao" role="tablist">
    <a role="tab" aria-selected="<?= $aba === 'ativas' ? 'true' : 'false' ?>" class="aba-item <?= $aba === 'ativas' ? 'ativa' : '' ?>" href="<?= url('reservas/minhas?aba=ativas') ?>">
      Solicitações em andamento e pendências (<?= count($ativas) ?>)
    </a>
    <a role="tab" aria-selected="<?= $aba === 'historico' ? 'true' : 'false' ?>" class="aba-item <?= $aba === 'historico' ? 'ativa' : '' ?>" href="<?= url('reservas/minhas?aba=historico') ?>">
      Histórico (<?= count($hist) ?>)
    </a>
  </div>

  <?php if ($aba === 'ativas'): ?>
    <?php if (empty($ativas)): ?>
      <div class="estado-vazio-erro">
        <h2>Você não possui solicitações em andamento</h2>
        <p>Explore nosso catálogo de espaços para solicitar uma nova reserva para o seu evento.</p>
        <a class="botao" href="<?= url('espacos') ?>">Buscar espaços disponíveis</a>
      </div>
    <?php else: ?>
      <div class="tabela-responsiva">
        <table>
          <thead>
            <tr>
              <th>Protocolo</th>
              <th>Espaço</th>
              <th>Data e Horário</th>
              <th>Tipo de evento</th>
              <th>Pessoas</th>
              <th>Status, Motivo e Prazo</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($ativas as $r):
              $temPendencia = temPrazoAtivo($r);
          ?>
            <tr class="<?= $temPendencia ? 'linha-pendencia' : '' ?>">
              <td><strong>#<?= (int)$r['id'] ?></strong></td>
              <td>
                <a href="<?= url('espacos/detalhes?id=' . (int)$r['espaco_id']) ?>"><strong><?= e($r['espaco']) ?></strong></a><br>
                <small class="nota"><?= e($r['tipo_espaco']) ?></small>
              </td>
              <td>
                <strong><?= dataBR($r['data_evento']) ?></strong><br>
                <small class="nota"><?= hora($r['hora_inicio']) ?> às <?= hora($r['hora_fim']) ?></small>
              </td>
              <td><?= e($r['tipo_evento']) ?></td>
              <td><?= (int)$r['qtd_pessoas'] ?> pessoas</td>
              <td>
                <?= renderStatusBadge($r['status'], $r['data_atualizacao'], true, $r) ?>
                <?php if (!empty($r['motivo_recusa'])): ?>
                  <div class="caixa-motivo-recusa">
                    <strong><?= $temPendencia ? 'Pendência a resolver:' : 'Motivo anterior informado:' ?></strong>
                    <span><?= e($r['motivo_recusa']) ?></span>
                    <?php if ($temPendencia): ?>
                      <div class="prazo-contador-linha">
                        ⏳ <strong>Prazo limite:</strong> <?= dataHoraBR($r['prazo_resolucao']) ?>
                        <span class="badge-prazo-contador" data-countdown-iso="<?= e(dataHoraIso($r['prazo_resolucao'])) ?>">
                          Restam <?= e(tempoRestanteTexto($r['prazo_resolucao'])) ?>
                        </span>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <div class="acoes-linha">
                  <?php if ($temPendencia): ?>
                    <a class="botao pequeno" href="<?= url('reservas/solicitar?editar=' . (int)$r['id']) ?>">Resolver pendência</a>
                  <?php elseif ($r['status'] === 'PENDENTE'): ?>
                    <a class="botao secundario pequeno" href="<?= url('reservas/solicitar?editar=' . (int)$r['id']) ?>">Editar solicitação</a>
                  <?php endif; ?>
                  <form method="post" action="<?= url('reservas/minhas') ?>" onsubmit="return confirm('Tem certeza de que deseja cancelar a solicitação #<?= (int)$r['id'] ?> para <?= e($r['espaco']) ?>?')">
                    <button type="submit" class="botao perigo pequeno" name="cancelar" value="<?= (int)$r['id'] ?>" data-loading-text="Cancelando...">
                      Cancelar reserva
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <?php if (empty($hist)): ?>
      <div class="estado-vazio-erro">
        <h2>Nenhuma solicitação no histórico</h2>
        <p>Solicitações concluídas, recusadas definitivamente ou canceladas (manual ou automaticamente) aparecerão aqui.</p>
      </div>
    <?php else: ?>
      <div class="tabela-responsiva">
        <table>
          <thead>
            <tr>
              <th>Protocolo</th>
              <th>Espaço</th>
              <th>Data e Horário</th>
              <th>Tipo de evento</th>
              <th>Pessoas</th>
              <th>Status final e Detalhes</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($hist as $r): ?>
            <tr>
              <td>#<?= (int)$r['id'] ?></td>
              <td>
                <a href="<?= url('espacos/detalhes?id=' . (int)$r['espaco_id']) ?>"><strong><?= e($r['espaco']) ?></strong></a><br>
                <small class="nota"><?= e($r['tipo_espaco']) ?></small>
              </td>
              <td>
                <?= dataBR($r['data_evento']) ?><br>
                <small class="nota"><?= hora($r['hora_inicio']) ?> às <?= hora($r['hora_fim']) ?></small>
              </td>
              <td><?= e($r['tipo_evento']) ?></td>
              <td><?= (int)$r['qtd_pessoas'] ?> pessoas</td>
              <td>
                <?= renderStatusBadge($r['status'], $r['data_atualizacao'], true, $r) ?>
                <?php if (!empty($r['motivo_recusa'])): ?>
                  <div class="caixa-motivo-recusa">
                    <strong>Motivo da recusa:</strong> <?= e($r['motivo_recusa']) ?>
                  </div>
                <?php endif; ?>
                <?php if (!empty($r['motivo_cancelamento']) && !empty($r['cancelada_automaticamente'])): ?>
                  <div class="caixa-motivo-recusa aviso-auto">
                    <strong>Cancelamento automático:</strong> <?= e($r['motivo_cancelamento']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <a class="botao secundario pequeno" href="<?= url('reservas/solicitar?repetir=' . (int)$r['id']) ?>">
                  ↻ Reservar novamente
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</section>
