<!DOCTYPE html>
<html>
<head>
    <title>Карта объектов</title>
    <meta charset="utf-8" />
    <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
    @vite('resources/js/map.js')
    <style>
        html, body, #map { width:100%; height:100vh; margin:0; padding:0; }
        .map-loader { position: fixed; inset: 0; display: flex; align-items: center; justify-content: center; font: 14px/1.4 system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial; background: #fff; }
        .map-loader.hidden { display: none; }
        .balloon { min-width: 280px; max-width: 420px; }
        .balloon h4 { margin: 0 0 6px; font-size: 16px; }
        .balloon table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .balloon table td { padding: 4px 6px; vertical-align: top; }
        .balloon table td:first-child { opacity: .7; white-space: nowrap; }
        .balloon .photo { margin: 8px 0; }
        .balloon .photo img { width: 100%; height: auto; display: block; border-radius: 4px; }
        .balloon .muted { opacity:.7; }
        .spinner { display:inline-block; width:16px; height:16px; border:2px solid rgba(0,0,0,.15); border-top-color: rgba(0,0,0,.5); border-radius:50%; animation:spin 1s linear infinite; vertical-align:-3px; margin-right:6px;}
        @keyframes spin { to { transform: rotate(360deg);} }
    </style>
</head>
<body>
<div id="map" data-type="house"></div>
<div id="loader" class="map-loader">Загружаю объекты…</div>

<script type="module">
ymaps.ready(init);

const { fetchObjects, loadDetails, typeLabel, fmtMs, escapeHtml, baseUrl } = window.mapUtils;

function renderBalloon(details, fallback) {
    const d = details || {};
    const name = d.name || fallback?.title || typeLabel(fallback?.type);
    const addr = d.position || fallback?.address || 'Адрес не указан';

    const walls = Array.isArray(d.wallMaterials) ? d.wallMaterials.join(', ') : d.wallMaterials || '-';
    const lastActualState = Array.isArray(d.stateList) ? d.stateList.find(s => s.actual) : null;
    const firstState = Array.isArray(d.stateList) && d.stateList.length ? d.stateList[0] : null;
    const state = lastActualState || firstState;

    const parcel = Array.isArray(d.parcels) ? (d.parcels.find(p => p.main) || d.parcels[0]) : null;

    const imgPath = (Array.isArray(d.images) && d.images.length) ? d.images[0].path : null;
    const imgUrl = imgPath ? `${baseUrl}/api/images/abandonedObject/${imgPath}` : null;

    return `
            <div class="balloon">
                <h4>${escapeHtml(name)}</h4>
                <div class="muted">${escapeHtml(addr)}</div>
                ${imgUrl ? `<div class="photo"><img src="${imgUrl}" alt="Фото объекта" loading="lazy"></div>` : ''}
                <table>
                    <tr><td>Тип</td><td>${escapeHtml(d.abandonedObjectType || typeLabel(fallback?.type))}</td></tr>
                    <tr><td>Назначение</td><td>${escapeHtml(d.purpose || '-')}</td></tr>
                    <tr><td>Материалы стен</td><td>${escapeHtml(walls)}</td></tr>
                    <tr><td>Площадь</td><td>${d.square ?? '-'} ${d.square ? 'м²' : ''}</td></tr>
                    <tr><td>Износ</td><td>${d.deterioration ?? '-'} ${d.deterioration != null ? '%' : ''}</td></tr>
                    <tr><td>Этажность</td><td>${d.floorCount ?? '-'}${d.undergroundFloorCount ? ` (подземных: ${d.undergroundFloorCount})` : ''}</td></tr>
                    <tr><td>Дата обследования</td><td>${fmtMs(d.inspectionDate)}</td></tr>
                    ${state ? `<tr><td>Статус</td><td>${escapeHtml(state.abandonedObjectStateType)} (${fmtMs(state.stateDate)})</td></tr>` : ''}
                    ${parcel ? `<tr><td>Участок</td><td>${parcel.square ?? '-'} га — ${escapeHtml(parcel.purpose || '-')}</td></tr>` : ''}
                    ${Array.isArray(d.contacts) && d.contacts.length ? `<tr><td>Контакты</td><td>${escapeHtml(d.contacts[0].name || '')}${d.contacts[0].phone ? `, ${escapeHtml(d.contacts[0].phone)}` : ''}${d.contacts[0].email ? `, ${escapeHtml(d.contacts[0].email)}` : ''}</td></tr>` : ''}
                </table>
                <div class="muted" style="margin-top:6px">ERI ID: ${escapeHtml(String(fallback?.eri_id || d.id || '—'))}</div>
            </div>
        `;
}

async function init() {
    const map = new ymaps.Map("map", {
        center: [53.9, 27.5667], // Минск по умолчанию
        zoom: 7,
        controls: ['zoomControl', 'typeSelector', 'fullscreenControl']
    });

    const loader = document.getElementById('loader');
    const dataType = document.getElementById('map').dataset.type;
    try {
        const data = await fetchObjects(dataType);

        const clusterer = new ymaps.Clusterer({
            groupByCoordinates: false,
            clusterDisableClickZoom: false,
            clusterOpenBalloonOnClick: true
        });

        const placemarks = [];

        (data || []).forEach(obj => {
            if (!obj.coords) return;

            const parts = String(obj.coords).split(',');
            if (parts.length !== 2) return;

            const lat = parseFloat(parts[0].trim());
            const lon = parseFloat(parts[1].trim());
            if (Number.isNaN(lat) || Number.isNaN(lon)) return;

            const initialBalloon = `
                    <div class="balloon"><span class="spinner"></span>Загружаю данные по объекту…</div>
                `;

            const placemark = new ymaps.Placemark([lat, lon], {
                // временно: покажем адрес и тип, пока грузим подробности
                balloonContent: initialBalloon,
                hintContent: escapeHtml(obj.address || typeLabel(obj.type))
            }, {
                preset: (Number(obj.type) === 2) ? 'islands#greenIcon' : 'islands#blueHomeIcon'
            });

            // При первом открытии шара — тянем подробности и обновляем контент
            let loaded = false;
            placemark.events.add('balloonopen', async () => {
                if (loaded) return;
                loaded = true;

                try {
                    const details = await loadDetails(dataType, obj.eri_id);
                    const html = renderBalloon(details, { type: obj.type, address: obj.address, eri_id: obj.eri_id });
                    placemark.properties.set('balloonContent', html);
                } catch (e) {
                    console.error(e);
                    placemark.properties.set('balloonContent', `
                            <div class="balloon">
                                <div style="margin-bottom:6px;"><strong>${escapeHtml(typeLabel(obj.type))}</strong></div>
                                <div class="muted">${escapeHtml(obj.address || '')}</div>
                                <div style="margin-top:8px;color:#b00;">Не удалось загрузить подробности. Попробуйте позже.</div>
                            </div>
                        `);
                }
            });

            placemarks.push(placemark);
        });

        if (placemarks.length) {
            clusterer.add(placemarks);
            map.geoObjects.add(clusterer);

            const bounds = clusterer.getBounds();
            if (bounds) {
                map.setBounds(bounds, { checkZoomRange: true, zoomMargin: 40 });
            }
        } else {
            console.warn('Нет валидных координат для отображения.');
        }
    } catch (e) {
        console.error(e);
        alert('Не удалось загрузить список объектов. Проверь API /api/objects');
    } finally {
        loader.classList.add('hidden');
    }
}
</script>
</body>
</html>
