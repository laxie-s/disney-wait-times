(() => {
    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

    function readJsonScript(id) {
        const node = document.getElementById(id);
        if (!node) {
            return null;
        }
        try {
            return JSON.parse(node.textContent);
        } catch (error) {
            return null;
        }
    }

    function storageGet(key, fallback) {
        try {
            const raw = window.localStorage.getItem(key);
            return raw ? JSON.parse(raw) : fallback;
        } catch (error) {
            return fallback;
        }
    }

    function storageSet(key, value) {
        try {
            window.localStorage.setItem(key, JSON.stringify(value));
        } catch (error) {
            return;
        }
    }

    function formatEuro(value) {
        return new Intl.NumberFormat("fr-FR", {
            style: "currency",
            currency: "EUR",
            maximumFractionDigits: 0,
        }).format(value);
    }

    function toMinutes(value) {
        if (!/^\d{2}:\d{2}$/.test(value || "")) {
            return null;
        }
        const [hours, minutes] = value.split(":").map(Number);
        return (hours * 60) + minutes;
    }

    function initFoodFilters() {
        const root = $("[data-food-filters]");
        if (!root) {
            return;
        }

        const buttons = $$("[data-filter-group]", root);
        const cards = $$("[data-food-card]");
        const state = {
            diet: "all",
            service: "all",
            land: "all",
        };

        function render() {
            buttons.forEach((button) => {
                const group = button.dataset.filterGroup;
                const value = button.dataset.filterValue;
                const active = state[group] === value;
                button.classList.toggle("is-active", active);
                button.classList.toggle("active", active);
            });

            cards.forEach((card) => {
                const dietVisible = state.diet === "all" || card.dataset[state.diet] === "1";
                const serviceVisible = state.service === "all" || card.dataset.service === state.service;
                const landVisible = state.land === "all" || card.dataset.landKey === state.land;
                const visible = dietVisible && serviceVisible && landVisible;
                card.classList.toggle("is-hidden", !visible);
            });
        }

        buttons.forEach((button) => {
            button.addEventListener("click", () => {
                state[button.dataset.filterGroup] = button.dataset.filterValue;
                render();
            });
        });

        render();
    }

    function initBudgetCalculator() {
        const root = $("[data-budget-calculator]");
        if (!root) {
            return;
        }

        const inputs = $$("[data-budget-field]", root);
        const totalNode = $("[data-budget-total]", root);
        const breakdownNode = $("[data-budget-breakdown]", root);
        const prices = {
            quickAdult: Number(root.dataset.quickAdult),
            quickKid: Number(root.dataset.quickKid),
            tableAdult: Number(root.dataset.tableAdult),
            tableKid: Number(root.dataset.tableKid),
            signatureAdult: Number(root.dataset.signatureAdult),
            signatureKid: Number(root.dataset.signatureKid),
            snack: Number(root.dataset.snackPrice),
        };

        function readValues() {
            const values = {};
            inputs.forEach((input) => {
                values[input.dataset.budgetField] = Math.max(0, Number(input.value) || 0);
            });
            return values;
        }

        function update() {
            const values = readValues();
            const adultTotal = values.adults * (
                values.quickMeals * prices.quickAdult +
                values.tableMeals * prices.tableAdult +
                values.signatureMeals * prices.signatureAdult +
                values.snacks * prices.snack
            );
            const kidTotal = values.kids * (
                values.quickMeals * prices.quickKid +
                values.tableMeals * prices.tableKid +
                values.signatureMeals * prices.signatureKid +
                values.snacks * prices.snack
            );

            totalNode.textContent = formatEuro(adultTotal + kidTotal);
            breakdownNode.textContent = `${values.adults} adulte(s), ${values.kids} enfant(s), ${values.quickMeals} repas rapide(s), ${values.tableMeals} repas a table, ${values.signatureMeals} repas premium et ${values.snacks} snack(s) par personne.`;
        }

        inputs.forEach((input) => input.addEventListener("input", update));
        update();
    }

    function initTrendChart() {
        const root = $("[data-trend-app]");
        if (!root) {
            return;
        }

        const dataset = readJsonScript("trend-dataset");
        if (!dataset || !dataset.length) {
            return;
        }

        const select = $("[data-trend-select]", root);
        const svg = $("[data-trend-svg]", root);
        const axis = $("[data-trend-axis]", root);
        const status = $("[data-trend-status]", root);
        const caption = $("[data-trend-caption]", root);
        const width = 720;
        const height = 260;
        const padding = { top: 18, right: 24, bottom: 24, left: 28 };

        function render(id) {
            const item = dataset.find((entry) => entry.id === id) || dataset[0];
            const maxWait = Math.max(...item.points.map((point) => point.wait), 20);
            const innerWidth = width - padding.left - padding.right;
            const innerHeight = height - padding.top - padding.bottom;

            const points = item.points.map((point, index) => {
                const x = padding.left + (innerWidth / Math.max(item.points.length - 1, 1)) * index;
                const y = padding.top + innerHeight - ((point.wait / maxWait) * innerHeight);
                return { ...point, x, y };
            });

            const line = points.map((point) => `${point.x},${point.y}`).join(" ");
            const area = [
                `${padding.left},${height - padding.bottom}`,
                ...points.map((point) => `${point.x},${point.y}`),
                `${points[points.length - 1].x},${height - padding.bottom}`,
            ].join(" ");

            const gridLines = [0.25, 0.5, 0.75].map((ratio) => {
                const y = padding.top + innerHeight * ratio;
                return `<line x1="${padding.left}" y1="${y}" x2="${width - padding.right}" y2="${y}" />`;
            }).join("");

            const pointNodes = points.map((point) => `<g><circle cx="${point.x}" cy="${point.y}" r="4.5"></circle><text x="${point.x}" y="${point.y - 12}" text-anchor="middle">${point.wait}</text></g>`).join("");

            svg.innerHTML = `
                <g class="trend-grid">${gridLines}</g>
                <polygon class="trend-area" points="${area}"></polygon>
                <polyline class="trend-line" points="${line}"></polyline>
                <g class="trend-points">${pointNodes}</g>
            `;

            axis.innerHTML = item.points.map((point) => `<span>${point.label}</span>`).join("");

            const directionLabels = { monte: "Ca risque de monter", redescend: "Ca devrait se detendre", stable: "Ca semble plutot stable" };
            const basisLabel = item.basis === "history" ? `Base observee sur ${item.days || 0} jour(s)` : "Base estimative";
            status.textContent = directionLabels[item.direction] || directionLabels.stable;
            caption.textContent = `${item.name} tourne autour de ${item.typical_wait} min d habitude. ${basisLabel}. Seuil vise: ${item.target_wait} min.`;
            select.value = item.id;
        }

        select.addEventListener("change", () => render(select.value));
        render(select.value || dataset[0].id);
    }

    function initThresholdPlanner() {
        const root = $("[data-threshold-planner]");
        const dataset = readJsonScript("trend-dataset");
        if (!root || !dataset || !Array.isArray(dataset) || !dataset.length) {
            return;
        }

        const select = $("[data-threshold-select]", root);
        const input = $("[data-threshold-target]", root);
        const feedback = $("[data-threshold-feedback]", root);

        function findItem(id) {
            return dataset.find((entry) => entry.id === id) || dataset[0];
        }

        function render() {
            const item = findItem(select.value || dataset[0].id);
            const target = Math.max(5, Number(input.value) || item.target_wait || 15);
            const great = Number(item.great_wait || 10);
            const recommended = Number(item.target_wait || 15);
            const typical = Number(item.typical_wait || recommended);

            let label = "Lecture confortable";
            let copy = "Tu gardes une alerte assez large, utile si tu veux surtout simplifier la decision du moment.";

            if (target <= great) {
                label = "Objectif tres ambitieux";
                copy = "Tu vises une vraie fenetre premium. C est excellent si cette attraction est une priorite absolue.";
            } else if (target <= recommended) {
                label = "Bon slot fan";
                copy = "Tu es dans la fenetre que le site recommande pour agir sans trop hesiter.";
            } else if (target <= typical) {
                label = "Choix realiste";
                copy = "Tu restes sous la lecture standard de la file, donc le seuil garde une vraie utilite terrain.";
            }

            feedback.innerHTML = `
                <strong>${label}</strong>
                <span>${item.name} tourne plutot autour de ${typical} min. Reco fan: viser ${recommended} min, et tres bon slot sous ${great} min.</span>
                <small>${copy}</small>
            `;
            select.value = item.id;
        }

        if (select && input) {
            select.addEventListener("change", () => {
                const item = findItem(select.value);
                input.value = item.target_wait;
                render();
            });
            input.addEventListener("input", render);
            render();
        }
    }

    function bindCheckboxBoard(rootSelector, checkboxSelector, storageKey, progressSelector) {
        const root = $(rootSelector);
        if (!root) {
            return;
        }

        const checkboxes = $$(checkboxSelector, root);
        const progressNode = progressSelector ? $(progressSelector) : null;
        const state = storageGet(storageKey, {});

        function updateProgress() {
            const completed = checkboxes.filter((input) => input.checked).length;
            if (progressNode) {
                progressNode.textContent = String(completed);
            }
        }

        checkboxes.forEach((input) => {
            const key = input.dataset.checklistKey || input.dataset.secretKey;
            input.checked = Boolean(state[key]);
            input.addEventListener("change", () => {
                state[key] = input.checked;
                storageSet(storageKey, state);
                updateProgress();
            });
        });

        updateProgress();
    }

    function initRatings() {
        const root = $("[data-rating-board]");
        if (!root) {
            return;
        }

        const cards = $$("[data-rating-card]", root);
        const leaderboard = $("[data-rating-leaderboard]");
        const localRatings = storageGet("dlp-fan-guide-ratings", {});

        function computeAverage(baseRating, baseVotes, localValue) {
            if (!localValue) {
                return { average: baseRating, votes: baseVotes };
            }
            const localWeight = 6;
            return {
                average: ((baseRating * baseVotes) + (localValue * localWeight)) / (baseVotes + localWeight),
                votes: baseVotes + localWeight,
            };
        }

        function updateCard(card) {
            const id = card.dataset.ratingId;
            const baseRating = Number(card.dataset.baseRating);
            const baseVotes = Number(card.dataset.baseVotes);
            const localValue = Number(localRatings[id] || 0);
            const summary = computeAverage(baseRating, baseVotes, localValue);

            $("[data-rating-average]", card).textContent = `${summary.average.toFixed(1)}/5`;
            $("[data-rating-votes]", card).textContent = `${summary.votes} avis fan`;

            $$("[data-rating-value]", card).forEach((button) => {
                const value = Number(button.dataset.ratingValue);
                const active = localValue >= value;
                button.classList.toggle("is-active", active);
                button.setAttribute("aria-pressed", active ? "true" : "false");
            });

            return {
                id,
                name: card.dataset.name,
                type: card.dataset.type,
                zone: card.dataset.zone,
                average: summary.average,
                votes: summary.votes,
            };
        }

        function updateLeaderboard() {
            const ranking = cards.map(updateCard).sort((left, right) => {
                if (right.average === left.average) {
                    return right.votes - left.votes;
                }
                return right.average - left.average;
            }).slice(0, 10);

            leaderboard.innerHTML = ranking.map((item, index) => `
                <li>
                    <strong>${index + 1}. ${item.name}</strong>
                    <span>${item.average.toFixed(1)}/5</span>
                    <small>${item.type} - ${item.zone}</small>
                </li>
            `).join("");
        }

        cards.forEach((card) => {
            $$("[data-rating-value]", card).forEach((button) => {
                button.addEventListener("click", () => {
                    localRatings[card.dataset.ratingId] = Number(button.dataset.ratingValue);
                    storageSet("dlp-fan-guide-ratings", localRatings);
                    updateLeaderboard();
                });
            });
        });

        updateLeaderboard();
    }

    function initWaitAlerts() {
        const root = $("[data-alert-board]");
        const dataset = readJsonScript("wait-alert-dataset");
        if (!root || !dataset || !Array.isArray(dataset.items)) {
            return;
        }

        const endpoint = root.dataset.alertEndpoint || dataset.endpoint;
        const liveList = $("[data-alert-live-list]", root);
        const permissionButton = $("[data-alert-permission]", root);
        const quickButtons = $$("[data-watch-action]");
        const select = $("[data-alert-select]", root);
        const thresholdInput = $("[data-alert-threshold]", root);
        const recommendation = $("[data-alert-recommendation]", root);
        const addWaitButton = $("[data-alert-add-wait]", root);
        const addReopenButton = $("[data-alert-add-reopen]", root);
        const storageKey = "dlp-fan-guide-alerts";
        const state = storageGet(storageKey, {});
        let lastSnapshot = Object.fromEntries(dataset.items.map((item) => [item.id, item]));

        function permissionLabel() {
            if (!("Notification" in window)) {
                return "Notifications non disponibles";
            }
            if (Notification.permission === "granted") {
                return "Notifications actives";
            }
            if (Notification.permission === "denied") {
                return "Notifications bloquees";
            }
            return "Activer les notifications";
        }

        function notify(title, body) {
            if (!("Notification" in window) || Notification.permission !== "granted") {
                return;
            }
            new Notification(title, { body });
        }

        function getWatch(id) {
            return state[id] || { wait: false, reopen: false, threshold: null, name: "" };
        }

        function ensureWatch(id, name = "") {
            if (!state[id]) {
                state[id] = { wait: false, reopen: false, threshold: null, name };
            }
            if (name) {
                state[id].name = name;
            }
            return state[id];
        }

        function cleanupWatch(id) {
            const watch = state[id];
            if (!watch) {
                return;
            }
            if (!watch.wait && !watch.reopen) {
                delete state[id];
            }
        }

        function findItem(id) {
            return dataset.items.find((item) => item.id === id) || dataset.items[0];
        }

        function currentBuilderItem() {
            return select ? findItem(select.value || dataset.items[0].id) : dataset.items[0];
        }

        function setWatchMode(id, mode, options = {}) {
            const watch = ensureWatch(id, options.name || "");
            watch.name = options.name || watch.name;
            if (mode === "wait") {
                watch.threshold = Math.max(5, Number(options.threshold || watch.threshold || 15));
            }
            watch[mode] = true;
            storageSet(storageKey, state);
        }

        function toggleWatchMode(id, mode, options = {}) {
            const current = getWatch(id);
            const nextActive = !current[mode];

            if (!nextActive) {
                const watch = ensureWatch(id, options.name || current.name);
                watch[mode] = false;
                cleanupWatch(id);
                storageSet(storageKey, state);
                return;
            }

            setWatchMode(id, mode, options);
        }

        function removeAlerts(id) {
            delete state[id];
            storageSet(storageKey, state);
            renderButtons();
            renderLiveList();
        }

        function renderBuilder() {
            if (!select || !thresholdInput || !recommendation) {
                return;
            }

            const item = currentBuilderItem();
            const watch = getWatch(item.id);
            const threshold = Math.max(5, Number(thresholdInput.value) || item.target_wait || 15);
            const typical = Number(item.typical_wait || item.avg_wait || item.target_wait || threshold);
            let label = "Lecture confortable";
            let copy = "Tu demandes une alerte assez large. C est pratique si tu veux surtout garder l attraction dans ton radar.";

            if (threshold <= Number(item.great_wait || 10)) {
                label = "Objectif tres ambitieux";
                copy = "Tu vises une vraie fenetre premium. C est top si cette attraction compte vraiment pour ta journee.";
            } else if (threshold <= Number(item.target_wait || 15)) {
                label = "Bon slot fan";
                copy = "Tu es au niveau de recommandation que le site juge vraiment utile pour agir vite.";
            } else if (threshold <= typical) {
                label = "Choix realiste";
                copy = "Tu restes encore sous la lecture standard de la file. Le seuil garde donc un vrai interet terrain.";
            }

            recommendation.innerHTML = `
                <strong>${item.name}</strong>
                <span>${item.park} - ${item.land}. Reco fan: viser ${item.target_wait} min, et tres bon slot sous ${item.great_wait} min. Lecture habituelle autour de ${typical} min.</span>
                <small>${label}. ${copy}</small>
            `;

            if (addWaitButton) {
                addWaitButton.classList.toggle("is-active", Boolean(watch.wait));
                addWaitButton.textContent = watch.wait ? "Alerte attente active" : "Ajouter une alerte attente";
            }
            if (addReopenButton) {
                addReopenButton.classList.toggle("is-active", Boolean(watch.reopen));
                addReopenButton.textContent = watch.reopen ? "Alerte sortie de panne active" : "Ajouter une alerte sortie de panne";
            }
        }

        function renderButtons() {
            quickButtons.forEach((button) => {
                const id = button.dataset.watchId;
                const mode = button.dataset.watchAction;
                const watch = getWatch(id);
                const active = Boolean(watch[mode]);
                button.classList.toggle("is-active", active);
                button.setAttribute("aria-pressed", active ? "true" : "false");
            });
            if (permissionButton) {
                permissionButton.textContent = permissionLabel();
            }
            renderBuilder();
            storageSet(storageKey, state);
        }

        function renderLiveList(snapshot = lastSnapshot) {
            const activeEntries = Object.entries(state).filter(([, item]) => item.wait || item.reopen);
            if (!activeEntries.length) {
                liveList.innerHTML = `<div class="empty-inline">Aucune alerte encore active sur cet appareil.</div>`;
                return;
            }

            liveList.innerHTML = activeEntries.map(([id, watch]) => {
                const current = snapshot[id];
                const status = current ? (current.status === "open" && Number.isFinite(current.wait_time) ? `${current.wait_time} min` : current.status) : "hors vue";
                const waitLabel = watch.wait ? `Sous ${watch.threshold} min` : null;
                const reopenLabel = watch.reopen ? "Sortie de panne" : null;
                const modes = [waitLabel, reopenLabel].filter(Boolean).join(" - ");
                return `
                    <div class="alert-live-item">
                        <div class="alert-live-copy">
                            <strong>${watch.name}</strong>
                            <span>${modes} - ${status}</span>
                        </div>
                        <button type="button" class="action-button subtle" data-alert-remove-id="${id}">Retirer</button>
                    </div>
                `;
            }).join("");

            $$("[data-alert-remove-id]", liveList).forEach((button) => {
                button.addEventListener("click", () => removeAlerts(button.dataset.alertRemoveId));
            });
        }

        async function requestPermission() {
            if (!("Notification" in window) || Notification.permission !== "default") {
                renderButtons();
                return;
            }
            await Notification.requestPermission();
            renderButtons();
        }

        function handleQuickToggle(button) {
            const id = button.dataset.watchId;
            const mode = button.dataset.watchAction;
            toggleWatchMode(id, mode, {
                name: button.dataset.watchName || "",
                threshold: Number(button.dataset.watchThreshold || 15),
            });
            renderButtons();
            renderLiveList();
        }

        function handleBuilderToggle(mode) {
            const item = currentBuilderItem();
            const threshold = Math.max(5, Number(thresholdInput.value) || item.target_wait || 15);
            const current = getWatch(item.id);
            const isSameWaitAlert = mode === "wait" && current.wait && Number(current.threshold) === threshold;
            const isSameReopenAlert = mode === "reopen" && current.reopen;

            if (isSameWaitAlert || isSameReopenAlert) {
                const watch = ensureWatch(item.id, item.name);
                watch[mode] = false;
                cleanupWatch(item.id);
                storageSet(storageKey, state);
            } else {
                setWatchMode(item.id, mode, { name: item.name, threshold });
            }

            renderButtons();
            renderLiveList();
        }

        async function poll() {
            if (!endpoint || window.location.protocol === "file:") {
                return;
            }

            try {
                const response = await fetch(endpoint, { headers: { Accept: "application/json" } });
                if (!response.ok) {
                    return;
                }
                const payload = await response.json();
                if (!payload || !Array.isArray(payload.items)) {
                    return;
                }

                const nextSnapshot = Object.fromEntries(payload.items.map((item) => [item.id, item]));

                Object.entries(state).forEach(([id, watch]) => {
                    const previous = lastSnapshot[id];
                    const current = nextSnapshot[id];
                    if (!current) {
                        return;
                    }

                    if (watch.wait && current.status === "open" && Number.isFinite(current.wait_time)) {
                        const threshold = Number(watch.threshold || current.target_wait || 15);
                        const wasAbove = !previous || previous.status !== "open" || !Number.isFinite(previous.wait_time) || previous.wait_time > threshold;
                        if (current.wait_time <= threshold && wasAbove) {
                            notify(`${watch.name} passe sous ${threshold} min`, `${watch.name} est maintenant a ${current.wait_time} min dans ${current.park}.`);
                        }
                    }

                    if (watch.reopen) {
                        const wasClosed = previous && previous.status !== "open";
                        if (wasClosed && current.status === "open") {
                            const waitCopy = Number.isFinite(current.wait_time) ? `${current.wait_time} min` : "sans temps affiche";
                            notify(`${watch.name} est de retour`, `${watch.name} redevient ouverte (${waitCopy}).`);
                        }
                    }
                });

                lastSnapshot = nextSnapshot;
                renderLiveList(nextSnapshot);
            } catch (error) {
                return;
            }
        }

        quickButtons.forEach((button) => {
            button.addEventListener("click", () => handleQuickToggle(button));
        });

        if (select && thresholdInput) {
            select.addEventListener("change", () => {
                const item = currentBuilderItem();
                thresholdInput.value = item.target_wait;
                renderBuilder();
            });
            thresholdInput.addEventListener("input", renderBuilder);
        }

        if (addWaitButton) {
            addWaitButton.addEventListener("click", () => handleBuilderToggle("wait"));
        }
        if (addReopenButton) {
            addReopenButton.addEventListener("click", () => handleBuilderToggle("reopen"));
        }
        if (permissionButton) {
            permissionButton.addEventListener("click", requestPermission);
        }

        renderButtons();
        renderLiveList();
        window.setInterval(poll, 60000);
    }

    function initShowProgram() {
        const root = $("[data-show-program]");
        const dataset = readJsonScript("show-program-dataset");
        if (!root || !dataset || !Array.isArray(dataset.sessions)) {
            return;
        }

        const input = $("[data-show-target]", root);
        const slots = $$("[data-show-slot]");
        const nearestList = $("[data-show-nearest-list]", root);

        function render(targetTime) {
            const targetMinutes = toMinutes(targetTime);
            if (targetMinutes === null) {
                return;
            }

            const ranking = dataset.sessions.map((session) => ({
                ...session,
                distance: Math.abs(toMinutes(session.time) - targetMinutes),
            })).sort((left, right) => {
                if (left.distance === right.distance) {
                    return left.time.localeCompare(right.time);
                }
                return left.distance - right.distance;
            });

            const nearest = ranking.slice(0, 6);
            const nearIds = new Set(nearest.map((item) => `${item.name}|${item.time}|${item.park}`));

            slots.forEach((slot) => {
                const key = `${slot.dataset.showName}|${slot.dataset.showTime}|${slot.dataset.showPark}`;
                slot.classList.toggle("is-near", nearIds.has(key));
            });

            if (nearestList) {
                nearestList.innerHTML = nearest.map((session) => `
                    <div class="alert-live-item">
                        <strong>${session.time} - ${session.name}</strong>
                        <span>${session.park} - ${session.location}</span>
                    </div>
                `).join("");
            }
        }

        if (input) {
            input.addEventListener("input", () => render(input.value));
            render(input.value || dataset.focus || "17:30");
        }
    }

    document.addEventListener("DOMContentLoaded", () => {
        initFoodFilters();
        initBudgetCalculator();
        initTrendChart();
        initThresholdPlanner();
        bindCheckboxBoard("[data-checklist-board]", "[data-checklist-key]", "dlp-fan-guide-checklist", "[data-checklist-complete]");
        bindCheckboxBoard("[data-secret-board]", "[data-secret-key]", "dlp-fan-guide-secrets", "[data-secret-complete]");
        initRatings();
        initWaitAlerts();
        initShowProgram();
    });
})();
