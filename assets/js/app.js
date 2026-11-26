function escapeHtml(texto) {
  return String(texto ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function appUrl(caminho) {
  const base = (document.body?.dataset?.baseUrl || "").replace(/\/+$/, "");
  const limpo = String(caminho || "").replace(/^\/+/, "");
  return base + (limpo ? "/" + limpo : "/");
}

function formatarDateTimeLocal(dateObj) {
  const pad = (n) => String(n).padStart(2, "0");
  return (
    dateObj.getFullYear() +
    "-" +
    pad(dateObj.getMonth() + 1) +
    "-" +
    pad(dateObj.getDate()) +
    "T" +
    pad(dateObj.getHours()) +
    ":" +
    pad(dateObj.getMinutes())
  );
}

async function buscarFotosWikimediaCors(termo, limite = 6) {
  const q = encodeURIComponent((termo || "event venue hall") + " filetype:bitmap");
  const url = `https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrnamespace=6&gsrsearch=${q}&gsrlimit=12&prop=imageinfo&iiprop=url|extmetadata|mime|size&iiurlwidth=1200&format=json&origin=*`;
  const resp = await fetch(url);
  if (!resp.ok) return [];
  const data = await resp.json();
  const pages = Object.values(data?.query?.pages || {});
  const fotos = [];
  for (const p of pages) {
    const info = p?.imageinfo?.[0];
    if (!info) continue;
    const mime = info.mime || "";
    if (!["image/jpeg", "image/png", "image/webp"].includes(mime)) continue;
    if ((info.width || 0) < 600 || (info.width || 0) < (info.height || 0)) continue;
    const imgUrl = info.thumburl || info.url;
    if (!imgUrl) continue;
    const rawArtist = info?.extmetadata?.Artist?.value || "Wikimedia Commons";
    const tmp = document.createElement("div");
    tmp.innerHTML = rawArtist;
    const autor = (tmp.textContent || "Wikimedia Commons").trim().slice(0, 60);
    fotos.push({
      url: imgUrl,
      thumb: imgUrl,
      alt: (p.title || termo).replace("File:", ""),
      credito: `${autor} · Wikimedia Commons`,
      fonte: "Wikimedia Commons API",
    });
    if (fotos.length >= limite) break;
  }
  return fotos;
}

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

  const contadores = Array.from(document.querySelectorAll("[data-countdown-iso]"));
  if (contadores.length > 0) {
    let recarregandoExpirado = false;
    const atualizarContadores = () => {
      const agora = Date.now();
      contadores.forEach((el) => {
        const iso = el.dataset.countdownIso || "";
        const alvo = Date.parse(iso);
        if (Number.isNaN(alvo)) return;
        const diffSeg = Math.floor((alvo - agora) / 1000);
        if (diffSeg <= 0) {
          el.textContent = "Prazo expirado — atualizando...";
          el.classList.add("expirado");
          if (!recarregandoExpirado) {
            recarregandoExpirado = true;
            setTimeout(() => window.location.reload(), 1500);
          }
          return;
        }
        const dias = Math.floor(diffSeg / 86400);
        const horas = Math.floor((diffSeg % 86400) / 3600);
        const mins = Math.floor((diffSeg % 3600) / 60);
        const segs = diffSeg % 60;
        const partes = [];
        if (dias > 0) partes.push(`${dias}d`);
        if (horas > 0 || dias > 0) partes.push(`${horas}h`);
        partes.push(`${String(mins).padStart(2, "0")}m`);
        partes.push(`${String(segs).padStart(2, "0")}s`);
        el.textContent = `Restam ${partes.join(" ")}`;
      });
    };
    atualizarContadores();
    setInterval(atualizarContadores, 1000);
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
    const listaNomes = selecionados.map((c) => c.closest(".card")?.dataset.nome || "");
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
        html += `<th>${escapeHtml(card.dataset.nome)}</th>`;
      });
      html += "</tr></thead><tbody>";

      html += "<tr><td><strong>Foto</strong></td>";
      selecionados.forEach((card) => {
        html += `<td><img src="${escapeHtml(card.dataset.imagem)}" alt="${escapeHtml(card.dataset.nome)}" style="width:140px;height:80px;object-fit:cover;border:1px solid #d6d6d2;"></td>`;
      });
      html += "</tr>";

      html += "<tr><td><strong>Tipo de espaço</strong></td>";
      selecionados.forEach((card) => (html += `<td>${escapeHtml(card.dataset.tipo)}</td>`));
      html += "</tr>";

      html += "<tr><td><strong>Localização</strong></td>";
      selecionados.forEach((card) => (html += `<td>${escapeHtml(card.dataset.endereco)}</td>`));
      html += "</tr>";

      html += "<tr><td><strong>Capacidade máxima</strong></td>";
      selecionados.forEach((card) => (html += `<td>${escapeHtml(card.dataset.capacidade)}</td>`));
      html += "</tr>";

      html += "<tr><td><strong>Valor da diária</strong></td>";
      selecionados.forEach((card) => (html += `<td><strong>${escapeHtml(card.dataset.preco)}</strong></td>`));
      html += "</tr>";

      html += "<tr><td><strong>Comodidades</strong></td>";
      selecionados.forEach((card) => (html += `<td>${escapeHtml(card.dataset.comodidades)}</td>`));
      html += "</tr>";

      html += "<tr><td><strong>Ação</strong></td>";
      selecionados.forEach((card) => {
        html += `<td><a class="botao pequeno" href="${escapeHtml(card.dataset.url)}">Solicitar reserva</a></td>`;
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

  const modalRecusa = document.getElementById("modal-recusa-reserva");
  const formRecusa = document.getElementById("form-recusa-reserva");
  const tituloModalRecusa = document.getElementById("titulo-modal-recusa");
  const subtituloModalRecusa = document.getElementById("subtitulo-modal-recusa");
  const inputRecusaId = document.getElementById("recusa-id");
  const inputRecusaMotivo = document.getElementById("recusa-motivo");
  const inputRecusaPrazo = document.getElementById("recusa-prazo");
  const radioComPrazo = document.getElementById("radio-com-prazo");
  const radioSemPrazo = document.getElementById("radio-sem-prazo");
  const grupoPrazo = document.getElementById("grupo-prazo-resolucao");
  const btnFecharModalRecusa = document.getElementById("btn-fechar-modal-recusa");
  const btnCancelarModalRecusa = document.getElementById("btn-cancelar-modal-recusa");
  let modoRecusaLote = false;

  function atualizarEstadoPrazo() {
    if (!radioComPrazo || !grupoPrazo || !inputRecusaPrazo) return;
    const ativo = radioComPrazo.checked;
    grupoPrazo.classList.toggle("oculto", !ativo);
    inputRecusaPrazo.required = ativo;
    inputRecusaPrazo.disabled = !ativo;
  }

  function abrirModalRecusaIndividual(id, espaco, cliente) {
    if (!modalRecusa) return;
    modoRecusaLote = false;
    if (tituloModalRecusa) tituloModalRecusa.textContent = `Recusar solicitação #${id}`;
    if (subtituloModalRecusa) {
      subtituloModalRecusa.textContent = `Cliente: ${cliente} · Espaço: ${espaco}. Informe o motivo e se haverá prazo para resolver pendências.`;
    }
    if (inputRecusaId) inputRecusaId.value = id;
    if (inputRecusaMotivo) inputRecusaMotivo.value = "";
    if (radioComPrazo) radioComPrazo.checked = true;
    if (inputRecusaPrazo) {
      const def = new Date(Date.now() + 48 * 3600 * 1000);
      inputRecusaPrazo.value = formatarDateTimeLocal(def);
    }
    atualizarEstadoPrazo();
    modalRecusa.classList.remove("oculto");
    inputRecusaMotivo?.focus();
  }

  document.querySelectorAll(".btn-abrir-recusa").forEach((btn) => {
    btn.addEventListener("click", () => {
      abrirModalRecusaIndividual(btn.dataset.id || "", btn.dataset.espaco || "", btn.dataset.cliente || "");
    });
  });

  const btnRecusarLote = document.getElementById("btn-recusar-lote");
  if (btnRecusarLote && modalRecusa) {
    btnRecusarLote.addEventListener("click", () => {
      const selecionados = Array.from(checksLote).filter((c) => c.checked);
      if (selecionados.length === 0) {
        alert("Selecione pelo menos uma solicitação pendente para recusar em lote.");
        return;
      }
      modoRecusaLote = true;
      if (tituloModalRecusa) tituloModalRecusa.textContent = `Recusar ${selecionados.length} solicitação(ões) em lote`;
      if (subtituloModalRecusa) {
        subtituloModalRecusa.textContent = "O mesmo motivo e regra de prazo serão aplicados a todas as solicitações selecionadas.";
      }
      if (inputRecusaId) inputRecusaId.value = "";
      if (inputRecusaMotivo) inputRecusaMotivo.value = "";
      if (radioComPrazo) radioComPrazo.checked = true;
      if (inputRecusaPrazo) {
        const def = new Date(Date.now() + 48 * 3600 * 1000);
        inputRecusaPrazo.value = formatarDateTimeLocal(def);
      }
      atualizarEstadoPrazo();
      modalRecusa.classList.remove("oculto");
      inputRecusaMotivo?.focus();
    });
  }

  if (radioComPrazo && radioSemPrazo) {
    radioComPrazo.addEventListener("change", atualizarEstadoPrazo);
    radioSemPrazo.addEventListener("change", atualizarEstadoPrazo);
  }

  document.querySelectorAll(".chip-atalho-motivo").forEach((chip) => {
    chip.addEventListener("click", () => {
      if (inputRecusaMotivo) {
        inputRecusaMotivo.value = chip.dataset.motivo || "";
        inputRecusaMotivo.focus();
      }
    });
  });

  document.querySelectorAll(".chip-atalho-prazo").forEach((chip) => {
    chip.addEventListener("click", () => {
      const horas = parseInt(chip.dataset.horas || "48", 10);
      if (radioComPrazo) radioComPrazo.checked = true;
      atualizarEstadoPrazo();
      if (inputRecusaPrazo) {
        const futuro = new Date(Date.now() + horas * 3600 * 1000);
        inputRecusaPrazo.value = formatarDateTimeLocal(futuro);
      }
    });
  });

  if (formRecusa) {
    formRecusa.addEventListener("submit", (ev) => {
      if (modoRecusaLote) {
        ev.preventDefault();
        const formLote = document.getElementById("form-lote");
        if (!formLote) return;
        document.getElementById("lote-acao-hidden").value = "rejeitar_lote";
        document.getElementById("lote-motivo-hidden").value = inputRecusaMotivo?.value.trim() || "";
        document.getElementById("lote-conceder-prazo-hidden").value = radioComPrazo?.checked ? "1" : "0";
        document.getElementById("lote-prazo-hidden").value = radioComPrazo?.checked ? (inputRecusaPrazo?.value || "") : "";
        formLote.submit();
      }
    });
  }

  if (btnFecharModalRecusa && modalRecusa) {
    btnFecharModalRecusa.addEventListener("click", () => modalRecusa.classList.add("oculto"));
    btnCancelarModalRecusa?.addEventListener("click", () => modalRecusa.classList.add("oculto"));
    modalRecusa.addEventListener("click", (e) => {
      if (e.target === modalRecusa) modalRecusa.classList.add("oculto");
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
      let extraHtml = "";
      if (d.motivo) {
        extraHtml += `<dt>${d.reenviada === "1" ? "Pendência anterior" : "Motivo informado"}</dt><dd>${escapeHtml(d.motivo)}</dd>`;
      }
      if (d.prazo) {
        extraHtml += `<dt>Prazo limite</dt><dd>${escapeHtml(d.prazo)}</dd>`;
      }

      corpoModalRes.innerHTML = `
        <dt>Protocolo</dt><dd><strong>#${escapeHtml(d.id)}</strong></dd>
        <dt>Espaço</dt><dd>${escapeHtml(d.espaco)}</dd>
        <dt>Cliente</dt><dd>${escapeHtml(d.cliente)}</dd>
        <dt>Data do evento</dt><dd>${escapeHtml(d.data)}</dd>
        <dt>Horário</dt><dd>${escapeHtml(d.horario)}</dd>
        <dt>Tipo de evento</dt><dd>${escapeHtml(d.evento)}</dd>
        <dt>Quantidade</dt><dd>${escapeHtml(d.pessoas)}</dd>
        <dt>Observações</dt><dd>${escapeHtml(d.obs)}</dd>
        ${extraHtml}
      `;

      if (d.status === "PENDENTE") {
        acoesModalRes.innerHTML = `
          <form method="post" action="${escapeHtml(appUrl("admin"))}" class="inline" onsubmit="return confirm('Confirmar aprovação da solicitação #${escapeHtml(d.id)}?')">
            <input type="hidden" name="id" value="${escapeHtml(d.id)}">
            <button type="submit" class="botao pequeno" name="acao" value="aprovar">Aprovar solicitação</button>
          </form>
          <button type="button" class="botao perigo pequeno" id="btn-modal-det-recusar">Recusar solicitação</button>
        `;
      } else if (d.status === "APROVADA") {
        acoesModalRes.innerHTML = `
          <button type="button" class="botao perigo pequeno" id="btn-modal-det-recusar">Cancelar aprovação (Recusar)</button>
        `;
      } else {
        acoesModalRes.innerHTML = "";
      }

      const btnDetRec = document.getElementById("btn-modal-det-recusar");
      if (btnDetRec) {
        btnDetRec.addEventListener("click", () => {
          modalRes.classList.add("oculto");
          abrirModalRecusaIndividual(d.id || "", d.espaco || "", d.cliente || "");
        });
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

  const imgPrincipal = document.getElementById("galeria-img-principal");
  const containerMiniaturas = document.getElementById("galeria-miniaturas");
  if (imgPrincipal && containerMiniaturas) {
    containerMiniaturas.addEventListener("click", (ev) => {
      const btn = ev.target.closest(".galeria-thumb");
      if (!btn) return;
      const novaUrl = btn.dataset.fotoUrl;
      if (!novaUrl) return;
      imgPrincipal.src = novaUrl;
      containerMiniaturas.querySelectorAll(".galeria-thumb").forEach((t) => t.classList.remove("ativa"));
      btn.classList.add("ativa");
    });
  }

  const imgsParaHidratar = document.querySelectorAll("img[data-hydrate-espaco-id]");
  imgsParaHidratar.forEach(async (img) => {
    const espacoId = img.dataset.hydrateEspacoId;
    const consulta = img.dataset.hydrateQuery || "event venue";
    if (!espacoId) return;

    try {
      const form = new FormData();
      form.append("espaco_id", espacoId);
      const resp = await fetch(appUrl("api/espacos/hidratar-foto"), { method: "POST", body: form });
      if (!resp.ok) return;
      let dados = await resp.json();

      if (!dados.ok) {
        const fotosBrowser = await buscarFotosWikimediaCors(dados.consulta || consulta, 3);
        if (fotosBrowser.length > 0) {
          const capa = fotosBrowser[0];
          const formSave = new FormData();
          formSave.append("espaco_id", espacoId);
          formSave.append("fallback_url", capa.url);
          formSave.append("fallback_credito", capa.credito);
          const respSave = await fetch(appUrl("api/espacos/hidratar-foto"), { method: "POST", body: formSave });
          if (respSave.ok) {
            dados = await respSave.json();
          } else {
            dados = { ok: true, imagem_url: capa.url, imagem_credito: capa.credito, galeria: fotosBrowser.map((f) => f.url) };
          }
        }
      }

      if (dados.ok && dados.imagem_url) {
        img.src = dados.imagem_url;
        const card = img.closest(".card");
        if (card) card.dataset.imagem = dados.imagem_url;

        if (img.dataset.hydrateGallery === "1") {
          const credEl = document.getElementById("galeria-credito");
          if (credEl && dados.imagem_credito) {
            credEl.textContent = "📷 " + dados.imagem_credito;
            credEl.classList.remove("oculto");
          }
          if (containerMiniaturas && Array.isArray(dados.galeria) && dados.galeria.length > 1) {
            containerMiniaturas.innerHTML = dados.galeria
              .map(
                (u, i) => `<button type="button" class="galeria-thumb ${i === 0 ? "ativa" : ""}" data-foto-url="${escapeHtml(u)}"><img src="${escapeHtml(u)}" alt="Miniatura ${i + 1}"></button>`
              )
              .join("");
            containerMiniaturas.classList.remove("oculto");
          }
        }
      }
    } catch {
    }
  });

  const btnBuscarFotos = document.getElementById("btn-buscar-fotos-api");
  const inputBuscaFotos = document.getElementById("input-busca-foto-api");
  const statusFotos = document.getElementById("status-busca-fotos");
  const gradeResultadosFotos = document.getElementById("resultados-fotos-api");
  const inputImgUrl = document.getElementById("esp-img-url");
  const inputImgCredito = document.getElementById("esp-img-credito");
  const previewCapaImg = document.getElementById("preview-capa-img");
  const inputGaleriaJson = document.getElementById("esp-galeria-json");
  const galeriaAdminLista = document.getElementById("galeria-admin-lista");

  function lerGaleriaAdmin() {
    if (!inputGaleriaJson || !inputGaleriaJson.value.trim()) return [];
    try {
      const arr = JSON.parse(inputGaleriaJson.value);
      return Array.isArray(arr) ? arr.filter((u) => typeof u === "string" && u.trim() !== "") : [];
    } catch {
      return [];
    }
  }

  function salvarGaleriaAdmin(lista) {
    const unicos = Array.from(new Set(lista.map((u) => u.trim()).filter(Boolean)));
    if (inputGaleriaJson) inputGaleriaJson.value = JSON.stringify(unicos);
    if (galeriaAdminLista) {
      galeriaAdminLista.innerHTML = unicos
        .map(
          (u) => `<div class="item-galeria-admin" data-url="${escapeHtml(u)}"><img src="${escapeHtml(u)}" alt="Foto galeria"><button type="button" class="btn-remover-galeria" title="Remover da galeria">×</button></div>`
        )
        .join("");
    }
  }

  if (galeriaAdminLista) {
    galeriaAdminLista.addEventListener("click", (ev) => {
      const btn = ev.target.closest(".btn-remover-galeria");
      if (!btn) return;
      const item = btn.closest(".item-galeria-admin");
      const urlRem = item?.dataset.url || "";
      salvarGaleriaAdmin(lerGaleriaAdmin().filter((u) => u !== urlRem));
    });
  }

  if (inputImgUrl && previewCapaImg) {
    inputImgUrl.addEventListener("change", () => {
      const val = inputImgUrl.value.trim();
      if (val) previewCapaImg.src = val;
    });
  }

  if (btnBuscarFotos && inputBuscaFotos && gradeResultadosFotos) {
    btnBuscarFotos.addEventListener("click", async () => {
      const termo = inputBuscaFotos.value.trim() || "salão de eventos";
      if (statusFotos) statusFotos.textContent = "Consultando API de fotos...";
      gradeResultadosFotos.innerHTML = "";

      try {
        const resp = await fetch(appUrl("api/fotos") + "?q=" + encodeURIComponent(termo) + "&limite=8");
        const data = await resp.json();
        let fotos = data.fotos || [];
        let provedor = data.provedor || "";

        if (fotos.length === 0) {
          fotos = await buscarFotosWikimediaCors(data.termo_usado || termo, 8);
          provedor = "Wikimedia Commons API";
        }

        if (fotos.length === 0) {
          if (statusFotos) statusFotos.textContent = "Nenhuma foto retornada para esse termo. Tente palavras como 'auditorium', 'banquet hall' ou 'garden'.";
          return;
        }

        if (statusFotos) statusFotos.textContent = `${fotos.length} foto(s) encontrada(s) via ${provedor}.`;
        gradeResultadosFotos.innerHTML = fotos
          .map(
            (f) => `
              <div class="card-foto-api">
                <img src="${escapeHtml(f.thumb || f.url)}" alt="${escapeHtml(f.alt)}" loading="lazy">
                <small>${escapeHtml(f.credito)}</small>
                <div class="card-foto-acoes">
                  <button type="button" class="botao pequeno btn-usar-capa" data-url="${escapeHtml(f.url)}" data-credito="${escapeHtml(f.credito)}">Usar capa</button>
                  <button type="button" class="botao secundario pequeno btn-add-galeria" data-url="${escapeHtml(f.url)}">+ Galeria</button>
                </div>
              </div>
            `
          )
          .join("");
      } catch {
        if (statusFotos) statusFotos.textContent = "Não foi possível consultar a API de fotos no momento.";
      }
    });

    gradeResultadosFotos.addEventListener("click", (ev) => {
      const btnCapa = ev.target.closest(".btn-usar-capa");
      if (btnCapa) {
        const u = btnCapa.dataset.url || "";
        const c = btnCapa.dataset.credito || "";
        if (inputImgUrl) inputImgUrl.value = u;
        if (inputImgCredito) inputImgCredito.value = c;
        if (previewCapaImg && u) previewCapaImg.src = u;
        return;
      }
      const btnGal = ev.target.closest(".btn-add-galeria");
      if (btnGal) {
        const u = btnGal.dataset.url || "";
        if (u) salvarGaleriaAdmin([...lerGaleriaAdmin(), u]);
      }
    });
  }

  const btnGeocodificar = document.getElementById("btn-geocodificar-endereco");
  const statusGeo = document.getElementById("status-geocodificacao");
  const mapaPreviewAdmin = document.getElementById("mapa-preview-admin");
  if (btnGeocodificar) {
    btnGeocodificar.addEventListener("click", () => {
      const end = document.getElementById("esp-endereco")?.value.trim() || "";
      const cid = document.getElementById("esp-cidade")?.value.trim() || "";
      const uf = document.getElementById("esp-estado")?.value.trim() || "";
      const cep = document.getElementById("esp-cep")?.value.trim() || "";
      const consulta = [end, cid && uf ? `${cid} - ${uf}` : cid, cep, "Brasil"].filter(Boolean).join(", ");
      if (!end) {
        if (statusGeo) statusGeo.textContent = "Preencha o campo Endereço antes de visualizar no mapa.";
        return;
      }
      if (mapaPreviewAdmin) {
        mapaPreviewAdmin.src = "https://maps.google.com/maps?q=" + encodeURIComponent(consulta) + "&z=15&hl=pt-BR&output=embed";
      }
      if (statusGeo) statusGeo.textContent = "Mapa atualizado para: " + consulta;
    });
  }
});
