<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 text-slate-900 antialiased">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CI4 Modern SPA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            900: '#134e4a',
                        }
                    }
                }
            }
        };
    </script>
    <style>
        :focus-visible { outline: 2px solid #0d9488; outline-offset: 2px; }
        .fade-in { animation: fadeIn 0.15s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(3px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="min-h-full flex flex-col font-sans">
    <div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-sm pointer-events-none"></div>

    <header id="app-header" class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm hidden">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14">
                <div class="flex items-center gap-6">
                    <a href="#/dashboard" class="flex items-center gap-2 font-semibold text-slate-900 hover:text-brand-700 tracking-tight transition">
                        <span class="w-6 h-6 rounded bg-slate-900 text-teal-400 text-xs font-mono flex items-center justify-center font-bold">CI</span>
                        <span>SPA Platform</span>
                    </a>
                    <nav class="flex items-center gap-1">
                        <a href="#/dashboard" id="nav-dashboard" class="nav-item px-3 py-1.5 text-sm font-medium rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition">Dashboard</a>
                        <a href="#/users" id="nav-users" class="nav-item px-3 py-1.5 text-sm font-medium rounded-md text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition hidden">Users</a>
                    </nav>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 text-xs">
                        <span id="nav-user-name" class="font-medium text-slate-800"></span>
                        <span id="nav-user-role" class="px-1.5 py-0.5 rounded border border-slate-200 bg-slate-100 text-slate-600 font-mono"></span>
                    </div>
                    <span class="text-slate-300">|</span>
                    <button id="btn-cache-sync" title="Sync data dari server" class="text-xs text-slate-500 hover:text-brand-600 font-medium flex items-center gap-1 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Sync</span>
                    </button>
                    <span class="text-slate-300">|</span>
                    <button id="btn-logout" class="text-xs font-medium text-rose-600 hover:text-rose-800 transition">Logout</button>
                </div>
            </div>
        </div>
    </header>

    <main id="app-viewport" class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8"></main>

    <footer class="border-t border-slate-200 py-4 text-center text-xs text-slate-400 bg-white">
        CodeIgniter 4 SPA Engine &bull; Client-Side Cache Active &bull; Zero Server Redownloads
    </footer>

    <script>
    /**
     * INITIAL BOOTSTRAP STATE
     * ponytail: Hydrated directly from server to eliminate initial session API call
     */
    window.__INITIAL_STATE__ = {
        user: <?= json_encode($initialUser) ?>
    };

    /**
     * CACHE & DATA STORE LAYER
     * Eliminates frequent API requests via In-Memory Caching + SWR (Stale-While-Revalidate)
     */
    const ApiCache = {
        store: new Map(),
        inFlight: new Map(),
        defaultTtl: 60000, // 60 seconds TTL

        get(key) {
            const item = this.store.get(key);
            if (!item) return null;
            if (Date.now() > item.expiresAt) {
                this.store.delete(key);
                return null;
            }
            return item.data;
        },

        set(key, data, ttl = this.defaultTtl) {
            this.store.set(key, {
                data,
                expiresAt: Date.now() + ttl,
                storedAt: Date.now()
            });
        },

        invalidate(tag) {
            for (const key of this.store.keys()) {
                if (key.startsWith(tag)) {
                    this.store.delete(key);
                }
            }
        },

        clear() {
            this.store.clear();
            this.inFlight.clear();
        }
    };

    /**
     * CENTRAL API CLIENT WITH DEDUPLICATION & CACHING
     */
    const api = {
        async request(url, options = {}, { cache = false, ttl = 60000, force = false } = {}) {
            const cacheKey = `${options.method || 'GET'}:${url}`;

            if (cache && !force) {
                const cachedData = ApiCache.get(cacheKey);
                if (cachedData !== null) {
                    return cachedData; // Returned immediately from memory!
                }
            }

            if (ApiCache.inFlight.has(cacheKey)) {
                return ApiCache.inFlight.get(cacheKey);
            }

            const fetchPromise = (async () => {
                const headers = {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(options.headers || {})
                };

                try {
                    const response = await fetch(url, { ...options, headers });
                    const result = await response.json();

                    if (!response.ok) {
                        if (response.status === 401) {
                            AppState.setUser(null);
                            Router.navigate('/login');
                        }
                        const error = new Error(result.message || 'Request failed');
                        error.status = response.status;
                        error.errors = result.errors;
                        throw error;
                    }

                    if (cache) {
                        ApiCache.set(cacheKey, result, ttl);
                    }

                    return result;
                } finally {
                    ApiCache.inFlight.delete(cacheKey);
                }
            })();

            ApiCache.inFlight.set(cacheKey, fetchPromise);
            return fetchPromise;
        },

        get(url, opts = {}) {
            return this.request(url, { method: 'GET' }, opts);
        },

        post(url, data, opts = {}) {
            return this.request(url, {
                method: 'POST',
                body: JSON.stringify(data)
            }, opts);
        },

        put(url, data, opts = {}) {
            return this.request(url, {
                method: 'PUT',
                body: JSON.stringify(data)
            }, opts);
        },

        delete(url, opts = {}) {
            return this.request(url, { method: 'DELETE' }, opts);
        }
    };

    /**
     * GLOBAL REACTIVE APP STATE
     */
    const AppState = {
        user: window.__INITIAL_STATE__.user,

        setUser(user) {
            this.user = user;
            this.syncHeader();
        },

        isAuthenticated() {
            return Boolean(this.user && this.user.id);
        },

        isAdmin() {
            return this.user && this.user.role === 'admin';
        },

        syncHeader() {
            const header = document.getElementById('app-header');
            const navUsers = document.getElementById('nav-users');
            const navName = document.getElementById('nav-user-name');
            const navRole = document.getElementById('nav-user-role');

            if (this.isAuthenticated()) {
                header.classList.remove('hidden');
                navName.textContent = this.user.username;
                navRole.textContent = this.user.role;
                if (this.isAdmin()) {
                    navUsers.classList.remove('hidden');
                } else {
                    navUsers.classList.add('hidden');
                }
            } else {
                header.classList.add('hidden');
            }
        }
    };

    /**
     * UTILITY HELPERS
     */
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        const bg = type === 'success' ? 'bg-teal-700 text-white' : 'bg-rose-700 text-white';
        toast.className = `${bg} px-4 py-3 rounded-lg shadow-lg text-sm flex items-center justify-between gap-3 pointer-events-auto fade-in`;
        toast.innerHTML = `<span>${escapeHtml(message)}</span><button class="opacity-70 hover:opacity-100 font-bold ml-2">&times;</button>`;
        toast.querySelector('button').onclick = () => toast.remove();
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }

    /**
     * VIEWS & CONTROLLERS
     */
    const Views = {
        renderLogin() {
            const viewport = document.getElementById('app-viewport');
            viewport.innerHTML = `
                <div class="max-w-md mx-auto mt-12 bg-white p-8 rounded-xl border border-slate-200 shadow-sm fade-in">
                    <div class="mb-6 text-center">
                        <div class="inline-flex w-10 h-10 rounded-lg bg-slate-900 text-teal-400 items-center justify-center font-mono font-bold text-sm mb-2">CI</div>
                        <h2 class="text-xl font-bold text-slate-900">Masuk ke Akun</h2>
                        <p class="text-xs text-slate-500 mt-1">Gunakan akun terdaftar Anda untuk melanjutkan</p>
                    </div>

                    <form id="login-form" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email</label>
                            <input type="email" name="email" required placeholder="admin@boilerplate.local" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:border-teal-500 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Password</label>
                            <input type="password" name="password" required placeholder="••••••••" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:border-teal-500 transition">
                        </div>
                        <button type="submit" id="btn-login-submit" class="w-full py-2.5 px-4 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition shadow-sm">
                            Masuk
                        </button>
                    </form>

                    <p class="mt-6 text-center text-xs text-slate-500">
                        Belum punya akun? <a href="#/register" class="font-semibold text-teal-600 hover:text-teal-700">Daftar sekarang</a>
                    </p>
                </div>
            `;

            document.getElementById('login-form').onsubmit = async (e) => {
                e.preventDefault();
                const btn = document.getElementById('btn-login-submit');
                btn.disabled = true;
                btn.textContent = 'Memproses...';

                const formData = new FormData(e.target);
                try {
                    const res = await api.post('/api/auth/login', Object.fromEntries(formData));
                    AppState.setUser(res.data);
                    ApiCache.clear();
                    showToast('Selamat datang kembali, ' + res.data.username);
                    Router.navigate('/dashboard');
                } catch (err) {
                    showToast(err.message, 'error');
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Masuk';
                }
            };
        },

        renderRegister() {
            const viewport = document.getElementById('app-viewport');
            viewport.innerHTML = `
                <div class="max-w-md mx-auto mt-8 bg-white p-8 rounded-xl border border-slate-200 shadow-sm fade-in">
                    <div class="mb-6 text-center">
                        <div class="inline-flex w-10 h-10 rounded-lg bg-slate-900 text-teal-400 items-center justify-center font-mono font-bold text-sm mb-2">CI</div>
                        <h2 class="text-xl font-bold text-slate-900">Pendaftaran Akun</h2>
                        <p class="text-xs text-slate-500 mt-1">Daftar untuk mengakses sistem</p>
                    </div>

                    <form id="register-form" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Username</label>
                            <input type="text" name="username" required minlength="3" placeholder="johndoe" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:border-teal-500 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Email</label>
                            <input type="email" name="email" required placeholder="john@example.com" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:border-teal-500 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Password</label>
                            <input type="password" name="password" required minlength="8" placeholder="Minimal 8 karakter" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:border-teal-500 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Konfirmasi Password</label>
                            <input type="password" name="password_confirm" required minlength="8" placeholder="Ulangi password" class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:border-teal-500 transition">
                        </div>
                        <button type="submit" id="btn-reg-submit" class="w-full py-2.5 px-4 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition shadow-sm">
                            Daftar
                        </button>
                    </form>

                    <p class="mt-6 text-center text-xs text-slate-500">
                        Sudah punya akun? <a href="#/login" class="font-semibold text-teal-600 hover:text-teal-700">Masuk di sini</a>
                    </p>
                </div>
            `;

            document.getElementById('register-form').onsubmit = async (e) => {
                e.preventDefault();
                const btn = document.getElementById('btn-reg-submit');
                btn.disabled = true;
                btn.textContent = 'Mendaftarkan...';

                const formData = new FormData(e.target);
                try {
                    const res = await api.post('/api/auth/register', Object.fromEntries(formData));
                    showToast(res.message);
                    Router.navigate('/login');
                } catch (err) {
                    showToast(err.message, 'error');
                } finally {
                    btn.disabled = false;
                    btn.textContent = 'Daftar';
                }
            };
        },

        async renderDashboard() {
            const viewport = document.getElementById('app-viewport');
            viewport.innerHTML = `
                <div class="fade-in space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Dashboard Ringkasan</h1>
                            <p class="text-xs text-slate-500 mt-1">Sistem informasi realtime dengan in-memory cache.</p>
                        </div>
                        <span id="cache-badge" class="text-[11px] font-mono px-2.5 py-1 rounded bg-slate-100 text-slate-600 border border-slate-200">
                            Memeriksa data...
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Pengguna</span>
                            <div id="stat-total" class="text-3xl font-bold text-slate-900 mt-2 font-mono">-</div>
                        </div>
                        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                            <span class="text-xs font-semibold text-teal-600 uppercase tracking-wider">Administrator</span>
                            <div id="stat-admin" class="text-3xl font-bold text-teal-600 mt-2 font-mono">-</div>
                        </div>
                        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">User Standar</span>
                            <div id="stat-user" class="text-3xl font-bold text-slate-700 mt-2 font-mono">-</div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-2">Keunggulan Arsitektur SPA</h2>
                        <ul class="text-xs text-slate-600 space-y-2 list-disc list-inside">
                            <li><strong class="text-slate-800">In-Memory SWR Cache:</strong> Perpindahan tab antar dashboard dan users tidak mengirim request berulang ke backend.</li>
                            <li><strong class="text-slate-800">Auto Invalidation:</strong> Cache otomatis dibersihkan secara instan begitu ada perubahan data (create/update/delete).</li>
                            <li><strong class="text-slate-800">Instant Routing:</strong> Navigasi 0-millisecond tanpa refresh halaman penuh.</li>
                        </ul>
                    </div>
                </div>
            `;

            try {
                const isCached = ApiCache.get('GET:/api/dashboard/stats') !== null;
                const res = await api.get('/api/dashboard/stats', { cache: true, ttl: 60000 });
                const stats = res.data;

                document.getElementById('stat-total').textContent = stats.totalUsers;
                document.getElementById('stat-admin').textContent = stats.adminCount;
                document.getElementById('stat-user').textContent = stats.userCount;

                const badge = document.getElementById('cache-badge');
                if (badge) {
                    badge.textContent = isCached ? 'Loaded from Client Cache (0 API calls)' : 'Synced from Server API';
                    badge.className = isCached
                        ? 'text-[11px] font-mono px-2.5 py-1 rounded bg-teal-50 text-teal-700 border border-teal-200'
                        : 'text-[11px] font-mono px-2.5 py-1 rounded bg-slate-100 text-slate-600 border border-slate-200';
                }
            } catch (err) {
                showToast(err.message, 'error');
            }
        },

        async renderUsers() {
            if (!AppState.isAdmin()) {
                Router.navigate('/dashboard');
                showToast('Hanya admin yang dapat mengakses menu Pengguna', 'error');
                return;
            }

            const viewport = document.getElementById('app-viewport');
            viewport.innerHTML = `
                <div class="fade-in space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Manajemen Pengguna</h1>
                            <p class="text-xs text-slate-500 mt-1">Kelola data akun pengguna secara terpusat.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span id="user-cache-badge" class="text-[11px] font-mono px-2.5 py-1 rounded bg-slate-100 text-slate-600 border border-slate-200">
                                Cache...
                            </span>
                            <button id="btn-open-user-modal" class="px-3.5 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition shadow-sm flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>Tambah Pengguna</span>
                            </button>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" id="user-search-input" placeholder="Cari username atau email (filter instan)..." class="w-full text-xs text-slate-800 bg-transparent border-none focus:outline-none">
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider">
                                    <tr>
                                        <th class="px-5 py-3">ID</th>
                                        <th class="px-5 py-3">Username</th>
                                        <th class="px-5 py-3">Email</th>
                                        <th class="px-5 py-3">Role</th>
                                        <th class="px-5 py-3">Status</th>
                                        <th class="px-5 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="users-tbody" class="divide-y divide-slate-100 font-medium text-slate-700">
                                    <tr><td colspan="6" class="px-5 py-6 text-center text-slate-400">Memuat data pengguna...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- MODAL USER -->
                <div id="user-modal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
                    <div class="bg-white w-full max-w-md rounded-xl shadow-xl border border-slate-200 p-6 fade-in">
                        <div class="flex items-center justify-between mb-4">
                            <h3 id="modal-title" class="text-base font-bold text-slate-900">Tambah Pengguna</h3>
                            <button id="btn-close-modal" class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
                        </div>
                        <form id="user-form" class="space-y-4">
                            <input type="hidden" id="form-user-id" name="id">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Username</label>
                                <input type="text" id="form-username" name="username" required minlength="3" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:border-teal-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
                                <input type="email" id="form-email" name="email" required class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:border-teal-500">
                            </div>
                            <div>
                                <label id="label-password" class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
                                <input type="password" id="form-password" name="password" minlength="8" placeholder="Minimal 8 karakter" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:border-teal-500">
                                <span id="help-password" class="text-[10px] text-slate-400 hidden">Biarkan kosong jika tidak ingin mengubah password.</span>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Role</label>
                                <select id="form-role" name="role" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:border-teal-500">
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" id="btn-cancel-modal" class="px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                                <button type="submit" id="btn-save-user" class="px-4 py-2 text-xs font-semibold bg-teal-600 hover:bg-teal-700 text-white rounded-lg">Simpan</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;

            let localUsers = [];

            const renderTable = (users) => {
                const tbody = document.getElementById('users-tbody');
                if (!users.length) {
                    tbody.innerHTML = '<tr><td colspan="6" class="px-5 py-6 text-center text-slate-400">Tidak ada data pengguna yang sesuai.</td></tr>';
                    return;
                }

                tbody.innerHTML = users.map(u => `
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-5 py-3.5 font-mono text-slate-500">#${u.id}</td>
                        <td class="px-5 py-3.5 font-semibold text-slate-900">${escapeHtml(u.username)}</td>
                        <td class="px-5 py-3.5 text-slate-600">${escapeHtml(u.email)}</td>
                        <td class="px-5 py-3.5">
                            <span class="px-2 py-0.5 rounded text-[11px] font-mono ${u.role === 'admin' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-100 text-slate-600 border border-slate-200'}">
                                ${escapeHtml(u.role)}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center gap-1 text-[11px] text-teal-700 font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span> Aktif
                            </span>
                        </td>
                        <td class="px-5 py-3.5 text-right space-x-2">
                            <button data-action="edit" data-id="${u.id}" class="text-teal-600 hover:text-teal-800 font-semibold text-xs">Edit</button>
                            ${Number(u.id) !== Number(AppState.user.id) ? `<button data-action="delete" data-id="${u.id}" class="text-rose-600 hover:text-rose-800 font-semibold text-xs">Hapus</button>` : ''}
                        </td>
                    </tr>
                `).join('');
            };

            const loadData = async (force = false) => {
                const cacheKey = 'GET:/api/users?per_page=50';
                const isCached = !force && ApiCache.get(cacheKey) !== null;

                const res = await api.get('/api/users?per_page=50', { cache: true, ttl: 60000, force });
                localUsers = res.data;
                renderTable(localUsers);

                const badge = document.getElementById('user-cache-badge');
                if (badge) {
                    badge.textContent = isCached ? 'Client Cache (0 Calls)' : 'Synced Server';
                    badge.className = isCached
                        ? 'text-[11px] font-mono px-2.5 py-1 rounded bg-teal-50 text-teal-700 border border-teal-200'
                        : 'text-[11px] font-mono px-2.5 py-1 rounded bg-slate-100 text-slate-600 border border-slate-200';
                }
            };

            await loadData();

            // Client-side Instant Filter (NO API calls!)
            document.getElementById('user-search-input').oninput = (e) => {
                const q = e.target.value.toLowerCase().trim();
                if (!q) {
                    renderTable(localUsers);
                    return;
                }
                const filtered = localUsers.filter(u =>
                    u.username.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
                );
                renderTable(filtered);
            };

            // Modal Controls
            const modal = document.getElementById('user-modal');
            const openModal = (editData = null) => {
                const form = document.getElementById('user-form');
                form.reset();
                if (editData) {
                    document.getElementById('modal-title').textContent = 'Edit Pengguna #' + editData.id;
                    document.getElementById('form-user-id').value = editData.id;
                    document.getElementById('form-username').value = editData.username;
                    document.getElementById('form-email').value = editData.email;
                    document.getElementById('form-role').value = editData.role;
                    document.getElementById('form-password').required = false;
                    document.getElementById('help-password').classList.remove('hidden');
                } else {
                    document.getElementById('modal-title').textContent = 'Tambah Pengguna Baru';
                    document.getElementById('form-user-id').value = '';
                    document.getElementById('form-password').required = true;
                    document.getElementById('help-password').classList.add('hidden');
                }
                modal.classList.remove('hidden');
            };

            const closeModal = () => modal.classList.add('hidden');
            document.getElementById('btn-open-user-modal').onclick = () => openModal();
            document.getElementById('btn-close-modal').onclick = closeModal;
            document.getElementById('btn-cancel-modal').onclick = closeModal;

            // Form Submit (Create / Edit)
            document.getElementById('user-form').onsubmit = async (e) => {
                e.preventDefault();
                const saveBtn = document.getElementById('btn-save-user');
                saveBtn.disabled = true;
                saveBtn.textContent = 'Menyimpan...';

                const id = document.getElementById('form-user-id').value;
                const payload = {
                    username: document.getElementById('form-username').value,
                    email: document.getElementById('form-email').value,
                    role: document.getElementById('form-role').value,
                };
                const pass = document.getElementById('form-password').value;
                if (pass) payload.password = pass;

                try {
                    if (id) {
                        await api.put(`/api/users/${id}`, payload);
                        showToast('User berhasil diperbarui');
                    } else {
                        await api.post('/api/users', payload);
                        showToast('User baru berhasil ditambahkan');
                    }
                    // Invalidate caches since mutations happened
                    ApiCache.invalidate('GET:/api/users');
                    ApiCache.invalidate('GET:/api/dashboard');
                    closeModal();
                    await loadData(true);
                } catch (err) {
                    showToast(err.message, 'error');
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Simpan';
                }
            };

            // Table Actions (Edit & Delete)
            document.getElementById('users-tbody').onclick = async (e) => {
                const btn = e.target.closest('button');
                if (!btn) return;
                const action = btn.dataset.action;
                const id = Number(btn.dataset.id);

                if (action === 'edit') {
                    const user = localUsers.find(u => Number(u.id) === id);
                    if (user) openModal(user);
                } else if (action === 'delete') {
                    if (confirm(`Yakin ingin menghapus user #${id}?`)) {
                        try {
                            await api.delete(`/api/users/${id}`);
                            showToast('User berhasil dihapus');
                            ApiCache.invalidate('GET:/api/users');
                            ApiCache.invalidate('GET:/api/dashboard');
                            await loadData(true);
                        } catch (err) {
                            showToast(err.message, 'error');
                        }
                    }
                }
            };
        }
    };

    /**
     * CLIENT-SIDE ROUTER
     */
    const Router = {
        routes: {
            '/': () => AppState.isAuthenticated() ? Views.renderDashboard() : Views.renderLogin(),
            '/login': () => AppState.isAuthenticated() ? Router.navigate('/dashboard') : Views.renderLogin(),
            '/register': () => AppState.isAuthenticated() ? Router.navigate('/dashboard') : Views.renderRegister(),
            '/dashboard': () => AppState.isAuthenticated() ? Views.renderDashboard() : Router.navigate('/login'),
            '/users': () => AppState.isAuthenticated() ? Views.renderUsers() : Router.navigate('/login'),
        },

        init() {
            window.addEventListener('hashchange', () => this.handleRoute());
            this.handleRoute();
        },

        navigate(path) {
            window.location.hash = path;
        },

        handleRoute() {
            const hash = window.location.hash.slice(1) || '/';
            const cleanPath = hash.split('?')[0];

            // Update active nav styling
            document.querySelectorAll('.nav-item').forEach(el => {
                el.classList.remove('bg-slate-100', 'text-slate-900');
                el.classList.add('text-slate-600');
            });
            if (cleanPath === '/dashboard') {
                document.getElementById('nav-dashboard')?.classList.add('bg-slate-100', 'text-slate-900');
            } else if (cleanPath === '/users') {
                document.getElementById('nav-users')?.classList.add('bg-slate-100', 'text-slate-900');
            }

            const handler = this.routes[cleanPath] || this.routes['/'];
            handler();
        }
    };

    // Header Global Events
    document.getElementById('btn-logout').onclick = async () => {
        try {
            await api.post('/api/auth/logout');
        } catch (_) {}
        AppState.setUser(null);
        ApiCache.clear();
        showToast('Anda telah logout');
        Router.navigate('/login');
    };

    document.getElementById('btn-cache-sync').onclick = () => {
        ApiCache.clear();
        showToast('Cache dibersihkan. Menyinkronkan data...');
        Router.handleRoute();
    };

    // Bootstrap
    AppState.syncHeader();
    Router.init();
    </script>
</body>
</html>
