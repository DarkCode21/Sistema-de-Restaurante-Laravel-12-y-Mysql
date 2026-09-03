<script>
    const registerCashierBellListeners = () => {
        if (window.__cashierBellListenersRegistered) {
            return;
        }

        window.__cashierBellListenersRegistered = true;
        const bellEnabled = @js((bool) ($empresa->alert_sounds_enabled ?? true));
        let audioContext;
        let toastTimeout;

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

            [740, 988].forEach((frequency, index) => {
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

        Livewire.on('order-ready-for-checkout', (event) => {
            const toast = document.querySelector('#cashier-ready-toast');
            const message = document.querySelector('[data-cashier-ready-message]');

            if (!toast || !message) {
                return;
            }

            message.textContent = `${event.tableName || 'Pedido'} está listo para cobrar.`;
            toast.classList.remove('hidden');
            ringBell();
            clearTimeout(toastTimeout);
            toastTimeout = setTimeout(() => toast.classList.add('hidden'), 5000);
        });
    };

    if (window.Livewire) {
        registerCashierBellListeners();
    } else {
        document.addEventListener('livewire:init', registerCashierBellListeners, { once: true });
    }
</script>
