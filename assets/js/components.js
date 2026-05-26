// Controle de Modais
function openModal() {
  const modal = document.getElementById("recordModal");
  if (modal) {
    modal.classList.remove("hidden");
    document.body.style.overflow = "hidden";
  }
}

function closeModal() {
  const modal = document.getElementById("recordModal");
  if (modal) {
    modal.classList.add("hidden");
    document.body.style.overflow = "";
    const form = document.getElementById("effectaForm");
    if (form) form.reset();
    document.getElementById("autorFeedbackHidden").value = "";
    document.getElementById("projetoHidden").value = "";

    if (typeof window.togglePrazoInput === "function") {
      window.togglePrazoInput();
    }
  }
}

// Seletor Inteligente de Busca e Adição (Smart Select)
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

    let currentData = dataArray === "people" ? window.peopleData : window.projectsData;
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
        let newItem;
        if (dataArray === "people") {
          newItem = await EffectaAPI.addPerson(term);
          window.peopleData.push(newItem);
        } else {
          newItem = await EffectaAPI.addProject(term);
          window.projectsData.push(newItem);
        }

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
    const container = document.getElementById(containerId);
    if (container && !container.contains(e.target)) {
      dropdown.classList.add("hidden");
    }
  });
}

// Filtro de Projetos e Autores
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
      if (typeof window.applyFilters === "function") window.applyFilters();
    };
    list.appendChild(liTodos);

    let currentData = dataArray === "people" ? window.peopleData : window.projectsData;
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
        if (typeof window.applyFilters === "function") window.applyFilters();
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
    if (typeof window.applyFilters === "function") window.applyFilters();
  });
  searchInput.addEventListener("focus", () =>
    updateDropdown(searchInput.value),
  );

  document.addEventListener("click", (e) => {
    const container = document.getElementById(containerId);
    if (container && !container.contains(e.target)) {
      dropdown.classList.add("hidden");
      if (searchInput.value.trim() === "") {
        hiddenInput.value = "";
        if (typeof window.applyFilters === "function") window.applyFilters();
      }
    }
  });
}

// Filtro Customizado de Status
function setupStatusFilterSelect() {
  const container = document.getElementById("filterStatusContainer");
  const searchInput = document.getElementById("filterStatusSearch");
  const hiddenInput = document.getElementById("filterStatus");
  const dropdown = document.getElementById("filterStatusDropdown");
  const list = document.getElementById("filterStatusList");

  if (!container) return;

  const options = [
    { value: "", label: "Todos os Status", icon: "fa-solid fa-list text-slate-400" },
    { value: "no_prazo", label: "No Prazo / Concluído", icon: "fa-solid fa-circle-check text-emerald-500" },
    { value: "atrasado", label: "Atrasado", icon: "fa-solid fa-circle-exclamation text-red-500" },
    { value: "atrasado_pendente", label: "Atrasado - Pendente", icon: "fa-solid fa-clock-rotate-left text-orange-500" },
    { value: "pendente", label: "Pendente", icon: "fa-solid fa-clock text-amber-500" }
  ];

  const updateDropdown = () => {
    dropdown.classList.remove("hidden");
    list.innerHTML = "";

    options.forEach(opt => {
      const li = document.createElement("li");
      li.className = "px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer flex items-center gap-2 transition-colors";
      li.innerHTML = `<i class="${opt.icon}"></i> ${opt.label}`;
      li.onmousedown = (e) => {
        e.preventDefault();
        searchInput.value = opt.label === "Todos os Status" ? "" : opt.label;
        hiddenInput.value = opt.value;
        dropdown.classList.add("hidden");
        if (typeof window.applyFilters === "function") window.applyFilters();
      };
      list.appendChild(li);
    });
  };

  searchInput.addEventListener("focus", updateDropdown);
  document.addEventListener("click", (e) => {
    if (!container.contains(e.target)) {
      dropdown.classList.add("hidden");
    }
  });
}
