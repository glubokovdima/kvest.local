document.addEventListener('DOMContentLoaded', () => {
    let userLat = null;
    let userLng = null;

    function getDistance(lat1, lon1, lat2, lon2) {
        const R = 6371e3;
        const toRad = x => x * Math.PI / 180;
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a = Math.sin(dLat / 2) ** 2 +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
            Math.sin(dLon / 2) ** 2;
        return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
    }

    function checkDistance() {
        const form = document.getElementById('answer-form');
        const message = document.getElementById('geo-message');
        if (!form || !message || userLat === null || userLng === null) return;

        const dist = getDistance(userLat, userLng, checkpointLat, checkpointLng);

        if (dist <= checkpointRadius) {
            form.classList.remove('hidden');
            message.textContent = "✅ Вы на месте! Можете отвечать.";
            message.className = "text-green-600 mt-4";
        } else {
            form.classList.add('hidden');
            message.textContent = `🚗 До точки: ${Math.round(dist)} м`;
            message.className = "text-gray-600 mt-4";
        }
    }

    if ('geolocation' in navigator) {
        navigator.geolocation.watchPosition(
            pos => {
                userLat = pos.coords.latitude;
                userLng = pos.coords.longitude;
                checkDistance();
            },
            err => {
                const message = document.getElementById('geo-message');
                if (message) message.textContent = '⚠️ Не удалось получить геолокацию';
            },
            { enableHighAccuracy: true }
        );
    } else {
        const message = document.getElementById('geo-message');
        if (message) message.textContent = '⚠️ Геолокация не поддерживается';
    }
});

let  answerForm = document.getElementById('answer-form');
if (answerForm) {
    answerForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const message = document.getElementById('answer-message');
        const formData = new FormData(answerForm);

        message.textContent = '⏳ Проверка...';
        message.className = 'text-gray-600';

        try {
            const response = await fetch('submit-answer.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                message.textContent = result.message || '✅ Ответ принят!';
                message.className = 'text-green-600';
                answerForm.querySelector('input[name="answer"]').disabled = true;
                answerForm.querySelector('button').disabled = true;

                // Перезагрузим страницу через 2 секунды
                setTimeout(() => window.location.reload(), 2000);
            } else {
                message.textContent = result.error || '❌ Неверный ответ';
                message.className = 'text-red-600';
            }
        } catch (err) {
            message.textContent = '❌ Ошибка сервера. Повторите попытку.';
            message.className = 'text-red-600';
        }
    });
}


// Обработка всех форм дополнительных вопросов
const extraForms = document.querySelectorAll('.extra-answer-form');

extraForms.forEach(form => {
    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const btn = form.querySelector('button[type="submit"]');
        const msg = form.querySelector('.extra-form-message');
        const formData = new FormData(form);

        btn.disabled = true;
        btn.textContent = '⏳ Отправка...';
        msg.textContent = '';
        msg.className = 'extra-form-message mt-2 text-left text-sm text-gray-600';

        try {
            const response = await fetch('submit-extra-answer.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                msg.textContent = result.message || '✅ Ответ принят!';
                msg.classList.replace('text-gray-600', 'text-green-600');
                form.querySelectorAll('input, textarea, button').forEach(el => el.disabled = true);

                // Перезагрузка через 2 секунды
                setTimeout(() => window.location.reload(), 2000);
            } else {
                msg.textContent = result.error || '❌ Ошибка';
                msg.classList.replace('text-gray-600', 'text-red-600');
                btn.disabled = false;
                btn.textContent = 'Отправить';
            }
        } catch (err) {
            msg.textContent = '❌ Сервер не отвечает';
            msg.classList.replace('text-gray-600', 'text-red-600');
            btn.disabled = false;
            btn.textContent = 'Отправить';
        }
    });
});

