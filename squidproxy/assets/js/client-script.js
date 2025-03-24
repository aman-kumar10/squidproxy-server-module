let isPasswordVisible = false;

    function togglePassword() {
        const passwordField = document.getElementById("passwordField");
        const eyeIcon = document.getElementById("eyeIcon");
        const toggleButton = document.getElementById("togglePassword");

        if (isPasswordVisible) {
            passwordField.textContent = ".................";
            eyeIcon.classList.remove("fa-eye-slash");
            eyeIcon.classList.add("fa-eye");
        } else {
            const password = toggleButton.getAttribute("data-password");
            passwordField.textContent = password;
            eyeIcon.classList.remove("fa-eye");
            eyeIcon.classList.add("fa-eye-slash");
        }

        isPasswordVisible = !isPasswordVisible;
    }