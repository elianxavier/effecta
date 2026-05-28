// Global state for feedbacks
window.feedbacksData = [];
window.currentFeedbackTab = "active";

document.addEventListener("DOMContentLoaded", () => {
  loadFeedbacks();

  const feedbackForm = document.getElementById("feedbackForm");
  if (feedbackForm) {
    feedbackForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      const formData = new FormData(feedbackForm);
      const data = Object.fromEntries(formData.entries());

      const button = feedbackForm.querySelector('button[type="submit"]');
      button.disabled = true;
      button.innerHTML =
        '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Enviando...';

      try {
        await EffectaAPI.saveFeedback(data);
        showToast("Feedback enviado com sucesso!", "success");
        feedbackForm.reset();
        await loadFeedbacks();
      } catch (err) {
        console.error("Erro ao enviar feedback:", err);
        showToast(err.message || "Erro ao enviar o feedback.", "error");
      } finally {
        button.disabled = false;
        button.innerHTML =
          '<i class="fa-solid fa-paper-plane mr-2"></i>Enviar Feedback';
      }
    });
  }
});

async function loadFeedbacks(tab = "active") {
  window.currentFeedbackTab = tab;

  // Update tab UI
  document.querySelectorAll(".tab-btn").forEach((btn) => {
    if (btn.dataset.tab === tab) {
      btn.classList.add("border-primary", "text-primary");
      btn.classList.remove("border-transparent", "text-slate-500");
    } else {
      btn.classList.remove("border-primary", "text-primary");
      btn.classList.add("border-transparent", "text-slate-500");
    }
  });

  const userContainer = document.getElementById("userFeedbackList");
  const bugContainer = document.getElementById("bugFeedbackList");
  const resolvedContainer = document.getElementById("resolvedFeedbackList");
  const mainView = document.getElementById("activeView");
  const resolvedView = document.getElementById("resolvedView");

  if (tab === "resolved") {
    mainView.classList.add("hidden");
    resolvedView.classList.remove("hidden");
  } else {
    mainView.classList.remove("hidden");
    resolvedView.classList.add("hidden");
  }

  try {
    const feedbacks = await EffectaAPI.getFeedbacks(tab);
    window.feedbacksData = feedbacks;

    if (tab === "active") {
      renderActiveFeedbacks(feedbacks);
    } else {
      renderResolvedFeedbacks(feedbacks);
    }
  } catch (err) {
    console.error("Erro ao carregar feedbacks:", err);
    showToast("Erro ao carregar histórico.", "error");
  }
}

function renderActiveFeedbacks(feedbacks) {
  const userContainer = document.getElementById("userFeedbackList");
  const bugContainer = document.getElementById("bugFeedbackList");

  // My Feedbacks (All types from user)
  const myFeedbacks = feedbacks.filter((f) => f.is_owner);
  // Global Bugs (All bugs from anyone)
  const publicBugs = feedbacks.filter((f) => f.type === "bug");

  userContainer.innerHTML = renderList(
    myFeedbacks,
    "Você ainda não enviou nenhum relato.",
  );
  bugContainer.innerHTML = renderList(
    publicBugs,
    "Nenhum erro relatado no momento.",
  );
}

function renderResolvedFeedbacks(feedbacks) {
  const container = document.getElementById("resolvedFeedbackList");
  container.innerHTML = renderList(
    feedbacks,
    "Nenhum feedback resolvido recentemente.",
  );
}

function renderList(list, emptyMsg) {
  if (list.length === 0) {
    return `
            <div class="text-center py-12 text-slate-400 dark:text-slate-500">
                <i class="fa-solid fa-folder-open text-4xl mb-3 opacity-20"></i>
                <p class="text-sm">${emptyMsg}</p>
            </div>`;
  }

  return list
    .map((f) => {
      const date = new Date(f.created_at).toLocaleDateString("pt-BR");
      const typeLabels = {
        bug: "Erro/Bug",
        feature: "Ideia",
        improvement: "Melhoria",
        elogio: "Elogio",
        other: "Outro",
      };

      const typeIcons = {
        bug: "fa-bug text-red-500",
        feature: "fa-lightbulb text-amber-500",
        improvement: "fa-chart-line text-indigo-500",
        elogio: "fa-heart text-pink-500",
        other: "fa-comment text-slate-500",
      };

      let statusClass =
        "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400";
      if (f.status === "analise")
        statusClass =
          "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400";
      if (f.status === "concluido")
        statusClass =
          "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400";

        const devViewedBadge =
        f.viewed_by_dev == 1
          ? `<span class="flex items-center gap-1.5 text-[10px] font-bold text-white bg-indigo-600 dark:bg-indigo-500 px-2.5 py-1 rounded-full shadow-sm animate-pulse">
                <i class="fa-solid fa-check-double text-[8px]"></i> LIDO PELO DEV
               </span>`
          : "";

      return `
            <div class="bg-white dark:bg-slate-800 p-5 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm transition-all hover:shadow-md border-l-4 ${f.viewed_by_dev == 1 ? 'border-l-indigo-500' : 'border-l-transparent'}">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-md ${statusClass} border border-current opacity-80">
                                ${f.status.toUpperCase()}
                            </span>
                            ${devViewedBadge}
                        </div>
                        <h4 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="fa-solid ${typeIcons[f.type] || "fa-comment"} text-xs"></i>
                            ${f.subject}
                        </h4>
                    </div>
                    ${
                      f.is_owner
                        ? `
                    <button onclick="archiveFeedback(${f.id})" class="text-slate-400 hover:text-red-500 transition-colors p-1" title="Retirar da lista">
                        <i class="fa-solid fa-trash-can text-sm"></i>
                    </button>
                    `
                        : ""
                    }
                </div>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-4 line-clamp-3">${f.message}</p>
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-3 text-[11px] text-slate-400">
                        <span class="flex items-center gap-1">
                            <i class="fa-solid fa-calendar"></i> ${date}
                        </span>
                    </div>
                    
                    ${
                      f.type === "bug"
                        ? `
                        <div class="flex items-center gap-2">
                            ${
                              !f.is_owner
                                ? `
                                <button onclick="reportFeedback(${f.id})" 
                                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full transition-all bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400 hover:bg-red-100 border border-red-200"
                                        title="Denunciar bug inapropriado">
                                    <i class="fa-solid fa-flag text-xs"></i>
                                </button>
                                <button onclick="handleLike(${f.id})" 
                                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-full transition-all ${f.user_liked ? "bg-indigo-50 text-primary dark:bg-indigo-900/30" : "bg-slate-50 text-slate-500 dark:bg-slate-700/50 hover:bg-slate-100"}">
                                    <i class="${f.user_liked ? "fa-solid" : "fa-regular"} fa-thumbs-up text-xs"></i>
                                    <span class="text-xs font-bold">${f.likes || 0}</span>
                                </button>
                            `
                                : `
                                <div class="flex items-center gap-1.5 px-3 py-1.5 text-slate-400">
                                    <i class="fa-regular fa-thumbs-up text-xs"></i>
                                    <span class="text-xs font-bold">${f.likes || 0}</span>
                                </div>
                            `
                            }
                        </div>
                    `
                        : ""
                    }
                </div>
            </div>
        `;
    })
    .join("");
}

// Global Dev Alert System
async function checkDevAlerts() {
  const userRole = localStorage.getItem("user_role");
  if (userRole !== "dev") return;

  try {
    const stats = await EffectaAPI.request(
      "api/index.php?action=get_feedback_stats",
    );
    const alertIcon = document.getElementById("globalDevAlert");
    if (alertIcon) {
      if (stats.alerts_count > 0) {
        alertIcon.classList.remove("hidden");
        alertIcon.title = `${stats.alerts_count} feedbacks com denúncias críticas!`;
      } else {
        alertIcon.classList.add("hidden");
      }
    }
  } catch (err) {
    console.error("Erro ao verificar alertas:", err);
  }
}

// Check every 60 seconds
setInterval(checkDevAlerts, 60000);
document.addEventListener("DOMContentLoaded", checkDevAlerts);

window.reportFeedback = async function (id) {
  const confirmed = await showConfirm(
    "Denunciar Bug?",
    "Deseja realmente denunciar este bug? Ao confirmar, este card deixará de aparecer para você permanentemente.",
    "warning"
  );
  if (!confirmed) return;

  try {
    await EffectaAPI.request("api/index.php?action=report_feedback", {
      method: "POST",
      body: JSON.stringify({ id }),
    });
    showToast(
      "Denúncia enviada. O item foi removido da sua visualização.",
      "success",
    );
    await loadFeedbacks(window.currentFeedbackTab);
  } catch (err) {
    console.error("Erro ao denunciar:", err);
    showToast(err.message || "Erro ao enviar denúncia.", "error");
  }
};

window.handleLike = async function (id) {
  try {
    const res = await EffectaAPI.likeFeedback(id);
    if (res.success) {
      await loadFeedbacks(window.currentFeedbackTab);
    }
  } catch (err) {
    console.error("Erro ao dar like:", err);
  }
};

window.archiveFeedback = async function (id) {
  const confirmed = await showConfirm(
    "Arquivar Feedback?",
    "Deseja retirar este feedback da sua lista? Ele continuará no sistema para análise técnica, mas não será mais exibido aqui.",
    "danger"
  );
  if (!confirmed) return;

  try {
    await EffectaAPI.archiveFeedback(id);
    showToast("Feedback retirado da lista.", "success");
    await loadFeedbacks(window.currentFeedbackTab);
  } catch (err) {
    console.error("Erro ao arquivar feedback:", err);
    showToast("Erro ao processar solicitação.", "error");
  }
};
