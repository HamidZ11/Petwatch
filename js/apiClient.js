class ApiClient {
    constructor() {
        this.csrf = document.querySelector('meta[name="csrf"]')?.content || "";
    }

    async get(url) {
        const res = await fetch(url, { headers: { "Accept": "application/json" } });
        if (!res.ok) throw new Error(`GET ${url} failed: ${res.status}`);
        return res.json();
    }

    async post(url, body) {
        const res = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-Token": this.csrf
            },
            body: JSON.stringify(body)
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(json.error || `POST ${url} failed: ${res.status}`);
        return json;
    }
}
