class ApiClient {
    constructor() {
        this.csrf = document.querySelector('meta[name="csrf"]')?.content || "";
    }

    get(url, params = null) {
        return new Promise((resolve, reject) => {
            let finalUrl = url;
            if (params && typeof params === "object") {
                const qs = new URLSearchParams();
                Object.entries(params).forEach(([key, value]) => {
                    if (value === undefined || value === null) return;
                    qs.append(key, String(value));
                });
                const query = qs.toString();
                if (query) {
                    finalUrl += (finalUrl.includes("?") ? "&" : "?") + query;
                }
            }

            const xhr = new XMLHttpRequest();
            xhr.open("GET", finalUrl, true);
            xhr.setRequestHeader("Accept", "application/json");
            xhr.onreadystatechange = () => {
                if (xhr.readyState !== 4) return;
                if (xhr.status === 200) {
                    try {
                        resolve(JSON.parse(xhr.responseText));
                    } catch (e) {
                        reject(new Error(`GET ${finalUrl} invalid JSON response`));
                    }
                    return;
                }
                reject(new Error(`GET ${finalUrl} failed: ${xhr.status}`));
            };
            xhr.onerror = () => reject(new Error(`GET ${finalUrl} network error`));
            xhr.send();
        });
    }

    post(url, body) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", url, true);
            xhr.setRequestHeader("Content-Type", "application/json");
            xhr.setRequestHeader("Accept", "application/json");
            xhr.setRequestHeader("X-CSRF-Token", this.csrf);
            xhr.onreadystatechange = () => {
                if (xhr.readyState !== 4) return;
                let json = {};
                try {
                    json = JSON.parse(xhr.responseText || "{}");
                } catch (e) {
                    json = {};
                }
                if (xhr.status === 200) {
                    resolve(json);
                    return;
                }
                reject(new Error(json.error || `POST ${url} failed: ${xhr.status}`));
            };
            xhr.onerror = () => reject(new Error(`POST ${url} network error`));
            xhr.send(JSON.stringify(body));
        });
    }
}
