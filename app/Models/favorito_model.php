<?php
declare(strict_types=1);

function idsFavoritosUsuario(mysqli $conn, ?array $usuario): array
{
    if ($usuario && !empty($usuario['id'])) {
        $uid = (int)$usuario['id'];
        $st = $conn->prepare('SELECT espaco_id FROM favoritos WHERE usuario_id = ?');
        $st->bind_param('i', $uid);
        $st->execute();
        $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        return array_map(static fn(array $r): int => (int)$r['espaco_id'], $rows);
    }

    $favs = $_SESSION['favoritos_visitante'] ?? [];
    return array_values(array_unique(array_map('intval', is_array($favs) ? $favs : [])));
}

function alternarFavoritoUsuario(mysqli $conn, ?array $usuario, int $espacoId): bool
{
    if ($espacoId <= 0) {
        return false;
    }

    if ($usuario && !empty($usuario['id'])) {
        $uid = (int)$usuario['id'];
        $st = $conn->prepare('SELECT 1 FROM favoritos WHERE usuario_id = ? AND espaco_id = ? LIMIT 1');
        $st->bind_param('ii', $uid, $espacoId);
        $st->execute();
        if ($st->get_result()->num_rows > 0) {
            $del = $conn->prepare('DELETE FROM favoritos WHERE usuario_id = ? AND espaco_id = ?');
            $del->bind_param('ii', $uid, $espacoId);
            $del->execute();
            return false;
        }
        $ins = $conn->prepare('INSERT IGNORE INTO favoritos (usuario_id, espaco_id) VALUES (?, ?)');
        $ins->bind_param('ii', $uid, $espacoId);
        $ins->execute();
        return true;
    }

    $favs = $_SESSION['favoritos_visitante'] ?? [];
    if (in_array($espacoId, $favs, true)) {
        $_SESSION['favoritos_visitante'] = array_values(array_diff($favs, [$espacoId]));
        return false;
    }
    $favs[] = $espacoId;
    $_SESSION['favoritos_visitante'] = array_values(array_unique(array_map('intval', $favs)));
    return true;
}
