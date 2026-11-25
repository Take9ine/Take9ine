document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("dashboard-search-input");
    const clearButton = document.getElementById("dashboard-search-clear");
    const searchStatus = document.getElementById("dashboard-search-status");

    if (!searchInput || !clearButton || !searchStatus) {
        return;
    }

    const panels = Array.from(document.querySelectorAll(".data-panel"));
    const panelLinks = new Map(
        panels.map(function (panel) {
            return [panel.id, document.querySelector('.admin-tabs a[href="#' + panel.id + '"]')];
        })
    );

    function searchableText(row) {
        const values = [row.textContent || ""];
        row.querySelectorAll("input, select, textarea").forEach(function (field) {
            if (
                field.type === "password" ||
                field.type === "hidden" ||
                field.type === "submit" ||
                field.type === "button"
            ) {
                return;
            }

            if (field.tagName === "SELECT") {
                const selectedOption = field.options[field.selectedIndex];
                values.push(selectedOption ? selectedOption.text : "");
            } else if (field.type === "checkbox" || field.type === "radio") {
                if (field.checked) {
                    values.push(field.value);
                }
            } else {
                values.push(field.value || "");
            }
        });

        return values.join(" ").toLocaleLowerCase();
    }

    function filterDashboard() {
        const query = searchInput.value.trim().toLocaleLowerCase();
        let totalRecords = 0;
        let matchingRecords = 0;
        let matchingSections = 0;

        panels.forEach(function (panel) {
            const rows = Array.from(panel.querySelectorAll("tbody tr"));
            let panelMatches = 0;
            totalRecords += rows.length;

            rows.forEach(function (row) {
                const matches = !query || searchableText(row).includes(query);
                row.classList.toggle("search-hidden", !matches);
                if (matches) {
                    panelMatches += 1;
                }
            });

            matchingRecords += panelMatches;
            const hasMatches = !query || panelMatches > 0;
            panel.hidden = !hasMatches;

            const link = panelLinks.get(panel.id);
            if (link) {
                link.hidden = !hasMatches;
            }

            if (query && panelMatches > 0) {
                matchingSections += 1;
            }
        });

        clearButton.hidden = query === "";

        if (!query) {
            searchStatus.textContent =
                "Search across all " + totalRecords + " records currently loaded from the database.";
        } else if (matchingRecords === 0) {
            searchStatus.textContent = "No matching records found.";
        } else {
            searchStatus.textContent =
                matchingRecords + " matching record" +
                (matchingRecords === 1 ? "" : "s") +
                " across " + matchingSections + " section" +
                (matchingSections === 1 ? "" : "s") + ".";
        }
    }

    searchInput.addEventListener("input", filterDashboard);
    clearButton.addEventListener("click", function () {
        searchInput.value = "";
        filterDashboard();
        searchInput.focus();
    });

    filterDashboard();
});
