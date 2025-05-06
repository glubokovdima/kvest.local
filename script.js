function initMap() {
    var step = steps[currentStep]; // Текущий шаг
    if (map) {
        map.remove();
        map = null;
    }

    map = L.map('map').setView([step.lat, step.lng], 15);  // Устанавливаем начальную позицию карты

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // Маркер для пользователя
    var carIcon = L.icon({
        iconUrl: '{{ "assets/car.png"|theme }}',  // Вы можете указать свой иконку
        iconSize: [40, 40],
        iconAnchor: [20, 40]
    });
    userMarker = L.marker([step.lat, step.lng], { icon: carIcon }).addTo(map);

    // Маркер для цели
    var finishIcon = L.icon({
        iconUrl: '{{ "assets/finish.png"|theme }}',  // Аналогично, для цели
        iconSize: [40, 40],
        iconAnchor: [20, 40]
    });
    destMarker = L.marker([step.lat, step.lng], { icon: finishIcon }).addTo(map);

    firstPositionReceived = false;  // Сбрасываем флаг для первого получения позиции
    if (routeLine) {
        routeLine.remove();
        routeLine = null;
    }
}

///


///


function updateRoadRoute(lat, lng) {
    var dest = steps[currentStep];  // Цель для текущего шага
    var url = [
        'https://router.project-osrm.org/route/v1/driving/',
        lng, ',', lat, ';',  // Текущая позиция
        dest.lng, ',', dest.lat, // Конечная точка
        '?overview=full&geometries=geojson'
    ].join('');

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (!data.routes || !data.routes.length) return;

// Убираем старую линию
            if (routeLine) {
                map.removeLayer(routeLine);
            }

// Строим новый маршрут
            var coords = data.routes[0].geometry.coordinates.map(pt => [pt[1], pt[0]]);
            routeLine = L.polyline(coords, {
                color: 'blue',
                weight: 5,
                opacity: 0.7
            }).addTo(map);

// Центрируем карту по маршруту
            map.fitBounds(routeLine.getBounds(), {
                padding: [50, 50]
            });
        })
        .catch(err => console.error('OSRM route error', err));
}


///


function startTracking() {
    if (!navigator.geolocation) {
        alert('Геолокация не поддерживается');
        return;
    }

    geoWatchId = navigator.geolocation.watchPosition(onPositionSuccess, showError, {
        enableHighAccuracy: true,
        maximumAge: 10000,
        timeout: 10000
    });
}

function stopTracking() {
    if (geoWatchId !== null) {
        navigator.geolocation.clearWatch(geoWatchId);
        geoWatchId = null;
    }
}

function onPositionSuccess(position) {
    var lat = position.coords.latitude;
    var lng = position.coords.longitude;

// Обновление координат пользователя на карте
    if (userMarker) {
        userMarker.setLatLng([lat, lng]);
    }

    updateRoadRoute(lat, lng);  // Обновление маршрута

// Расчёт дистанции до цели
    var step = steps[currentStep];
    var dist = getDistance(lat, lng, step.lat, step.lng);
    var text = dist < 1000
        ? Math.round(dist) + ' м'
        : (dist / 1000).toFixed(1) + ' км';
    $('#distance-info').text('🚗 До цели: ' + text);

// Проверка, достиг ли пользователь цели
    var radius = step.radius || 100;
    if (dist <= radius) {
        stopTracking();
        $('#map, #open-in-maps, #distance-info').fadeOut(400);
        $('#step-block').fadeIn(400, function () {
            $('#submit-button').prop('disabled', false);  // Разрешить ответ
        });
        startTimer(step.min_time_spent || 0);
    }
}


///

function restoreStep() {
    if (currentStep >= steps.length) {
        finishQuest();
        return;
    }

    var step = steps[currentStep];

// Восстанавливаем вопрос
    $('#step-title').text(step.title);
    $('#step-question').text(step.question);
    $('input[name="checkpoint_index"]').val(currentStep);

// Проверяем, был ли уже ответ на основной вопрос
    var saved = answersJson['checkpoint_' + currentStep] || {};
    var mainAnswered = !!saved.main_answer;

    if (mainAnswered) {
        $('#answer-input').prop('disabled', true).val(saved.main_answer).addClass('bg-gray-100');
        $('#submit-button').hide();
        $('#form-message').addClass('text-green-600').text('✅ Ответ принят ранее: ' + saved.main_answer);
    } else {
        $('#answer-input').prop('disabled', false).val('');
        $('#submit-button').show().prop('disabled', false).text('Отправить ответ');
    }

// Дополнительные вопросы
    if (step.additional_questions && step.additional_questions.length) {
        showExtraQuestions(step.additional_questions);
    }
}


///


function onAnswerResponse(data) {
    if (data.success) {
        $('#form-message').removeClass('text-red-600').addClass('text-green-600').text(data.message || '✅ Ответ принят!');
        $('#answer-input').prop('disabled', true).val('✅ Ответ принят');
        $('#submit-button').hide();
        correctMainAnswer = true;

// Переход к следующему шагу или дополнительные вопросы
        if (hasExtraQuestion) {
            showExtraQuestions();
        } else {
            setTimeout(function() {
                reloadData();
            }, 800);
        }
    } else {
        $('#form-message').removeClass('text-green-600').addClass('text-red-600').text('❌ ' + (data.error || 'Ошибка'));
        $('#submit-button').prop('disabled', false).text('Отправить ответ');
    }
}


///


function finishQuest() {
    $('#quest-finish').fadeIn();
}



///


function showExtraQuestions() {
    if (!extraQuestions.length) return;
    $('#extra-questions-list').empty();

    extraQuestions.forEach(function(extra, index) {
        var header = extra.title || extra.question || 'Доп. вопрос';
        var $form = $('<form>', { class: 'extra-answer-form mb-6' }).append(
            $('<input>', { type: 'text', name: 'answer', placeholder: header }),
            $('<button>', { type: 'submit', text: 'Ответить' })
        );
        $('#extra-questions-list').append($form);

        $form.on('submit', function(e) {
            e.preventDefault();
            handleExtraAnswerSubmit(this);
        });
    });

    $('#extra-questions-container').removeClass('hidden');
}

