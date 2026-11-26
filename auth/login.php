<?php
/** 
 * Cliente:        joao@email.com / 123456
 * Administrador:  admin@eventspace.com / admin123
 */
require_once __DIR__ . "/../includes/funcoes.php";
$erro = "";
$email = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = textoEntrada($_POST, "email");
    $senha = textoEntrada($_POST, "senha");
    if ($email === "" || $senha === "") {
        $erro = "Informe seu e-mail e sua senha para entrar.";
    } else {
        $st = $conn->prepare("SELECT id, nome, senha, tipo FROM usuarios WHERE email = ?");
        $st->bind_param("s", $email);
        $st->execute();
        $u = $st->get_result()->fetch_assoc();
        if ($u && password_verify($senha, $u["senha"])) {
            $_SESSION["usuario"] = ["id" => $u["id"], "nome" => $u["nome"], "tipo" => $u["tipo"]];
            definirToast("Bem-vindo(a), " . $u["nome"] . "!");
            header("Location: " . ($u["tipo"] === "administrador" ? url("admin/index.php") : url("index.php")));
            exit;
        }
        $erro = "E-mail ou senha incorretos. Verifique os dados digitados e tente novamente.";
    }
}
cabecalho("Entrar na conta", "entrar");
?>
<section class="caixa">
  <div class="rotulo">Acesso ao sistema</div>
  <h1 class="menor">Entrar</h1>
  <p class="nota">Campos marcados com <span class="obrigatorio">*</span> são obrigatórios.</p>

  <?php if ($erro): ?><div class="msg erro" role="alert"><?= e($erro) ?></div><?php endif; ?>

  <form method="post" novalidate>
    <div class="grupo-campo">
      <label for="login-email">E-mail <span class="obrigatorio">*</span></label>
      <input type="email" id="login-email" name="email" value="<?= e($email) ?>" placeholder="seu@email.com" required>
    </div>
    <div class="grupo-campo">
      <label for="login-senha">Senha <span class="obrigatorio">*</span></label>
      <input type="password" id="login-senha" name="senha" placeholder="Digite sua senha" required>
    </div>
    <button type="submit" class="btn-primario" data-loading-text="Autenticando...">Entrar</button>
  </form>

  <p class="nota">Ainda não possui conta? <a href="<?= url('auth/cadastro.php') ?>">Cadastre-se gratuitamente</a></p>
</section>
<?php rodape(); ?>
