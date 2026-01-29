(async function () {
    const api = new ApiClient();
    const mapView = new MapView("map");
    const focusPetId = new URLSearchParams(window.location.search).get("petID");
    const appEl = document.getElementById("mapApp");
    const isLoggedIn = appEl?.dataset?.loggedIn === "1";

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

    async function loadPets() {
        const res = await api.get("/index.php?page=api_missing_pets");
        const sightings = res.data || [];
        const petToSighting = new Map();

        sightings.forEach(s => {
            const lat = Number(s.latitude);
            const lng = Number(s.longitude);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
            const popup = `<b>${s.name}</b><br>${s.sightingDescription || "No sighting details"}<br><small>${s.dateReported || ""}</small>`;
            mapView.upsertMarker(`sighting:${s.sightingID}`, lat, lng, popup);
            const petKey = String(s.petID);
            if (petKey && !petToSighting.has(petKey)) {
                petToSighting.set(petKey, s.sightingID);
            }
        });

        const listItems = sightings.map(s => ({
            petID: s.petID,
            name: s.name,
            type: s.type,
            sightingDescription: s.sightingDescription,
            sightingID: s.sightingID
        }));

        list.setItems(listItems);
        list.petToSighting = petToSighting;
        if (focusPetId) {
            focusByPetId(focusPetId);
        }
    }

    await loadPets();

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

    const searchEl = document.getElementById("petSearch");
    if (searchEl) {
        let timer = null;
        searchEl.addEventListener("input", () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                list.setFilter(searchEl.value);
            }, 250);
        });
    }
})();
