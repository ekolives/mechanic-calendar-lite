// Pilnuje ważności sesji: co 30 s pyta serwer, a po wygaśnięciu przenosi do logowania.
// Aktywność użytkownika (klik, klawisz, dotyk, scroll) odnawia sesję - najwyżej raz na 60 s.
(function () {
    var CHECK_URL = 'backend/session_check.php';
    var LOGIN_URL = '../index.php';
    var CHECK_EVERY = 30 * 1000;
    var TOUCH_EVERY = 60 * 1000;

    var lastTouch = Date.now(); // strona właśnie się załadowała, więc serwer już ją policzył jako aktywność
    var pendingActivity = false;
    var redirecting = false;

    function goToLogin() {
        if (redirecting) return;
        redirecting = true;
        // ukryj od razu całą stronę, żeby po wygaśnięciu sesji nie dało się już oglądać rezerwacji
        document.documentElement.style.display = 'none';
        window.location.replace(LOGIN_URL);
    }

    function check(touch) {
        fetch(CHECK_URL + (touch ? '?touch=1' : ''), { credentials: 'same-origin', cache: 'no-store' })
            .then(function (r) {
                if (r.status === 401 || (r.redirected && r.url.indexOf('session_check') === -1)) goToLogin();
            })
            .catch(function () { /* brak sieci - nie wylogowujemy */ });
    }

    // przy kliknięciu sprawdź sesję od razu (nie częściej niż co 3 s), nie czekając na cykl 30 s
    var lastClickCheck = 0;
    document.addEventListener('click', function () {
        var now = Date.now();
        if (now - lastClickCheck >= 3000) {
            lastClickCheck = now;
            check(false);
        }
    }, true);

    function onActivity() { pendingActivity = true; }
    ['click', 'keydown', 'touchstart', 'scroll', 'input'].forEach(function (ev) {
        document.addEventListener(ev, onActivity, { passive: true, capture: true });
    });

    setInterval(function () {
        var touch = pendingActivity && (Date.now() - lastTouch) >= TOUCH_EVERY;
        if (touch) {
            pendingActivity = false;
            lastTouch = Date.now();
        }
        check(touch);
    }, CHECK_EVERY);

    // powrót do karty po dłuższej przerwie - sprawdź od razu
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) check(false);
    });
})();
