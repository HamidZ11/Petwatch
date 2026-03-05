(async function () {

    const api = new ApiClient();
    const mapView = new MapView("map");

    const params = new URLSearchParams(window.location.search);
    const focusPetId = params.get("petID");
    const focusSightingId = params.get("sightingID");
    const focusLat = Number(params.get("lat"));
    const focusLng = Number(params.get("lng"));

    const appEl = document.getElementById("mapApp");
    const isLoggedIn = appEl?.dataset?.loggedIn === "1";

    const searchEl = document.getElementById("mapSearch") || document.getElementById("petSearch");
    const loadMoreBtn = document.getElementById("loadMoreBtn");
    const sortNewestBtn = document.getElementById("sortNewestBtn");
    const sortOldestBtn = document.getElementById("sortOldestBtn");

    const limit = 20;

    let currentPage = 1;
    let currentQuery = "";
    let currentSort = params.get("sort") === "oldest" ? "oldest" : "newest";
    let activePetId = focusPetId;
    let isLoading = false;
    let hasMore = false;
    let requestSeq = 0;

    const fallbackCenter = { lat:53.4808 , lng:-2.2426 , zoom:12 };

    mapView.setCenter(fallbackCenter.lat,fallbackCenter.lng,fallbackCenter.zoom);

    if(
        navigator.geolocation &&
        !focusPetId &&
        !focusSightingId &&
        !(Number.isFinite(focusLat) && Number.isFinite(focusLng))
    ){
        navigator.geolocation.getCurrentPosition(
            pos => mapView.setCenter(pos.coords.latitude,pos.coords.longitude,13),
            () => mapView.setCenter(fallbackCenter.lat,fallbackCenter.lng,fallbackCenter.zoom)
        );
    }

    const list = new PetListView(
        document.getElementById("petList"),

        (sightingId)=>focusBySightingId(sightingId),

        (petId)=>{
            if(!petId) return;

            if(!isLoggedIn){
                alert("Login required to report a sighting.");
                window.location.href="/index.php?page=login";
                return;
            }

            window.location.href=`/index.php?page=reportSighting&petID=${encodeURIComponent(petId)}`;
        }
    );

    list.petToSighting = new Map();

    function syncSortButtons(){
        if(!sortNewestBtn || !sortOldestBtn) return;
        if(currentSort === "oldest"){
            sortNewestBtn.className = "btn btn-outline-primary";
            sortOldestBtn.className = "btn btn-primary";
            return;
        }
        sortNewestBtn.className = "btn btn-primary";
        sortOldestBtn.className = "btn btn-outline-primary";
    }

    function esc(s){
        return String(s ?? "").replace(/[&<>"']/g,c=>({
            "&":"&amp;",
            "<":"&lt;",
            ">":"&gt;",
            "\"":"&quot;",
            "'":"&#39;"
        }[c]));
    }

    function processSightings(sightings){

        mapView.clearMarkers();
        list.setItems([]);
        list.petToSighting = new Map();

        let focusMarkerKey = null;

        sightings.forEach((s,i)=>{

            const lat = Number(s.latitude);
            const lng = Number(s.longitude);

            if(!Number.isFinite(lat) || !Number.isFinite(lng)) return;

            const popup = `<b>${esc(s.name)}</b><br>${esc(s.sightingDescription || "")}<br><small>${esc(s.dateReported || "")}</small>`;

            mapView.upsertMarker(`sighting:${s.sightingID}`,lat,lng,popup);

            const petKey = String(s.petID);
            list.petToSighting.set(petKey,s.sightingID);

            if (
                Number.isFinite(focusLat) &&
                Number.isFinite(focusLng) &&
                Math.abs(lat - focusLat) < 0.000001 &&
                Math.abs(lng - focusLng) < 0.000001
            ) {
                focusMarkerKey = `sighting:${s.sightingID}`;
            }

        });

        const listItems = sightings.map(s=>({

            petID:s.petID,
            name:s.name,
            type:s.type,
            sightingDescription:s.sightingDescription,
            sightingID:s.sightingID,
            username:s.username,
            reporterName:s.username || s.reporterName

        }));

        list.setItems(listItems);

        // If a specific sighting was requested, focus it
        if (focusSightingId) {
            mapView.focusMarker(`sighting:${focusSightingId}`);
            if (Number.isFinite(focusLat) && Number.isFinite(focusLng)) {
                mapView.setCenter(focusLat, focusLng, 15);
            }
            return;
        }

        // If specific coordinates were requested, focus that marker and zoom in
        if (focusMarkerKey) {
            mapView.focusMarker(focusMarkerKey);
            mapView.setCenter(focusLat, focusLng, 15);
            return;
        }

        // Fallback: center map on coordinates even if marker key was not matched exactly
        if (Number.isFinite(focusLat) && Number.isFinite(focusLng)) {
            mapView.setCenter(focusLat, focusLng, 15);
        }
    }

    async function fetchPage(page,query,append=false){

        if(isLoading) return;

        isLoading = true;
        const currentRequest = ++requestSeq;

        try{

            let res;

            if(activePetId && !query){

                res = await api.get("/index.php?page=api_missing_pets",{
                    petID:activePetId,
                    sort:currentSort
                });

            }else{

                res = await api.get("/index.php?page=api_missing_pets",{
                    search:query,
                    sort:currentSort,
                    pageNum:page,
                    limit
                });

            }

            if(!res || res.ok !== true){
                throw new Error("Unable to fetch sightings");
            }
            if(currentRequest !== requestSeq) return;

            const sightings = Array.isArray(res.data) ? res.data : [];

            if(!append){
                processSightings(sightings);
            }else{
                sightings.forEach(s=>{

                    const lat = Number(s.latitude);
                    const lng = Number(s.longitude);

                    if(!Number.isFinite(lat) || !Number.isFinite(lng)) return;

                    const popup = `<b>${esc(s.name)}</b><br>${esc(s.sightingDescription || "")}<br><small>${esc(s.dateReported || "")}</small>`;

                    mapView.upsertMarker(`sighting:${s.sightingID}`,lat,lng,popup);

                    const petKey = String(s.petID);
                    list.petToSighting.set(petKey,s.sightingID);
                });

                const listItems = sightings.map(s=>({
                    petID:s.petID,
                    name:s.name,
                    type:s.type,
                    sightingDescription:s.sightingDescription,
                    sightingID:s.sightingID,
                    username:s.username,
                    reporterName:s.username || s.reporterName
                }));

                list.appendItems(listItems);
            }

            const total = Number(res.total ?? 0);

            if(!activePetId && loadMoreBtn){
                hasMore = (page * limit) < total;
                loadMoreBtn.style.display = hasMore ? "inline-block" : "none";
            } else if (loadMoreBtn) {
                loadMoreBtn.style.display = "none";
            }

        }catch(err){

            console.error(err);
        }

        isLoading = false;
    }

    await fetchPage(currentPage,currentQuery);
    syncSortButtons();

    if(loadMoreBtn){

        loadMoreBtn.addEventListener("click", async ()=>{

            if(isLoading || !hasMore) return;

            currentPage += 1;

            await fetchPage(currentPage,currentQuery,true);

        });

    }

    if(searchEl){
        let timer = null;
        searchEl.addEventListener("input", ()=>{
            clearTimeout(timer);
            timer = setTimeout(async ()=>{
                currentQuery = searchEl.value.trim();
                if(currentQuery){
                    activePetId = null;
                }
                currentPage = 1;
                hasMore = false;
                if(loadMoreBtn){
                    loadMoreBtn.style.display = "none";
                }
                await fetchPage(currentPage,currentQuery,false);
            },300);
        });
    }

    if(sortNewestBtn && sortOldestBtn){
        sortNewestBtn.addEventListener("click", async ()=>{
            if(currentSort === "newest") return;
            currentSort = "newest";
            currentPage = 1;
            hasMore = false;
            syncSortButtons();
            if(loadMoreBtn){
                loadMoreBtn.style.display = "none";
            }
            await fetchPage(currentPage,currentQuery,false);
        });

        sortOldestBtn.addEventListener("click", async ()=>{
            if(currentSort === "oldest") return;
            currentSort = "oldest";
            currentPage = 1;
            hasMore = false;
            syncSortButtons();
            if(loadMoreBtn){
                loadMoreBtn.style.display = "none";
            }
            await fetchPage(currentPage,currentQuery,false);
        });
    }

    function focusBySightingId(sightingId){
        if(!sightingId) return;
        mapView.focusMarker(`sighting:${sightingId}`);
    }

})();
