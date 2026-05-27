// Global state for users
window.usersData = [];

document.addEventListener("DOMContentLoaded", () => {
    loadUsers();

    const userForm = document.getElementById("userForm");
    if (userForm) {
        userForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            // Handle checkbox for active status
            data.active = e.target.querySelector('#userActive').checked;

            const userId = document.getElementById("userIdHidden").value;

            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalContent = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Salvando...';
            submitBtn.disabled = true;

            try {
                let response;
                if (userId) {
                    // Update user
                    response = await EffectaAPI.updateUser(userId, data);
                    showToast("Usuário atualizado com sucesso!", "success");
                } else {
                    // Create user
                    response = await EffectaAPI.createUser(data);
                    showToast("Usuário criado com sucesso!", "success");
                }
                closeUserModal();
                await loadUsers();
            } catch (err) {
                console.error("Erro ao salvar usuário:", err);
                showToast(err.message || "Erro ao salvar o usuário.", "error");
            } finally {
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            }
        });
    }
});

async function loadUsers() {
    try {
        window.usersData = await EffectaAPI.getUsers();
        renderUsers(window.usersData);
    } catch (err) {
        console.error("Erro ao carregar usuários:", err);
        showToast(err.message || "Erro ao carregar a lista de usuários.", "error");
        renderUsers([]); // Clear table on error
    }
}

function renderUsers(users) {
    const tableBody = document.getElementById("userTableBody");
    tableBody.innerHTML = "";

    if (users.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="px-6 py-4 text-center text-slate-500 dark:text-slate-400">Nenhum usuário encontrado.</td>
            </tr>
        `;
        return;
    }

    users.forEach(user => {
        const row = tableBody.insertRow();
        row.className = 'hover:bg-slate-50 dark:hover:bg-slate-700/50';

        row.insertCell(0).textContent = user.name;
        row.insertCell(1).textContent = user.email;
        row.insertCell(2).textContent = user.role === 'admin' ? 'Administrador' : 'Comum';

        const statusCell = row.insertCell(3);
        const statusBadge = document.createElement('span');
        statusBadge.className = `px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${user.active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'}`;
        statusBadge.textContent = user.active ? 'Ativo' : 'Inativo';
        statusCell.appendChild(statusBadge);

        const actionsCell = row.insertCell(4);
        actionsCell.className = 'px-6 py-4 whitespace-nowrap text-right text-sm font-medium';
        actionsCell.innerHTML = `
            <button onclick="openUserModal('edit', '${user.id}')" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3" title="Editar Usuário">
                <i class="fa-solid fa-pencil"></i>
            </button>
            <button onclick="toggleUserStatus('${user.id}', ${user.active})" class="${user.active ? 'text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300' : 'text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300'}" title="${user.active ? 'Desativar Usuário' : 'Ativar Usuário'}">
                <i class="fa-solid ${user.active ? 'fa-user-slash' : 'fa-user-check'}"></i>
            </button>
        `;
    });
}

// Modal functions
window.openUserModal = function (mode, userId = null) {
    const userModal = document.getElementById("userModal");
    const userForm = document.getElementById("userForm");
    const userModalTitle = document.getElementById("userModalTitle");

    userForm.reset();
    userForm.querySelector('#userIdHidden').value = '';
    userForm.querySelector('#userPassword').required = (mode === 'create'); // Password required only for create

    if (mode === 'create') {
        userModalTitle.textContent = "Adicionar Novo Usuário";
        userForm.querySelector('#userActive').checked = true; // Default to active for new users
        // Clear new fields for new user creation
        userForm.querySelector('#userDateOfBirth').value = '';
        userForm.querySelector('#userPhoneNumber').value = '';
        userForm.querySelector('#userGender').value = '';
        userForm.querySelector('#userProfilePictureUrl').value = '';
        userForm.querySelector('#userBio').value = '';
    } else if (mode === 'edit' && userId) {
        userModalTitle.textContent = "Editar Usuário";
        const user = window.usersData.find(u => String(u.id) === String(userId));
        if (user) {
            userForm.querySelector('#userIdHidden').value = user.id;
            userForm.querySelector('#userName').value = user.name;
            userForm.querySelector('#userEmail').value = user.email;
            userForm.querySelector('#userRole').value = user.role;
            userForm.querySelector('#userActive').checked = user.active;
            // Populate new fields
            userForm.querySelector('#userDateOfBirth').value = user.date_of_birth || '';
            userForm.querySelector('#userPhoneNumber').value = user.phone_number || '';
            userForm.querySelector('#userGender').value = user.gender || '';
            userForm.querySelector('#userProfilePictureUrl').value = user.profile_picture_url || '';
            userForm.querySelector('#userBio').value = user.bio || '';
        }
    }
    userModal.classList.remove("hidden");
    document.body.style.overflow = "hidden";
};

window.closeUserModal = function () {
    const userModal = document.getElementById("userModal");
    userModal.classList.add("hidden");
    document.body.style.overflow = "";
    document.getElementById("userForm").reset();
    document.getElementById("userForm").querySelector('#userIdHidden').value = '';
};

window.toggleUserStatus = async function (userId, currentStatus) {
    const action = currentStatus ? "desativar" : "ativar";
    if (!confirm(`Tem certeza que deseja ${action} este usuário?`)) {
        return;
    }

    try {
        await EffectaAPI.toggleUserStatus(userId, !currentStatus);
        showToast(`Usuário ${action}do com sucesso!`, "success");
        await loadUsers(); // Reload users to reflect changes
    } catch (err) {
        console.error(`Erro ao ${action} usuário:`, err);
        showToast(err.message || `Erro ao ${action} o usuário.`, "error");
    }
};