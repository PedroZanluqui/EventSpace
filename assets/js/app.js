document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("form").forEach((form) => {
    form.addEventListener("submit", (ev) => {
      const submitter = ev.submitter || form.querySelector('button[type="submit"]');
      if (submitter && submitter.dataset.loadingText && !ev.defaultPrevented) {
        setTimeout(() => {
          submitter.disabled = true;
          submitter.textContent = submitter.dataset.loadingText;
          submitter.classList.add("carregando");
        }, 10);
      }
    });
  });

  const campoPessoas = document.getElementById("campo-pessoas");
  const erroPessoasJs = document.getElementById("erro-pessoas-js");
  if (campoPessoas && erroPessoasJs) {
    const maxCap = parseInt(campoPessoas.dataset.maxCap || "0", 10);
    campoPessoas.addEventListener("input", () => {
      const val = parseInt(campoPessoas.value || "0", 10);
      const grupo = campoPessoas.closest(".grupo-campo");
      if (campoPessoas.value !== "" && val < 1) {
        erroPessoasJs.textContent = "A quantidade deve ser de pelo menos 1 pessoa.";
        erroPessoasJs.classList.remove("oculto");
        if (grupo) grupo.classList.add("tem-erro");
      } else if (maxCap > 0 && val > maxCap) {
        erroPessoasJs.textContent = `A quantidade informada (${val} pessoas) ultrapassa o limite máximo deste espaço (${maxCap} pessoas). Reduza o número de convidados.`;
        erroPessoasJs.classList.remove("oculto");
        if (grupo) grupo.classList.add("tem-erro");
      } else {
        erroPessoasJs.classList.add("oculto");
        if (grupo) grupo.classList.remove("tem-erro");
      }
    });
  }

  const checkboxesComp = document.querySelectorAll(".seletor-comparar");
  const barraComp = document.getElementById("barra-comparacao");
  const contadorComp = document.getElementById("contador-comparar");
  const nomesComp = document.getElementById("nomes-comparar");
  const btnAbrirComp = document.getElementById("btn-abrir-comparador");
  const btnLimparComp = document.getElementById("btn-limpar-comparador");
  const modalComp = document.getElementById("modal-comparacao");
  const btnFecharComp = document.getElementById("btn-fechar-comparador");
  const conteudoComp = document.getElementById("conteudo-comparacao");

  function atualizarBarraComparacao() {
    if (!barraComp) return;
    const selecionados = Array.from(checkboxesComp).filter((c) => c.checked);
    if (selecionados.length === 0) {
      barraComp.classList.add("oculto");
      return;
    }
    barraComp.classList.remove("oculto");
    if (contadorComp) contadorComp.textContent = String(selecionados.length);
    const listaNomes = selecionados.map((c) => {
      const card = c.closest(".card");
      return card ? card.dataset.nome : "";
    });
    if (nomesComp) nomesComp.textContent = " · " + listaNomes.join(" vs. ");
  }

  checkboxesComp.forEach((chk) => {
    chk.addEventListener("change", () => {
      const selecionados = Array.from(checkboxesComp).filter((c) => c.checked);
      if (selecionados.length > 3) {
        chk.checked = false;
        alert("Você pode selecionar no máximo 3 espaços para comparar lado a lado.");
        return;
      }
      atualizarBarraComparacao();
    });
  });

  if (btnLimparComp) {
    btnLimparComp.addEventListener("click", () => {
      checkboxesComp.forEach((c) => (c.checked = false));
      atualizarBarraComparacao();
    });
  }

  if (btnAbrirComp && modalComp && conteudoComp) {
    btnAbrirComp.addEventListener("click", () => {
      const selecionados = Array.from(checkboxesComp)
        .filter((c) => c.checked)
        .map((c) => c.closest(".card"));
      if (selecionados.length < 2) {
        alert("Selecione pelo menos 2 espaços para comparar lado a lado.");
        return;
      }

      let html = '<table class="tabela-comparacao"><thead><tr><th>Critério</th>';
      selecionados.forEach((card) => {
        html += `<th>${card.dataset.nome}</th>`;
      });
      html += "</tr></thead><tbody>";

      html += "<tr><td><strong>Tipo de espaço</strong></td>";
      selecionados.forEach((card) => (html += `<td>${card.dataset.tipo}</td>`));
      html += "</tr>";

      html += "<tr><td><strong>Capacidade máxima</strong></td>";
      selecionados.forEach((card) => (html += `<td>${card.dataset.capacidade}</td>`));
      html += "</tr>";

      html += "<tr><td><strong>Valor da diária</strong></td>";
      selecionados.forEach((card) => (html += `<td><strong>${card.dataset.preco}</strong></td>`));
      html += "</tr>";

      html += "<tr><td><strong>Comodidades</strong></td>";
      selecionados.forEach((card) => (html += `<td>${card.dataset.comodidades}</td>`));
      html += "</tr>";

      html += "<tr><td><strong>Ação</strong></td>";
      selecionados.forEach((card) => {
        html += `<td><a class="botao pequeno" href="${card.dataset.url}">Solicitar reserva</a></td>`;
      });
      html += "</tr></tbody></table>";

      conteudoComp.innerHTML = html;
      modalComp.classList.remove("oculto");
    });
  }

  if (btnFecharComp && modalComp) {
    btnFecharComp.addEventListener("click", () => modalComp.classList.add("oculto"));
    modalComp.addEventListener("click", (e) => {
      if (e.target === modalComp) modalComp.classList.add("oculto");
    });
  }

  const checkTodas = document.getElementById("selecionar-todas");
  const checksLote = document.querySelectorAll(".check-item-lote");
  if (checkTodas) {
    checkTodas.addEventListener("change", () => {
      checksLote.forEach((c) => (c.checked = checkTodas.checked));
    });
  }

  const modalRes = document.getElementById("modal-detalhe-reserva");
  const corpoModalRes = document.getElementById("corpo-modal-res");
  const acoesModalRes = document.getElementById("acoes-modal-res");
  const btnFecharModalRes = document.getElementById("btn-fechar-modal-res");

  document.querySelectorAll(".btn-abrir-detalhe-reserva").forEach((btn) => {
    btn.addEventListener("click", () => {
      if (!modalRes || !corpoModalRes || !acoesModalRes) return;
      const d = btn.dataset;
      corpoModalRes.innerHTML = `
        <dt>Protocolo</dt><dd><strong>#${d.id}</strong></dd>
        <dt>Espaço</dt><dd>${d.espaco}</dd>
        <dt>Cliente</dt><dd>${d.cliente}</dd>
        <dt>Data do evento</dt><dd>${d.data}</dd>
        <dt>Horário</dt><dd>${d.horario}</dd>
        <dt>Tipo de evento</dt><dd>${d.evento}</dd>
        <dt>Quantidade</dt><dd>${d.pessoas}</dd>
        <dt>Observações</dt><dd>${d.obs}</dd>
      `;

      if (d.status === "PENDENTE") {
        acoesModalRes.innerHTML = `
          <form method="post" class="inline" onsubmit="return confirm('Confirmar aprovação da solicitação #${d.id}?')">
            <input type="hidden" name="id" value="${d.id}">
            <button type="submit" class="botao pequeno" name="acao" value="aprovar">Aprovar solicitação</button>
          </form>
          <form method="post" class="inline" onsubmit="return confirm('Confirmar recusa da solicitação #${d.id}?')">
            <input type="hidden" name="id" value="${d.id}">
            <button type="submit" class="botao perigo pequeno" name="acao" value="rejeitar">Recusar solicitação</button>
          </form>
        `;
      } else {
        acoesModalRes.innerHTML = `
          <form method="post" class="inline" onsubmit="return confirm('Deseja cancelar/recusar esta reserva aprovada #${d.id}?')">
            <input type="hidden" name="id" value="${d.id}">
            <button type="submit" class="botao perigo pequeno" name="acao" value="rejeitar">Cancelar aprovação (Recusar)</button>
          </form>
        `;
      }

      modalRes.classList.remove("oculto");
    });
  });

  if (btnFecharModalRes && modalRes) {
    btnFecharModalRes.addEventListener("click", () => modalRes.classList.add("oculto"));
    modalRes.addEventListener("click", (e) => {
      if (e.target === modalRes) modalRes.classList.add("oculto");
    });
  }
});
