<?php
require_once __DIR__ . "/../includes/funcoes.php";
exigeLogin("administrador");

$comodidadesSugeridas = [
    "Wi-Fi",
    "Ar-condicionado",
    "Estacionamento",
    "Projetor",
    "Som",
    "Cozinha",
    "Iluminação",
    "Banheiros",
    "Acessibilidade",
    "Área externa",
    "Café e Água"
];

$tiposSugeridos = [
    "Salão de festas",
    "Auditório",
    "Área externa",
    "Sala de reunião",
    "Espaço corporativo",
    "Estúdio / Coworking"
];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["acao_rapida"])) {
    $acaoRapida = textoEntrada($_POST, "acao_rapida");
    $eid = inteiroEntrada($_POST, "espaco_id");

    if ($acaoRapida === "alternar_status" && $eid > 0) {
        $conn->query("UPDATE espacos SET disponivel = 1 - disponivel WHERE id = $eid");
        definirToast(
            "Status de disponibilidade do espaço #$eid atualizado.",
            "sucesso",
            [
                "url" => url("admin/espacos.php"),
                "campos" => ["acao_rapida" => "alternar_status", "espaco_id" => $eid]
            ]
        );
        header("Location: " . url("admin/espacos.php"));
        exit;
    }

    if ($acaoRapida === "excluir" && $eid > 0) {
        $stCheck = $conn->prepare("SELECT COUNT(*) FROM reservas WHERE espaco_id = ?");
        $stCheck->bind_param("i", $eid);
        $stCheck->execute();
        $qtdReservas = (int)$stCheck->get_result()->fetch_row()[0];

        if ($qtdReservas > 0) {
            $conn->query("UPDATE espacos SET disponivel = 0 WHERE id = $eid");
            definirToast(
                "Este espaço possui $qtdReservas solicitação(ões) no histórico e não pode ser apagado definitivamente. Ele foi ARQUIVADO para preservar os registros.",
                "sucesso",
                [
                    "url" => url("admin/espacos.php"),
                    "campos" => ["acao_rapida" => "alternar_status", "espaco_id" => $eid]
                ]
            );
        } else {
            $stDel = $conn->prepare("DELETE FROM espacos WHERE id = ?");
            $stDel->bind_param("i", $eid);
            $stDel->execute();
            definirToast("Espaço #$eid excluído permanentemente do catálogo.");
        }
        header("Location: " . url("admin/espacos.php"));
        exit;
    }
}

$editarId = isset($_POST["id"]) ? inteiroEntrada($_POST, "id") : inteiroEntrada($_GET, "editar");
$modoFormulario = isset($_GET["novo"]) || $editarId > 0 || ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["salvar_espaco"]));

$espacoAtual = [
    "id"                  => 0,
    "nome"                => "",
    "tipo"                => "",
    "descricao"           => "",
    "comodidades"         => "",
    "capacidade"          => "",
    "preco"               => "",
    "disponivel"          => 1,
    "responsavel_nome"    => "Coordenação EventSpace",
    "responsavel_contato" => "contato@eventspace.com.br · (11) 99999-0000"
];

if ($editarId > 0 && $_SERVER["REQUEST_METHOD"] !== "POST") {
    $st = $conn->prepare("SELECT * FROM espacos WHERE id = ?");
    $st->bind_param("i", $editarId);
    $st->execute();
    $encontrado = $st->get_result()->fetch_assoc();
    if ($encontrado) {
        $espacoAtual = $encontrado;
    } else {
        definirToast("Espaço não encontrado.", "erro");
        header("Location: " . url("admin/espacos.php"));
        exit;
    }
}

$erros = [];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["salvar_espaco"])) {
    $espacoAtual["id"]                  = inteiroEntrada($_POST, "id");
    $espacoAtual["nome"]                = textoEntrada($_POST, "nome");
    $espacoAtual["tipo"]                = textoEntrada($_POST, "tipo");
    $espacoAtual["descricao"]           = textoEntrada($_POST, "descricao");
    $espacoAtual["capacidade"]          = inteiroEntrada($_POST, "capacidade");
    $precoBruto                         = str_replace(",", ".", textoEntrada($_POST, "preco"));
    $espacoAtual["preco"]               = is_numeric($precoBruto) ? (float)$precoBruto : -1;
    $espacoAtual["disponivel"]          = isset($_POST["disponivel"]) ? 1 : 0;
    $espacoAtual["responsavel_nome"]    = textoEntrada($_POST, "responsavel_nome") ?: "Coordenação EventSpace";
    $espacoAtual["responsavel_contato"] = textoEntrada($_POST, "responsavel_contato") ?: "contato@eventspace.com.br · (11) 99999-0000";

    $comodidadesMarcadas = isset($_POST["comodidades_check"]) && is_array($_POST["comodidades_check"])
        ? array_map("trim", $_POST["comodidades_check"])
        : [];
    $comodidadesExtras = array_filter(array_map("trim", explode(",", textoEntrada($_POST, "comodidades_extra"))));
    $todasCom = array_values(array_unique(array_merge($comodidadesMarcadas, $comodidadesExtras)));
    $espacoAtual["comodidades"] = implode(", ", $todasCom);

    if ($espacoAtual["nome"] === "") {
        $erros["nome"] = "Informe o nome do espaço (em português e sem abreviações).";
    }
    if ($espacoAtual["tipo"] === "") {
        $erros["tipo"] = "Selecione ou informe o tipo de espaço (ex.: Salão de festas, Auditório).";
    }
    if ($espacoAtual["descricao"] === "") {
        $erros["descricao"] = "Informe uma descrição clara sobre a estrutura do espaço.";
    }
    if ($espacoAtual["capacidade"] < 1) {
        $erros["capacidade"] = "A capacidade máxima deve ser de pelo menos 1 pessoa.";
    }
    if ($espacoAtual["preco"] <= 0) {
        $erros["preco"] = "Informe um valor de diária válido maior que zero (ex.: 2500.00).";
    }
    if ($espacoAtual["comodidades"] === "") {
        $erros["comodidades"] = "Selecione pelo menos uma comodidade oferecida pelo espaço.";
    }

    if (empty($erros)) {
        if ($espacoAtual["id"] > 0) {
            $st = $conn->prepare("UPDATE espacos
                SET nome=?, tipo=?, descricao=?, comodidades=?, capacidade=?, preco=?, disponivel=?, responsavel_nome=?, responsavel_contato=?
                WHERE id=?");
            $st->bind_param(
                "ssssidissi",
                $espacoAtual["nome"],
                $espacoAtual["tipo"],
                $espacoAtual["descricao"],
                $espacoAtual["comodidades"],
                $espacoAtual["capacidade"],
                $espacoAtual["preco"],
                $espacoAtual["disponivel"],
                $espacoAtual["responsavel_nome"],
                $espacoAtual["responsavel_contato"],
                $espacoAtual["id"]
            );
            $st->execute();
            definirToast("Espaço \"" . $espacoAtual["nome"] . "\" atualizado com sucesso!");
        } else {
            $st = $conn->prepare("INSERT INTO espacos
                (nome, tipo, descricao, comodidades, capacidade, preco, disponivel, responsavel_nome, responsavel_contato)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $st->bind_param(
                "ssssidiss",
                $espacoAtual["nome"],
                $espacoAtual["tipo"],
                $espacoAtual["descricao"],
                $espacoAtual["comodidades"],
                $espacoAtual["capacidade"],
                $espacoAtual["preco"],
                $espacoAtual["disponivel"],
                $espacoAtual["responsavel_nome"],
                $espacoAtual["responsavel_contato"]
            );
            $st->execute();
            definirToast("Novo espaço \"" . $espacoAtual["nome"] . "\" cadastrado no catálogo!");
        }
        header("Location: " . url("admin/espacos.php"));
        exit;
    }
}

$comodidadesAtuaisLista = array_filter(array_map("trim", explode(",", (string)$espacoAtual["comodidades"])));
$comodidadesExtrasLista = array_diff($comodidadesAtuaisLista, $comodidadesSugeridas);

$espacos = $conn->query("SELECT e.*,
    (SELECT COUNT(*) FROM reservas r WHERE r.espaco_id = e.id) AS total_reservas
    FROM espacos e ORDER BY e.nome")->fetch_all(MYSQLI_ASSOC);

cabecalho("Gerenciar espaços", "admin_espacos");
?>
<section class="largo">
  <div class="cabecalho-secao">
    <div>
      <div class="rotulo">Administração do catálogo (CRUD)</div>
      <h1 class="menor">Gerenciar espaços e comodidades</h1>
      <p class="nota">Cadastre novos espaços, edite informações, comodidades, preço e capacidade, ou arquive espaços temporariamente indisponíveis.</p>
    </div>
    <div class="acoes-linha">
      <?php if (!$modoFormulario): ?>
        <a class="botao" href="<?= url('admin/espacos.php?novo=1') ?>">+ Cadastrar novo espaço</a>
      <?php endif; ?>
      <a class="botao secundario" href="<?= url('admin/index.php') ?>">← Voltar ao painel</a>
    </div>
  </div>

  <?php if ($modoFormulario): ?>
    <div class="bloco-painel-destaque largo-medio" style="margin-bottom: 36px;">
      <div class="rotulo"><?= $espacoAtual["id"] > 0 ? "Edição de registro #" . (int)$espacoAtual["id"] : "Novo cadastro" ?></div>
      <h2 style="margin-top: 4px;"><?= $espacoAtual["id"] > 0 ? "Editar espaço: " . e($espacoAtual["nome"]) : "Cadastrar novo espaço" ?></h2>
      <p class="nota">Os campos marcados com <span class="obrigatorio">*</span> são obrigatórios. Utilize termos em português e por extenso.</p>

      <?php if (!empty($erros)): ?>
        <div class="msg erro" role="alert">
          <strong>Corrija os campos destacados em vermelho abaixo antes de salvar.</strong>
        </div>
      <?php endif; ?>

      <form method="post" action="<?= url('admin/espacos.php') ?>" novalidate>
        <input type="hidden" name="salvar_espaco" value="1">
        <input type="hidden" name="id" value="<?= (int)$espacoAtual["id"] ?>">

        <div class="linha">
          <div class="grupo-campo <?= !empty($erros['nome']) ? 'tem-erro' : '' ?>">
            <label for="esp-nome">
              Nome do espaço <span class="obrigatorio">*</span>
              <span class="dica-campo">Ex.: Salão Cristal, Auditório Central</span>
            </label>
            <input type="text" id="esp-nome" name="nome" value="<?= e($espacoAtual["nome"]) ?>" placeholder="Nome completo do espaço" required>
            <?php if (!empty($erros["nome"])): ?><span class="erro-campo"><?= e($erros["nome"]) ?></span><?php endif; ?>
          </div>

          <div class="grupo-campo <?= !empty($erros['tipo']) ? 'tem-erro' : '' ?>">
            <label for="esp-tipo">
              Tipo de espaço <span class="obrigatorio">*</span>
              <span class="dica-campo">Categoria física do ambiente</span>
            </label>
            <input type="text" id="esp-tipo" name="tipo" list="lista-tipos-espaco" value="<?= e($espacoAtual["tipo"]) ?>" placeholder="Selecione ou digite o tipo" required>
            <datalist id="lista-tipos-espaco">
              <?php foreach ($tiposSugeridos as $ts): ?>
                <option value="<?= e($ts) ?>">
              <?php endforeach; ?>
            </datalist>
            <?php if (!empty($erros["tipo"])): ?><span class="erro-campo"><?= e($erros["tipo"]) ?></span><?php endif; ?>
          </div>
        </div>

        <div class="linha">
          <div class="grupo-campo <?= !empty($erros['capacidade']) ? 'tem-erro' : '' ?>">
            <label for="esp-cap">
              Capacidade máxima (pessoas) <span class="obrigatorio">*</span>
              <span class="dica-campo">Limite máximo permitido nas solicitações</span>
            </label>
            <input type="number" id="esp-cap" name="capacidade" min="1" value="<?= $espacoAtual["capacidade"] !== "" ? (int)$espacoAtual["capacidade"] : "" ?>" placeholder="Ex.: 150" required>
            <?php if (!empty($erros["capacidade"])): ?><span class="erro-campo"><?= e($erros["capacidade"]) ?></span><?php endif; ?>
          </div>

          <div class="grupo-campo <?= !empty($erros['preco']) ? 'tem-erro' : '' ?>">
            <label for="esp-preco">
              Valor da diária (R$) <span class="obrigatorio">*</span>
              <span class="dica-campo">Preço por dia de locação</span>
            </label>
            <input type="number" id="esp-preco" name="preco" step="0.01" min="1" value="<?= $espacoAtual["preco"] !== "" && $espacoAtual["preco"] > 0 ? e(number_format((float)$espacoAtual["preco"], 2, ".", "")) : "" ?>" placeholder="Ex.: 2500.00" required>
            <?php if (!empty($erros["preco"])): ?><span class="erro-campo"><?= e($erros["preco"]) ?></span><?php endif; ?>
          </div>
        </div>

        <div class="grupo-campo <?= !empty($erros['descricao']) ? 'tem-erro' : '' ?>">
          <label for="esp-desc">
            Descrição detalhada do espaço <span class="obrigatorio">*</span>
            <span class="dica-campo">Descreva o ambiente, diferenciais e estrutura disponível para os clientes.</span>
          </label>
          <textarea id="esp-desc" name="descricao" rows="3" placeholder="Ex.: Salão climatizado com palco, camarim e cozinha de apoio equipada..." required><?= e($espacoAtual["descricao"]) ?></textarea>
          <?php if (!empty($erros["descricao"])): ?><span class="erro-campo"><?= e($erros["descricao"]) ?></span><?php endif; ?>
        </div>

        <div class="grupo-campo <?= !empty($erros['comodidades']) ? 'tem-erro' : '' ?>">
          <label>
            Comodidades oferecidas pelo espaço <span class="obrigatorio">*</span>
            <span class="dica-campo">Selecione os itens inclusos (aparecerão com ícones nos cartões de busca e detalhes).</span>
          </label>
          <div class="grade-checkbox-comodidades">
            <?php foreach ($comodidadesSugeridas as $com):
                $marcado = in_array($com, $comodidadesAtuaisLista);
            ?>
              <label class="item-check-comodidade">
                <input type="checkbox" name="comodidades_check[]" value="<?= e($com) ?>" <?= $marcado ? "checked" : "" ?>>
                <span><?= iconeComodidade($com) ?> <?= e($com) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <label for="esp-com-extra" style="margin-top: 10px;">
            Outras comodidades <span class="opcional">(opcional, separadas por vírgula)</span>
          </label>
          <input type="text" id="esp-com-extra" name="comodidades_extra" value="<?= e(implode(", ", $comodidadesExtrasLista)) ?>" placeholder="Ex.: Gerador próprio, Camarim, Valet">
          <?php if (!empty($erros["comodidades"])): ?><span class="erro-campo"><?= e($erros["comodidades"]) ?></span><?php endif; ?>
        </div>

        <div class="linha">
          <div class="grupo-campo">
            <label for="esp-resp-nome">
              Nome do responsável pelo espaço
              <span class="dica-campo">Exibido para o cliente tirar dúvidas em Detalhes</span>
            </label>
            <input type="text" id="esp-resp-nome" name="responsavel_nome" value="<?= e($espacoAtual["responsavel_nome"]) ?>">
          </div>

          <div class="grupo-campo">
            <label for="esp-resp-contato">
              Contato do responsável (e-mail e telefone)
              <span class="dica-campo">Canal de atendimento direto do espaço</span>
            </label>
            <input type="text" id="esp-resp-contato" name="responsavel_contato" value="<?= e($espacoAtual["responsavel_contato"]) ?>">
          </div>
        </div>

        <div class="grupo-campo">
          <label class="label-checkbox">
            <input type="checkbox" name="disponivel" value="1" <?= $espacoAtual["disponivel"] ? "checked" : "" ?>>
            <span><strong>Espaço ativo e disponível no catálogo público</strong> (desmarque para deixá-lo arquivado/oculto de novas buscas)</span>
          </label>
        </div>

        <div class="barra-acoes-form">
          <button type="submit" class="btn-primario" data-loading-text="Salvando espaço...">
            <?= $espacoAtual["id"] > 0 ? "Salvar alterações do espaço" : "Cadastrar espaço no catálogo" ?>
          </button>
          <a class="botao secundario" href="<?= url('admin/espacos.php') ?>">Cancelar</a>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <div class="bloco-painel-destaque">
    <div class="cabecalho-bloco">
      <h2>Espaços cadastrados no catálogo (<?= count($espacos) ?>)</h2>
      <?php if (!$modoFormulario): ?>
        <a class="botao pequeno" href="<?= url('admin/espacos.php?novo=1') ?>">+ Novo espaço</a>
      <?php endif; ?>
    </div>

    <div class="tabela-responsiva">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Nome e Responsável</th>
            <th>Tipo de espaço</th>
            <th>Comodidades oferecidas</th>
            <th>Capacidade</th>
            <th>Valor da diária</th>
            <th>Status</th>
            <th>Ações (Editar / Arquivar / Excluir)</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($espacos as $s): ?>
          <tr>
            <td><strong>#<?= (int)$s["id"] ?></strong></td>
            <td>
              <strong><a href="<?= url('espacos/detalhes.php?id=' . (int)$s['id']) ?>"><?= e($s["nome"]) ?></a></strong><br>
              <small class="nota">Resp.: <?= e($s["responsavel_nome"] ?? "Coordenação") ?></small>
            </td>
            <td><?= e($s["tipo"]) ?></td>
            <td><?= renderChipsComodidades($s["comodidades"], 4) ?></td>
            <td><?= capacidadeTexto($s["capacidade"]) ?></td>
            <td><strong><?= moeda($s["preco"]) ?></strong><small>/dia</small></td>
            <td>
              <span class="selo-status <?= $s["disponivel"] ? 'status-APROVADA' : 'status-CANCELADA' ?>">
                <?= $s["disponivel"] ? "Ativo" : "Arquivado" ?>
              </span>
            </td>
            <td>
              <div class="acoes-linha">
                <a class="botao secundario pequeno" href="<?= url('admin/espacos.php?editar=' . (int)$s['id']) ?>">Editar</a>

                <form method="post" class="inline" onsubmit="return confirm('Deseja <?= $s['disponivel'] ? 'arquivar' : 'reativar' ?> o espaço <?= e($s['nome']) ?>?')">
                  <input type="hidden" name="acao_rapida" value="alternar_status">
                  <input type="hidden" name="espaco_id" value="<?= (int)$s["id"] ?>">
                  <button type="submit" class="botao secundario pequeno">
                    <?= $s["disponivel"] ? "Arquivar" : "Reativar" ?>
                  </button>
                </form>

                <?php if ((int)$s["total_reservas"] === 0): ?>
                  <form method="post" class="inline" onsubmit="return confirm('Tem certeza de que deseja EXCLUIR permanentemente o espaço <?= e($s['nome']) ?>? Esta ação não poderá ser desfeita.')">
                    <input type="hidden" name="acao_rapida" value="excluir">
                    <input type="hidden" name="espaco_id" value="<?= (int)$s["id"] ?>">
                    <button type="submit" class="botao perigo pequeno">Excluir</button>
                  </form>
                <?php else: ?>
                  <span class="nota" title="Possui <?= (int)$s['total_reservas'] ?> solicitação(ões) vinculada(s); utilize Arquivar para ocultá-lo.">
                    (<?= (int)$s["total_reservas"] ?> solic.)
                  </span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
<?php rodape(); ?>

