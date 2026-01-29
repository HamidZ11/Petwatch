class PetListView {
    constructor(containerEl, onSelect, onReport) {
        this.el = containerEl;
        this.onSelect = onSelect;
        this.onReport = onReport;
        this.items = [];
        this.filterQuery = "";
    }

    setItems(items) {
        this.items = Array.isArray(items) ? items : [];
        this.render();
    }

    setFilter(query) {
        this.filterQuery = String(query ?? "").trim().toLowerCase();
        this.render();
    }

    render() {
        this.el.innerHTML = "";
        const q = this.filterQuery;
        const list = q
            ? this.items.filter(p => {
                const name = String(p.name ?? "").toLowerCase();
                const type = String(p.type ?? "").toLowerCase();
                const desc = String(p.sightingDescription ?? "").toLowerCase();
                return name.includes(q) || type.includes(q) || desc.includes(q);
            })
            : this.items;

        list.forEach(p => {
            const row = document.createElement("div");
            row.style.border = "1px solid #ddd";
            row.style.padding = "10px";
            row.style.marginBottom = "8px";
            row.style.borderRadius = "8px";
            row.dataset.petId = String(p.petID ?? "");

            row.innerHTML = `
        <div><b>${this.esc(p.name)}</b> (${this.esc(p.type)})</div>
        <div style="font-size: 0.9em; opacity:0.8;">${this.esc(p.sightingDescription || "No sightings yet")}</div>
        <div style="margin-top:8px; display:flex; gap:8px;">
          <button data-action="focus">Show on map</button>
          <button data-action="report">Add sighting</button>
        </div>
      `;

            row.querySelector('[data-action="focus"]').onclick = () => this.onSelect(p.petID);
            row.querySelector('[data-action="report"]').onclick = () => this.onReport(p.petID);

            this.el.appendChild(row);
        });
    }

    esc(s) {
        return String(s ?? "").replace(/[&<>"']/g, c => ({
            "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"
        }[c]));
    }
}
