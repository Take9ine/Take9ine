/* =========================================
   T@ke9ine
   GLOBAL JAVASCRIPT
========================================= */

document.addEventListener("DOMContentLoaded", () => {
    "use strict";

    /* =====================================
       1. THEME MANAGEMENT
    ===================================== */

    const themeToggle = document.querySelector("#theme-toggle");
    const root = document.documentElement;

    const THEME_STORAGE_KEY = "take9ine-theme";

    /**
     * Read the saved theme safely.
     */
    function getSavedTheme() {
        try {
            return localStorage.getItem(THEME_STORAGE_KEY);
        } catch (error) {
            return null;
        }
    }

    /**
     * Save the selected theme safely.
     */
    function saveTheme(theme) {
        try {
            localStorage.setItem(THEME_STORAGE_KEY, theme);
        } catch (error) {
            // Theme switching still works if storage is unavailable.
        }
    }

    /**
     * Apply the selected theme to the entire website.
     * global.css uses html[data-theme="dark"] and
     * html[data-theme="light"] for theme-specific styling.
     */
    function applyTheme(theme, shouldSave = true) {
        const selectedTheme = theme === "dark" ? "dark" : "light";

        // Apply the theme globally.
        root.setAttribute("data-theme", selectedTheme);

        // Keep the body attribute synchronized for compatibility.
        document.body.setAttribute("data-theme", selectedTheme);

        const isDark = selectedTheme === "dark";

        if (themeToggle) {
            themeToggle.setAttribute(
                "aria-label",
                isDark ? "Switch to light mode" : "Switch to dark mode"
            );

            themeToggle.setAttribute("aria-pressed", String(isDark));

            // Update the icon if the HTML contains one.
            const themeIcon = themeToggle.querySelector(
                ".theme-icon, #themeIcon, i"
            );

            if (themeIcon) {
                if (themeIcon.tagName.toLowerCase() === "i") {
                    themeIcon.className = isDark
                        ? "fa-solid fa-sun"
                        : "fa-solid fa-moon";
                    themeIcon.setAttribute("aria-hidden", "true");
                } else {
                    themeIcon.textContent = isDark ? "☀" : "☾";
                }
            }

            // Update an optional text label.
            const themeLabel = themeToggle.querySelector(
                ".theme-label, #themeLabel"
            );

            if (themeLabel) {
                themeLabel.textContent = isDark ? "Light mode" : "Dark mode";
            }
        }

        if (shouldSave) {
            saveTheme(selectedTheme);
        }
    }

    // Restore the previously selected theme, or use light mode.
    const savedTheme = getSavedTheme();

    applyTheme(savedTheme || "light", false);

    // Toggle between dark and light modes.
    if (themeToggle) {
        themeToggle.addEventListener("click", () => {
            const currentTheme = root.getAttribute("data-theme");

            const nextTheme =
                currentTheme === "dark" ? "light" : "dark";

            applyTheme(nextTheme);
        });
    }

    const currentYear = document.querySelector("#current-year");
    if (currentYear) {
        currentYear.textContent = String(new Date().getFullYear());
    }


    /* =====================================
       2. MOBILE NAVIGATION
    ===================================== */

    const menuToggle = document.querySelector("#menu-toggle");
    const navLinks = document.querySelector("#primary-navigation");

    if (menuToggle && navLinks) {
        /**
         * Open or close the mobile navigation menu.
         */
        function setMenuState(isOpen) {
            navLinks.classList.toggle("open", isOpen);

            menuToggle.setAttribute("aria-expanded", String(isOpen));

            menuToggle.setAttribute(
                "aria-label",
                isOpen ? "Close navigation menu" : "Open navigation menu"
            );

            // Support a text-based hamburger icon.
            if (
                menuToggle.children.length === 0 ||
                menuToggle.querySelector(".menu-icon, #menuIcon")
            ) {
                const menuIcon = menuToggle.querySelector(
                    ".menu-icon, #menuIcon"
                );

                if (menuIcon) {
                    menuIcon.textContent = isOpen ? "✕" : "☰";
                } else {
                    menuToggle.textContent = isOpen ? "✕" : "☰";
                }
            }
        }

        // Toggle the menu when the menu button is clicked.
        menuToggle.addEventListener("click", () => {
            const isCurrentlyOpen =
                navLinks.classList.contains("open");

            setMenuState(!isCurrentlyOpen);
        });

        // Close the menu after a navigation link is selected.
        navLinks.querySelectorAll("a").forEach((link) => {
            link.addEventListener("click", () => {
                setMenuState(false);
            });
        });

        // Close the menu when Escape is pressed.
        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                setMenuState(false);
                menuToggle.focus();
            }
        });

        // Close the mobile menu when returning to desktop width.
        window.addEventListener("resize", () => {
            if (window.innerWidth > 768) {
                setMenuState(false);
            }
        });

        // Ensure the menu starts closed.
        setMenuState(false);
    }

});
