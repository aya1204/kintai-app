document.addEventListener('DOMContentLoaded', function () {
    function updateClock() {
        const now = new Date();
        const hours = now.getHours().toString().padStart(2, '0');
        const minutes = now.getMinutes().toString().padStart(2, '0');
        const currentTime = `${hours}:${minutes}`;
        document.getElementById('real-time-clock').textContent = currentTime;
    }

    // 初回即時実行
    updateClock();
    // 毎秒更新
    setInterval(updateClock, 1000);
});