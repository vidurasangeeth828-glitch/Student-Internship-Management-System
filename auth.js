document.querySelectorAll(".password-toggle").forEach(function (button) {
    button.addEventListener("click", function () {
        var field = document.getElementById(button.getAttribute("aria-controls"));
        var show = field.type === "password";
        field.type = show ? "text" : "password";
        button.setAttribute("aria-pressed", String(show));
        var passwordName = "password";
        if (field.id === "confirm-password") {
            passwordName = "confirm password";
        }
        button.setAttribute("aria-label", (show ? "Hide " : "Show ") + passwordName);
    });
});

var roles = document.querySelectorAll('input[name="role"]');
var companyFields = document.getElementById("company-fields");

function showAccountType() {
    var company = document.querySelector('input[name="role"]:checked').value === "company";
    document.getElementById("name-label").textContent = company ? "Company name" : "Full name";
    var name = document.getElementById("account-name");
    name.placeholder = company ? "Enter your company name" : "Enter your full name";
    name.autocomplete = company ? "organization" : "name";
    companyFields.hidden = !company;
    companyFields.disabled = !company;
    document.getElementById("approval-note").hidden = !company;
}

if (companyFields) {
    roles.forEach(function (role) {
        role.addEventListener("change", showAccountType);
    });
    showAccountType();
}
