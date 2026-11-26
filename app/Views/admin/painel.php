<section class="largo">
  <div class="cabecalho-secao">
    <div>
      <div class="rotulo">Gestão e aprovação de reservas</div>
      <h1 class="menor">Painel administrativo</h1>
      <p class="nota">Priorize a análise das solicitações pendentes, acompanhe clientes resolvendo pendências e confira a agenda confirmada.</p>
    </div>
    <div class="acoes-linha">
      <a class="botao" href="<?= url('admin/espacos?novo=1') ?>">+ Cadastrar novo espaço</a>
      <a class="botao secundario" href="<?= url('admin/espacos') ?>">Gerenciar espaços</a>
    </div>
  </div>

  <details class="caixa-criterios-admin" style="margin-bottom: 18px;">
    <summary>📋 Orientações e critérios para aprovação e pendências</summary>
    <div class="conteudo-criterios">
      <ul>
        <li><strong>1. Verificação de conflito:</strong> O sistema sinaliza automaticamente em vermelho caso duas solicitações disputem o mesmo horário.</li>
        <li><strong>2. Motivo da recusa e prazo de resolução:</strong> Ao recusar, informe sempre o motivo. Você pode conceder um prazo (ex.: 48 horas) para o cliente resolver pendências; se ele não corrigir dentro do período, a solicitação é cancelada automaticamente.</li>
        <li><strong>3. Reenvios identificados:</strong> Solicitações ajustadas e reenviadas pelo cliente chegam com o selo <em>🔄 Reenviada</em> para sua conferência prioritária.</li>
      </ul>
    </div>
  </details>

  <form class="busca busca-admin" method="get" action="<?= url('admin') ?>">
    <label>Filtrar por espaço
      <select name="espaco_id">
        <option value="0">Todos os espaços</option>
        <?php foreach ($espacos as $esp): ?>
          <option value="<?= (int)$esp['id'] ?>" <?= $filtroEspaco === (int)$esp['id'] ? 'selected' : '' ?>>
            <?= e($esp['nome']) ?> (<?= e($esp['tipo']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Filtrar por data do evento
      <input type="date" name="data" value="<?= e($filtroData) ?>">
    </label>
    <div class="acoes-filtro-admin">
      <button type="submit" class="btn-primario pequeno" data-loading-text="Filtrando...">Filtrar painel</button>
      <?php if ($filtroEspaco > 0 || $filtroData !== ''): ?>
        <a class="botao secundario pequeno" href="<?= url('admin') ?>">Limpar filtros</a>
      <?php endif; ?>
    </div>
  </form>

  <div class="bloco-painel-destaque">
    <div class="cabecalho-bloco">
      <h2>Solicitações aguardando aprovação (<?= count($pendentes) ?>)</h2>
      <?php if (!empty($pendentes)): ?>
        <div class="acoes-lote-topo">
          <button type="submit" form="form-lote" name="acao_lote" value="aprovar_lote" class="botao pequeno" onclick="return confirm('Aprovar todas as solicitações selecionadas que não possuam conflito?')">
            ✓ Aprovar selecionadas
          </button>
          <button type="button" id="btn-recusar-lote" class="botao perigo pequeno">
            ✕ Recusar selecionadas
          </button>
        </div>
      <?php endif; ?>
    </div>

    <?php if (empty($pendentes)): ?>
      <p class="vazio">Nenhuma solicitação aguardando aprovação com os filtros atuais.</p>
    <?php else: ?>
      <form id="form-lote" method="post" action="<?= url('admin') ?>">
        <input type="hidden" name="acao_lote" id="lote-acao-hidden" value="">
        <input type="hidden" name="motivo_recusa" id="lote-motivo-hidden" value="">
        <input type="hidden" name="conceder_prazo" id="lote-conceder-prazo-hidden" value="1">
        <input type="hidden" name="prazo_resolucao" id="lote-prazo-hidden" value="">
      </form>
      <div class="tabela-responsiva">
        <table>
          <thead>
            <tr>
              <th style="width: 36px;">
                <input type="checkbox" id="selecionar-todas" title="Selecionar todas as solicitações">
              </th>
              <th>Protocolo</th>
              <th>Espaço</th>
              <th>Data e Horário</th>
              <th>Cliente</th>
              <th>Tipo de evento</th>
              <th>Pessoas</th>
              <th>Conflito</th>
              <th>Ações de decisão</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($pendentes as $r):
              $conflitoAprovada = !empty($r['tem_conflito']);
          ?>
            <tr class="<?= $conflitoAprovada ? 'linha-conflito' : '' ?>">
              <td>
                <input type="checkbox" name="ids_selecionados[]" value="<?= (int)$r['id'] ?>" form="form-lote" class="check-item-lote">
              </td>
              <td>
                <strong>#<?= (int)$r['id'] ?></strong>
                <?php if (!empty($r['reenviada'])): ?>
                  <br><span class="tag-reenviada">🔄 Reenviada</span>
                <?php endif; ?>
              </td>
              <td>
                <strong><?= e($r['espaco']) ?></strong><br>
                <small class="nota"><?= e($r['tipo_espaco']) ?></small>
              </td>
              <td>
                <strong><?= dataBR($r['data_evento']) ?></strong><br>
                <small class="nota"><?= hora($r['hora_inicio']) ?> às <?= hora($r['hora_fim']) ?></small>
              </td>
              <td>
                <?= e($r['cliente']) ?><br>
                <small class="nota"><?= e($r['cliente_email']) ?></small>
              </td>
              <td>
                <?= e($r['tipo_evento']) ?>
                <?php if (!empty($r['reenviada']) && !empty($r['motivo_recusa'])): ?>
                  <div class="nota-pendencia-anterior" title="Pendência anterior resolvida pelo cliente">
                    <strong>Pendência anterior:</strong> <?= e($r['motivo_recusa']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td><?= (int)$r['qtd_pessoas'] ?> / <?= (int)$r['capacidade'] ?></td>
              <td>
                <?php if ($conflitoAprovada): ?>
                  <span class="alerta-conflito-tag" title="Já existe uma reserva aprovada neste horário">⚠️ Conflito de agenda</span>
                <?php else: ?>
                  <span class="ok-conflito-tag">✓ Horário livre</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="acoes-linha">
                  <button type="button" class="botao secundario pequeno btn-abrir-detalhe-reserva"
                    data-id="<?= (int)$r['id'] ?>"
                    data-espaco="<?= e($r['espaco'] . ' (' . $r['tipo_espaco'] . ')') ?>"
                    data-cliente="<?= e($r['cliente'] . ' (' . $r['cliente_email'] . ')') ?>"
                    data-data="<?= dataBR($r['data_evento']) ?>"
                    data-horario="<?= hora($r['hora_inicio']) ?> às <?= hora($r['hora_fim']) ?>"
                    data-evento="<?= e($r['tipo_evento']) ?>"
                    data-pessoas="<?= (int)$r['qtd_pessoas'] ?> pessoas (capacidade até <?= (int)$r['capacidade'] ?>)"
                    data-obs="<?= e($r['observacoes'] ?: 'Nenhuma observação informada') ?>"
                    data-status="<?= e($r['status']) ?>"
                    data-motivo="<?= e($r['motivo_recusa'] ?? '') ?>"
                    data-prazo="<?= !empty($r['prazo_resolucao']) ? e(dataHoraBR($r['prazo_resolucao'])) : '' ?>"
                    data-reenviada="<?= !empty($r['reenviada']) ? '1' : '0' ?>">
                    Ver detalhes
                  </button>

                  <form method="post" action="<?= url('admin') ?>" class="inline" onsubmit="return confirm('Confirmar APROVAÇÃO da solicitação #<?= (int)$r['id'] ?> para <?= e($r['cliente']) ?>?')">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button type="submit" class="botao pequeno" name="acao" value="aprovar" <?= $conflitoAprovada ? 'disabled title="Há conflito com outra reserva aprovada"' : '' ?> data-loading-text="Aprovando...">
                      Aprovar
                    </button>
                  </form>

                  <button type="button"
                    class="botao perigo pequeno btn-abrir-recusa"
                    data-id="<?= (int)$r['id'] ?>"
                    data-espaco="<?= e($r['espaco']) ?>"
                    data-cliente="<?= e($r['cliente']) ?>">
                    Recusar
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="bloco-painel-destaque">
    <h2>Aguardando resolução de pendências pelo cliente (<?= count($aguardandoAjuste) ?>)</h2>
    <p class="nota">Solicitações recusadas com prazo ativo. Se o cliente não corrigir e reenviar antes do vencimento, serão canceladas automaticamente.</p>
    <?php if (empty($aguardandoAjuste)): ?>
      <p class="vazio">Nenhuma solicitação aguardando correção de pendência no momento.</p>
    <?php else: ?>
      <div class="tabela-responsiva">
        <table>
          <thead>
            <tr>
              <th>Protocolo</th>
              <th>Espaço / Evento</th>
              <th>Cliente</th>
              <th>Motivo informado</th>
              <th>Prazo limite</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($aguardandoAjuste as $r): ?>
            <tr class="linha-pendencia">
              <td><strong>#<?= (int)$r['id'] ?></strong></td>
              <td>
                <strong><?= e($r['espaco']) ?></strong><br>
                <small class="nota"><?= dataBR($r['data_evento']) ?> · <?= hora($r['hora_inicio']) ?>–<?= hora($r['hora_fim']) ?></small>
              </td>
              <td>
                <?= e($r['cliente']) ?><br>
                <small class="nota"><?= e($r['cliente_email']) ?></small>
              </td>
              <td><?= e($r['motivo_recusa'] ?? '—') ?></td>
              <td>
                <strong><?= dataHoraBR($r['prazo_resolucao']) ?></strong><br>
                <span class="badge-prazo-contador" data-countdown-iso="<?= e(dataHoraIso($r['prazo_resolucao'])) ?>">
                  Restam <?= e(tempoRestanteTexto($r['prazo_resolucao'])) ?>
                </span>
              </td>
              <td>
                <div class="acoes-linha">
                  <button type="button" class="botao secundario pequeno btn-abrir-detalhe-reserva"
                    data-id="<?= (int)$r['id'] ?>"
                    data-espaco="<?= e($r['espaco'] . ' (' . $r['tipo_espaco'] . ')') ?>"
                    data-cliente="<?= e($r['cliente'] . ' (' . $r['cliente_email'] . ')') ?>"
                    data-data="<?= dataBR($r['data_evento']) ?>"
                    data-horario="<?= hora($r['hora_inicio']) ?> às <?= hora($r['hora_fim']) ?>"
                    data-evento="<?= e($r['tipo_evento']) ?>"
                    data-pessoas="<?= (int)$r['qtd_pessoas'] ?> pessoas (capacidade até <?= (int)$r['capacidade'] ?>)"
                    data-obs="<?= e($r['observacoes'] ?: 'Nenhuma observação informada') ?>"
                    data-status="<?= e($r['status']) ?>"
                    data-motivo="<?= e($r['motivo_recusa'] ?? '') ?>"
                    data-prazo="<?= !empty($r['prazo_resolucao']) ? e(dataHoraBR($r['prazo_resolucao'])) : '' ?>"
                    data-reenviada="0">
                    Ver detalhes
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="bloco-painel-destaque">
    <h2>Agenda de eventos aprovados (<?= count($agenda) ?>)</h2>
    <p class="nota">Clique em qualquer evento da agenda para inspecionar os detalhes completos da solicitação ou alterar a decisão caso necessário.</p>
    <?php if (empty($agenda)): ?>
      <p class="vazio">Nenhum evento aprovado agendado para o filtro selecionado.</p>
    <?php else: ?>
      <div class="tabela-responsiva">
        <table>
          <thead>
            <tr>
              <th>Data</th>
              <th>Horário</th>
              <th>Espaço</th>
              <th>Cliente</th>
              <th>Tipo de evento</th>
              <th>Quantidade de pessoas</th>
              <th>Ação</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($agenda as $r): ?>
            <tr class="linha-agenda-clicavel">
              <td><strong><?= dataBR($r['data_evento']) ?></strong></td>
              <td><?= hora($r['hora_inicio']) ?> às <?= hora($r['hora_fim']) ?></td>
              <td><?= e($r['espaco']) ?></td>
              <td><?= e($r['cliente']) ?></td>
              <td><?= e($r['tipo_evento']) ?></td>
              <td><?= (int)$r['qtd_pessoas'] ?> pessoas</td>
              <td>
                <button type="button" class="botao secundario pequeno btn-abrir-detalhe-reserva"
                  data-id="<?= (int)$r['id'] ?>"
                  data-espaco="<?= e($r['espaco'] . ' (' . $r['tipo_espaco'] . ')') ?>"
                  data-cliente="<?= e($r['cliente'] . ' (' . $r['cliente_email'] . ')') ?>"
                  data-data="<?= dataBR($r['data_evento']) ?>"
                  data-horario="<?= hora($r['hora_inicio']) ?> às <?= hora($r['hora_fim']) ?>"
                  data-evento="<?= e($r['tipo_evento']) ?>"
                  data-pessoas="<?= (int)$r['qtd_pessoas'] ?> pessoas (capacidade até <?= (int)$r['capacidade'] ?>)"
                  data-obs="<?= e($r['observacoes'] ?: 'Nenhuma observação informada') ?>"
                  data-status="<?= e($r['status']) ?>"
                  data-motivo=""
                  data-prazo=""
                  data-reenviada="0">
                  Abrir solicitação #<?= (int)$r['id'] ?>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <details class="bloco-secundario-admin" style="margin-bottom: 22px;">
    <summary><strong>Histórico recente de recusas e cancelamentos automáticos (<?= count($historicoRecente) ?>)</strong></summary>
    <div class="tabela-responsiva" style="margin-top: 12px;">
      <?php if (empty($historicoRecente)): ?>
        <p class="vazio">Nenhum registro recente de recusa ou cancelamento.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Protocolo</th>
              <th>Espaço / Data</th>
              <th>Cliente</th>
              <th>Status final</th>
              <th>Motivo registrado</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($historicoRecente as $r): ?>
            <tr>
              <td>#<?= (int)$r['id'] ?></td>
              <td><strong><?= e($r['espaco']) ?></strong><br><small class="nota"><?= dataBR($r['data_evento']) ?></small></td>
              <td><?= e($r['cliente']) ?></td>
              <td><?= renderStatusBadge($r['status'], $r['data_atualizacao'], true, $r) ?></td>
              <td>
                <?php if (!empty($r['motivo_recusa'])): ?>
                  <div><strong>Recusa:</strong> <?= e($r['motivo_recusa']) ?></div>
                <?php endif; ?>
                <?php if (!empty($r['motivo_cancelamento'])): ?>
                  <div class="nota"><?= e($r['motivo_cancelamento']) ?></div>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </details>

  <details class="bloco-secundario-admin" open>
    <summary><strong>Gerenciar catálogo de espaços e comodidades (<?= count($espacos) ?> espaços)</strong></summary>
    <div class="cabecalho-bloco" style="margin-top: 14px;">
      <span class="nota">Cadastre novos espaços, gerencie fotos pela API, localização no Google Maps ou altere a disponibilidade.</span>
      <div class="acoes-linha">
        <a class="botao pequeno" href="<?= url('admin/espacos?novo=1') ?>">+ Cadastrar novo espaço</a>
        <a class="botao secundario pequeno" href="<?= url('admin/espacos') ?>">Ver gerenciamento completo</a>
      </div>
    </div>
    <div class="tabela-responsiva" style="margin-top: 12px;">
      <table>
        <thead>
          <tr>
            <th>Espaço</th>
            <th>Tipo / Localização</th>
            <th>Comodidades oferecidas</th>
            <th>Capacidade</th>
            <th>Preço da diária</th>
            <th>Status</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($espacos as $s): ?>
          <tr>
            <td>
              <div class="celula-espaco-mini">
                <img src="<?= e(imagemEspaco($s)) ?>" alt="<?= e($s['nome']) ?>" class="thumb-tabela-espaco">
                <strong><?= e($s['nome']) ?></strong>
              </div>
            </td>
            <td>
              <?= e($s['tipo']) ?><br>
              <small class="nota">📍 <?= e(($s['cidade'] ?? 'São Paulo') . '/' . ($s['estado'] ?? 'SP')) ?></small>
            </td>
            <td><?= renderChipsComodidades($s['comodidades'], 3) ?></td>
            <td><?= capacidadeTexto($s['capacidade']) ?></td>
            <td><?= moeda($s['preco']) ?>/dia</td>
            <td>
              <span class="selo-status <?= $s['disponivel'] ? 'status-APROVADA' : 'status-CANCELADA' ?>">
                <?= $s['disponivel'] ? 'Ativo no catálogo' : 'Arquivado (Inativo)' ?>
              </span>
            </td>
            <td>
              <div class="acoes-linha">
                <a class="botao secundario pequeno" href="<?= url('admin/espacos?editar=' . (int)$s['id']) ?>">Editar</a>
                <form method="post" action="<?= url('admin') ?>" class="inline" onsubmit="return confirm('Deseja <?= $s['disponivel'] ? 'arquivar (ocultar de novas buscas)' : 'reativar' ?> o espaço <?= e($s['nome']) ?>?')">
                  <button type="submit" class="botao secundario pequeno" name="alternar_espaco" value="<?= (int)$s['id'] ?>">
                    <?= $s['disponivel'] ? 'Arquivar' : 'Reativar' ?>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </details>
</section>

<div id="modal-detalhe-reserva" class="modal-fundo oculto" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-res">
  <div class="modal-caixa">
    <div class="modal-cabecalho">
      <h2 id="titulo-modal-res">Detalhes da solicitação</h2>
      <button type="button" class="fechar-modal" id="btn-fechar-modal-res" aria-label="Fechar janela">×</button>
    </div>
    <dl class="dados" id="corpo-modal-res"></dl>
    <div class="barra-acoes-form" id="acoes-modal-res"></div>
  </div>
</div>

<div id="modal-recusa-reserva" class="modal-fundo oculto" role="dialog" aria-modal="true" aria-labelledby="titulo-modal-recusa">
  <div class="modal-caixa">
    <div class="modal-cabecalho">
      <h2 id="titulo-modal-recusa">Recusar solicitação</h2>
      <button type="button" class="fechar-modal" id="btn-fechar-modal-recusa" aria-label="Fechar janela">×</button>
    </div>

    <p class="nota" id="subtitulo-modal-recusa">Informe o motivo da recusa e defina se o cliente terá um prazo para resolver pendências.</p>

    <form id="form-recusa-reserva" method="post" action="<?= url('admin') ?>">
      <input type="hidden" name="acao" value="rejeitar" id="recusa-acao">
      <input type="hidden" name="id" value="" id="recusa-id">

      <div class="grupo-campo">
        <label for="recusa-motivo">
          Motivo da recusa / Pendência <span class="obrigatorio">*</span>
          <span class="dica-campo">O cliente verá esta mensagem na notificação e em Minhas solicitações.</span>
        </label>
        <div class="chips-motivo-rapido">
          <button type="button" class="chip-atalho-motivo" data-motivo="Ajuste o horário de término ou confirme os detalhes de montagem nas observações.">Horário / montagem</button>
          <button type="button" class="chip-atalho-motivo" data-motivo="Informe a quantidade exata de convidados compatível com o formato do espaço.">Capacidade / layout</button>
          <button type="button" class="chip-atalho-motivo" data-motivo="O espaço já possui outro evento próximo; escolha uma nova data disponível.">Conflito de agenda</button>
          <button type="button" class="chip-atalho-motivo" data-motivo="Detalhe os equipamentos necessários nas observações para prosseguir com a aprovação.">Equipamentos / apoio</button>
        </div>
        <textarea id="recusa-motivo" name="motivo_recusa" rows="3" placeholder="Descreva claramente o motivo ou o que o cliente precisa ajustar..." required></textarea>
      </div>

      <div class="caixa-opcoes-prazo">
        <label class="label-checkbox">
          <input type="radio" name="conceder_prazo" value="1" checked id="radio-com-prazo">
          <span><strong>Conceder prazo para o cliente resolver pendências</strong> (cancela automaticamente se não corrigir a tempo)</span>
        </label>
        <label class="label-checkbox" style="margin-top: 8px;">
          <input type="radio" name="conceder_prazo" value="0" id="radio-sem-prazo">
          <span><strong>Recusa definitiva</strong> (sem prazo para correção)</span>
        </label>

        <div id="grupo-prazo-resolucao" style="margin-top: 14px;">
          <label for="recusa-prazo">
            Prazo limite para resolver pendências
            <span class="dica-campo">Após essa data e hora, o sistema cancela a solicitação automaticamente.</span>
          </label>
          <div class="chips-prazo-rapido">
            <button type="button" class="chip-atalho-prazo" data-horas="1">+ 1 hora</button>
            <button type="button" class="chip-atalho-prazo" data-horas="24">+ 24 horas</button>
            <button type="button" class="chip-atalho-prazo" data-horas="48">+ 48 horas</button>
            <button type="button" class="chip-atalho-prazo" data-horas="72">+ 72 horas</button>
            <button type="button" class="chip-atalho-prazo" data-horas="168">+ 7 dias</button>
          </div>
          <input type="datetime-local" id="recusa-prazo" name="prazo_resolucao" value="<?= e($prazoPadraoIso) ?>" required>
        </div>
      </div>

      <div class="barra-acoes-form">
        <button type="submit" class="botao perigo" id="btn-confirmar-recusa" data-loading-text="Registrando recusa...">Confirmar recusa</button>
        <button type="button" class="botao secundario" id="btn-cancelar-modal-recusa">Voltar</button>
      </div>
    </form>
  </div>
</div>
