document.addEventListener('DOMContentLoaded', function() {
    const usernameInput = document.querySelector('input[name="username"]');
    const emailInput = document.querySelector('input[name="email"]');

    async function checkField(input, checkUrl) {
        try {
            const value = encodeURIComponent(input.value.trim());
            if (!value) return;

            const response = await fetch(`${checkUrl}?${input.name}=${value}`);
            if (!response.ok) throw new Error('Network response was not ok');
            
            const data = await response.json();
            if (data.exists) {
                input.setCustomValidity(`This ${input.name} already exists`);
            } else {
                input.setCustomValidity('');
            }
            input.reportValidity();
        } catch (error) {
            console.error('Validation error:', error);
        }
    }

    if (usernameInput) {
        usernameInput.addEventListener('blur', () => checkField(usernameInput, 'check_username.php'));
    }

    if (emailInput) {
        emailInput.addEventListener('blur', () => checkField(emailInput, 'check_email.php'));
    }
});