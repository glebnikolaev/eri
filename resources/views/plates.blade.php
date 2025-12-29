<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8" />
    <title>Полигоны участков</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Загружаем полный пакет модулей; при желании добавь свой apikey=... -->
    <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU&load=package.full" type="text/javascript"></script>
    @vite('resources/js/map.js')
    <style>
        html, body, #map { width:100%; height:100vh; margin:0; padding:0; }
        .map-loader { position: fixed; inset: 0; display:flex; align-items:center; justify-content:center; background:#fff; font:14px/1.4 system-ui, -apple-system, Segoe UI, Roboto, Arial; }
        .map-loader.hidden { display:none; }
        .balloon { min-width: 300px; max-width: 520px; }
        .balloon h4 { margin:0 0 6px; font-size:16px; }
        .balloon table { width:100%; border-collapse:collapse; font-size:13px; }
        .balloon td { padding:4px 6px; vertical-align:top; }
        .balloon td:first-child { opacity:.7; white-space:nowrap; }
        .muted { opacity:.7; }
        .spinner { display:inline-block; width:16px; height:16px; border:2px solid rgba(0,0,0,.15); border-top-color: rgba(0,0,0,.55); border-radius:50%; animation:spin 1s linear infinite; vertical-align:-3px; margin-right:6px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .error { position:fixed; top:12px; left:50%; transform:translateX(-50%); background:#fee; color:#900; padding:8px 12px; border:1px solid #f99; border-radius:6px; font:14px/1.3 system-ui; z-index: 1000; }
    </style>
</head>
<body>
<div id="map" data-type="plot"></div>
<div id="loader" class="map-loader">Загружаю участки…</div>
<div id="err" class="error" style="display:none"></div>

<script type="module">
// 👇 Если у тебя без префикса /api — замени на '/objects/2'
const { fetchObjects, loadDetails, fmtMs, escapeHtml } = window.mapUtils;

// Показываем ошибку пользователю
function showError(msg) {
    const e = document.getElementById('err');
    e.textContent = msg;
    e.style.display = 'block';
}

// Гарантируем наличие ядра карт
function ensureYmapsCore() {
    if (!window.ymaps) throw new Error('Яндекс.Карты не загрузились (window.ymaps отсутствует).');
    if (typeof ymaps.Map !== 'function') throw new Error('Ядро Яндекс.Карт не подгружено (ymaps.Map недоступен).');
}

// -------- API helpers --------

// -------- utils --------
function normalizeRing(ring) {
    const out = [];
    for (const p of (ring || [])) {
        if (!Array.isArray(p) || p.length < 2) continue;
        let [a,b] = p.map(Number); // [lat, lon] ожидаемо
        // лёгкая эвристика на случай [lon,lat]
        const looksLatLon = a >= 50 && a <= 60 && b >= 20 && b <= 35;
        const looksLonLat = a >= 20 && a <= 35 && b >= 50 && b <= 60;
        if (looksLonLat && !looksLatLon) [a,b] = [b,a];
        if (Number.isFinite(a) && Number.isFinite(b)) out.push([a,b]);
    }
    return out;
}
function parseBorders(borders) {
    if (!borders || typeof borders !== 'object') return [];
    const type = borders.type;
    const coords = borders.coordinates;

    if (type === 'Polygon' && Array.isArray(coords)) {
        return [ coords.map(normalizeRing).filter(r => r.length) ];
    }
    if (type === 'MultiPolygon' && Array.isArray(coords)) {
        const result = [];
        for (const poly of coords) {
            const rings = (poly || []).map(normalizeRing).filter(r => r.length);
            if (rings.length) result.push(rings);
        }
        return result;
    }
    return [];
}
function renderInvestmentBalloon(data, fallback) {
    const d = data || {};
    const title = d.name || fallback?.address || `Участок ${escapeHtml(fallback?.eri_id ?? '')}`;
    const addr = d.position || d.addressRemark || fallback?.address || '';
    const rightTypes = Array.isArray(d.rightTypes) ? d.rightTypes.join(', ') : d.rightTypes || '-';

    return `
            <div class="balloon">
                <h4>${escapeHtml(title)}</h4>
                ${addr ? `<div class="muted">${escapeHtml(addr)}</div>` : ''}
                <table>
                    <tr><td>ERI ID</td><td><a target="_blank" href="https://eri2.nca.by/guest/investmentObject/${escapeHtml(String(fallback?.eri_id || d.id || '—'))}#main">${escapeHtml(String(fallback?.eri_id ?? d.id ?? '—'))}</a></td></tr>
                    <tr><td>Кадастр</td><td>${escapeHtml(d.cadNum ?? '-')}</td></tr>
                    <tr><td>Площадь</td><td>${d.square ?? '-'} ${d.square ? 'га' : ''}</td></tr>
                    <tr><td>Назначение</td><td>${escapeHtml(d.purpose ?? d.purposeUseRemark ?? '-')}</td></tr>
                    <tr><td>Права</td><td>${escapeHtml(rightTypes)}</td></tr>
                    <tr><td>Реестр</td><td>${escapeHtml(d.investmentRegistryTypeName ?? '-')}</td></tr>
                    <tr><td>Доступность</td><td>${d.actual === true ? 'актуален' : (d.actual === false ? 'не актуален' : '-')}</td></tr>
                    <tr><td>Начало учета</td><td>${fmtMs(d.startDate)}</td></tr>
                </table>
            </div>
        `;
}

// -------- init --------
(function boot() {
    // Иногда расширения браузера мешают загрузке — отловим и покажем понятный текст
    const loader = document.getElementById('loader');

    // Ждем, когда API прогрузится
    if (window.ymaps && typeof ymaps.ready === 'function') {
        ymaps.ready(function () {
            try {
                ensureYmapsCore();
            } catch (e) {
                console.error(e);
                showError(e.message + ' Попробуй отключить блокировщики/расширения или добавить apikey.');
                loader.classList.add('hidden');
                return;
            }
            init(); // всё ок — запускаем
        });
    } else {
        // Скрипт вообще не подгрузился (CSP/адблок/офлайн)
        showError('Не удалось загрузить скрипт Яндекс.Карт. Проверь сеть, CSP и блокировщики. При необходимости добавь apikey.');
        loader.classList.add('hidden');
    }
})();

async function init() {
    const map = new ymaps.Map('map', {
        center: [53.9, 27.5667],
        zoom: 7,
        controls: ['zoomControl', 'typeSelector', 'fullscreenControl', 'searchControl']
    });

    const loader = document.getElementById('loader');
    const dataType = document.getElementById('map').dataset.type;
    const listUrl = (dataType === 'plot') ? '/api/objects/2' : '/api/objects';

    try {
        const objects = await fetchObjects(dataType); // [{ id,type,address,coords,eri_id,borders,... }, ...]
        const collection = new ymaps.GeoObjectCollection();

        for (const obj of (objects || [])) {
            if (Number(obj.type) !== 2) continue;

            const multipoly = parseBorders(obj.borders);
            if (!multipoly.length) continue;

            for (const rings of multipoly) {
                const polygon = new ymaps.Polygon(
                    rings,
                    {
                        hintContent: obj.address || `Участок ${obj.eri_id}`,
                        balloonContent: `<div class="balloon"><span class="spinner"></span> Загружаю данные по участку…</div>`
                    },
                    {
                        strokeWidth: 2,
                        strokeColor: '#007f3b',
                        fillColor: '#00bf6f55',
                        interactivityModel: 'default#geoObject',
                        fillRule: 'nonZero'
                    }
                );

                polygon.properties.set('eri_id', obj.eri_id);
                polygon.properties.set('address', obj.address || '');

                polygon.events.add('click', async (e) => {
                    const clickCoords = e.get('coords');
                    polygon.properties.set('balloonContent', `<div class="balloon"><span class="spinner"></span> Загружаю данные по участку…</div>`);
                    polygon.balloon.open(clickCoords);

                    try {
                        const data = await loadDetails(dataType, obj.eri_id);
                        const html = renderInvestmentBalloon(data, { eri_id: obj.eri_id, address: obj.address });
                        polygon.properties.set('balloonContent', html);
                    } catch (err) {
                        console.error(err);
                        polygon.properties.set('balloonContent', `<div class="balloon" style="color:#b00">Не удалось загрузить подробности. Попробуйте позже.</div>`);
                    }
                });

                collection.add(polygon);
            }
        }

        if (collection.getLength() > 0) {
            map.geoObjects.add(collection);
            const bounds = collection.getBounds();
            if (bounds) map.setBounds(bounds, { checkZoomRange: true, zoomMargin: 40 });
        } else {
            console.warn('Нет полигонов для отображения.');
        }
    } catch (e) {
        console.error(e);
        showError('Не удалось загрузить список участков. Проверь API ' + listUrl);
    } finally {
        loader.classList.add('hidden');
    }
}
</script>
</body>
</html>
