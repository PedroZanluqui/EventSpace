<?php

$u = $usuarioAtual ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>EventSpace — <?= e($titulo ?? 'Espaços para eventos') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
  <script src="<?= asset('js/app.js') ?>" defer></script>
</head>
<body data-base-url="<?= e(baseUrl()) ?>">
  <header class="topo">
    <a class="logo" href="<?= url('') ?>">EventSpace</a>
    <nav aria-label="Navegação principal">
      <?php if ($u && ($u['tipo'] ?? '') === 'administrador'): ?>
        <a href="<?= url('admin') ?>" class="<?= ($itemMenuAtivo ?? '') === 'admin' ? 'ativo' : '' ?>">Painel administrativo</a>
        <a href="<?= url('admin/espacos') ?>" class="<?= ($itemMenuAtivo ?? '') === 'admin_espacos' ? 'ativo' : '' ?>">Gerenciar espaços</a>
        <a href="<?= url('espacos') ?>" class="<?= ($itemMenuAtivo ?? '') === 'espacos' ? 'ativo' : '' ?>">Catálogo público</a>
        <a href="<?= url('ajuda') ?>" class="<?= ($itemMenuAtivo ?? '') === 'ajuda' ? 'ativo' : '' ?>">Ajuda</a>
      <?php else: ?>
        <a href="<?= url('') ?>" class="<?= ($itemMenuAtivo ?? '') === 'inicio' ? 'ativo' : '' ?>">Início</a>
        <a href="<?= url('espacos') ?>" class="<?= ($itemMenuAtivo ?? '') === 'espacos' ? 'ativo' : '' ?>">Espaços</a>
        <?php if ($u): ?>
          <a href="<?= url('reservas/minhas') ?>" class="<?= ($itemMenuAtivo ?? '') === 'solicitudes' ? 'ativo' : '' ?>">
            Minhas solicitações
            <?php if (!empty($notificacoes)): ?>
              <span class="contador-alerta" title="Atualizações nas suas solicitações"><?= count($notificacoes) ?></span>
            <?php endif; ?>
          </a>
        <?php endif; ?>
        <a href="<?= url('favoritos') ?>" class="<?= ($itemMenuAtivo ?? '') === 'favoritos' ? 'ativo' : '' ?>">
          Favoritos<?php if (($qtdFavs ?? 0) > 0): ?> (<?= (int)$qtdFavs ?>)<?php endif; ?>
        </a>
        <a href="<?= url('ajuda') ?>" class="<?= ($itemMenuAtivo ?? '') === 'ajuda' ? 'ativo' : '' ?>">Ajuda</a>
      <?php endif; ?>

      <div class="area-usuario">
        <?php if ($u): ?>
          <span class="identificacao-usuario">
            <span class="perfil-tag"><?= ($u['tipo'] ?? '') === 'administrador' ? 'Administrador' : 'Cliente' ?></span>
            <strong><?= e($u['nome']) ?></strong>
          </span>
          <a class="link-sair" href="<?= url('logout') ?>">Sair</a>
        <?php else: ?>
          <a href="<?= url('login') ?>" class="<?= ($itemMenuAtivo ?? '') === 'entrar' ? 'ativo' : '' ?>">Entrar</a>
          <a href="<?= url('cadastro') ?>" class="botao-menu <?= ($itemMenuAtivo ?? '') === 'cadastrar' ? 'ativo' : '' ?>">Cadastrar</a>
        <?php endif; ?>
      </div>
    </nav>
  </header>

  <?php if (!empty($_SESSION['toast_msg'])):
      $t = $_SESSION['toast_msg'];
      unset($_SESSION['toast_msg']);
  ?>
    <div class="toast-flutuante toast-<?= e($t['tipo']) ?>" role="status" aria-live="polite">
      <div class="toast-conteudo">
        <span><?= e($t['texto']) ?></span>
        <?php if (!empty($t['desfazer'])): ?>
          <form method="post" action="<?= e($t['desfazer']['url']) ?>" class="form-desfazer">
            <?php foreach ($t['desfazer']['campos'] as $k => $v): ?>
              <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
            <?php endforeach; ?>
            <button type="submit" class="btn-desfazer">Desfazer ação</button>
          </form>
        <?php endif; ?>
      </div>
      <button type="button" class="fechar-toast" onclick="this.parentElement.remove()" aria-label="Fechar aviso">×</button>
    </div>
  <?php endif; ?>

  <?php if (!empty($notificacoes)): ?>
    <section class="painel-notificacoes" aria-label="Notificações de solicitações">
      <?php foreach ($notificacoes as $n):
          $temPendencia = ($n['status'] === 'REJEITADA' && temPrazoAtivo($n));
          $ehAutoCancel = ($n['status'] === 'CANCELADA' && !empty($n['cancelada_automaticamente']));
          $classeBorda = $temPendencia ? 'PENDENCIA' : ($ehAutoCancel ? 'CANCELADA' : $n['status']);
      ?>
        <div class="alerta-notificacao status-borda-<?= e($classeBorda) ?>">
          <div class="alerta-notificacao-corpo">
            <?php if ($n['status'] === 'APROVADA'): ?>
              <strong>🎉 Sua solicitação foi aprovada!</strong>
              <p>
                O pedido <strong>#<?= (int)$n['id'] ?></strong> para <strong><?= e($n['espaco']) ?></strong>
                (evento em <?= dataBR($n['data_evento']) ?>) foi confirmado
                <?php if ($n['data_atualizacao']): ?>em <?= dataHoraBR($n['data_atualizacao']) ?><?php endif; ?>.
              </p>
            <?php elseif ($temPendencia): ?>
              <strong>⏳ Pendência na sua solicitação — resolva dentro do prazo</strong>
              <p>
                O pedido <strong>#<?= (int)$n['id'] ?></strong> para <strong><?= e($n['espaco']) ?></strong>
                (evento em <?= dataBR($n['data_evento']) ?>) precisa de ajustes para continuar.
              </p>
              <?php if (!empty($n['motivo_recusa'])): ?>
                <div class="notificacao-motivo">
                  <strong>Motivo informado:</strong> <?= e($n['motivo_recusa']) ?>
                </div>
              <?php endif; ?>
              <p class="notificacao-prazo">
                <strong>Prazo limite:</strong> <?= dataHoraBR($n['prazo_resolucao']) ?>
                <span class="badge-prazo-contador" data-countdown-iso="<?= e(dataHoraIso($n['prazo_resolucao'])) ?>">
                  Restam <?= e(tempoRestanteTexto($n['prazo_resolucao'])) ?>
                </span>
                — se não for ajustada a tempo, será cancelada automaticamente.
              </p>
            <?php elseif ($ehAutoCancel): ?>
              <strong>⏰ Solicitação cancelada automaticamente</strong>
              <p>
                O pedido <strong>#<?= (int)$n['id'] ?></strong> para <strong><?= e($n['espaco']) ?></strong>
                (evento em <?= dataBR($n['data_evento']) ?>) expirou porque o prazo para resolver pendências terminou.
              </p>
              <?php if (!empty($n['motivo_cancelamento'])): ?>
                <div class="notificacao-motivo"><?= e($n['motivo_cancelamento']) ?></div>
              <?php endif; ?>
            <?php else: ?>
              <strong>⚠️ Solicitação recusada</strong>
              <p>
                O pedido <strong>#<?= (int)$n['id'] ?></strong> para <strong><?= e($n['espaco']) ?></strong>
                (evento em <?= dataBR($n['data_evento']) ?>) foi recusado
                <?php if ($n['data_atualizacao']): ?>em <?= dataHoraBR($n['data_atualizacao']) ?><?php endif; ?>.
              </p>
              <?php if (!empty($n['motivo_recusa'])): ?>
                <div class="notificacao-motivo">
                  <strong>Motivo da recusa:</strong> <?= e($n['motivo_recusa']) ?>
                </div>
              <?php endif; ?>
            <?php endif; ?>
          </div>

          <div class="acoes-notificacao">
            <?php if ($temPendencia): ?>
              <a class="botao pequeno" href="<?= url('reservas/solicitar?editar=' . (int)$n['id'] . '&dispensar_notif=' . (int)$n['id']) ?>">Resolver pendência agora</a>
            <?php elseif ($ehAutoCancel): ?>
              <a class="botao pequeno" href="<?= url('reservas/minhas?aba=historico&dispensar_notif=' . (int)$n['id']) ?>">Ver histórico</a>
            <?php else: ?>
              <a class="botao pequeno" href="<?= url('reservas/minhas?dispensar_notif=' . (int)$n['id']) ?>">Ver solicitação</a>
            <?php endif; ?>
            <a class="botao pequeno secundario" href="?dispensar_notif=<?= (int)$n['id'] ?>">Marcar como lida</a>
          </div>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <main class="pagina">
    <?= $conteudo ?? '' ?>
  </main>

  <footer class="rodape">
    <div class="rodape-interno">
      <div>
        <strong>EventSpace</strong>
        <p class="nota">Plataforma de solicitação e gestão de espaços para eventos.</p>
      </div>
      <div class="rodape-links">
        <a href="<?= url('') ?>">Início</a>
        <a href="<?= url('espacos') ?>">Espaços</a>
        <a href="<?= url('favoritos') ?>">Favoritos</a>
        <a href="<?= url('ajuda') ?>">Ajuda e Política de Cancelamento</a>
      </div>
    </div>
  </footer>
</body>
</html>
