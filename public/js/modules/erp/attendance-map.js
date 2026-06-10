/**
 * HR attendance GPS map (Leaflet) for check-in / check-out locations.
 */
$(function () {
    var mapEl = document.getElementById('attendanceMap');
    if (!mapEl || typeof L === 'undefined' || !window.attendanceMapDataUrl) {
        return;
    }

    var map = L.map('attendanceMap').setView([20.5937, 78.9629], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap',
    }).addTo(map);

    var markerLayer = L.layerGroup().addTo(map);

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
                    var label = isIn ? 'Check-in' : 'Check-out';
                    var popup =
                        '<strong>' +
                        item.employee +
                        '</strong><br>' +
                        label +
                        '<br>' +
                        lat.toFixed(6) +
                        ', ' +
                        lng.toFixed(6) +
                        (item.accuracy_m ? '<br>±' + Math.round(item.accuracy_m) + ' m' : '') +
                        (item.recorded_at ? '<br>' + item.recorded_at : '');

                    L.circleMarker([lat, lng], {
                        radius: 8,
                        color: color,
                        fillColor: color,
                        fillOpacity: 0.85,
                    })
                        .bindPopup(popup)
                        .addTo(markerLayer);

                    if (item.accuracy_m) {
                        L.circle([lat, lng], {
                            radius: item.accuracy_m,
                            color: color,
                            fillOpacity: 0.08,
                            weight: 1,
                        })
                            .bindPopup(popup)
                            .addTo(markerLayer);
                    }

                    bounds.push([lat, lng]);
                });

                if (bounds.length === 1) {
                    map.setView(bounds[0], 16);
                } else {
                    map.fitBounds(bounds, { padding: [30, 30], maxZoom: 17 });
                }
            },
            error: function () {
                notifyError('Could not load attendance map data.');
            },
        });
    };

    window.reloadAttendanceMap();
});
