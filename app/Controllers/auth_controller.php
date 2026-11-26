<?php
declare(strict_types=1);

function authLogin(mysqli $conn): void
{
    $uLogado = usuarioLogado();
    if ($uLogado) {
        redirecionar(($uLogado['tipo'] ?? '') === 'administrador' ? 'admin' : '');
    }

    $erro  = '';
    $email = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = textoEntrada($_POST, 'email');
        $senha = textoEntrada($_POST, 'senha');

        if ($email === '' || $senha === '') {
            $erro = 'Informe seu e-mail e sua senha para entrar.';
        } else {
            $u = buscarUsuarioPorEmail($conn, $email);
            if ($u && password_verify($senha, (string)$u['senha'])) {
                $_SESSION['usuario'] = [
                    'id'   => (int)$u['id'],
                    'nome' => (string)$u['nome'],
                    'tipo' => (string)$u['tipo'],
                ];
                definirToast('Bem-vindo(a), ' . $u['nome'] . '!');
                redirecionar($u['tipo'] === 'administrador' ? 'admin' : '');
            }
            $erro = 'E-mail ou senha incorretos. Verifique os dados digitados e tente novamente.';
        }
    }

    renderizarView($conn, 'auth/login', [
        'erro'  => $erro,
        'email' => $email,
    ], 'Entrar na conta', 'entrar');
}

function authCadastro(mysqli $conn): void
{
    if (usuarioLogado()) {
        redirecionar('');
    }

    $erros = [];
    $nome  = '';
    $email = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nome  = textoEntrada($_POST, 'nome');
        $email = textoEntrada($_POST, 'email');
        $senha = textoEntrada($_POST, 'senha');

        $erros = validarCadastroUsuario($nome, $email, $senha);

        if (empty($erros)) {
            if (usuarioEmailExiste($conn, $email)) {
                $erros['email'] = 'Já existe uma conta cadastrada com este e-mail. Tente fazer login.';
            } else {
                $hash   = password_hash($senha, PASSWORD_DEFAULT);
                $novoId = criarUsuario($conn, $nome, $email, $hash, 'cliente');
                $_SESSION['usuario'] = [
                    'id'   => $novoId,
                    'nome' => $nome,
                    'tipo' => 'cliente',
                ];
                definirToast("Conta criada com sucesso! Bem-vindo(a), $nome.");
                redirecionar('');
            }
        }
    }

    renderizarView($conn, 'auth/cadastro', [
        'erros' => $erros,
        'nome'  => $nome,
        'email' => $email,
    ], 'Criar conta', 'cadastrar');
}

function authLogout(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    redirecionar('login');
}
