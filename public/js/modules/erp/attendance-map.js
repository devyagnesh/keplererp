/**
 * HR attendance GPS map (Leaflet) for check-in / check-out locations.
 */
$(function () {
    var mapEl = document.getElementById('attendanceMap');
    if (!mapEl || typeof L === 'undefined' || !window.attendanceMapDataUrl) {
        return;
    }

    var locationHelper = window.AttendanceLocation || null;
    var map = L.map('attendanceMap', { scrollWheelZoom: true }).setView([20.5937, 78.9629], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors',
    }).addTo(map);

    var markerLayer = L.layerGroup().addTo(map);

    setTimeout(function () {
        map.invalidateSize();
    }, 200);

    /**
     * Build popup content for a map marker.
     * @param {object} item
     * @returns {string}
     */
    function buildPopup(item) {
        if (locationHelper) {
            return locationHelper.buildPopup({
                employee: item.employee,
                type: item.type,
                latitude: item.latitude,
                longitude: item.longitude,
                accuracy_m: item.accuracy_m,
                address: item.address,
                map_url: item.map_url,
                recorded_at: item.recorded_at,
            });
        }

        return item.employee + '<br>' + item.latitude.toFixed(6) + ', ' + item.longitude.toFixed(6);
    }

    /**
     * Load markers from server using current filter values.
     */
    window.reloadAttendanceMap = function () {
        var workDate = $('#attendanceFilterDate').val() || window.attendanceWorkDate;
        $.ajax({
            url: window.attendanceMapDataUrl,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                Accept: 'application/json',
            },
            data: {
                work_date: workDate,
                employee_id: $('#attendanceFilterEmployee').val() || '',
                source: $('#attendanceFilterSource').val() || '',
            },
            success: function (response) {
                markerLayer.clearLayers();
                var payload = response.data || {};
                var markers = payload.markers || [];
                $('#attendanceMapCount').text(markers.length + ' GPS point(s)');

                if (markers.length === 0) {
                    if (payload.center) {
                        map.setView([payload.center.latitude, payload.center.longitude], 5);
                    }
                    return;
                }

                var bounds = [];
                markers.forEach(function (item) {
                    var lat = item.latitude;
                    var lng = item.longitude;
                    var isIn = item.type === 'check_in';
                    var color = isIn ? '#28a745' : '#dc3545';
                    var popup = buildPopup(item);

                    L.circleMarker([lat, lng], {
                        radius: 9,
                        color: color,
                        fillColor: color,
                        fillOpacity: 0.9,
                        weight: 2,
                    })
                        .bindPopup(popup, { maxWidth: 320 })
                        .addTo(markerLayer);

                    if (item.accuracy_m) {
                        L.circle([lat, lng], {
                            radius: item.accuracy_m,
                            color: color,
                            fillOpacity: 0.1,
                            weight: 1,
                        })
                            .bindPopup(popup, { maxWidth: 320 })
                            .addTo(markerLayer);
                    }

                    bounds.push([lat, lng]);
                });

                if (bounds.length === 1) {
                    map.setView(bounds[0], 18);
                } else {
                    map.fitBounds(bounds, { padding: [40, 40], maxZoom: 18 });
                }

                setTimeout(function () {
                    map.invalidateSize();
                }, 100);
            },
            error: function () {
                notifyError('Could not load attendance map data.');
            },
        });
    };

    window.reloadAttendanceMap();
});
