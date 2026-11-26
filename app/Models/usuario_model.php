<?php
declare(strict_types=1);

function validarCadastroUsuario(string $nome, string $email, string $senha): array
{
    $erros = [];
    if ($nome === '') {
        $erros['nome'] = 'Informe seu nome completo.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erros['email'] = 'Informe um endereço de e-mail válido (ex.: nome@dominio.com).';
    }
    if (strlen($senha) < 6) {
        $erros['senha'] = 'A senha deve ter pelo menos 6 caracteres.';
    }
    return $erros;
}

function buscarUsuarioPorEmail(mysqli $conn, string $email): ?array
{
    $st = $conn->prepare('SELECT id, nome, email, senha, tipo FROM usuarios WHERE email = ? LIMIT 1');
    $st->bind_param('s', $email);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    return $row ?: null;
}

function usuarioEmailExiste(mysqli $conn, string $email): bool
{
    $st = $conn->prepare('SELECT 1 FROM usuarios WHERE email = ? LIMIT 1');
    $st->bind_param('s', $email);
    $st->execute();
    return $st->get_result()->num_rows > 0;
}

function criarUsuario(mysqli $conn, string $nome, string $email, string $senhaHash, string $tipo = 'cliente'): int
{
    $st = $conn->prepare('INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)');
    $st->bind_param('ssss', $nome, $email, $senhaHash, $tipo);
    $st->execute();
    return (int)$conn->insert_id;
}
