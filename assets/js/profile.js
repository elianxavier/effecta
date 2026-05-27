document.addEventListener("DOMContentLoaded", () => {
    loadProfile();

    const profileForm = document.getElementById("profileForm");
    if (profileForm) {
        profileForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalContent = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Salvando...';
            submitBtn.disabled = true;

            try {
                const response = await EffectaAPI.updateMyProfile(data);
                if (response.success) {
                    showToast("Perfil atualizado com sucesso!", "success");
                    // Update local storage if name changed
                    if (data.name) {
                        localStorage.setItem('user_name', data.name);
                        // Optional: trigger re-render of header if name is displayed there
                        // window.location.reload(); 
                    }
                    loadProfile(); // Reload to ensure UI is consistent
                }
            } catch (err) {
                console.error("Erro ao atualizar perfil:", err);
                showToast(err.message || "Erro ao atualizar o perfil.", "error");
            } finally {
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            }
        });
    }

    const passwordForm = document.getElementById("passwordForm");
    if (passwordForm) {
        passwordForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            if (data.new_password !== data.confirm_password) {
                showToast("A nova senha e a confirmação não coincidem.", "error");
                return;
            }

            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalContent = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Alterando...';
            submitBtn.disabled = true;

            try {
                const response = await EffectaAPI.changeMyPassword(data);
                if (response.success) {
                    showToast("Senha alterada com sucesso!", "success");
                    passwordForm.reset(); // Clear form fields
                }
            } catch (err) {
                console.error("Erro ao alterar senha:", err);
                showToast(err.message || "Erro ao alterar a senha.", "error");
            } finally {
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            }
        });
    }
});

async function loadProfile() {
    try {
        const response = await EffectaAPI.getMyProfile();
        if (response.success && response.user) {
            const user = response.user;
            document.getElementById('profileName').value = user.name || '';
            document.getElementById('profileEmail').value = user.email || '';
            document.getElementById('profileRole').value = user.role || '';
            document.getElementById('profileDateOfBirth').value = user.date_of_birth || '';
            document.getElementById('profilePhoneNumber').value = user.phone_number || '';
            // Populate new fields
            document.getElementById('profileGender').value = user.gender || '';
            document.getElementById('profileProfilePictureUrl').value = user.profile_picture_url || '';
            document.getElementById('profileBio').value = user.bio || '';
        }
    } catch (err) {
        console.error("Erro ao carregar perfil:", err);
        showToast(err.message || "Erro ao carregar os dados do perfil.", "error");
    }
}