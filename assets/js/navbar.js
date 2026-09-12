document.addEventListener("DOMContentLoaded", function() {
    const navContainer = document.createElement("nav");
    navContainer.className = "navbar";

    // Read real session data passed directly from PHP on protected pages
    const user = window.APP_USER || null;
    const isLoggedIn = user && user.isLoggedIn;

    let authSection = "";

    if (isLoggedIn) {
        // Shown ONLY when a valid PHP session exists (e.g. admin.php)
        authSection = `
            <div class="user-menu-container">
                <button class="user-menu-btn" onclick="toggleUserDropdown(event)">
                    👤 ${user.name} (${user.role}) &#9660;
                </button>
                <div id="userDropdown" class="user-dropdown-content">
                    <div class="dropdown-header">Logged in as: <strong>${user.name}</strong></div>
                    <a href="/elderly_care/logout.php" onclick="handleLogout(event)">🚪 Logout</a>
                </div>
            </div>
        `;
    } else {
        // Default for public pages / when logged out: SHOW LOGIN BUTTON
        authSection = `<a href="/elderly_care/login.php" class="login-btn">Login</a>`;
    }

    navContainer.innerHTML = `
        <div class="navbar-brand">Elderly Care System</div>
        <div class="navbar-links">
            <a href="/elderly_care/index.php">Home</a>
            <a href="/elderly_care/functionalities.php">Functionalities</a>
            <a href="/elderly_care/help.php">Help</a>
            ${authSection}
        </div>
    `;

    document.body.prepend(navContainer);
});

// Toggle dropdown menu visibility
function toggleUserDropdown(event) {
    if (event) event.stopPropagation();
    const dropdown = document.getElementById("userDropdown");
    if (dropdown) {
        dropdown.classList.toggle("show");
    }
}

// Close dropdown if user clicks anywhere outside
window.addEventListener('click', function(event) {
    if (!event.target.matches('.user-menu-btn')) {
        const dropdowns = document.getElementsByClassName("user-dropdown-content");
        for (let i = 0; i < dropdowns.length; i++) {
            let openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) {
                openDropdown.classList.remove('show');
            }
        }
    }
});

// Handle Logout execution
function handleLogout(event) {
    window.location.href = "/elderly_care/logout.php";
}