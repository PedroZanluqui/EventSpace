<?php
declare(strict_types=1);

function homeIndex(mysqli $conn): void
{
    $u = usuarioLogado();
    if ($u && ($u['tipo'] ?? '') === 'administrador') {
        redirecionar('admin');
    }

    $tipos        = listarTiposEspacos($conn);
    $destaques    = listarEspacosDestaque($conn, 3);
    $favIds       = idsFavoritosUsuario($conn, $u);
    $buscasSalvas = $_SESSION['buscas_salvas'] ?? [];

    renderizarView($conn, 'home/index', [
        'tipos'        => $tipos,
        'destaques'    => $destaques,
        'favIds'       => $favIds,
        'buscasSalvas' => $buscasSalvas,
    ], 'Início', 'inicio');
}

function ajudaIndex(mysqli $conn): void
{
    renderizarView($conn, 'ajuda/index', [], 'Central de Ajuda e Documentação', 'ajuda');
}

function favoritosIndex(mysqli $conn): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        favoritosAlternar($conn);
        return;
    }

    $favIds = idsFavoritosUsuario($conn, usuarioLogado());
    $lista  = buscarEspacosPorIds($conn, $favIds);

    renderizarView($conn, 'favoritos/index', [
        'lista' => $lista,
    ], 'Meus espaços favoritos', 'favoritos');
}

function favoritosAlternar(mysqli $conn): void
{
    $eid = inteiroEntrada($_POST, 'espaco_id');
    if ($eid > 0) {
        $adicionado = alternarFavoritoUsuario($conn, usuarioLogado(), $eid);
        definirToast(
            $adicionado
                ? 'Espaço salvo na sua lista de Favoritos!'
                : 'Espaço removido dos Favoritos.'
        );
    }

    $retorno = textoEntrada($_POST, 'retorno');
    if ($retorno !== '' && str_starts_with($retorno, '/') && !str_starts_with($retorno, '//')) {
        redirecionar($retorno);
    }
    redirecionar('favoritos');
}
