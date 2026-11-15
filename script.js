var menuButton = document.querySelector(".menu-toggle");
var navigation = document.getElementById("main-navigation");

function closeMenu() {
    navigation.classList.remove("is-open");
    menuButton.setAttribute("aria-expanded", "false");
    menuButton.setAttribute("aria-label", "Open navigation menu");
}

menuButton.addEventListener("click", function () {
    var isOpen = navigation.classList.toggle("is-open");
    menuButton.setAttribute("aria-expanded", String(isOpen));
    menuButton.setAttribute("aria-label", isOpen ? "Close navigation menu" : "Open navigation menu");
});

navigation.addEventListener("click", function (event) {
    if (event.target.closest("a, button")) {
        closeMenu();
    }
});

document.addEventListener("click", function (event) {
    if (!event.target.closest(".header")) {
        closeMenu();
    }
});

document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && navigation.classList.contains("is-open")) {
        closeMenu();
        menuButton.focus();
    }
});

window.matchMedia("(max-width: 600px)").addEventListener("change", closeMenu);

