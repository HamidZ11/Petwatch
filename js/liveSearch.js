(function () {
    function createDebouncer(delay) {
        let timer = null;
        return function (callback, value) {
            clearTimeout(timer);
            timer = setTimeout(() => callback(value), delay);
        };
    }

    function serializeForm(form) {
        const params = new URLSearchParams(new FormData(form));
        params.delete("pageNum");
        return params;
    }

    async function fetchAndReplace(url, containerId) {
        const res = await fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" } });
        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, "text/html");
        const nextContainer = doc.getElementById(containerId);
        const currentContainer = document.getElementById(containerId);
        if (!nextContainer || !currentContainer) return;
        currentContainer.innerHTML = nextContainer.innerHTML;
    }

    function attachLiveSearch(config) {
        const form = document.getElementById(config.formId);
        const input = document.getElementById(config.inputId);
        if (!form || !input) return;

        const debounceSearch = createDebouncer(300);

        input.addEventListener("input", () => {
            debounceSearch(async (value) => {
                const params = serializeForm(form);
                params.set("search", value);

                const url = `index.php?${params.toString()}`;
                await fetchAndReplace(url, config.containerId);
                history.replaceState(null, "", url);
            }, input.value);
        });
    }

    attachLiveSearch({
        formId: "petsSearchForm",
        inputId: "petSearch",
        containerId: "petsContainer"
    });

    attachLiveSearch({
        formId: "sightingsSearchForm",
        inputId: "sightingSearch",
        containerId: "sightingsContainer"
    });
})();
