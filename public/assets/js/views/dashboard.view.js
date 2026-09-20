import { api, ApiCache } from '../core/api.js';
import { AppState, escapeHtml } from '../core/state.js';

export async function renderDashboard() {
    const viewport = document.getElementById('app-viewport');
    viewport.innerHTML = `
        <div class="space-y-6 fade-in">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Dashboard Overview</h1>
                    <p class="text-xs text-slate-500 mt-1">Status sistem & metrik performa aplikasi</p>
                </div>
                <div class="flex items-center gap-2">
                    <span id="cache-status-badge" class="text-[11px] font-mono px-2.5 py-1 rounded bg-slate-100 text-slate-600 border border-slate-200">Checking Cache...</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" id="stats-container">
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs animate-pulse h-24"></div>
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs animate-pulse h-24"></div>
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs animate-pulse h-24"></div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-6">
                <h3 class="text-sm font-bold text-slate-900 mb-2">Informasi Sesi Pengguna</h3>
                <div class="space-y-1 text-xs text-slate-600">
                    <p><span class="font-medium text-slate-800">Akun:</span> ${escapeHtml(AppState.user?.username)} (${escapeHtml(AppState.user?.email)})</p>
                    <p><span class="font-medium text-slate-800">Role & Level:</span> <span class="uppercase font-mono text-teal-700">${escapeHtml(AppState.user?.role)}</span> • Level ${AppState.user?.level || 1}</p>
                    <p><span class="font-medium text-slate-800">Client Engine:</span> Native Modular ES Architecture • Zero Bundler</p>
                </div>
            </div>
        </div>
    `;

    try {
        const cacheKey = 'GET:/api/dashboard/stats';
        const isCached = ApiCache.get(cacheKey) !== null;

        const res = await api.get('/api/dashboard/stats', { cache: true, ttl: 30000 });
        const stats = res.data;

        const badge = document.getElementById('cache-status-badge');
        if (badge) {
            badge.textContent = isCached ? 'Loaded from Client Cache' : 'Fetched Fresh from Server';
            badge.className = isCached
                ? 'text-[11px] font-mono px-2.5 py-1 rounded bg-teal-50 text-teal-700 border border-teal-200'
                : 'text-[11px] font-mono px-2.5 py-1 rounded bg-slate-100 text-slate-600 border border-slate-200';
        }

        const totalUsers = stats.total_users ?? stats.totalUsers ?? 0;
        const activeUsers = stats.active_users ?? stats.userCount ?? 0;
        const adminUsers = stats.admins ?? stats.adminCount ?? 0;

        const statsHtml = `
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Pengguna</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">${totalUsers}</div>
                <div class="mt-1 text-[11px] text-slate-500">Terdaftar di sistem</div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">User Aktif</div>
                <div class="mt-2 text-2xl font-bold text-teal-600">${activeUsers}</div>
                <div class="mt-1 text-[11px] text-slate-500">Status akun aktif</div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Admin</div>
                <div class="mt-2 text-2xl font-bold text-slate-900">${adminUsers}</div>
                <div class="mt-1 text-[11px] text-slate-500">Akses administrator</div>
            </div>
        `;

        document.getElementById('stats-container').innerHTML = statsHtml;
    } catch (err) {
        document.getElementById('stats-container').innerHTML = `
            <div class="col-span-3 bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-xl text-xs">
                Gagal memuat data dashboard: ${escapeHtml(err.message)}
            </div>
        `;
    }
}
