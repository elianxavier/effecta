window.peopleData = [];
window.projectsData = [];
window.allRegistersData = [];

document.addEventListener("DOMContentLoaded", () => {
  loadData();
  loadAuxData();

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

  setupStatusFilterSelect();
});

async function loadAuxData() {
  window.peopleData = await EffectaAPI.getPeople();
  window.projectsData = await EffectaAPI.getProjects();
}

async function loadData() {
  window.allRegistersData = await EffectaAPI.getRegisters();
  window.applyFilters();
}

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

window.togglePrazoInput = function () {
  const tipo =
    document.querySelector('input[name="tipo_prazo"]:checked')?.value ||
    "horas";
  const inputHoras = document.getElementById("inputHorasContainer");
  const inputData = document.getElementById("inputDataContainer");

  const entregaDataContainer = document.getElementById("entregaDataContainer");
  const entregaHorasContainer = document.getElementById(
    "entregaHorasContainer",
  );

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
    btn.innerHTML =
      '<i class="fa-solid fa-circle-notch fa-spin"></i> Salvando...';
    btn.disabled = true;

    try {
      const recordId = document.getElementById("recordIdHidden").value;
      if (recordId) {
        await EffectaAPI.updateRegister(recordId, data);
        showToast("Registro atualizado com sucesso!", "success");
      } else {
        await EffectaAPI.saveRegister(data);
        showToast("Registro salvo com sucesso!", "success");
      }

      closeModal();
      await loadData();
    } catch (err) {
      console.error("Erro ao salvar/atualizar registro:", err);
      showToast("Erro ao salvar/atualizar o registro.", "error");
    } finally {
      btn.innerHTML = originalContent;
      btn.disabled = false;
    }
  });
}

document.getElementById("searchInput").addEventListener("input", () => {
  window.applyFilters();
});

window.applyFilters = function () {
  const term = document.getElementById("searchInput").value.toLowerCase();
  const projId = document.getElementById("filterProjeto").value;
  const autorId = document.getElementById("filterAutor").value;
  const status = document.getElementById("filterStatus").value;

  let filtered = window.allRegistersData.filter((item) => {
    let pass = true;

    if (projId && String(item.projeto_id) !== String(projId)) pass = false;
    if (autorId && String(item.pessoa_feedback_id) !== String(autorId))
      pass = false;

    if (status) {
      const itemStatus = getItemStatus(item);
      if (status === "atrasado") {
        if (itemStatus !== "atrasado" && itemStatus !== "atrasado_pendente")
          pass = false;
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

// INICIO ALTERACAO: Aplicando o visual premium nos cards (bordas arredondadas, hover effects, gradientes sutis)
function renderData(data) {
  const container = document.getElementById("results");
  if (!container) return;
  container.innerHTML = "";

  if (data.length === 0) {
    container.innerHTML = `
            <div class="col-span-full flex flex-col items-center justify-center p-16 bg-white/50 dark:bg-slate-800/30 rounded-3xl border border-dashed border-slate-300 dark:border-slate-700 backdrop-blur-sm">
                <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800/80 rounded-full flex items-center justify-center mb-4 shadow-sm">
                    <i class="fa-solid fa-folder-open text-3xl text-slate-400 dark:text-slate-500"></i>
                </div>
                <p class="text-slate-500 dark:text-slate-400 font-medium">Nenhum registro encontrado.</p>
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
        return Number(item.horas_gastas) <= Number(item.horas_trabalhadas)
          ? "no_prazo"
          : "atrasado";
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
        "bg-white dark:bg-slate-800/80 rounded-3xl shadow-sm hover:shadow-xl border border-slate-200/60 dark:border-slate-700 p-6 transition-all duration-300 hover:-translate-y-1 flex flex-col backdrop-blur-sm group";

      let tagsHtml = "";
      if (item.stakeholders) {
        tagsHtml = item.stakeholders
          .split(",")
          .map(
            (s) =>
              `<span class="inline-flex items-center gap-1 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 text-[10px] font-bold px-2.5 py-1 rounded-full mr-1.5 mb-1.5 shadow-sm"><i class="fa-solid fa-users opacity-70"></i>${s.trim()}</span>`,
          )
          .join("");
      }

      let feedbackHtml = "";
      if (
        item.feedbacks &&
        item.pessoa_feedback_name &&
        item.pessoa_feedback_name !== "Nenhum"
      ) {
        feedbackHtml = `
            <div class="mt-4 p-4 bg-gradient-to-br from-indigo-50/50 to-purple-50/50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-2xl border border-indigo-100/50 dark:border-indigo-800/30">
                <div class="flex items-center gap-2 mb-1.5">
                    <div class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-800 flex items-center justify-center text-primary dark:text-indigo-300 text-[10px]">
                        <i class="fa-solid fa-quote-left"></i>
                    </div>
                    <p class="text-xs font-bold text-slate-700 dark:text-indigo-200">${item.pessoa_feedback_name}</p>
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed pl-7">"${item.feedbacks}"</p>
            </div>`;
      }

      let statusBadge = "";
      const itemStatus = getItemStatus(item);
      if (itemStatus === "no_prazo") {
        statusBadge = `<span class="bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/50 text-[10px] font-bold px-3 py-1.5 rounded-full backdrop-blur-sm flex items-center gap-1 shadow-sm"><i class="fa-solid fa-check"></i>No Prazo</span>`;
      } else if (itemStatus === "atrasado") {
        statusBadge = `<span class="bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 border border-red-200/60 dark:border-red-800/50 text-[10px] font-bold px-3 py-1.5 rounded-full backdrop-blur-sm flex items-center gap-1 shadow-sm"><i class="fa-solid fa-triangle-exclamation"></i>Atrasado</span>`;
      } else if (itemStatus === "atrasado_pendente") {
        statusBadge = `<span class="bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 border border-red-200/60 dark:border-red-800/50 text-[10px] font-bold px-3 py-1.5 rounded-full animate-pulse backdrop-blur-sm flex items-center gap-1 shadow-sm"><i class="fa-solid fa-clock-rotate-left"></i>Atrasado - Pendente</span>`;
      } else {
        statusBadge = `<span class="bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-200/60 dark:border-amber-800/50 text-[10px] font-bold px-3 py-1.5 rounded-full backdrop-blur-sm flex items-center gap-1 shadow-sm"><i class="fa-regular fa-clock"></i>Pendente</span>`;
      }

      let prazoHtml = "";
      let entregaHtml = "";
      if (item.tipo_prazo === "horas") {
        if (item.horas_trabalhadas) {
          prazoHtml = `<div class="flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/50 px-2 py-1 rounded-lg border border-slate-100 dark:border-slate-700" title="Horas Planejadas"><i class="fa-regular fa-clock opacity-70"></i> ${decimalToTime(item.horas_trabalhadas)}</div>`;
        }
        if (item.horas_gastas) {
          entregaHtml = `<div class="flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/50 px-2 py-1 rounded-lg border border-slate-100 dark:border-slate-700" title="Horas Gastas"><i class="fa-solid fa-stopwatch opacity-70"></i> ${decimalToTime(item.horas_gastas)}</div>`;
        }
      } else {
        if (item.prazo) {
          prazoHtml = `<div class="flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/50 px-2 py-1 rounded-lg border border-slate-100 dark:border-slate-700" title="Prazo Planejado"><i class="fa-regular fa-calendar opacity-70"></i> ${item.prazo.split("-").reverse().join("/")}</div>`;
        }
        if (item.data_entrega) {
          entregaHtml = `<div class="flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/50 px-2 py-1 rounded-lg border border-slate-100 dark:border-slate-700" title="Data de Entrega"><i class="fa-solid fa-flag-checkered opacity-70"></i> ${item.data_entrega.split("-").reverse().join("/")}</div>`;
        }
      }

      card.innerHTML = `
            <div class="flex justify-between items-start mb-5 gap-3">
                <div class="flex-1">
                    <span class="inline-block px-2.5 py-1 bg-gradient-to-r from-indigo-100 to-purple-100 dark:from-indigo-900/40 dark:to-purple-900/40 border border-indigo-200/50 dark:border-indigo-800/50 text-indigo-700 dark:text-indigo-300 text-[10px] font-extrabold rounded-md mb-3 uppercase tracking-widest shadow-sm">${item.projeto_name}</span>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white leading-tight">${item.atividade}</h3>
                </div>
                <div class="flex-shrink-0 flex flex-col items-end gap-2">
                    ${statusBadge}
                    <div class="flex gap-1.5 mt-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                        <button class="w-8 h-8 rounded-full bg-slate-50 dark:bg-slate-700/50 flex items-center justify-center text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/50 transition-colors shadow-sm" title="Editar Registro" onclick="openEditModal('${item.id}')">
                            <i class="fa-solid fa-pencil text-xs"></i>
                        </button>
                        <button class="w-8 h-8 rounded-full bg-slate-50 dark:bg-slate-700/50 flex items-center justify-center text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/50 transition-colors shadow-sm" title="Excluir Registro" onclick="deleteRecord('${item.id}')">
                            <i class="fa-solid fa-trash-alt text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="flex-grow space-y-3.5 mb-5">
                ${item.meta ? `<div class="flex gap-2"><i class="fa-solid fa-bullseye mt-0.5 w-4 text-slate-400 dark:text-slate-500"></i><p class="text-sm text-slate-600 dark:text-slate-300"><strong class="text-slate-800 dark:text-slate-200">Meta:</strong> ${item.meta}</p></div>` : ""}
                ${item.impacto ? `<div class="flex gap-2"><i class="fa-solid fa-arrow-trend-up mt-0.5 w-4 text-emerald-500 dark:text-emerald-400"></i><p class="text-sm text-slate-600 dark:text-slate-300"><strong class="text-slate-800 dark:text-slate-200">Impacto:</strong> ${item.impacto}</p></div>` : ""}
                ${feedbackHtml}
            </div>
            
            <div class="mt-auto pt-4 border-t border-slate-100 dark:border-slate-700/50 flex flex-col gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    ${prazoHtml}
                    ${entregaHtml}
                </div>
                ${tagsHtml ? `<div>${tagsHtml}</div>` : ""}
            </div>
        `;
      container.appendChild(card);
    });
}
// FIM ALTERACAO

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
      return Number(item.horas_gastas) <= Number(item.horas_trabalhadas)
        ? "no_prazo"
        : "atrasado";
    }
    return "pendente";
  }
}

window.openEditModal = async function (recordId) {
  const record = window.allRegistersData.find(
    (r) => String(r.id) === String(recordId),
  );
  if (!record) {
    showToast("Registro não encontrado para edição.", "error");
    return;
  }

  const form = document.getElementById("effectaForm");
  form.reset();

  form.querySelector("#recordIdHidden").value = record.id;
  document.getElementById("modal-title").textContent = "Editar Registro";

  for (const key in record) {
    const input = form.querySelector(`[name="${key}"]`);
    if (input) {
      if (input.type === "radio") {
        if (input.value === record[key]) {
          input.checked = true;
        }
      } else {
        if (key === "horas_trabalhadas" || key === "horas_gastas") {
          input.value = decimalToTime(record[key]);
        } else {
          input.value = record[key];
        }
      }
    }
  }

  document.getElementById("projetoSearch").value = record.projeto_name || "";
  document.getElementById("projetoHidden").value = record.projeto_id || "";
  document.getElementById("autorFeedbackSearch").value =
    record.pessoa_feedback_name !== "Nenhum" ? record.pessoa_feedback_name : "";
  document.getElementById("autorFeedbackHidden").value =
    record.pessoa_feedback_id || "";

  window.togglePrazoInput();

  openModal();
};

window.deleteRecord = async function (recordId) {
  const confirmed = await showConfirm(
    "Excluir Registro?",
    "Tem certeza que deseja excluir este registro? Esta ação é irreversível e os dados serão removidos permanentemente.",
    "danger",
  );

  if (!confirmed) return;

  try {
    await EffectaAPI.deleteRegister(recordId);
    await loadData();
    showToast("Registro excluído com sucesso!", "success");
  } catch (err) {
    console.error("Erro ao excluir registro:", err);
    showToast("Erro ao excluir o registro.", "error");
  }
};
