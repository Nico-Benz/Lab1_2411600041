document.addEventListener('DOMContentLoaded', function() {
    
    const loginBtn = document.getElementById('loginBtn');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const feedbackDiv = document.getElementById('loginFeedback');


    // I add function of my remeber me
    const rememberCheckbox = document.getElementById('rememberMe');
    const savedUsername = localStorage.getItem('rememberedUsername');
    if (savedUsername) {
    usernameInput.value = savedUsername;
    rememberCheckbox.checked = true;
    }


    if (localStorage.getItem('isLoggedIn') === 'true') {
        window.location.href = 'dashboard.html';
    }

    loginBtn.addEventListener('click', function() {
        const username = usernameInput.value.trim();
        const password = passwordInput.value.trim();

        feedbackDiv.innerHTML = '';

        if (username === '' || password === '') {
            showFeedback('Please enter both username and password.', 'danger');
            return;
        }

        const validUsername = 'admin';
        const validPassword = 'password123';

        if (username === validUsername && password === validPassword) {
            localStorage.setItem('isLoggedIn', 'true');
            localStorage.setItem('user', username);

            // also this the added code
        if (rememberCheckbox.checked) {
             localStorage.setItem('rememberedUsername', username);
        } else {
             localStorage.removeItem('rememberedUsername');
        }
           

            showFeedback('Login successful! Redirecting...', 'success');

            setTimeout(function() {
                window.location.href = 'dashboard.html';
            }, 1000);
        } else {
            showFeedback('Invalid username or password. Please try again.', 'danger');
        }
    });

    function showFeedback(message, type) {
        const alertClass = type === 'danger' ? 'alert-danger' : 'alert-success';
        feedbackDiv.innerHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
    }

    usernameInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loginBtn.click();
        }
    });

    passwordInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loginBtn.click();
        }
    });
});