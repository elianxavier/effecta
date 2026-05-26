let peopleData = [];
let projectsData = [];
let allLogsData = [];
const html = document.documentElement;
const themeBtn = document.getElementById("themeToggle");

document.addEventListener("DOMContentLoaded", () => {
  initTheme();
  loadData();
  loadAuxData();
});

function initTheme() {
  if (
    localStorage.theme === "dark" ||
    (!("theme" in localStorage) &&
      window.matchMedia("(prefers-color-scheme: dark)").matches)
  ) {
    html.classList.add("dark");
  } else {
    html.classList.remove("dark");
  }
}

themeBtn.addEventListener("click", () => {
  html.classList.toggle("dark");
  localStorage.theme = html.classList.contains("dark") ? "dark" : "light";
});

function openModal() {
  document.getElementById("recordModal").classList.remove("hidden");
  document.body.style.overflow = "hidden";
}

function closeModal() {
  document.getElementById("recordModal").classList.add("hidden");
  document.body.style.overflow = "";
  document.getElementById("effectaForm").reset();
  document.getElementById("autorFeedbackHidden").value = "";
  document.getElementById("projetoHidden").value = "";

  togglePrazoInput();
}

function timeToDecimal(timeStr) {
  if (!timeStr || !timeStr.includes(":")) return parseFloat(timeStr) || 0;
  const [hours, minutes] = timeStr.split(":").map(Number);
  return hours + minutes / 60;
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

document.getElementById("effectaForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);
  const data = Object.fromEntries(formData.entries());

  if (data.horas_trabalhadas)
    data.horas_trabalhadas = timeToDecimal(data.horas_trabalhadas);
  if (data.horas_gastas) data.horas_gastas = timeToDecimal(data.horas_gastas);

  const btn = e.target.querySelector('button[type="submit"]');
  const originalContent = btn.innerHTML;
  btn.innerHTML =
    '<i class="fa-solid fa-circle-notch fa-spin"></i> Salvando...';
  btn.disabled = true;

  await fetch("index.php?action=save_log", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data),
  });

  closeModal();
  loadData();

  btn.innerHTML = originalContent;
  btn.disabled = false;
});

async function loadAuxData() {
  const resPeople = await fetch("index.php?action=get_people");
  peopleData = await resPeople.json();

  const resProjects = await fetch("index.php?action=get_projects");
  projectsData = await resProjects.json();
}

function setupSmartSelect(
  containerId,
  searchId,
  hiddenId,
  dropdownId,
  listId,
  dataArray,
  addAction,
  iconClass,
) {
  const searchInput = document.getElementById(searchId);
  const hiddenInput = document.getElementById(hiddenId);
  const dropdown = document.getElementById(dropdownId);
  const list = document.getElementById(listId);

  const updateDropdown = (term) => {
    dropdown.classList.remove("hidden");
    list.innerHTML = "";

    let currentData = dataArray === "people" ? peopleData : projectsData;
    const filtered = currentData.filter((p) =>
      p.name.toLowerCase().includes(term.toLowerCase()),
    );

    filtered.forEach((item) => {
      const li = document.createElement("li");
      li.className =
        "px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer flex items-center gap-2 transition-colors";
      li.innerHTML = `<i class="${iconClass} text-slate-400"></i> ${item.name}`;
      li.onmousedown = (e) => {
        e.preventDefault();
        searchInput.value = item.name;
        hiddenInput.value = item.name;
        dropdown.classList.add("hidden");
      };
      list.appendChild(li);
    });

    const exactMatch = filtered.find(
      (p) => p.name.toLowerCase() === term.toLowerCase(),
    );
    if (term.trim() !== "" && !exactMatch) {
      const addLi = document.createElement("li");
      addLi.className =
        "px-4 py-2 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 text-primary cursor-pointer border-t border-slate-100 dark:border-slate-700 font-medium transition-colors";
      addLi.innerHTML = `<i class="fa-solid fa-plus mr-1"></i> Adicionar "${term}"`;
      addLi.onmousedown = async (e) => {
        e.preventDefault();
        const res = await fetch(`index.php?action=${addAction}`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ name: term }),
        });
        const newItem = await res.json();
        if (dataArray === "people") peopleData.push(newItem);
        if (dataArray === "projects") projectsData.push(newItem);

        searchInput.value = newItem.name;
        hiddenInput.value = newItem.name;
        dropdown.classList.add("hidden");
      };
      list.appendChild(addLi);
    }

    if (list.children.length === 0) {
      list.innerHTML =
        '<li class="px-4 py-2 text-slate-400 italic">Digite para buscar ou adicionar...</li>';
    }
  };

  searchInput.addEventListener("input", () => {
    hiddenInput.value = "";
    updateDropdown(searchInput.value);
  });
  searchInput.addEventListener("focus", () =>
    updateDropdown(searchInput.value),
  );

  document.addEventListener("click", (e) => {
    if (!document.getElementById(containerId).contains(e.target)) {
      dropdown.classList.add("hidden");
    }
  });
}

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

function setupFilterSelect(
  containerId,
  searchId,
  hiddenId,
  dropdownId,
  listId,
  dataArray,
  iconClass,
  placeholderText,
) {
  const searchInput = document.getElementById(searchId);
  const hiddenInput = document.getElementById(hiddenId);
  const dropdown = document.getElementById(dropdownId);
  const list = document.getElementById(listId);

  const updateDropdown = (term) => {
    dropdown.classList.remove("hidden");
    list.innerHTML = "";

    const liTodos = document.createElement("li");
    liTodos.className =
      "px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer flex items-center gap-2 transition-colors font-medium text-slate-500";
    liTodos.innerHTML = `<i class="fa-solid fa-list text-slate-400"></i> ${placeholderText}`;
    liTodos.onmousedown = (e) => {
      e.preventDefault();
      searchInput.value = "";
      hiddenInput.value = "";
      dropdown.classList.add("hidden");
      applyFilters();
    };
    list.appendChild(liTodos);

    let currentData = dataArray === "people" ? peopleData : projectsData;
    const filtered = currentData.filter((p) =>
      p.name.toLowerCase().includes(term.toLowerCase()),
    );

    filtered.forEach((item) => {
      const li = document.createElement("li");
      li.className =
        "px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer flex items-center gap-2 transition-colors";
      li.innerHTML = `<i class="${iconClass} text-slate-400"></i> ${item.name}`;
      li.onmousedown = (e) => {
        e.preventDefault();
        searchInput.value = item.name;
        hiddenInput.value = item.name;
        dropdown.classList.add("hidden");
        applyFilters();
      };
      list.appendChild(li);
    });

    if (filtered.length === 0) {
      const liEmpty = document.createElement("li");
      liEmpty.className = "px-4 py-2 text-slate-400 italic text-xs";
      liEmpty.textContent = "Nenhum encontrado...";
      list.appendChild(liEmpty);
    }
  };

  searchInput.addEventListener("input", () => {
    hiddenInput.value = "";
    updateDropdown(searchInput.value);
    applyFilters();
  });
  searchInput.addEventListener("focus", () =>
    updateDropdown(searchInput.value),
  );

  document.addEventListener("click", (e) => {
    if (!document.getElementById(containerId).contains(e.target)) {
      dropdown.classList.add("hidden");
      if (searchInput.value.trim() === "") {
        hiddenInput.value = "";
        applyFilters();
      }
    }
  });
}

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

document.getElementById("searchInput").addEventListener("input", applyFilters);
document
  .getElementById("filterStatus")
  .addEventListener("change", applyFilters);

function applyFilters() {
  const term = document.getElementById("searchInput").value.toLowerCase();
  const proj = document.getElementById("filterProjeto").value;
  const autor = document.getElementById("filterAutor").value;
  const status = document.getElementById("filterStatus").value;

  let filtered = allLogsData.filter((item) => {
    let pass = true;

    if (proj && item.projeto !== proj) pass = false;
    if (autor && item.autor_feedback !== autor) pass = false;

    if (status) {
      if (item.tipo_prazo === "data") {
        const hasDataEntrega = !!item.data_entrega;
        const atrasado = hasDataEntrega && item.data_entrega > item.prazo;
        if (status === "no_prazo" && (!hasDataEntrega || atrasado))
          pass = false;
        if (status === "atrasado" && (!hasDataEntrega || !atrasado))
          pass = false;
      } else {
        const hasHorasGastas = !!item.horas_gastas;
        const atrasado =
          hasHorasGastas &&
          Number(item.horas_gastas) > Number(item.horas_trabalhadas);
        if (status === "no_prazo" && (!hasHorasGastas || atrasado))
          pass = false;
        if (status === "atrasado" && (!hasHorasGastas || !atrasado))
          pass = false;
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
}

async function loadData() {
  const res = await fetch("index.php?action=get_logs");
  allLogsData = await res.json();
  applyFilters();
}

function decimalToTime(decimal) {
  if (!decimal) return "0h";
  const hours = Math.floor(decimal);
  const minutes = Math.round((decimal - hours) * 60);
  return `${hours}h ${minutes > 0 ? minutes + "m" : ""}`;
}

function renderData(data) {
  const container = document.getElementById("results");
  container.innerHTML = "";

  if (data.length === 0) {
    container.innerHTML = `
            <div class="col-span-full flex flex-col items-center justify-center p-12 text-slate-400 dark:text-slate-500">
                <i class="fa-solid fa-folder-open text-4xl mb-3 opacity-50"></i>
                <p>Nenhum registro encontrado.</p>
            </div>`;
    return;
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
      if (item.tipo_prazo === "data") {
        if (item.data_entrega && item.prazo) {
          if (item.data_entrega <= item.prazo) {
            statusBadge = `<span class="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold px-2 py-1 rounded-full"><i class="fa-solid fa-check mr-1"></i>No Prazo</span>`;
          } else {
            statusBadge = `<span class="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-[10px] font-bold px-2 py-1 rounded-full"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Atrasado</span>`;
          }
        } else if (!item.data_entrega) {
          statusBadge = `<span class="bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-[10px] font-bold px-2 py-1 rounded-full"><i class="fa-regular fa-clock mr-1"></i>Pendente</span>`;
        }
      } else {
        if (item.horas_gastas && item.horas_trabalhadas) {
          if (Number(item.horas_gastas) <= Number(item.horas_trabalhadas)) {
            statusBadge = `<span class="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold px-2 py-1 rounded-full"><i class="fa-solid fa-check mr-1"></i>No Prazo</span>`;
          } else {
            statusBadge = `<span class="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-[10px] font-bold px-2 py-1 rounded-full"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Atrasado</span>`;
          }
        } else {
          statusBadge = `<span class="bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-[10px] font-bold px-2 py-1 rounded-full"><i class="fa-regular fa-clock mr-1"></i>Pendente</span>`;
        }
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
                <div class="flex-shrink-0 text-right mt-1">
                    ${statusBadge}
                </div>
            </div>
            
            <div class="flex-grow space-y-3 mb-4">
                ${item.meta ? `<p class="text-sm text-slate-600 dark:text-slate-300"><i class="fa-solid fa-bullseye w-5 text-slate-400"></i> <strong>Meta:</strong> ${item.meta}</p>` : ""}
                ${item.o_que_fiz ? `<p class="text-sm text-slate-600 dark:text-slate-300"><i class="fa-solid fa-hammer w-5 text-slate-400"></i> <strong>Ação:</strong> ${item.o_que_fiz}</p>` : ""}
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
}
