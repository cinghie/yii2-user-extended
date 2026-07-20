(function (window, document) {
    'use strict';

    var config = window.userextendedSessionExpire || null;
    if (!config || !config.timeout || config.timeout <= 0 || !config.loginUrl) {
        return;
    }

    var timeoutMs = parseInt(config.timeout, 10) * 1000;
    var warningMs = Math.max(0, parseInt(config.warningBefore || 0, 10) * 1000);
    var expireAt = Date.now() + timeoutMs;
    var warningShown = false;
    var redirected = false;
    var timerId = null;

    function redirectToLogin() {
        if (redirected) {
            return;
        }
        redirected = true;
        window.location.href = config.loginUrl;
    }

    function showWarning() {
        if (warningShown || !config.warningMessage) {
            return;
        }
        warningShown = true;
        if (window.console && typeof window.console.warn === 'function') {
            window.console.warn(config.warningMessage);
        }
        if (window.alert) {
            window.alert(config.warningMessage);
        }
    }

    function checkExpire() {
        var remaining = expireAt - Date.now();
        if (remaining <= 0) {
            redirectToLogin();
            return;
        }
        if (warningMs > 0 && remaining <= warningMs) {
            showWarning();
        }
    }

    function renewFromServerActivity() {
        expireAt = Date.now() + timeoutMs;
        warningShown = false;
    }

    function startTimer() {
        if (timerId) {
            window.clearInterval(timerId);
        }
        timerId = window.setInterval(checkExpire, 1000);
        checkExpire();
    }

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            checkExpire();
        }
    });

    window.addEventListener('focus', checkExpire);

    if (window.jQuery) {
        window.jQuery(document).ajaxComplete(function (event, xhr) {
            if (redirected) {
                return;
            }

            var status = xhr && xhr.status ? xhr.status : 0;
            var responseUrl = '';
            try {
                responseUrl = xhr.getResponseHeader('X-Redirect-Url') || '';
            } catch (e) {
                responseUrl = '';
            }

            if (status === 401 || status === 403) {
                redirectToLogin();
                return;
            }

            if (status >= 200 && status < 400) {
                renewFromServerActivity();
            }

            if (responseUrl && responseUrl.indexOf(config.loginPath || '/user/security/login') !== -1) {
                redirectToLogin();
            }
        });

        window.jQuery(document).ajaxError(function (event, xhr) {
            if (!xhr) {
                return;
            }
            if (xhr.status === 401 || xhr.status === 403) {
                redirectToLogin();
            }
        });
    }

    startTimer();
})(window, document);
