<?php
require_once __DIR__ . "/../includes/funcoes.php";
$erros = [];
$nome = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome  = textoEntrada($_POST, "nome");
    $email = textoEntrada($_POST, "email");
    $senha = textoEntrada($_POST, "senha");

    if ($nome === "") {
        $erros["nome"] = "Informe seu nome completo.";
    }
    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros["email"] = "Informe um endereço de e-mail válido (ex.: nome@dominio.com).";
    }
    if (strlen($senha) < 6) {
        $erros["senha"] = "A senha deve ter pelo menos 6 caracteres.";
    }

    if (empty($erros)) {
        $st = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $st->bind_param("s", $email);
        $st->execute();
        if ($st->get_result()->num_rows > 0) {
            $erros["email"] = "Já existe uma conta cadastrada com este e-mail. Tente fazer login.";
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $st = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, 'cliente')");
            $st->bind_param("sss", $nome, $email, $hash);
            $st->execute();
            $_SESSION["usuario"] = ["id" => $conn->insert_id, "nome" => $nome, "tipo" => "cliente"];
            definirToast("Conta criada com sucesso! Bem-vindo(a), $nome.");
            header("Location: " . url("index.php"));
            exit;
        }
    }
}
cabecalho("Criar conta", "cadastrar");
?>
<section class="caixa">
  <div class="rotulo">Nova conta de cliente</div>
  <h1 class="menor">Cadastrar</h1>
  <p class="nota">Campos marcados com <span class="obrigatorio">*</span> são obrigatórios.</p>

  <?php if (!empty($erros)): ?>
    <div class="msg erro" role="alert">Corrija os campos destacados em vermelho para concluir seu cadastro.</div>
  <?php endif; ?>

  <form method="post" novalidate>
    <div class="grupo-campo <?= !empty($erros['nome']) ? 'tem-erro' : '' ?>">
      <label for="cad-nome">Nome completo <span class="obrigatorio">*</span></label>
      <input type="text" id="cad-nome" name="nome" value="<?= e($nome) ?>" placeholder="Ex.: Maria Oliveira" required>
      <?php if (!empty($erros["nome"])): ?><span class="erro-campo"><?= e($erros["nome"]) ?></span><?php endif; ?>
    </div>

    <div class="grupo-campo <?= !empty($erros['email']) ? 'tem-erro' : '' ?>">
      <label for="cad-email">E-mail <span class="obrigatorio">*</span></label>
      <input type="email" id="cad-email" name="email" value="<?= e($email) ?>" placeholder="seu@email.com" required>
      <?php if (!empty($erros["email"])): ?><span class="erro-campo"><?= e($erros["email"]) ?></span><?php endif; ?>
    </div>

    <div class="grupo-campo <?= !empty($erros['senha']) ? 'tem-erro' : '' ?>">
      <label for="cad-senha">
        Senha <span class="obrigatorio">*</span>
        <span class="dica-campo">Mínimo de 6 caracteres</span>
      </label>
      <input type="password" id="cad-senha" name="senha" minlength="6" placeholder="Crie uma senha segura" required>
      <?php if (!empty($erros["senha"])): ?><span class="erro-campo"><?= e($erros["senha"]) ?></span><?php endif; ?>
    </div>

    <button type="submit" class="btn-primario" data-loading-text="Criando conta...">Criar conta</button>
  </form>
  <p class="nota">Já possui conta? <a href="<?= url('auth/login.php') ?>">Entrar</a></p>
</section>
<?php rodape(); ?>
