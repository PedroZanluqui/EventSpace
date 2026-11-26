<?php
declare(strict_types=1);

$conn = new mysqli('localhost', 'root', '', 'eventspace');
if ($conn->connect_error) {
    die('Erro na conexão com o banco de dados: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

$offset = date('P');
$stTz = $conn->prepare('SET time_zone = ?');
if ($stTz) {
    $stTz->bind_param('s', $offset);
    $stTz->execute();
    $stTz->close();
}

function garantirEsquema(mysqli $conn): void
{
    static $verificado = false;
    if ($verificado) {
        return;
    }
    $verificado = true;

    $colsEsp = [];
    $res = $conn->query('SHOW COLUMNS FROM espacos');
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $colsEsp[$row['Field']] = true;
        }
        $alterEsp = [
            'responsavel_nome'    => "ALTER TABLE espacos ADD COLUMN responsavel_nome VARCHAR(100) DEFAULT 'Coordenação EventSpace'",
            'responsavel_contato' => "ALTER TABLE espacos ADD COLUMN responsavel_contato VARCHAR(100) DEFAULT 'contato@eventspace.com.br · (11) 99999-0000'",
            'imagem_url'          => 'ALTER TABLE espacos ADD COLUMN imagem_url VARCHAR(500) NULL DEFAULT NULL',
            'imagem_credito'      => 'ALTER TABLE espacos ADD COLUMN imagem_credito VARCHAR(255) NULL DEFAULT NULL',
            'galeria_imagens'     => 'ALTER TABLE espacos ADD COLUMN galeria_imagens TEXT NULL DEFAULT NULL',
            'endereco'            => 'ALTER TABLE espacos ADD COLUMN endereco VARCHAR(255) NULL DEFAULT NULL',
            'cidade'              => "ALTER TABLE espacos ADD COLUMN cidade VARCHAR(100) NOT NULL DEFAULT 'São Paulo'",
            'estado'              => "ALTER TABLE espacos ADD COLUMN estado VARCHAR(2) NOT NULL DEFAULT 'SP'",
            'cep'                 => 'ALTER TABLE espacos ADD COLUMN cep VARCHAR(15) NULL DEFAULT NULL',
            'latitude'            => 'ALTER TABLE espacos ADD COLUMN latitude DECIMAL(10,7) NULL DEFAULT NULL',
            'longitude'           => 'ALTER TABLE espacos ADD COLUMN longitude DECIMAL(10,7) NULL DEFAULT NULL',
        ];
        foreach ($alterEsp as $col => $sqlAlter) {
            if (!isset($colsEsp[$col])) {
                $conn->query($sqlAlter);
            }
        }
    }

    $conn->query("UPDATE espacos SET nome = 'Grande Salão Imperial' WHERE nome = 'Grand Hall'");
    $conn->query("UPDATE espacos SET nome = 'Jardim Eventos' WHERE nome = 'Garden Eventos'");
    $conn->query("UPDATE espacos SET nome = 'Sala Executiva Central' WHERE nome = 'Meeting Room'");

    $locPadrao = [
        ['Espaço Premium', 'Av. Paulista, 1578 - Bela Vista', 'São Paulo', 'SP', '01310-200', -23.5614140, -46.6558819],
        ['Grande Salão Imperial', 'Av. Brigadeiro Faria Lima, 3477 - Itaim Bibi', 'São Paulo', 'SP', '04538-133', -23.5868430, -46.6820340],
        ['Jardim Eventos', 'Av. Pedro Álvares Cabral, s/n - Vila Mariana', 'São Paulo', 'SP', '04094-050', -23.5874162, -46.6576336],
        ['Sala Executiva Central', 'Rua Augusta, 1939 - Cerqueira César', 'São Paulo', 'SP', '01413-000', -23.5600580, -46.6626140],
    ];
    $stLoc = $conn->prepare(
        "UPDATE espacos
         SET endereco = ?, cidade = ?, estado = ?, cep = ?, latitude = ?, longitude = ?
         WHERE nome = ? AND (endereco IS NULL OR endereco = '')"
    );
    if ($stLoc) {
        foreach ($locPadrao as $loc) {
            $stLoc->bind_param('ssssdds', $loc[1], $loc[2], $loc[3], $loc[4], $loc[5], $loc[6], $loc[0]);
            $stLoc->execute();
        }
        $stLoc->close();
    }

    $colsRes = [];
    $resR = $conn->query('SHOW COLUMNS FROM reservas');
    if ($resR) {
        while ($row = $resR->fetch_assoc()) {
            $colsRes[$row['Field']] = true;
        }
        $alterRes = [
            'data_atualizacao'          => 'ALTER TABLE reservas ADD COLUMN data_atualizacao DATETIME NULL DEFAULT NULL',
            'notificado'                => 'ALTER TABLE reservas ADD COLUMN notificado TINYINT(1) NOT NULL DEFAULT 1',
            'motivo_recusa'             => 'ALTER TABLE reservas ADD COLUMN motivo_recusa TEXT NULL DEFAULT NULL',
            'prazo_resolucao'           => 'ALTER TABLE reservas ADD COLUMN prazo_resolucao DATETIME NULL DEFAULT NULL',
            'cancelada_automaticamente' => 'ALTER TABLE reservas ADD COLUMN cancelada_automaticamente TINYINT(1) NOT NULL DEFAULT 0',
            'motivo_cancelamento'       => 'ALTER TABLE reservas ADD COLUMN motivo_cancelamento TEXT NULL DEFAULT NULL',
            'reenviada'                 => 'ALTER TABLE reservas ADD COLUMN reenviada TINYINT(1) NOT NULL DEFAULT 0',
        ];
        foreach ($alterRes as $col => $sqlAlter) {
            if (!isset($colsRes[$col])) {
                $conn->query($sqlAlter);
            }
        }
    }

    $conn->query(
        'CREATE TABLE IF NOT EXISTS favoritos (
            usuario_id INT NOT NULL,
            espaco_id INT NOT NULL,
            PRIMARY KEY (usuario_id, espaco_id)
        )'
    );
}

garantirEsquema($conn);
