import { api, ApiCache } from '../core/api.js';
import { AppState, showToast } from '../core/state.js';

export function renderLogin() {
    const viewport = document.getElementById('auth-viewport');
    viewport.innerHTML = `
        <div class="max-w-md w-full mx-auto bg-white p-8 rounded-xl border border-slate-200 shadow-sm fade-in">
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
            window.location.hash = '#/dashboard';
        } catch (err) {
            showToast(err.message, 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Masuk';
        }
    };
}

export function renderRegister() {
    const viewport = document.getElementById('auth-viewport');
    viewport.innerHTML = `
        <div class="max-w-md w-full mx-auto bg-white p-8 rounded-xl border border-slate-200 shadow-sm fade-in">
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
                <button type="submit" id="btn-register-submit" class="w-full py-2.5 px-4 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition shadow-sm">
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
        const btn = document.getElementById('btn-register-submit');
        btn.disabled = true;
        btn.textContent = 'Memproses...';

        const formData = new FormData(e.target);
        try {
            const res = await api.post('/api/auth/register', Object.fromEntries(formData));
            showToast(res.message);
            window.location.hash = '#/login';
        } catch (err) {
            showToast(err.message, 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Daftar';
        }
    };
}
