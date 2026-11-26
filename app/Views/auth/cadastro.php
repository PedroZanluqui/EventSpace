<section class="caixa">
  <div class="rotulo">Nova conta de cliente</div>
  <h1 class="menor">Cadastrar</h1>
  <p class="nota">Campos marcados com <span class="obrigatorio">*</span> são obrigatórios.</p>

  <?php if (!empty($erros)): ?>
    <div class="msg erro" role="alert">Corrija os campos destacados em vermelho para concluir seu cadastro.</div>
  <?php endif; ?>

  <form method="post" action="<?= url('cadastro') ?>" novalidate>
    <div class="grupo-campo <?= !empty($erros['nome']) ? 'tem-erro' : '' ?>">
      <label for="cad-nome">Nome completo <span class="obrigatorio">*</span></label>
      <input type="text" id="cad-nome" name="nome" value="<?= e($nome ?? '') ?>" placeholder="Ex.: Maria Oliveira" required>
      <?php if (!empty($erros['nome'])): ?><span class="erro-campo"><?= e($erros['nome']) ?></span><?php endif; ?>
    </div>

    <div class="grupo-campo <?= !empty($erros['email']) ? 'tem-erro' : '' ?>">
      <label for="cad-email">E-mail <span class="obrigatorio">*</span></label>
      <input type="email" id="cad-email" name="email" value="<?= e($email ?? '') ?>" placeholder="seu@email.com" required>
      <?php if (!empty($erros['email'])): ?><span class="erro-campo"><?= e($erros['email']) ?></span><?php endif; ?>
    </div>

    <div class="grupo-campo <?= !empty($erros['senha']) ? 'tem-erro' : '' ?>">
      <label for="cad-senha">
        Senha <span class="obrigatorio">*</span>
        <span class="dica-campo">Mínimo de 6 caracteres</span>
      </label>
      <input type="password" id="cad-senha" name="senha" minlength="6" placeholder="Crie uma senha segura" required>
      <?php if (!empty($erros['senha'])): ?><span class="erro-campo"><?= e($erros['senha']) ?></span><?php endif; ?>
    </div>

    <button type="submit" class="btn-primario" data-loading-text="Criando conta...">Criar conta</button>
  </form>
  <p class="nota">Já possui conta? <a href="<?= url('login') ?>">Entrar</a></p>
</section>
