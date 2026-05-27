// Inicialização das variáveis de estado globais
window.peopleData = [];
window.projectsData = [];
window.allRegistersData = [];

document.addEventListener("DOMContentLoaded", () => {
  loadData();
  loadAuxData();

  // Setup dos seletores de formulários
  setupSmartSelect(
    "autorSelectContainer",
    "autorFeedbackSearch",
    "autorFeedbackHidden",
    "autorDropdown",
    "autorList",
    "people",
    "add_person",
    "fa-solid fa-user-circle",
  );
  setupSmartSelect(
    "projetoSelectContainer",
    "projetoSearch",
    "projetoHidden",
    "projetoDropdown",
    "projetoList",
    "projects",
    "add_project",
    "fa-solid fa-folder",
  );

  // Setup dos seletores de filtros
  setupFilterSelect(
    "filterProjetoContainer",
    "filterProjetoSearch",
    "filterProjeto",
    "filterProjetoDropdown",
    "filterProjetoList",
    "projects",
    "fa-solid fa-folder",
    "Todos os Projetos",
  );
  setupFilterSelect(
    "filterAutorContainer",
    "filterAutorSearch",
    "filterAutor",
    "filterAutorDropdown",
    "filterAutorList",
    "people",
    "fa-solid fa-user-circle",
    "Todos os Autores",
  );

  // Setup do filtro customizado de status
  setupStatusFilterSelect();
});

// Carrega os dados assincronamente da API
async function loadAuxData() {
  window.peopleData = await EffectaAPI.getPeople();
  window.projectsData = await EffectaAPI.getProjects();
}

async function loadData() {
  window.allRegistersData = await EffectaAPI.getRegisters();
  window.applyFilters();
}

// Helpers de Conversão de Hora
function timeToDecimal(timeStr) {
  if (!timeStr || !timeStr.includes(":")) return parseFloat(timeStr) || 0;
  const [hours, minutes] = timeStr.split(":").map(Number);
  return hours + minutes / 60;
}

function decimalToTime(decimal) {
  if (!decimal) return "0h";
  const hours = Math.floor(decimal);
  const minutes = Math.round((decimal - hours) * 60);
  return `${hours}h ${minutes > 0 ? minutes + "m" : ""}`;
}

// Alternância de Campos no Modal (Prazo/Horas)
window.togglePrazoInput = function () {
  const tipo =
    document.querySelector('input[name="tipo_prazo"]:checked')?.value ||
    "horas";
  const inputHoras = document.getElementById("inputHorasContainer");
  const inputData = document.getElementById("inputDataContainer");

  const entregaDataContainer = document.getElementById("entregaDataContainer");
  const entregaHorasContainer = document.getElementById("entregaHorasContainer");

  if (!inputHoras) return;

  if (tipo === "horas") {
    inputHoras.classList.remove("hidden");
    inputData.classList.add("hidden");
    entregaHorasContainer.classList.remove("hidden");
    entregaDataContainer.classList.add("hidden");

    document.querySelector('input[name="horas_trabalhadas"]').required = true;
    document.querySelector('input[name="prazo"]').required = false;
  } else {
    inputHoras.classList.add("hidden");
    inputData.classList.remove("hidden");
    entregaHorasContainer.classList.add("hidden");
    entregaDataContainer.classList.remove("hidden");

    document.querySelector('input[name="horas_trabalhadas"]').required = false;
    document.querySelector('input[name="prazo"]').required = true;
  }
};

// Handler de Envio do Formulário (Salvar Registro)
const form = document.getElementById("effectaForm");
if (form) {
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    if (data.horas_trabalhadas) {
      data.horas_trabalhadas = timeToDecimal(data.horas_trabalhadas);
    }
    if (data.horas_gastas) {
      data.horas_gastas = timeToDecimal(data.horas_gastas);
    }

    const btn = e.target.querySelector('button[type="submit"]');
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Salvando...';
    btn.disabled = true;

    try {
      await EffectaAPI.saveRegister(data);
      closeModal();
      await loadData();
      showToast("Registro salvo com sucesso!", "success");
    } catch (err) {
      console.error("Erro ao salvar registro:", err);
      showToast("Erro ao salvar o registro no banco.", "error");
    } finally {
      btn.innerHTML = originalContent;
      btn.disabled = false;
    }
  });
}

// Filtro de Texto
document.getElementById("searchInput").addEventListener("input", () => {
  window.applyFilters();
});

// Motor de Filtros
window.applyFilters = function () {
  const term = document.getElementById("searchInput").value.toLowerCase();
  const proj = document.getElementById("filterProjeto").value;
  const autor = document.getElementById("filterAutor").value;
  const status = document.getElementById("filterStatus").value;

  let filtered = window.allRegistersData.filter((item) => {
    let pass = true;

    if (proj && item.projeto !== proj) pass = false;
    if (autor && item.autor_feedback !== autor) pass = false;

    if (status) {
      const itemStatus = getItemStatus(item);
      if (status === "atrasado") {
        if (itemStatus !== "atrasado" && itemStatus !== "atrasado_pendente") pass = false;
      } else {
        if (itemStatus !== status) pass = false;
      }
    }

    if (term) {
      let matchText = false;
      for (let key in item) {
        if (
          typeof item[key] === "string" &&
          item[key].toLowerCase().includes(term)
        ) {
          matchText = true;
          break;
        }
      }
      if (!matchText) pass = false;
    }

    return pass;
  });

  renderData(filtered);
};

// Renderização dos Cards na Tela
function renderData(data) {
  const container = document.getElementById("results");
  if (!container) return;
  container.innerHTML = "";

  if (data.length === 0) {
    container.innerHTML = `
            <div class="col-span-full flex flex-col items-center justify-center p-12 text-slate-400 dark:text-slate-500">
                <i class="fa-solid fa-folder-open text-4xl mb-3 opacity-50"></i>
                <p>Nenhum registro encontrado.</p>
            </div>`;
    return;
  }

  function getItemStatus(item) {
    if (item.tipo_prazo === "data") {
      if (item.data_entrega && item.prazo) {
        return item.data_entrega <= item.prazo ? "no_prazo" : "atrasado";
      } else if (!item.data_entrega && item.prazo) {
        const today = new Date().toLocaleDateString("en-CA");
        return today > item.prazo ? "atrasado_pendente" : "pendente";
      }
      return "pendente";
    } else {
      if (item.horas_gastas && item.horas_trabalhadas) {
        return Number(item.horas_gastas) <= Number(item.horas_trabalhadas) ? "no_prazo" : "atrasado";
      }
      return "pendente";
    }
  }

  data
    .slice()
    .reverse()
    .forEach((item) => {
      const card = document.createElement("div");
      card.className =
        "bg-white dark:bg-slate-800 rounded-xl shadow-sm hover:shadow-md border border-slate-200 dark:border-slate-700 p-6 transition-all flex flex-col";

      let tagsHtml = "";
      if (item.stakeholders) {
        tagsHtml = item.stakeholders
          .split(",")
          .map(
            (s) =>
              `<span class="inline-block bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs px-2 py-1 rounded-md mr-1 mb-1"><i class="fa-solid fa-users text-[10px] mr-1"></i>${s.trim()}</span>`,
          )
          .join("");
      }

      let feedbackHtml = "";
      if (item.feedbacks && item.autor_feedback) {
        feedbackHtml = `
            <div class="mt-4 p-3 bg-indigo-50/50 dark:bg-indigo-900/10 rounded-lg border border-indigo-100 dark:border-indigo-800/30">
                <p class="text-xs font-semibold text-primary dark:text-indigo-400 mb-1"><i class="fa-solid fa-quote-left mr-1"></i> ${item.autor_feedback} disse:</p>
                <p class="text-sm italic text-slate-600 dark:text-slate-300">"${item.feedbacks}"</p>
            </div>`;
      }

      let statusBadge = "";
      const itemStatus = getItemStatus(item);
      if (itemStatus === "no_prazo") {
        statusBadge = `<span class="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold px-2 py-1 rounded-full"><i class="fa-solid fa-check mr-1"></i>No Prazo</span>`;
      } else if (itemStatus === "atrasado") {
        statusBadge = `<span class="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-[10px] font-bold px-2 py-1 rounded-full"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Atrasado</span>`;
      } else if (itemStatus === "atrasado_pendente") {
        statusBadge = `<span class="bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800/50 text-[10px] font-bold px-2.5 py-1 rounded-full animate-pulse"><i class="fa-solid fa-clock-rotate-left mr-1"></i>Atrasado - Pendente de preenchimento</span>`;
      } else {
        statusBadge = `<span class="bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-[10px] font-bold px-2 py-1 rounded-full"><i class="fa-regular fa-clock mr-1"></i>Pendente</span>`;
      }

      let prazoHtml = "";
      let entregaHtml = "";
      if (item.tipo_prazo === "horas") {
        if (item.horas_trabalhadas) {
          prazoHtml = `<span title="Horas Planejadas"><i class="fa-regular fa-clock mr-1"></i> Planejado: ${decimalToTime(item.horas_trabalhadas)}</span>`;
        }
        if (item.horas_gastas) {
          entregaHtml = `<span title="Horas Gastas"><i class="fa-solid fa-stopwatch mr-1"></i> Gasto: ${decimalToTime(item.horas_gastas)}</span>`;
        }
      } else {
        if (item.prazo) {
          prazoHtml = `<span title="Prazo Planejado"><i class="fa-regular fa-calendar mr-1"></i> Prazo: ${item.prazo.split("-").reverse().join("/")}</span>`;
        }
        if (item.data_entrega) {
          entregaHtml = `<span title="Data de Entrega"><i class="fa-solid fa-flag-checkered mr-1"></i> Entregue: ${item.data_entrega.split("-").reverse().join("/")}</span>`;
        }
      }

      card.innerHTML = `
            <div class="flex justify-between items-start mb-4 gap-2">
                <div>
                    <span class="inline-block px-2 py-1 bg-indigo-100 dark:bg-indigo-900/50 text-primary dark:text-indigo-300 text-xs font-semibold rounded-md mb-2 uppercase tracking-wide">${item.projeto}</span>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white leading-tight">${item.atividade}</h3>
                </div>
                <div class="flex-shrink-0 text-right mt-1 font-sans">
                    ${statusBadge}
                </div>
            </div>
            
            <div class="flex-grow space-y-3 mb-4">
                ${item.meta ? `<p class="text-sm text-slate-600 dark:text-slate-300"><i class="fa-solid fa-bullseye w-5 text-slate-400"></i> <strong>Meta:</strong> ${item.meta}</p>` : ""}
                ${item.impacto ? `<p class="text-sm text-slate-600 dark:text-slate-300"><i class="fa-solid fa-arrow-trend-up w-5 text-emerald-500"></i> <strong>Impacto:</strong> ${item.impacto}</p>` : ""}
                ${feedbackHtml}
            </div>
            
            <div class="mt-auto pt-4 border-t border-slate-100 dark:border-slate-700">
                <div class="flex flex-wrap justify-between items-center text-xs text-slate-500 dark:text-slate-400 mb-2 gap-2">
                    ${prazoHtml}
                    ${entregaHtml}
                </div>
                <div>${tagsHtml}</div>
            </div>
        `;
      container.appendChild(card);
    });
};

// Declaração de suporte a getItemStatus fora do renderData para uso no filtro
function getItemStatus(item) {
  if (item.tipo_prazo === "data") {
    if (item.data_entrega && item.prazo) {
      return item.data_entrega <= item.prazo ? "no_prazo" : "atrasado";
    } else if (!item.data_entrega && item.prazo) {
      const today = new Date().toLocaleDateString("en-CA");
      return today > item.prazo ? "atrasado_pendente" : "pendente";
    }
    return "pendente";
  } else {
    if (item.horas_gastas && item.horas_trabalhadas) {
      return Number(item.horas_gastas) <= Number(item.horas_trabalhadas) ? "no_prazo" : "atrasado";
    }
    return "pendente";
  }
}
