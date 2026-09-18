const databaseName = 'ceviche-flow-offline';
const storeName = 'orders';
const cacheName = 'ceviche-flow-shell-v2';

function openDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(databaseName, 1);
        request.onupgradeneeded = () => request.result.createObjectStore(storeName, { keyPath: 'token' });
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function readQueuedOrders(context) {
    const database = await openDatabase();
    const orders = await new Promise((resolve, reject) => {
        const request = database.transaction(storeName).objectStore(storeName).getAll();
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
    database.close();

    return orders.filter((order) => order.user_id === context.user_id && order.branch_id === context.branch_id);
}

async function saveQueuedOrder(order) {
    const database = await openDatabase();
    await new Promise((resolve, reject) => {
        const request = database.transaction(storeName, 'readwrite').objectStore(storeName).put(order);
        request.onsuccess = resolve;
        request.onerror = () => reject(request.error);
    });
    database.close();
}

async function removeQueuedOrder(token) {
    const database = await openDatabase();
    await new Promise((resolve, reject) => {
        const request = database.transaction(storeName, 'readwrite').objectStore(storeName).delete(token);
        request.onsuccess = resolve;
        request.onerror = () => reject(request.error);
    });
    database.close();
}

function showMessage(panel, message) {
    panel.querySelector('[data-offline-message]').textContent = message;
}

function escapeHtml(value) {
    return String(value).replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[character]));
}

function customerData(context) {
    const input = (model) => document.querySelector(`[wire\\:model\\.live="${model}"]`);

    return {
        customer_name: input('customer_name')?.value || 'Consumidor Final',
        customer_phone: input('customer_phone')?.value || '',
        delivery_address: input('delivery_address')?.value || '',
        order_type: context.order_type,
        table_id: context.table_id,
    };
}

function cacheCurrentOrderPage() {
    if (!navigator.onLine || !('caches' in window)) return;

    const urls = [location.href, ...performance.getEntriesByType('resource')
        .map((entry) => entry.name)
        .filter((url) => url.startsWith(location.origin + '/build/') || url.startsWith(location.origin + '/storage/'))];

    caches.open(cacheName).then((cache) => Promise.all(urls.map(async (url) => {
        const response = await fetch(url, { credentials: 'same-origin' });
        if (response.ok) await cache.put(url, response);
    }))).catch(() => {});
}

function setupOfflineOrders(context) {
    const catalog = new Map(context.products.map((product) => [product.id, product]));
    const root = document.querySelector('[wire\\:poll\\.5s]');
    const cart = [];
    const panel = document.createElement('aside');
    panel.className = 'fixed bottom-4 right-4 z-[200] hidden w-[min(24rem,calc(100vw-2rem))] rounded-2xl border border-orange-200 bg-white p-4 shadow-2xl';
    panel.innerHTML = '<div class="flex items-center justify-between gap-3"><strong class="text-sm text-slate-800">Modo sin conexion</strong><span class="rounded-full bg-orange-100 px-2 py-1 text-[10px] font-bold text-orange-700" data-offline-count>0</span></div><p class="mt-1 text-xs text-slate-500" data-offline-message>Los pagos siguen bloqueados.</p><div class="mt-3 max-h-40 space-y-2 overflow-y-auto" data-offline-items></div><button type="button" class="mt-3 w-full rounded-lg bg-orange-600 px-4 py-2 text-xs font-bold uppercase text-white" data-offline-save>Guardar para sincronizar</button>';
    document.body.append(panel);

    const update = () => {
        panel.querySelector('[data-offline-count]').textContent = String(cart.reduce((total, item) => total + item.quantity, 0));
        panel.querySelector('[data-offline-items]').innerHTML = cart.map((item) => `<div class="rounded-lg bg-slate-50 p-2 text-xs"><div class="flex items-center justify-between gap-2"><span class="font-bold text-slate-700">${escapeHtml(item.name)}</span><span><button type="button" data-offline-change="-" data-offline-id="${item.product_id}" class="px-2 text-orange-700">-</button>${item.quantity}<button type="button" data-offline-change="+" data-offline-id="${item.product_id}" class="px-2 text-orange-700">+</button></span></div><input data-offline-note="${item.product_id}" value="${escapeHtml(item.notes)}" placeholder="Nota" class="mt-1 w-full rounded border-slate-200 py-1 text-xs"></div>`).join('') || '<p class="text-xs text-slate-400">Agrega productos del menu.</p>';
    };

    const refreshMode = () => {
        const offline = !navigator.onLine;
        panel.classList.toggle('hidden', !offline);
        if (root) {
            if (offline) root.removeAttribute('wire:poll.5s');
            else root.setAttribute('wire:poll.5s', 'refreshReadyOrderAlert');
        }
        if (offline) update();
    };

    document.addEventListener('click', (event) => {
        if (navigator.onLine) return;
        const button = event.target.closest('[data-offline-product-id]');
        if (!button) return;

        event.preventDefault();
        event.stopImmediatePropagation();
        const product = catalog.get(Number(button.dataset.offlineProductId));
        if (!product) return;
        if (!product.available_offline) {
            showMessage(panel, `${product.name} requiere configuracion y debe registrarse con conexion.`);
            return;
        }

        const item = cart.find((entry) => entry.product_id === product.id);
        if (item) item.quantity += 1;
        else cart.push({ product_id: product.id, name: product.name, quantity: 1, notes: '' });
        showMessage(panel, 'Pedido local listo para sincronizar.');
        update();
    }, true);

    panel.addEventListener('click', async (event) => {
        const change = event.target.closest('[data-offline-change]');
        if (change) {
            const item = cart.find((entry) => entry.product_id === Number(change.dataset.offlineId));
            if (!item) return;
            item.quantity += change.dataset.offlineChange === '+' ? 1 : -1;
            if (item.quantity < 1) cart.splice(cart.indexOf(item), 1);
            update();
            return;
        }

        if (!event.target.closest('[data-offline-save]') || cart.length === 0) return;
        const details = customerData(context);
        if (details.order_type === 'delivery' && (!details.customer_name || !details.customer_phone || !details.delivery_address)) {
            showMessage(panel, 'Delivery requiere cliente, telefono y direccion.');
            return;
        }
        await saveQueuedOrder({
            token: crypto.randomUUID(),
            user_id: context.user_id,
            branch_id: context.branch_id,
            ...details,
            items: cart.map((item) => ({ product_id: item.product_id, quantity: item.quantity, notes: document.querySelector(`[data-offline-note="${item.product_id}"]`)?.value || '' })),
        });
        cart.splice(0);
        showMessage(panel, 'Pedido guardado. Se enviara al recuperar conexion.');
        update();
    });

    async function synchronize() {
        if (!navigator.onLine) return;
        const orders = await readQueuedOrders(context);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        for (const order of orders) {
            try {
                const response = await fetch(context.endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify(order),
                });
                const result = await response.json();
                if (response.ok) {
                    await removeQueuedOrder(order.token);
                    window.dispatchEvent(new CustomEvent('print-job', { detail: result.print_jobs || [] }));
                }
            } catch (_) {
                return;
            }
        }
    }

    window.addEventListener('online', () => { refreshMode(); synchronize(); });
    window.addEventListener('offline', refreshMode);
    refreshMode();
    cacheCurrentOrderPage();
    synchronize();
}

document.addEventListener('submit', async (event) => {
    if (!event.target.matches('[data-clear-offline]')) return;

    event.preventDefault();
    await Promise.all([
        'indexedDB' in window ? new Promise((resolve) => {
            const request = indexedDB.deleteDatabase(databaseName);
            request.onsuccess = request.onerror = request.onblocked = resolve;
        }) : Promise.resolve(),
        'caches' in window ? caches.delete(cacheName) : Promise.resolve(),
    ]);
    event.target.submit();
});

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}

const contextElement = document.getElementById('offline-order-context');
if (contextElement && 'indexedDB' in window && 'crypto' in window && crypto.randomUUID) {
    setupOfflineOrders(JSON.parse(contextElement.textContent));
}
