document.addEventListener('DOMContentLoaded', function () {
    if (typeof startTime === 'undefined') return;

    const timerEl = document.getElementById('timer');
    if (!timerEl) return;

    function updateTimer() {
        const now = Date.now();
        const diff = startTime - now;
        if (diff <= 0) {
            timerEl.textContent = 'Квест начнётся сейчас!';
        } else {
            const h = Math.floor(diff / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            const s = Math.floor((diff % 60000) / 1000);
            timerEl.textContent = `До начала: ${h}ч ${m}м ${s}с`;
        }
    }

    updateTimer();
    setInterval(updateTimer, 1000);
});
