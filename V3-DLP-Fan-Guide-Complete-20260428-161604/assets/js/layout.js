(() => {
    const header = document.getElementById("main-header");
    if (!header) {
        return;
    }

    const root = document.body;
    let lastY = window.scrollY || 0;
    let ticking = false;

    function syncHeader() {
        const currentY = window.scrollY || 0;
        const delta = currentY - lastY;

        root.classList.toggle("header-scrolled", currentY > 8);

        if (currentY <= 24) {
            root.classList.remove("header-hidden");
        } else if (delta > 6) {
            root.classList.add("header-hidden");
        } else if (delta < -6) {
            root.classList.remove("header-hidden");
        }

        lastY = currentY;
        ticking = false;
    }

    window.addEventListener("scroll", () => {
        if (ticking) {
            return;
        }
        ticking = true;
        window.requestAnimationFrame(syncHeader);
    }, { passive: true });

    syncHeader();
})();
