/**
 * Shared helpers for attendance GPS maps and address popups.
 */
(function (window) {
    /**
     * Escape HTML for safe popup content.
     * @param {string} text
     * @returns {string}
     */
    function escapeHtml(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /**
     * Format coordinates when no street address is available.
     * @param {number|string|null} lat
     * @param {number|string|null} lng
     * @param {number|string|null} accuracyM
     * @returns {string}
     */
    function formatCoordinateLabel(lat, lng, accuracyM) {
        if (lat === null || lat === undefined || lng === null || lng === undefined) {
            return '—';
        }

        var label = parseFloat(lat).toFixed(6) + ', ' + parseFloat(lng).toFixed(6);
        if (accuracyM !== null && accuracyM !== undefined && accuracyM !== '') {
            label += ' (±' + Math.round(parseFloat(accuracyM)) + ' m)';
        }

        return label;
    }

    /**
     * Build Leaflet popup HTML for a check-in / check-out marker.
     * @param {object} options
     * @returns {string}
     */
    function buildAttendanceLocationPopup(options) {
        var type = options.type === 'check_out' ? 'check_out' : 'check_in';
        var typeLabel = type === 'check_in' ? 'Check-in' : 'Check-out';
        var badgeClass = type === 'check_in' ? 'success' : 'danger';

        var html =
            '<div class="attendance-map-popup" style="min-width:220px;max-width:300px;">';

        if (options.employee) {
            html += '<strong>' + escapeHtml(options.employee) + '</strong><br>';
        }

        html +=
            '<span class="badge bg-' +
            badgeClass +
            '-transparent mt-1">' +
            typeLabel +
            '</span>';

        if (options.address) {
            html +=
                '<p class="mb-1 mt-2 fs-12 fw-medium">' +
                escapeHtml(options.address) +
                '</p>';
        }

        html +=
            '<p class="text-muted fs-11 mb-1">' +
            parseFloat(options.latitude).toFixed(6) +
            ', ' +
            parseFloat(options.longitude).toFixed(6);

        if (options.accuracy_m) {
            html += '<br>±' + Math.round(options.accuracy_m) + ' m GPS accuracy';
        }

        html += '</p>';

        if (options.recorded_at) {
            html += '<p class="text-muted fs-11 mb-1">' + escapeHtml(options.recorded_at) + '</p>';
        }

        var mapUrl =
            options.map_url ||
            'https://www.openstreetmap.org/?mlat=' +
                options.latitude +
                '&mlon=' +
                options.longitude +
                '#map=18/' +
                options.latitude +
                '/' +
                options.longitude;

        html +=
            '<a href="' +
            escapeHtml(mapUrl) +
            '" target="_blank" rel="noopener noreferrer" class="fs-12">View on OpenStreetMap</a>';
        html += '</div>';

        return html;
    }

    /**
     * Display label for tables and summary cards (address preferred, coords fallback).
     * @param {object} options
     * @returns {string}
     */
    function locationDisplayLabel(options) {
        if (options.address) {
            var label = options.address;
            if (options.accuracy_m) {
                label += ' (±' + Math.round(parseFloat(options.accuracy_m)) + ' m GPS)';
            }

            return label;
        }

        return formatCoordinateLabel(options.latitude, options.longitude, options.accuracy_m);
    }

    window.AttendanceLocation = {
        escapeHtml: escapeHtml,
        formatCoordinateLabel: formatCoordinateLabel,
        buildPopup: buildAttendanceLocationPopup,
        displayLabel: locationDisplayLabel,
    };
})(window);
