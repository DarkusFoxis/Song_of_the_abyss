//jshint maxerr:1000
function getWebGLInfo() {
    try {
        const canvas = document.createElement('canvas');
        const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');

        if (!gl) {
            return 'WebGL не поддерживается';
        }

        const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
        return {
            vendor: debugInfo ? gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL) : 'недоступно',
            renderer: debugInfo ? gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) : 'недоступно',
            version: gl.getParameter(gl.VERSION)
        };
    } catch (e) {
        return 'ошибка получения данных WebGL';
    }
}

document.getElementById('startBtn').addEventListener('click', async () => {
    const consentCheckbox = document.getElementById('consentCheck');
    const statusMessage = document.getElementById('statusMessage');
    const errorMessage = document.getElementById('errorMessage');
    const progressBar = document.getElementById('progressBar');

    statusMessage.innerHTML = '';
    errorMessage.innerHTML = '';
    progressBar.style.width = '0%';

    if (!consentCheckbox.checked) {
        errorMessage.innerHTML = 'Ошибка: Необходимо дать согласие на сбор данных';
        return;
    }

    try {
        statusMessage.innerHTML = 'Подготовка к сбору данных...';
        progressBar.style.width = '10%';

        const deviceData = {
            userAgent: navigator.userAgent,
            platform: navigator.platform,
            language: navigator.language,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            screen: {
                width: screen.width,
                height: screen.height,
                colorDepth: screen.colorDepth
            },
            hardwareConcurrency: navigator.hardwareConcurrency || 'недоступно',
            deviceMemory: navigator.deviceMemory || 'недоступно',
            timestamp: new Date().toISOString()
        };

        statusMessage.innerHTML = 'Измерение скорости соединения...';
        progressBar.style.width = '30%';

        const pingTimes = [];
        for (let i = 0; i < 3; i++) {
            const start = performance.now();
            await fetch('ping.php?t=' + Date.now(), {
                method: 'HEAD',
                cache: 'no-store'
            });
            const duration = performance.now() - start;
            pingTimes.push(duration);
        }

        const averagePing = Math.round(pingTimes.reduce((a, b) => a + b, 0) / pingTimes.length);
        deviceData.ping = averagePing;

        statusMessage.innerHTML = 'Определение местоположения...';
        progressBar.style.width = '50%';

        if ("geolocation" in navigator) {
            const position = await new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(resolve, reject);
            });

            deviceData.location = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                accuracy: position.coords.accuracy + ' метров'
            };
        } else {
            deviceData.location = 'недоступно';
        }

        statusMessage.innerHTML = 'Анализ графической системы...';
        progressBar.style.width = '60%';

        const glInfo = getWebGLInfo();
        deviceData.webgl = glInfo;

        deviceData.pixelDensity = window.devicePixelRatio;

        deviceData.webAssembly = typeof WebAssembly === 'object';

        deviceData.viewport = {
            width: window.innerWidth,
            height: window.innerHeight
        };

        deviceData.touchSupport = 'ontouchstart' in window;

        if ('getBattery' in navigator) {
            try {
                const battery = await navigator.getBattery();
                deviceData.battery = {
                    level: battery.level,
                    charging: battery.charging,
                    chargingTime: battery.chargingTime,
                    dischargingTime: battery.dischargingTime
                };
            } catch (e) {
                deviceData.battery = 'недоступно';
            }
        }

        deviceData.referrer = document.referrer || 'прямой заход';

        deviceData.pageLoadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;

        deviceData.serviceWorker = 'serviceWorker' in navigator;

        statusMessage.innerHTML = 'Анализ сетевых параметров...';
        progressBar.style.width = '70%';

        if ("connection" in navigator) {
            const connection = navigator.connection;
            deviceData.connection = {
                type: connection.type,
                effectiveType: connection.effectiveType,
                rtt: connection.rtt,
                downlink: connection.downlink,
                saveData: connection.saveData
            };
        }

        statusMessage.innerHTML = 'Отправка данных на сервер...';
        progressBar.style.width = '90%';
        const response = await fetch('save_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(deviceData)
        });

        const result = await response.json();

        if (result.success) {
            progressBar.style.width = '100%';
            statusMessage.innerHTML = `
                <div class="alert alert-success py-2">
                    Данные успешно отправлены! Спасибо за вашу помощь.
                </div>
                <div class="mt-2">
                    <strong>Ваш пинг:</strong> ${averagePing}мс
                </div>
            `;
        } else {
            throw new Error(result.message || 'Ошибка при сохранении данных');
        }
    } catch (error) {
        console.error('Ошибка:', error);
        errorMessage.innerHTML = `
            <div class="alert alert-danger py-2">
                Ошибка: ${error.message || 'Произошла ошибка при сборе данных'}
            </div>
        `;
        statusMessage.innerHTML = '';
        progressBar.style.width = '0%';
    }
});