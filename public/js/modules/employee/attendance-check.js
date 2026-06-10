/**
 * Employee GPS check-in / check-out with high-accuracy location capture.
 */
$(function () {
    var $checkInBtn = $('#employeeCheckInBtn');
    var $checkOutBtn = $('#employeeCheckOutBtn');
    if (!$checkInBtn.length && !$checkOutBtn.length) {
        return;
    }

    var warnAccuracyM = window.employeeGpsWarnAccuracyM || 50;

    /**
     * Update today status UI from API payload.
     * @param {object} data
     */
    function applyTodayStatus(data) {
        $('#todayCheckInTime').text(data.check_in_at || '—');
        $('#todayCheckOutTime').text(data.check_out_at || '—');
        $('#todayStatusLabel').text(data.status ? data.status.toUpperCase() : 'NOT MARKED');

        if (data.has_checked_in) {
            $checkInBtn.prop('disabled', true);
            $checkOutBtn.prop('disabled', !!data.has_checked_out);
        } else {
            $checkInBtn.prop('disabled', false);
            $checkOutBtn.prop('disabled', true);
        }

        if (data.check_in_latitude && data.check_in_longitude && typeof L !== 'undefined' && window.employeeTodayMap) {
            renderTodayMap(data);
        }
    }

    /**
     * Render mini map for today's check-in/out points.
     * @param {object} data
     */
    function renderTodayMap(data) {
        var mapEl = document.getElementById('employeeTodayMap');
        if (!mapEl) {
            return;
        }

        if (!window.employeeTodayMapInstance) {
            window.employeeTodayMapInstance = L.map('employeeTodayMap', { scrollWheelZoom: false });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap',
            }).addTo(window.employeeTodayMapInstance);
        }

        var map = window.employeeTodayMapInstance;
        map.eachLayer(function (layer) {
            if (layer instanceof L.Marker || layer instanceof L.Circle) {
                map.removeLayer(layer);
            }
        });

        var bounds = [];
        if (data.check_in_latitude && data.check_in_longitude) {
            var inLat = parseFloat(data.check_in_latitude);
            var inLng = parseFloat(data.check_in_longitude);
            L.circleMarker([inLat, inLng], { radius: 8, color: '#28a745', fillColor: '#28a745', fillOpacity: 0.9 })
                .addTo(map)
                .bindPopup('Check-in');
            if (data.check_in_accuracy_m) {
                L.circle([inLat, inLng], {
                    radius: parseFloat(data.check_in_accuracy_m),
                    color: '#28a745',
                    fillOpacity: 0.15,
                }).addTo(map);
            }
            bounds.push([inLat, inLng]);
        }
        if (data.check_out_latitude && data.check_out_longitude) {
            var outLat = parseFloat(data.check_out_latitude);
            var outLng = parseFloat(data.check_out_longitude);
            L.circleMarker([outLat, outLng], { radius: 8, color: '#dc3545', fillColor: '#dc3545', fillOpacity: 0.9 })
                .addTo(map)
                .bindPopup('Check-out');
            if (data.check_out_accuracy_m) {
                L.circle([outLat, outLng], {
                    radius: parseFloat(data.check_out_accuracy_m),
                    color: '#dc3545',
                    fillOpacity: 0.15,
                }).addTo(map);
            }
            bounds.push([outLat, outLng]);
        }

        if (bounds.length === 1) {
            map.setView(bounds[0], 17);
        } else if (bounds.length > 1) {
            map.fitBounds(bounds, { padding: [24, 24], maxZoom: 17 });
        }
    }

    /**
     * Run check-in or check-out AJAX after GPS capture.
     * @param {string} url
     * @param {jQuery} $btn
     */
    function submitAttendance(url, $btn) {
        var original = $btn.html();
        $btn.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-1"></span> Getting GPS...'
        );
        $('#employeeGpsStatus').text('Acquiring high-accuracy GPS fix…');

        capturePreciseGeolocation({
            warnAccuracyM: warnAccuracyM,
            onSample: function (accuracy) {
                $('#employeeGpsStatus').text('GPS sample: ±' + Math.round(accuracy) + ' m');
            },
        })
            .then(function (payload) {
                if (payload.accuracy_m > warnAccuracyM) {
                    notifyWarning(
                        'GPS accuracy is ±' +
                            Math.round(payload.accuracy_m) +
                            ' m. Move outdoors for a tighter fix if possible.'
                    );
                }

                $btn.html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
                $('#employeeGpsStatus').text(
                    'Sending ' +
                        payload.latitude.toFixed(6) +
                        ', ' +
                        payload.longitude.toFixed(6) +
                        ' (±' +
                        Math.round(payload.accuracy_m) +
                        ' m)'
                );

                return $.ajax({
                    url: url,
                    type: 'POST',
                    data: payload,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                });
            })
            .then(function (response) {
                notifySuccess(response.message || 'Saved.');
                if (response.data) {
                    window.employeeTodayStatus = response.data;
                    applyTodayStatus(response.data);
                }
                if ($('#employeeAttendanceTable').length && $.fn.DataTable.isDataTable('#employeeAttendanceTable')) {
                    $('#employeeAttendanceTable').DataTable().ajax.reload(null, false);
                }
            })
            .catch(function (err) {
                var message = 'Could not capture location.';
                if (err.responseJSON && err.responseJSON.message) {
                    message = err.responseJSON.message;
                } else if (err.message) {
                    message = err.message;
                }
                notifyError(message);
            })
            .finally(function () {
                $checkInBtn.html('Check in');
                $checkOutBtn.html('Check out');
                if (window.employeeTodayStatus) {
                    applyTodayStatus(window.employeeTodayStatus);
                }
            });
    }

    if (window.employeeTodayStatus) {
        applyTodayStatus(window.employeeTodayStatus);
    }

    $checkInBtn.on('click', function () {
        if (!window.employeeCheckInUrl) {
            return;
        }
        submitAttendance(window.employeeCheckInUrl, $checkInBtn);
    });

    $checkOutBtn.on('click', function () {
        if (!window.employeeCheckOutUrl) {
            return;
        }
        submitAttendance(window.employeeCheckOutUrl, $checkOutBtn);
    });
});
