(async function () {

    const api = new ApiClient();
    const mapView = new MapView("map");

    const params = new URLSearchParams(window.location.search);
    const focusPetId = params.get("petID");
    const focusSightingId = params.get("sightingID");

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

    const fallbackCenter = { lat:53.483959 , lng:-2.244644 , zoom:12 };

    mapView.setCenter(fallbackCenter.lat,fallbackCenter.lng,fallbackCenter.zoom);

    if(navigator.geolocation){
        navigator.geolocation.getCurrentPosition(
            pos => mapView.setCenter(pos.coords.latitude,pos.coords.longitude,13),
            () => mapView.setCenter(fallbackCenter.lat,fallbackCenter.lng,fallbackCenter.zoom)
        );
    }

    const list = new PetListView(
        document.getElementById("petList"),

        (petId)=>focusByPetId(petId),

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

        sightings.forEach((s,i)=>{

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
            sightingID:s.sightingID

        }));

        list.setItems(listItems);

        // If a specific sighting was requested, focus it
        if (focusSightingId) {
            mapView.focusMarker(`sighting:${focusSightingId}`);
        }
    }

    async function fetchPage(page,query,append=false){

        if(isLoading) return;

        isLoading = true;

        try{

            let res;

            if(focusPetId){

                res = await api.get("/index.php?page=api_missing_pets",{
                    petID:focusPetId
                });

            }else{

                res = await api.get("/index.php?page=api_missing_pets",{
                    search:query,
                    pageNum:page,
                    limit
                });

            }

            if(!res || res.ok !== true){
                throw new Error("Unable to fetch sightings");
            }

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
                    sightingID:s.sightingID
                }));

                list.appendItems(listItems);
            }

            const total = Number(res.total ?? 0);

            if(!focusPetId && loadMoreBtn){
                hasMore = (page * limit) < total;
                loadMoreBtn.style.display = hasMore ? "inline-block" : "none";
            }

        }catch(err){

            console.error(err);
        }

        isLoading = false;
    }

    await fetchPage(currentPage,currentQuery);

    if(loadMoreBtn){

        loadMoreBtn.addEventListener("click", async ()=>{

            if(isLoading || !hasMore) return;

            currentPage += 1;

            await fetchPage(currentPage,currentQuery,true);

        });

    }

    function focusByPetId(petId){

        if(!petId || !list.petToSighting) return;

        const sightingId = list.petToSighting.get(String(petId));

        if(sightingId){
            mapView.focusMarker(`sighting:${sightingId}`);
        }
    }

})();