class PetListView {
    constructor(containerEl, onSelect, onReport) {
        this.el = containerEl;
        this.onSelect = onSelect;
        this.onReport = onReport;
        this.items = [];
    }

    setItems(items) {
        this.items = Array.isArray(items) ? items : [];
        this.render();
    }

    appendItems(items) {
        if (!Array.isArray(items) || items.length === 0) return;
        this.items = this.items.concat(items);
        this.render();
    }

    render() {
        this.el.innerHTML = "";
        const list = this.items;

        if (list.length === 0) {
            const empty = document.createElement("p");
            empty.className = "text-muted mb-0";
            empty.textContent = "No sightings found.";
            this.el.appendChild(empty);
            return;
        }

        list.forEach(p => {
            const row = document.createElement("div");
            row.className = "card-sighting";
            row.dataset.petId = String(p.petID ?? "");

            row.innerHTML = `
        <div class="sighting-title"><b>${this.esc(p.name)}</b> (${this.esc(p.type)})</div>
        <div class="sighting-desc">${this.esc(p.sightingDescription || "No sightings yet")}</div>
        <p class="text-muted small mb-2">
          Reported by: ${this.esc(p.reporterName || "Unknown")}
          • Sighting ID: ${this.esc(p.sightingID ?? "")}
        </p>
        <div class="sighting-actions">
          <button data-action="focus" class="btn btn-sm btn-outline-primary">Show on map</button>
          <button data-action="report" class="btn btn-sm btn-outline-secondary">Add sighting</button>
        </div>
      `;

            row.querySelector('[data-action="focus"]').onclick = () => this.onSelect(p.sightingID);
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
