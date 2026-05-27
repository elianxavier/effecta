const EffectaAPI = {
  baseUrl: "api/index.php",

  async getRegisters() {
    const res = await fetch(`${this.baseUrl}?action=get_registers`);
    return await res.json();
  },

  async getPeople() {
    const res = await fetch(`${this.baseUrl}?action=get_people`);
    return await res.json();
  },

  async getProjects() {
    const res = await fetch(`${this.baseUrl}?action=get_projects`);
    return await res.json();
  },

  async addPerson(name) {
    const res = await fetch(`${this.baseUrl}?action=add_person`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ name }),
    });
    return await res.json();
  },

  async addProject(name) {
    const res = await fetch(`${this.baseUrl}?action=add_project`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ name }),
    });
    return await res.json();
  },

  async saveRegister(data) {
    const res = await fetch(`${this.baseUrl}?action=save_register`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data),
    });
    return await res.json();
  }
};
