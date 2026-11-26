<?php
require_once __DIR__ . "/includes/funcoes.php";
cabecalho("Central de Ajuda e Documentação", "ajuda");
?>
<section class="largo">
  <div class="rotulo">Suporte e documentação</div>
  <h1 class="menor">Central de Ajuda — Como funciona o EventSpace</h1>
  <p class="lead largo-texto">Encontre respostas rápidas sobre o processo de solicitação, prazos de aprovação, significado de cada status e nossa política de cancelamento.</p>

  <div class="bloco-secao">
    <h2>Etapas da reserva de um espaço</h2>
    <div class="passos-grade">
      <div class="passo-item">
        <span class="passo-numero">01</span>
        <strong>Busca e comparação</strong>
        <p>Filtre por tipo de espaço, data, quantidade de pessoas e comodidades. Você pode comparar até 3 espaços lado a lado antes de decidir.</p>
      </div>
      <div class="passo-item">
        <span class="passo-numero">02</span>
        <strong>Preenchimento e revisão</strong>
        <p>Informe data, horário de início e término, tipo de evento e quantidade de convidados. Na etapa 02, revise tudo e aceite a política de cancelamento.</p>
      </div>
      <div class="passo-item">
        <span class="passo-numero">03</span>
        <strong>Análise do administrador (até 2 dias úteis)</strong>
        <p>Sua solicitação fica com o status <strong>Aguardando aprovação</strong>. Enquanto estiver em análise, você pode editar os dados ou cancelar a qualquer momento.</p>
      </div>
      <div class="passo-item">
        <span class="passo-numero">04</span>
        <strong>Confirmação e evento</strong>
        <p>Quando o administrador aprova sua solicitação, você recebe uma notificação no topo do sistema e o horário passa a constar oficialmente na agenda do espaço.</p>
      </div>
    </div>
  </div>

  <div class="bloco-secao">
    <h2>O que significa cada status?</h2>
    <div class="tabela-responsiva">
      <table>
        <thead>
          <tr>
            <th>Selo de status</th>
            <th>Significado para o cliente</th>
            <th>O que você pode fazer</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><?= renderStatusBadge('PENDENTE', null, false) ?></td>
            <td>Seu pedido foi enviado e aguarda a avaliação do administrador. <strong>O espaço ainda não está garantido</strong>. Prazo de resposta: até <strong>2 dias úteis</strong>.</td>
            <td>Você pode <strong>Editar solicitação</strong> (mudar data, horário ou pessoas) ou <strong>Cancelar reserva</strong> gratuitamente.</td>
          </tr>
          <tr>
            <td><?= renderStatusBadge('APROVADA', null, false) ?></td>
            <td>O administrador aprovou seu pedido e o espaço está <strong>confirmado e bloqueado na agenda</strong> para o seu evento.</td>
            <td>Comparecer na data combinada ou cancelar gratuitamente até 48 horas antes do evento.</td>
          </tr>
          <tr>
            <td><?= renderStatusBadge('REJEITADA', null, false) ?></td>
            <td>Não foi possível aprovar o pedido (por exemplo, por conflito de agenda ou manutenção).</td>
            <td>Usar o botão <strong>Reservar novamente</strong> no Histórico para escolher outra data ou horário.</td>
          </tr>
          <tr>
            <td><?= renderStatusBadge('CANCELADA', null, false) ?></td>
            <td>A solicitação foi cancelada por você.</td>
            <td>Você pode desfazer logo após cancelar ou iniciar uma nova solicitação quando desejar.</td>
          </tr>
          <tr>
            <td><?= renderStatusBadge('CONCLUIDA', null, false) ?></td>
            <td>Evento aprovado cuja data já foi realizada.</td>
            <td>Usar o botão <strong>Reservar novamente</strong> no Histórico para repetir o evento em uma nova data.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="bloco-secao">
    <h2>Política de cancelamento e alterações</h2>
    <ul class="lista-regras">
      <li><strong>Solicitações aguardando aprovação:</strong> podem ser editadas ou canceladas livremente sem qualquer custo.</li>
      <li><strong>Reservas já aprovadas:</strong> podem ser canceladas sem custo até <strong>48 horas antes</strong> do horário de início do evento.</li>
      <li><strong>Desfazer imediato:</strong> caso cancele uma solicitação por engano, utilize o botão <em>"Desfazer ação"</em> exibido no aviso de confirmação no topo da tela.</li>
    </ul>
  </div>

  <div class="bloco-secao caixa-responsavel">
    <h2>Falar com a coordenação e responsáveis pelos espaços</h2>
    <p>Precisa de ajuda personalizada ou quer tirar dúvidas específicas antes de reservar?</p>
    <ul>
      <li><strong>Atendimento Geral EventSpace:</strong> contato@eventspace.com.br · (11) 99999-0000 (Segunda a Sexta, das 08h às 19h)</li>
      <li><strong>Contato direto por espaço:</strong> na página de <em>Detalhes</em> de cada espaço você encontra o nome, telefone e e-mail do responsável dedicado.</li>
    </ul>
    <a class="botao pequeno" href="<?= url('espacos/index.php') ?>">Ver espaços disponíveis</a>
  </div>
</section>
<?php rodape(); ?>

