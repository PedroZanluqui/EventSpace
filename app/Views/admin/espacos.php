<section class="largo">
  <div class="cabecalho-secao">
    <div>
      <div class="rotulo">Administração do catálogo (CRUD)</div>
      <h1 class="menor">Gerenciar espaços e comodidades</h1>
      <p class="nota">Cadastre novos espaços, busque fotos pela API de Fotos, defina localização no Google Maps ou arquive espaços indisponíveis.</p>
    </div>
    <div class="acoes-linha">
      <?php if (!$modoFormulario): ?>
        <a class="botao" href="<?= url('admin/espacos?novo=1') ?>">+ Cadastrar novo espaço</a>
      <?php endif; ?>
      <a class="botao secundario" href="<?= url('admin') ?>">← Voltar ao painel</a>
    </div>
  </div>

  <?php if ($modoFormulario): ?>
    <div class="bloco-painel-destaque largo-medio" style="margin-bottom: 36px;">
      <div class="rotulo"><?= (int)$espacoAtual['id'] > 0 ? 'Edição de registro #' . (int)$espacoAtual['id'] : 'Novo cadastro' ?></div>
      <h2 style="margin-top: 4px;"><?= (int)$espacoAtual['id'] > 0 ? 'Editar espaço: ' . e($espacoAtual['nome']) : 'Cadastrar novo espaço' ?></h2>
      <p class="nota">Os campos marcados com <span class="obrigatorio">*</span> são obrigatórios. Utilize termos em português e por extenso.</p>

      <?php if (!empty($erros)): ?>
        <div class="msg erro" role="alert">
          <strong>Corrija os campos destacados em vermelho abaixo antes de salvar.</strong>
        </div>
      <?php endif; ?>

      <form method="post" action="<?= url('admin/espacos') ?>" novalidate>
        <input type="hidden" name="salvar_espaco" value="1">
        <input type="hidden" name="id" value="<?= (int)$espacoAtual['id'] ?>">

        <div class="linha">
          <div class="grupo-campo <?= !empty($erros['nome']) ? 'tem-erro' : '' ?>">
            <label for="esp-nome">
              Nome do espaço <span class="obrigatorio">*</span>
              <span class="dica-campo">Ex.: Salão Cristal, Auditório Central</span>
            </label>
            <input type="text" id="esp-nome" name="nome" value="<?= e($espacoAtual['nome']) ?>" placeholder="Nome completo do espaço" required>
            <?php if (!empty($erros['nome'])): ?><span class="erro-campo"><?= e($erros['nome']) ?></span><?php endif; ?>
          </div>

          <div class="grupo-campo <?= !empty($erros['tipo']) ? 'tem-erro' : '' ?>">
            <label for="esp-tipo">
              Tipo de espaço <span class="obrigatorio">*</span>
              <span class="dica-campo">Categoria física do ambiente</span>
            </label>
            <input type="text" id="esp-tipo" name="tipo" list="lista-tipos-espaco" value="<?= e($espacoAtual['tipo']) ?>" placeholder="Selecione ou digite o tipo" required>
            <datalist id="lista-tipos-espaco">
              <?php foreach ($tiposSugeridos as $ts): ?>
                <option value="<?= e($ts) ?>">
              <?php endforeach; ?>
            </datalist>
            <?php if (!empty($erros['tipo'])): ?><span class="erro-campo"><?= e($erros['tipo']) ?></span><?php endif; ?>
          </div>
        </div>

        <div class="linha">
          <div class="grupo-campo <?= !empty($erros['capacidade']) ? 'tem-erro' : '' ?>">
            <label for="esp-cap">
              Capacidade máxima (pessoas) <span class="obrigatorio">*</span>
              <span class="dica-campo">Limite máximo permitido nas solicitações</span>
            </label>
            <input type="number" id="esp-cap" name="capacidade" min="1" value="<?= $espacoAtual['capacidade'] !== '' ? (int)$espacoAtual['capacidade'] : '' ?>" placeholder="Ex.: 150" required>
            <?php if (!empty($erros['capacidade'])): ?><span class="erro-campo"><?= e($erros['capacidade']) ?></span><?php endif; ?>
          </div>

          <div class="grupo-campo <?= !empty($erros['preco']) ? 'tem-erro' : '' ?>">
            <label for="esp-preco">
              Valor da diária (R$) <span class="obrigatorio">*</span>
              <span class="dica-campo">Preço por dia de locação</span>
            </label>
            <input type="number" id="esp-preco" name="preco" step="0.01" min="1" value="<?= $espacoAtual['preco'] !== '' && (float)$espacoAtual['preco'] > 0 ? e(number_format((float)$espacoAtual['preco'], 2, '.', '')) : '' ?>" placeholder="Ex.: 2500.00" required>
            <?php if (!empty($erros['preco'])): ?><span class="erro-campo"><?= e($erros['preco']) ?></span><?php endif; ?>
          </div>
        </div>

        <div class="grupo-campo <?= !empty($erros['descricao']) ? 'tem-erro' : '' ?>">
          <label for="esp-desc">
            Descrição detalhada do espaço <span class="obrigatorio">*</span>
            <span class="dica-campo">Descreva o ambiente, diferenciais e estrutura disponível para os clientes.</span>
          </label>
          <textarea id="esp-desc" name="descricao" rows="3" placeholder="Ex.: Salão climatizado com palco, camarim e cozinha de apoio equipada..." required><?= e($espacoAtual['descricao']) ?></textarea>
          <?php if (!empty($erros['descricao'])): ?><span class="erro-campo"><?= e($erros['descricao']) ?></span><?php endif; ?>
        </div>

        <div class="buscador-fotos-box <?= !empty($erros['imagem_url']) ? 'campo-invalido-box' : '' ?>">
          <div class="rotulo">API de Fotos — Imagem de Capa e Galeria</div>
          <p class="nota">Pesquise fotos reais pela Wikimedia Commons API ou informe uma URL direta.</p>

          <div class="barra-busca-api-fotos">
            <input type="text" id="input-busca-foto-api" value="<?= e($espacoAtual['tipo'] ?: $espacoAtual['nome']) ?>" placeholder="Ex.: Salão de festas, Auditório, Jardim...">
            <button type="button" class="botao pequeno" id="btn-buscar-fotos-api">🔍 Buscar fotos na API</button>
          </div>
          <p class="nota" id="status-busca-fotos"></p>
          <div id="resultados-fotos-api" class="grade-resultados-fotos"></div>

          <div class="linha" style="margin-top: 14px; align-items: start;">
            <div>
              <div class="grupo-campo <?= !empty($erros['imagem_url']) ? 'tem-erro' : '' ?>">
                <label for="esp-img-url">URL da foto de capa</label>
                <input type="text" id="esp-img-url" name="imagem_url" value="<?= e($espacoAtual['imagem_url'] ?? '') ?>" placeholder="https://...">
                <?php if (!empty($erros['imagem_url'])): ?><span class="erro-campo"><?= e($erros['imagem_url']) ?></span><?php endif; ?>
              </div>
              <div class="grupo-campo">
                <label for="esp-img-credito">Crédito / Fonte da foto</label>
                <input type="text" id="esp-img-credito" name="imagem_credito" value="<?= e($espacoAtual['imagem_credito'] ?? '') ?>" placeholder="Ex.: Acervo Unsplash / Openverse">
              </div>
            </div>
            <div class="preview-capa-admin">
              <span class="rotulo-inline">Pré-visualização da capa</span>
              <img id="preview-capa-img" src="<?= e(imagemEspaco($espacoAtual)) ?>" alt="Pré-visualização da capa">
            </div>
          </div>

          <input type="hidden" id="esp-galeria-json" name="galeria_imagens" value="<?= e($espacoAtual['galeria_imagens'] ?? '') ?>">
          <div id="galeria-admin-lista" class="chips-galeria-admin">
            <?php foreach ($galeriaLista as $gIdx => $gUrl): ?>
              <div class="item-galeria-admin" data-url="<?= e($gUrl) ?>">
                <img src="<?= e($gUrl) ?>" alt="Foto da galeria <?= $gIdx + 1 ?>">
                <button type="button" class="btn-remover-galeria" title="Remover da galeria">×</button>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="buscador-fotos-box <?= !empty($erros['endereco']) ? 'campo-invalido-box' : '' ?>" style="margin-top: 20px;">
          <div class="rotulo">Google Maps — Endereço e Localização</div>
          <p class="nota">Informe o endereço completo e clique em "Visualizar no Google Maps" para conferir o local no mapa.</p>

          <div class="grupo-campo <?= !empty($erros['endereco']) ? 'tem-erro' : '' ?>">
            <label for="esp-endereco">
              Endereço (Rua, número e bairro) <span class="obrigatorio">*</span>
            </label>
            <input type="text" id="esp-endereco" name="endereco" value="<?= e($espacoAtual['endereco'] ?? '') ?>" placeholder="Ex.: Av. Paulista, 1578 - Bela Vista" required>
            <?php if (!empty($erros['endereco'])): ?><span class="erro-campo"><?= e($erros['endereco']) ?></span><?php endif; ?>
          </div>

          <div class="linha-3">
            <div class="grupo-campo">
              <label for="esp-cidade">Cidade</label>
              <input type="text" id="esp-cidade" name="cidade" value="<?= e($espacoAtual['cidade'] ?? 'São Paulo') ?>">
            </div>
            <div class="grupo-campo">
              <label for="esp-estado">UF</label>
              <input type="text" id="esp-estado" name="estado" maxlength="2" value="<?= e($espacoAtual['estado'] ?? 'SP') ?>">
            </div>
            <div class="grupo-campo">
              <label for="esp-cep">CEP</label>
              <input type="text" id="esp-cep" name="cep" value="<?= e($espacoAtual['cep'] ?? '') ?>" placeholder="00000-000">
            </div>
          </div>

          <div class="barra-acoes-form" style="margin-top: 6px;">
            <button type="button" class="botao secundario pequeno" id="btn-geocodificar-endereco">📍 Visualizar no Google Maps</button>
            <span class="nota" id="status-geocodificacao"></span>
          </div>

          <div class="mapa-container" style="margin-top: 12px;">
            <iframe
              id="mapa-preview-admin"
              class="mapa-iframe"
              src="<?= e($mapaPreviewUrl) ?>"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
              title="Pré-visualização do local no Google Maps"
            ></iframe>
          </div>
        </div>

        <div class="grupo-campo <?= !empty($erros['comodidades']) ? 'tem-erro' : '' ?>" style="margin-top: 20px;">
          <label>
            Comodidades oferecidas pelo espaço <span class="obrigatorio">*</span>
            <span class="dica-campo">Selecione os itens inclusos (aparecerão com ícones nos cartões de busca e detalhes).</span>
          </label>
          <div class="grade-checkbox-comodidades">
            <?php foreach ($comodidadesSugeridas as $com):
                $marcado = in_array($com, $comodidadesAtuaisLista, true);
            ?>
              <label class="item-check-comodidade">
                <input type="checkbox" name="comodidades_check[]" value="<?= e($com) ?>" <?= $marcado ? 'checked' : '' ?>>
                <span><?= iconeComodidade($com) ?> <?= e($com) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <label for="esp-com-extra" style="margin-top: 10px;">
            Outras comodidades <span class="opcional">(opcional, separadas por vírgula)</span>
          </label>
          <input type="text" id="esp-com-extra" name="comodidades_extra" value="<?= e(implode(', ', $comodidadesExtrasLista)) ?>" placeholder="Ex.: Gerador próprio, Camarim, Valet">
          <?php if (!empty($erros['comodidades'])): ?><span class="erro-campo"><?= e($erros['comodidades']) ?></span><?php endif; ?>
        </div>

        <div class="linha">
          <div class="grupo-campo">
            <label for="esp-resp-nome">
              Nome do responsável pelo espaço
              <span class="dica-campo">Exibido para o cliente tirar dúvidas em Detalhes</span>
            </label>
            <input type="text" id="esp-resp-nome" name="responsavel_nome" value="<?= e($espacoAtual['responsavel_nome']) ?>">
          </div>

          <div class="grupo-campo">
            <label for="esp-resp-contato">
              Contato do responsável (e-mail e telefone)
              <span class="dica-campo">Canal de atendimento direto do espaço</span>
            </label>
            <input type="text" id="esp-resp-contato" name="responsavel_contato" value="<?= e($espacoAtual['responsavel_contato']) ?>">
          </div>
        </div>

        <div class="grupo-campo">
          <label class="label-checkbox">
            <input type="checkbox" name="disponivel" value="1" <?= !empty($espacoAtual['disponivel']) ? 'checked' : '' ?>>
            <span><strong>Espaço ativo e disponível no catálogo público</strong> (desmarque para deixá-lo arquivado/oculto de novas buscas)</span>
          </label>
        </div>

        <div class="barra-acoes-form">
          <button type="submit" class="btn-primario" data-loading-text="Salvando espaço...">
            <?= (int)$espacoAtual['id'] > 0 ? 'Salvar alterações do espaço' : 'Cadastrar espaço no catálogo' ?>
          </button>
          <a class="botao secundario" href="<?= url('admin/espacos') ?>">Cancelar</a>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <div class="bloco-painel-destaque">
    <div class="cabecalho-bloco">
      <h2>Espaços cadastrados no catálogo (<?= count($espacos) ?>)</h2>
      <?php if (!$modoFormulario): ?>
        <a class="botao pequeno" href="<?= url('admin/espacos?novo=1') ?>">+ Novo espaço</a>
      <?php endif; ?>
    </div>

    <div class="tabela-responsiva">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Espaço e Foto</th>
            <th>Tipo / Localização</th>
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
            <td><strong>#<?= (int)$s['id'] ?></strong></td>
            <td>
              <div class="celula-espaco-mini">
                <img src="<?= e(imagemEspaco($s)) ?>" alt="<?= e($s['nome']) ?>" class="thumb-tabela-espaco">
                <div>
                  <strong><a href="<?= url('espacos/detalhes?id=' . (int)$s['id']) ?>"><?= e($s['nome']) ?></a></strong><br>
                  <small class="nota">Resp.: <?= e($s['responsavel_nome'] ?? 'Coordenação') ?></small>
                </div>
              </div>
            </td>
            <td>
              <?= e($s['tipo']) ?><br>
              <small class="nota">📍 <?= e(enderecoCurto($s)) ?></small>
            </td>
            <td><?= renderChipsComodidades($s['comodidades'], 4) ?></td>
            <td><?= capacidadeTexto($s['capacidade']) ?></td>
            <td><strong><?= moeda($s['preco']) ?></strong><small>/dia</small></td>
            <td>
              <span class="selo-status <?= $s['disponivel'] ? 'status-APROVADA' : 'status-CANCELADA' ?>">
                <?= $s['disponivel'] ? 'Ativo' : 'Arquivado' ?>
              </span>
            </td>
            <td>
              <div class="acoes-linha">
                <a class="botao secundario pequeno" href="<?= url('admin/espacos?editar=' . (int)$s['id']) ?>">Editar</a>

                <form method="post" action="<?= url('admin/espacos') ?>" class="inline" onsubmit="return confirm('Deseja <?= $s['disponivel'] ? 'arquivar' : 'reativar' ?> o espaço <?= e($s['nome']) ?>?')">
                  <input type="hidden" name="acao_rapida" value="alternar_status">
                  <input type="hidden" name="espaco_id" value="<?= (int)$s['id'] ?>">
                  <button type="submit" class="botao secundario pequeno">
                    <?= $s['disponivel'] ? 'Arquivar' : 'Reativar' ?>
                  </button>
                </form>

                <?php if ((int)$s['total_reservas'] === 0): ?>
                  <form method="post" action="<?= url('admin/espacos') ?>" class="inline" onsubmit="return confirm('Tem certeza de que deseja EXCLUIR permanentemente o espaço <?= e($s['nome']) ?>? Esta ação não poderá ser desfeita.')">
                    <input type="hidden" name="acao_rapida" value="excluir">
                    <input type="hidden" name="espaco_id" value="<?= (int)$s['id'] ?>">
                    <button type="submit" class="botao perigo pequeno">Excluir</button>
                  </form>
                <?php else: ?>
                  <span class="nota" title="Possui <?= (int)$s['total_reservas'] ?> solicitação(ões) vinculada(s); utilize Arquivar para ocultá-lo.">
                    (<?= (int)$s['total_reservas'] ?> solic.)
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
