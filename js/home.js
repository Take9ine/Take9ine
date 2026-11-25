/* =========================================
   T@ke9ine
   HOMEPAGE JAVASCRIPT
========================================= */

document.addEventListener("DOMContentLoaded", () => {
    const currentYear = document.getElementById("current-year");
    if (currentYear) {
        currentYear.textContent = String(new Date().getFullYear());
    }
});
