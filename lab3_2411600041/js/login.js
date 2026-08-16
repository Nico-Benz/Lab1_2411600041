// login.js — handles the login form and simulates authentication

// Demo credentials (for this school exercise only — not real authentication)
const DEMO_USERNAME = "admin";
const DEMO_PASSWORD = "password123";

const loginBtn = document.getElementById("loginBtn");
const usernameInput = document.getElementById("username");
const passwordInput = document.getElementById("password");
const loginAlert = document.getElementById("loginAlert");

// Event Listener #1: respond when the Log In button is clicked
loginBtn.addEventListener("click", function () {
  const username = usernameInput.value.trim();
  const password = passwordInput.value.trim();

  if (username === DEMO_USERNAME && password === DEMO_PASSWORD) {
    // Store login state and username in localStorage
    localStorage.setItem("isLoggedIn", "true");
    localStorage.setItem("username", username);

    // Redirect to the dashboard
    window.location.href = "dashboard.html";
  } else {
    // Show error alert
    loginAlert.textContent = "Invalid username or password. Please try again.";
    loginAlert.classList.remove("d-none");
  }
});

// Event Listener #2: allow pressing "Enter" inside the password field to log in
passwordInput.addEventListener("keydown", function (event) {
  if (event.key === "Enter") {
    loginBtn.click();
  }
});