class MapView {
    constructor(mapElId) {
        this.map = L.map(mapElId);
        this.markers = new Map();

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: "&copy; OpenStreetMap"
        }).addTo(this.map);
    }

    setCenter(lat, lng, zoom = 13) {
        this.map.setView([lat, lng], zoom);
    }

    upsertMarker(markerKey, lat, lng, popupHtml) {
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

        const key = String(markerKey);
        if (this.markers.has(key)) {
            this.markers.get(key).setLatLng([lat, lng]).setPopupContent(popupHtml);
            return;
        }
        const marker = L.marker([lat, lng]).addTo(this.map).bindPopup(popupHtml);
        this.markers.set(key, marker);
    }

    clearMarkers() {
        this.markers.forEach(marker => this.map.removeLayer(marker));
        this.markers.clear();
    }

    focusMarker(markerKey) {
        const marker = this.markers.get(String(markerKey));
        if (!marker) return;
        this.map.panTo(marker.getLatLng());
        marker.openPopup();
    }
}
