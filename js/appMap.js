(async function () {
    const api = new ApiClient();
    const mapView = new MapView("map");
    const focusPetId = new URLSearchParams(window.location.search).get("petID");
    const appEl = document.getElementById("mapApp");
    const isLoggedIn = appEl?.dataset?.loggedIn === "1";
    const searchEl = document.getElementById("petSearch");
    const loadMoreBtn = document.getElementById("loadMoreBtn");
    const limit = 20;
    let currentPage = 1;
    let currentQuery = "";
    let isLoading = false;
    let hasMore = false;
    let requestSeq = 0;

    const fallbackCenter = { lat: 53.483959, lng: -2.244644, zoom: 12 };

    // centre on user location
    mapView.setCenter(fallbackCenter.lat, fallbackCenter.lng, fallbackCenter.zoom);
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            pos => mapView.setCenter(pos.coords.latitude, pos.coords.longitude, 13),
            () => mapView.setCenter(fallbackCenter.lat, fallbackCenter.lng, fallbackCenter.zoom)
        );
    }

    const list = new PetListView(
        document.getElementById("petList"),
        (petId) => focusByPetId(petId),
        (petId) => {
            if (!petId) return;
            if (!isLoggedIn) {
                alert("Login required to report a sighting.");
                window.location.href = "/index.php?page=login";
                return;
            }
            window.location.href = `/index.php?page=reportSighting&petID=${encodeURIComponent(petId)}`;
        }
    );
    list.petToSighting = new Map();

    function esc(s) {
        return String(s ?? "").replace(/[&<>"']/g, c => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            "\"": "&quot;",
            "'": "&#39;"
        }[c]));
    }

    function setLoadMoreVisible(visible) {
        if (!loadMoreBtn) return;
        loadMoreBtn.style.display = visible ? "inline-block" : "none";
        loadMoreBtn.disabled = isLoading;
    }

    function processSightings(sightings, append = false) {
        if (!append) {
            mapView.clearMarkers();
            list.setItems([]);
            list.petToSighting = new Map();
        }

        sightings.forEach(s => {
            const lat = Number(s.latitude);
            const lng = Number(s.longitude);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
            const popup = `<b>${esc(s.name)}</b><br>${esc(s.sightingDescription || "No sighting details")}<br><small>${esc(s.dateReported || "")}</small>`;
            mapView.upsertMarker(`sighting:${s.sightingID}`, lat, lng, popup);
            const petKey = String(s.petID);
            if (petKey && !list.petToSighting.has(petKey)) {
                list.petToSighting.set(petKey, s.sightingID);
            }
        });

        const listItems = sightings.map(s => ({
            petID: s.petID,
            name: s.name,
            type: s.type,
            sightingDescription: s.sightingDescription,
            sightingID: s.sightingID
        }));

        if (append) {
            list.appendItems(listItems);
        } else {
            list.setItems(listItems);
        }

        if (!append && focusPetId) {
            focusByPetId(focusPetId);
        }
    }

    async function fetchPage(page, query, append = false) {
        if (isLoading) return;
        isLoading = true;
        setLoadMoreVisible(hasMore);
        const requestId = ++requestSeq;

        try {
            const res = await api.get("/index.php?page=api_missing_pets", {
                search: query,
                pageNum: page,
                limit
            });
            if (requestId !== requestSeq) return;
            if (!res || res.ok !== true) {
                throw new Error(res?.error || "Unable to fetch sightings");
            }

            const sightings = Array.isArray(res.data) ? res.data : [];
            processSightings(sightings, append);
            const total = Number(res.total ?? 0);
            hasMore = Number.isFinite(total) ? (page * limit) < total : sightings.length === limit;
            setLoadMoreVisible(hasMore);
        } catch (err) {
            console.error(err);
            if (!append) {
                mapView.clearMarkers();
                list.setItems([]);
                list.petToSighting = new Map();
            }
            setLoadMoreVisible(false);
        } finally {
            isLoading = false;
            if (loadMoreBtn) {
                loadMoreBtn.disabled = false;
            }
        }
    }

    await fetchPage(currentPage, currentQuery, false);

    function focusByPetId(petId) {
        if (!petId || !list.petToSighting) return;
        const sightingId = list.petToSighting.get(String(petId));
        if (sightingId) {
            mapView.focusMarker(`sighting:${sightingId}`);
        } else {
            console.warn("No sighting found for petID:", petId);
            alert("No sightings found for this pet yet.");
        }
    }

    if (searchEl) {
        let timer = null;
        searchEl.addEventListener("input", () => {
            clearTimeout(timer);
            timer = setTimeout(async () => {
                currentQuery = searchEl.value.trim();
                currentPage = 1;
                await fetchPage(currentPage, currentQuery, false);
            }, 250);
        });
    }

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener("click", async () => {
            if (isLoading || !hasMore) return;
            currentPage += 1;
            loadMoreBtn.disabled = true;
            await fetchPage(currentPage, currentQuery, true);
        });
    }
})();
