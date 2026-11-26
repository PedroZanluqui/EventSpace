<section class="caixa">
  <div class="rotulo">Acesso ao sistema</div>
  <h1 class="menor">Entrar</h1>
  <p class="nota">Campos marcados com <span class="obrigatorio">*</span> são obrigatórios.</p>

  <?php if (!empty($erro)): ?><div class="msg erro" role="alert"><?= e($erro) ?></div><?php endif; ?>

  <form method="post" action="<?= url('login') ?>" novalidate>
    <div class="grupo-campo">
      <label for="login-email">E-mail <span class="obrigatorio">*</span></label>
      <input type="email" id="login-email" name="email" value="<?= e($email ?? '') ?>" placeholder="seu@email.com" required>
    </div>
    <div class="grupo-campo">
      <label for="login-senha">Senha <span class="obrigatorio">*</span></label>
      <input type="password" id="login-senha" name="senha" placeholder="Digite sua senha" required>
    </div>
    <button type="submit" class="btn-primario" data-loading-text="Autenticando...">Entrar</button>
  </form>

  <p class="nota">Ainda não possui conta? <a href="<?= url('cadastro') ?>">Cadastre-se gratuitamente</a></p>
</section>
