var sidebar = document.getElementById("student-sidebar");
var openButton = document.querySelector(".sidebar-open");
var closeButton = document.querySelector(".sidebar-close");
var overlay = document.querySelector(".sidebar-overlay");
var mobileScreen = window.matchMedia("(max-width: 850px)");

function closeSidebar() {
    sidebar.classList.remove("is-open");
    openButton.setAttribute("aria-expanded", "false");
    overlay.hidden = true;
    document.body.classList.remove("menu-open");
    sidebar.inert = mobileScreen.matches;
}

openButton.addEventListener("click", function () {
    sidebar.inert = false;
    sidebar.classList.add("is-open");
    openButton.setAttribute("aria-expanded", "true");
    overlay.hidden = false;
    document.body.classList.add("menu-open");
    closeButton.focus();
});

closeButton.addEventListener("click", function () {
    closeSidebar();
    openButton.focus();
});

overlay.addEventListener("click", function () {
    closeSidebar();
    openButton.focus();
});

sidebar.querySelectorAll('a[href^="#"]').forEach(function (link) {
    link.addEventListener("click", function () {
        closeSidebar();
        if (mobileScreen.matches) {
            var section = document.querySelector(link.getAttribute("href"));
            section.setAttribute("tabindex", "-1");
            section.focus();
        }
    });
});

document.addEventListener("keydown", function (event) {
    if (!mobileScreen.matches || !sidebar.classList.contains("is-open")) {
        return;
    }
    if (event.key === "Escape") {
        closeSidebar();
        openButton.focus();
    }
    if (event.key === "Tab") {
        var controls = sidebar.querySelectorAll("a, button");
        var first = controls[0];
        var last = controls[controls.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }
});

mobileScreen.addEventListener("change", closeSidebar);
closeSidebar();
