(() => {
    const root = document.querySelector("[data-home-dock]");
    if (!root) {
        return;
    }

    const tabs = Array.from(root.querySelectorAll("[data-home-tab]"));
    const panels = Array.from(root.querySelectorAll("[data-home-panel]"));

    if (!tabs.length || !panels.length) {
        return;
    }

    function activate(name) {
        tabs.forEach((tab) => {
            const active = tab.dataset.homeTab === name;
            tab.classList.toggle("is-active", active);
            tab.setAttribute("aria-selected", active ? "true" : "false");
        });

        panels.forEach((panel) => {
            panel.hidden = panel.dataset.homePanel !== name;
        });
    }

    tabs.forEach((tab) => {
        tab.addEventListener("click", () => activate(tab.dataset.homeTab));
    });

    const defaultTab = tabs.find((tab) => tab.classList.contains("is-active"))?.dataset.homeTab || "waits";
    activate(defaultTab);
})();
