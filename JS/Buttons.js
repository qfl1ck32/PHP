const sign_up_button = document.getElementById("login");
const logout_button = document.getElementById("logout");
const home_button = document.getElementById("home");
const about_button = document.getElementById("about");

const online_banking = document.getElementById("onlineBanking");
const settings = document.getElementById("settings");

const admin = document.getElementById('admin')

if (sign_up_button)
    sign_up_button.onclick = () => {
        window.location.href = 'Login.php'
    }

if (logout_button)
    logout_button.onclick = () => {
        window.location.href = 'Logout.php'
    }

if (online_banking)
    online_banking.onclick = () => {
        window.location.href = 'onlineBanking.php'
    }

if (settings)
    settings.onclick = () => {
        window.location.href = 'Settings.php'
    }

if (admin)
    $(admin).click(() => {
        window.location.href = 'Administrate.php'
    })

home_button.onclick = () => {
    window.location.href = '/';
}

about_button.onclick = () => {
    window.location.href = 'About.php';
}