/**
 * Client-side session idle expire: non-blocking warning toast + redirect.
 * Config: window.userextendedSessionExpire (must be set before this script runs).
 */
(function (window, document) {
    'use strict';

    function boot() {
        var config = window.userextendedSessionExpire || null;
        if (!config || !config.timeout || config.timeout <= 0 || !config.loginUrl) {
            return;
        }

        var timeoutMs = parseInt(config.timeout, 10) * 1000;
        var warningMs = Math.max(0, parseInt(config.warningBefore || 0, 10) * 1000);
        var heartbeatMs = Math.max(0, parseInt(config.heartbeatInterval || 0, 10) * 1000);
        var warnOnce = config.warnOnce !== false;
        var expireAt = Date.now() + timeoutMs;
        var warningShown = false;
        var redirected = false;
        var timerId = null;
        var heartbeatId = null;
        var toastEl = null;

        function redirectToLogin() {
            if (redirected) {
                return;
            }
            redirected = true;
            stopTimers();
            hideToast();
            window.location.href = config.loginUrl;
        }

        function stopTimers() {
            if (timerId) {
                window.clearInterval(timerId);
                timerId = null;
            }
            if (heartbeatId) {
                window.clearInterval(heartbeatId);
                heartbeatId = null;
            }
        }

        function ensureToast() {
            if (toastEl) {
                return toastEl;
            }
            if (!document.body) {
                return null;
            }

            toastEl = document.createElement('div');
            toastEl.className = 'userextended-session-toast';
            toastEl.setAttribute('role', 'status');
            toastEl.setAttribute('aria-live', 'polite');

            var msg = document.createElement('span');
            msg.className = 'userextended-session-toast__msg';
            toastEl.appendChild(msg);

            var dismiss = document.createElement('button');
            dismiss.type = 'button';
            dismiss.className = 'userextended-session-toast__close';
            dismiss.setAttribute('aria-label', 'Close');
            dismiss.innerHTML = '&times;';
            dismiss.addEventListener('click', function () {
                hideToast();
            });
            toastEl.appendChild(dismiss);

            document.body.appendChild(toastEl);
            return toastEl;
        }

        function hideToast() {
            if (toastEl && toastEl.parentNode) {
                toastEl.parentNode.removeChild(toastEl);
            }
            toastEl = null;
        }

        function showWarning() {
            if (!config.warningMessage) {
                return;
            }
            if (warnOnce && warningShown) {
                return;
            }
            warningShown = true;

            if (window.console && typeof window.console.warn === 'function') {
                window.console.warn(config.warningMessage);
            }

            var el = ensureToast();
            if (!el) {
                return;
            }
            var msg = el.querySelector('.userextended-session-toast__msg');
            if (msg) {
                msg.textContent = config.warningMessage;
            }
            el.classList.add('is-visible');
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
            hideToast();
        }

        function startTimer() {
            if (timerId) {
                window.clearInterval(timerId);
            }
            timerId = window.setInterval(checkExpire, 1000);
            checkExpire();
        }

        function heartbeat() {
            if (redirected || !heartbeatMs || !config.heartbeatUrl) {
                return;
            }

            var url = config.heartbeatUrl;
            var opts = {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json, text/plain, */*'
                }
            };

            if (window.fetch) {
                window.fetch(url, opts).then(function (res) {
                    if (res.status === 401 || res.status === 403) {
                        redirectToLogin();
                        return;
                    }
                    // 204 No Content is ok; treat 2xx as activity
                    if (res.ok || res.status === 204) {
                        renewFromServerActivity();
                    }
                }).catch(function () {
                    // Network errors: keep local timer; do not force logout
                });
                return;
            }

            if (window.jQuery) {
                window.jQuery.ajax({
                    url: url,
                    method: 'GET',
                    cache: false,
                    dataType: 'text'
                }).done(function () {
                    renewFromServerActivity();
                }).fail(function (xhr) {
                    if (xhr && (xhr.status === 401 || xhr.status === 403)) {
                        redirectToLogin();
                    }
                });
            }
        }

        function startHeartbeat() {
            if (!heartbeatMs || !config.heartbeatUrl) {
                return;
            }
            if (heartbeatId) {
                window.clearInterval(heartbeatId);
            }
            // First ping after one interval (page load already renewed auth)
            heartbeatId = window.setInterval(heartbeat, heartbeatMs);
        }

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                checkExpire();
            }
        });

        window.addEventListener('focus', checkExpire);

        if (window.jQuery) {
            window.jQuery(document).ajaxComplete(function (event, xhr, settings) {
                if (redirected) {
                    return;
                }

                // Ignore our own heartbeat — already handled in heartbeat()
                if (settings && settings.url && config.heartbeatUrl) {
                    if (String(settings.url).indexOf(config.heartbeatUrl) !== -1) {
                        return;
                    }
                }

                var status = xhr && xhr.status ? xhr.status : 0;

                if (status === 401 || status === 403) {
                    redirectToLogin();
                    return;
                }

                if (status >= 200 && status < 400) {
                    renewFromServerActivity();
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
        startHeartbeat();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})(window, document);
