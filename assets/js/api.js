const EffectaAPI = {
  baseUrl: "api/index.php",
  isRefreshing: false,
  refreshQueue: [],

  async request(url, options = {}) {
    options.headers = options.headers || {};
    
    // Obtém o token de acesso
    let accessToken = localStorage.getItem("access_token");
    if (accessToken) {
      options.headers["Authorization"] = `Bearer ${accessToken}`;
    }

    let res = await fetch(url, options);

    // Se retornar 401 (Não Autorizado), tenta renovar o Access Token usando o Refresh Token
    if (res.status === 401) {
      if (this.isRefreshing) {
        return new Promise((resolve, reject) => {
          this.refreshQueue.push({
            resolve: async (newAccessToken) => {
              options.headers["Authorization"] = `Bearer ${newAccessToken}`;
              try {
                const retryRes = await fetch(url, options);
                if (!retryRes.ok) {
                  const errData = await retryRes.json().catch(() => ({}));
                  reject(new Error(errData.error || `Erro de HTTP: ${retryRes.status}`));
                } else {
                  resolve(await retryRes.json());
                }
              } catch (err) {
                reject(err);
              }
            },
            reject: (err) => {
              reject(err);
            }
          });
        });
      }

      this.isRefreshing = true;

      const refreshed = await this.refreshToken();
      if (refreshed) {
        this.isRefreshing = false;
        const newAccessToken = localStorage.getItem("access_token");
        
        // Resolve a fila de requisições pendentes
        const currentQueue = [...this.refreshQueue];
        this.refreshQueue = [];
        currentQueue.forEach(item => item.resolve(newAccessToken));

        // Refaz a chamada original com o novo token de acesso
        options.headers["Authorization"] = `Bearer ${newAccessToken}`;
        res = await fetch(url, options);
      } else {
        this.isRefreshing = false;
        
        // Rejeita a fila de requisições pendentes
        const error = new Error("Sessao expirada. Por favor, faca login novamente.");
        const currentQueue = [...this.refreshQueue];
        this.refreshQueue = [];
        currentQueue.forEach(item => item.reject(error));

        // Se falhar o refresh (token expirou ou revogado), desloga
        this.clearSessionAndRedirect();
        throw error;
      }
    }

    if (!res.ok) {
      const errData = await res.json().catch(() => ({}));
      throw new Error(errData.error || `Erro de HTTP: ${res.status}`);
    }

    return await res.json();
  },

  async refreshToken() {
    try {
      const res = await fetch(`${this.baseUrl}?action=refresh`, {
        method: "POST",
        headers: { "Content-Type": "application/json" }
      });

      if (!res.ok) return false;

      const data = await res.json();
      
      // Armazena a nova sessão rotacionada
      localStorage.setItem("access_token", data.access_token);
      localStorage.setItem("user_name", data.user.name);
      localStorage.setItem("user_role", data.user.role);

      // Define cookie para validação do roteador PHP
      document.cookie = `access_token=${data.access_token}; max-age=900; path=/`;
      return true;
    } catch (err) {
      return false;
    }
  },

  clearSessionAndRedirect() {
    localStorage.removeItem("access_token");
    localStorage.removeItem("user_name");
    localStorage.removeItem("user_role");
    
    // Deleta o cookie do access token
    document.cookie = "access_token=; max-age=0; path=/";
    
    window.location.href = "index.php?page=login";
  },

  async getRegisters() {
    return this.request(`${this.baseUrl}?action=get_registers`);
  },

  async getPeople() {
    return this.request(`${this.baseUrl}?action=get_people`);
  },

  async getProjects() {
    return this.request(`${this.baseUrl}?action=get_projects`);
  },

  async addPerson(name) {
    return this.request(`${this.baseUrl}?action=add_person`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ name }),
    });
  },

  async addProject(name) {
    return this.request(`${this.baseUrl}?action=add_project`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ name }),
    });
  },

  async saveRegister(data) {
    return this.request(`${this.baseUrl}?action=save_register`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    });
  },

  async updateRegister(id, data) {
    return this.request(`${this.baseUrl}?action=update_register`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id, ...data }),
    });
  },

  async deleteRegister(id) {
    return this.request(`${this.baseUrl}?action=delete_register`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id }),
    });
  },

  // User Management
  async getUsers() {
    return this.request(`${this.baseUrl}?action=get_users`);
  },

  async createUser(data) {
    return this.request(`${this.baseUrl}?action=create_user`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    });
  },

  async updateUser(id, data) {
    return this.request(`${this.baseUrl}?action=update_user`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id, ...data }),
    });
  },

  async toggleUserStatus(id, activeStatus) {
    return this.request(`${this.baseUrl}?action=toggle_user_status`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id, active: activeStatus }),
    });
  },

  // User Profile Management
  async getMyProfile() {
    return this.request(`${this.baseUrl}?action=get_my_profile`);
  },

  async updateMyProfile(data) {
    return this.request(`${this.baseUrl}?action=update_my_profile`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    });
  },

  async changeMyPassword(data) {
    return this.request(`${this.baseUrl}?action=change_my_password`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    });
  },

  async getExportData() {
    return this.request(`${this.baseUrl}?action=get_export_data`);
  },

  async importData(data) {
    return this.request(`${this.baseUrl}?action=import_data`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    });
  }
};
