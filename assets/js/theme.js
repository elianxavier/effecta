(function () {
  const html = document.documentElement;
  
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

  // Inicializa imediatamente para evitar flash de luz/escuridão
  initTheme();

  document.addEventListener("DOMContentLoaded", () => {
    const themeBtn = document.getElementById("themeToggle");
    if (themeBtn) {
      themeBtn.addEventListener("click", () => {
        html.classList.toggle("dark");
        localStorage.theme = html.classList.contains("dark") ? "dark" : "light";
      });
    }
  });
})();
