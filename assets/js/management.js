document.addEventListener("DOMContentLoaded", () => {
  loadAllData();

  const managementForm = document.getElementById("managementForm");
  if (managementForm) {
    managementForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      const id = document.getElementById("managementIdHidden").value;
      const type = document.getElementById("managementTypeHidden").value;
      const name = document.getElementById("managementName").value;

      const submitBtn = document.getElementById("managementSubmitBtn");
      const originalContent = submitBtn.innerHTML;
      submitBtn.innerHTML =
        '<i class="fa-solid fa-circle-notch fa-spin"></i> Salvando...';
      submitBtn.disabled = true;

      try {
        if (type === "projects") {
          if (id) {
            await EffectaAPI.updateProject(id, name);
            showToast("Projeto atualizado com sucesso!", "success");
          } else {
            await EffectaAPI.addProject(name);
            showToast("Projeto criado com sucesso!", "success");
          }
        } else if (type === "people") {
          if (id) {
            await EffectaAPI.updatePerson(id, name);
            showToast("Autor atualizado com sucesso!", "success");
          } else {
            await EffectaAPI.addPerson(name);
            showToast("Autor criado com sucesso!", "success");
          }
        }
        closeManagementModal();
        await loadAllData();
      } catch (err) {
        console.error("Erro ao salvar:", err);
        showToast(err.message || "Erro ao salvar.", "error");
      } finally {
        submitBtn.innerHTML = originalContent;
        submitBtn.disabled = false;
      }
    });
  }
});

async function loadAllData() {
  try {
    const projects = await EffectaAPI.getProjects();
    const people = await EffectaAPI.getPeople();

    renderTable("projects", projects);
    renderTable("people", people);

    window.projectsData = projects;
    window.peopleData = people;
  } catch (err) {
    console.error("Erro ao carregar dados:", err);
    showToast("Erro ao carregar os dados.", "error");
  }
}

function renderTable(type, data) {
  const tableBody = document.getElementById(`${type}TableBody`);
  tableBody.innerHTML = "";

  if (data.length === 0) {
    tableBody.innerHTML = `
            <tr>
                <td colspan="3" class="px-6 py-4 text-center text-slate-500 dark:text-slate-400">Nenhum item encontrado.</td>
            </tr>
        `;
    return;
  }

  data.forEach((item) => {
    const row = tableBody.insertRow();
    row.className = "hover:bg-slate-50 dark:hover:bg-slate-700/50";

    const nameCell = row.insertCell(0);
    nameCell.className =
      "px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-slate-100";
    nameCell.textContent = item.name;

    const dateCell = row.insertCell(1);
    dateCell.className =
      "px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400";
    dateCell.textContent = new Date(item.created_at).toLocaleDateString();

    const actionsCell = row.insertCell(2);
    actionsCell.className =
      "px-6 py-4 whitespace-nowrap text-right text-sm font-medium";
    actionsCell.innerHTML = `
            <button onclick="openManagementModal('${type}', 'edit', '${item.id}')" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3" title="Editar">
                <i class="fa-solid fa-pencil"></i>
            </button>
            <button onclick="deleteItem('${type}', '${item.id}')" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" title="Excluir">
                <i class="fa-solid fa-trash"></i>
            </button>
        `;
  });
}

window.switchTab = function (tab) {
  // Buttons
  document
    .getElementById("tab-projects")
    .classList.remove("border-primary", "text-primary");
  document
    .getElementById("tab-projects")
    .classList.add("border-transparent", "text-slate-500");
  document
    .getElementById("tab-people")
    .classList.remove("border-primary", "text-primary");
  document
    .getElementById("tab-people")
    .classList.add("border-transparent", "text-slate-500");

  document
    .getElementById(`tab-${tab}`)
    .classList.remove("border-transparent", "text-slate-500");
  document
    .getElementById(`tab-${tab}`)
    .classList.add("border-primary", "text-primary");

  // Content
  document.getElementById("content-projects").classList.add("hidden");
  document.getElementById("content-people").classList.add("hidden");
  document.getElementById(`content-${tab}`).classList.remove("hidden");
};

window.openManagementModal = function (type, mode, id = null) {
  const modal = document.getElementById("managementModal");
  const title = document.getElementById("managementModalTitle");
  const form = document.getElementById("managementForm");

  form.reset();
  document.getElementById("managementIdHidden").value = id || "";
  document.getElementById("managementTypeHidden").value = type;

  const typeLabel = type === "projects" ? "Projeto" : "Autor";
  title.textContent =
    mode === "create" ? `Novo ${typeLabel}` : `Editar ${typeLabel}`;

  if (mode === "edit" && id) {
    const data = type === "projects" ? window.projectsData : window.peopleData;
    const item = data.find((i) => String(i.id) === String(id));
    if (item) {
      document.getElementById("managementName").value = item.name;
    }
  }

  modal.classList.remove("hidden");
  document.body.style.overflow = "hidden";
};

window.closeManagementModal = function () {
  document.getElementById("managementModal").classList.add("hidden");
  document.body.style.overflow = "";
};

window.deleteItem = async function (type, id) {
  const typeLabel = type === "projects" ? "este projeto" : "este autor";
  const titleLabel = type === "projects" ? "Projeto" : "Autor";
  
  const confirmed = await showConfirm(
    `Excluir ${titleLabel}?`,
    `Tem certeza que deseja excluir ${typeLabel}? Esta ação removerá o vínculo com todos os registros existentes.`,
    "danger"
  );

  if (!confirmed) return;

  try {
    if (type === "projects") {
      await EffectaAPI.deleteProject(id);
    } else {
      await EffectaAPI.deletePerson(id);
    }
    showToast("Excluído com sucesso!", "success");
    await loadAllData();
  } catch (err) {
    console.error("Erro ao excluir:", err);
    showToast(err.message || "Erro ao excluir.", "error");
  }
};
