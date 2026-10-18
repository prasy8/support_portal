// ========================================
// Display Message
// ========================================

function showMessage(message, type = "info") {

    const messageElement = document.getElementById("message");

    if (!messageElement) {

        return;

    }

    messageElement.innerHTML = `

        <div class="alert alert-${type}">

            ${escapeHtml(message)}

        </div>

    `;

}

// ========================================
// HTML Escape Function
// ========================================

function escapeHtml(value) {

    return String(value)

        .replace(/&/g, "&amp;")

        .replace(/</g, "&lt;")

        .replace(/>/g, "&gt;")

        .replace(/"/g, "&quot;")

        .replace(/'/g, "&#039;");

}

function getUrlParameter(name) {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
}
