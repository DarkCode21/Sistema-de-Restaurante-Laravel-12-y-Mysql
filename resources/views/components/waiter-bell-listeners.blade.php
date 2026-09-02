<script>
    const registerWaiterBellListeners = () => {
        if (window.__waiterBellListenersRegistered) {
            return;
        }

        window.__waiterBellListenersRegistered = true;
        const storageKey = 'restaurant-waiter-bell-enabled';
        let audioContext;
        let bellEnabled = localStorage.getItem(storageKey) !== 'false';
        let toastTimeout;

        const syncBellToggle = () => {
            document.querySelectorAll('[data-waiter-bell-toggle]').forEach((toggle) => {
                const label = toggle.querySelector('[data-waiter-bell-label]');

                toggle.setAttribute('aria-pressed', String(bellEnabled));
                toggle.title = bellEnabled ? 'Silenciar campana' : 'Activar campana';
                label.textContent = bellEnabled ? 'Silenciar campana' : 'Activar campana';
                toggle.classList.toggle('bg-emerald-50', bellEnabled);
                toggle.classList.toggle('border-emerald-200', bellEnabled);
                toggle.classList.toggle('text-emerald-700', bellEnabled);
            });
        };

        const activateAudio = async () => {
            const AudioContext = window.AudioContext || window.webkitAudioContext;

            if (!AudioContext) {
                return;
            }

            audioContext ??= new AudioContext();
            await audioContext.resume();
        };

        const ringBell = () => {
            if (!bellEnabled || !audioContext || audioContext.state !== 'running') {
                return;
            }

            [988, 1319].forEach((frequency, index) => {
                const oscillator = audioContext.createOscillator();
                const gain = audioContext.createGain();
                const startAt = audioContext.currentTime + (index * 0.14);

                oscillator.type = 'sine';
                oscillator.frequency.value = frequency;
                gain.gain.setValueAtTime(0.0001, startAt);
                gain.gain.exponentialRampToValueAtTime(0.18, startAt + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, startAt + 0.55);
                oscillator.connect(gain).connect(audioContext.destination);
                oscillator.start(startAt);
                oscillator.stop(startAt + 0.56);
            });
        };

        document.addEventListener('pointerdown', () => {
            if (bellEnabled) {
                activateAudio().catch(() => {});
            }
        }, { once: true });

        document.addEventListener('click', async (event) => {
            const toggle = event.target.closest('[data-waiter-bell-toggle]');

            if (!toggle) {
                return;
            }

            bellEnabled = !bellEnabled;
            localStorage.setItem(storageKey, String(bellEnabled));

            if (bellEnabled) {
                await activateAudio();
            }

            syncBellToggle();
            ringBell();
        });

        Livewire.on('order-ready-for-service', (event) => {
            const toast = document.querySelector('#waiter-ready-toast');
            const message = document.querySelector('[data-waiter-ready-message]');

            if (!toast || !message) {
                return;
            }

            message.textContent = `${event.tableName || 'Pedido'}: todos los platos de cocina están listos.`;
            toast.classList.remove('hidden');
            ringBell();
            clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => toast.classList.add('hidden'), 5000);
        });

        syncBellToggle();
    };

    if (window.Livewire) {
        registerWaiterBellListeners();
    } else {
        document.addEventListener('livewire:init', registerWaiterBellListeners, { once: true });
    }
</script>
