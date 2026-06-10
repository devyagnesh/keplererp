/**
 * Captures a high-accuracy GPS fix using repeated Geolocation API samples.
 *
 * @param {object} [options]
 * @param {number} [options.maxWaitMs=12000] Maximum sampling duration.
 * @param {number} [options.targetAccuracyM=30] Stop early when this accuracy is reached.
 * @param {number} [options.warnAccuracyM=50] Warn callback threshold.
 * @param {function(number): void} [options.onSample] Called on each reading with accuracy metres.
 * @returns {Promise<object>} Resolved geolocation payload for the server.
 */
function capturePreciseGeolocation(options) {
    options = options || {};
    var maxWaitMs = options.maxWaitMs || 12000;
    var targetAccuracyM = options.targetAccuracyM || 30;
    var warnAccuracyM = options.warnAccuracyM || 50;

    return new Promise(function (resolve, reject) {
        if (!navigator.geolocation) {
            reject(new Error('Geolocation is not supported on this device.'));
            return;
        }

        var best = null;
        var watchId = null;
        var settled = false;

        function finish(err, position) {
            if (settled) {
                return;
            }
            settled = true;
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
            }
            clearTimeout(timer);

            if (err) {
                reject(err);
                return;
            }

            var pos = position || (best ? best.position : null);
            if (!pos) {
                reject(new Error('Could not obtain a GPS location. Enable location services and try again.'));
                return;
            }

            resolve(buildPayload(pos));
        }

        function consider(position) {
            if (!position || !position.coords) {
                return;
            }

            var accuracy = position.coords.accuracy;
            if (typeof options.onSample === 'function') {
                options.onSample(accuracy);
            }

            if (!best || accuracy < best.accuracy) {
                best = { position: position, accuracy: accuracy };
            }

            if (accuracy <= targetAccuracyM) {
                finish(null, position);
            }
        }

        var timer = setTimeout(function () {
            if (best) {
                finish(null, best.position);
            } else {
                finish(new Error('GPS fix timed out. Move to an open area and try again.'));
            }
        }, maxWaitMs);

        watchId = navigator.geolocation.watchPosition(
            function (position) {
                consider(position);
            },
            function (error) {
                var message = 'Location permission denied or unavailable.';
                if (error.code === error.TIMEOUT) {
                    message = 'GPS timed out. Try again outdoors with precise location enabled.';
                } else if (error.code === error.POSITION_UNAVAILABLE) {
                    message = 'GPS signal unavailable.';
                }
                finish(new Error(message));
            },
            {
                enableHighAccuracy: true,
                maximumAge: 0,
                timeout: maxWaitMs,
            }
        );

        navigator.geolocation.getCurrentPosition(
            function (position) {
                consider(position);
            },
            function () {
                /* watchPosition handles errors */
            },
            {
                enableHighAccuracy: true,
                maximumAge: 0,
                timeout: maxWaitMs,
            }
        );
    });

    /**
     * @param {GeolocationPosition} position
     * @returns {object}
     */
    function buildPayload(position) {
        var coords = position.coords;
        var capturedAt = position.timestamp
            ? new Date(position.timestamp).toISOString()
            : new Date().toISOString();

        return {
            latitude: coords.latitude,
            longitude: coords.longitude,
            accuracy_m: coords.accuracy,
            altitude_m: coords.altitude !== null && coords.altitude !== undefined ? coords.altitude : null,
            altitude_accuracy_m:
                coords.altitudeAccuracy !== null && coords.altitudeAccuracy !== undefined
                    ? coords.altitudeAccuracy
                    : null,
            heading: coords.heading !== null && coords.heading !== undefined ? coords.heading : null,
            speed_m_s: coords.speed !== null && coords.speed !== undefined ? coords.speed : null,
            captured_at: capturedAt,
            warn_accuracy_m: warnAccuracyM,
        };
    }
}
