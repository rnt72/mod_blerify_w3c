define([], function() {

    var pollTimer = null;
    var countdownTimer = null;
    var MAX_CONSECUTIVE_ERRORS = 5;

    return {
        init: function(config) {
            var countdownEl = document.getElementById('blerify-qr-countdown');
            var consecutiveErrors = 0;
            var pollInterval = 5000;

            if (countdownEl) {
                countdownTimer = setInterval(function() {
                    var now = Math.floor(Date.now() / 1000);
                    var remaining = config.expiresAt - now;

                    if (remaining <= 0) {
                        clearInterval(countdownTimer);
                        clearInterval(pollTimer);
                        countdownEl.textContent = '';
                        var qrContainer = document.getElementById('blerify-qr-container');
                        if (qrContainer) {
                            qrContainer.style.opacity = '0.3';
                        }
                        var expiredEl = document.getElementById('blerify-qr-expired');
                        if (expiredEl) {
                            expiredEl.style.display = '';
                        }
                        return;
                    }

                    var minutes = Math.floor(remaining / 60);
                    var seconds = remaining % 60;
                    countdownEl.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                }, 1000);
            }

            if (!config.skipPolling) {
                var doPoll = function() {
                    var url = config.statusUrl +
                        '?sesskey=' + encodeURIComponent(config.sesskey) +
                        '&cmid=' + encodeURIComponent(config.cmid);

                    fetch(url, {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {'Accept': 'application/json'}
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        consecutiveErrors = 0;
                        pollInterval = 5000;
                        if (data.status === 'assembled') {
                            clearInterval(pollTimer);
                            clearInterval(countdownTimer);
                            window.location.href = config.refreshUrl;
                        }
                    })
                    .catch(function() {
                        consecutiveErrors++;
                        if (consecutiveErrors >= MAX_CONSECUTIVE_ERRORS) {
                            clearInterval(pollTimer);
                            window.console && console.warn('Blerify: polling stopped after ' + MAX_CONSECUTIVE_ERRORS + ' consecutive errors');
                        } else {
                            clearInterval(pollTimer);
                            pollInterval = Math.min(pollInterval * 2, 60000);
                            pollTimer = setInterval(doPoll, pollInterval);
                        }
                    });
                };

                pollTimer = setInterval(doPoll, pollInterval);
            }
        }
    };
});
