const ERI_BASE = 'https://eri2.nca.by';
const detailsCache = new Map();

function apiListUrl(type) {
    return (type === 'plot' || Number(type) === 2) ? '/api/objects/2' : '/api/objects';
}

window.mapUtils = {
    async fetchObjects(type) {
        const url = apiListUrl(type);
        const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
        if (!res.ok) throw new Error('API error: ' + res.status);
        return await res.json();
    },

    async loadDetails(type, eriId) {
        if (detailsCache.has(eriId)) return detailsCache.get(eriId);
        const path = (type === 'plot' || Number(type) === 2) ? 'investmentObject' : 'abandonedObject';
        const url = `${ERI_BASE}/api/guest/${path}/${encodeURIComponent(eriId)}/forView`;
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json, text/plain, */*',
                'Content-Type': 'application/json',
            },
            body: '{}',
        });
        if (!res.ok) throw new Error('ERI API error: ' + res.status);
        const json = await res.json();
        const data = json?.data ?? json ?? {};
        detailsCache.set(eriId, data);
        return data;
    },

    typeLabel(type) {
        return (Number(type) === 2 || type === 'plot') ? 'Земельный участок' : 'Дом';
    },

    fmtMs(ms) {
        if (!ms && ms !== 0) return '-';
        const d = new Date(Number(ms));
        const pad = n => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
    },

    escapeHtml(s) {
        if (s == null) return '';
        return String(s)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    },
    baseUrl: ERI_BASE,
};

export {};
