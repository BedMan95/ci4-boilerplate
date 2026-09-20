import { api, ApiCache } from './api.js';

export const AppState = {
    user: window.__INITIAL_STATE__?.user || null,

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
        const layout = document.getElementById('app-layout');
        const authViewport = document.getElementById('auth-viewport');
        const navAdminGroup = document.getElementById('nav-admin-group');
        const navName = document.getElementById('nav-user-name');
        const navRole = document.getElementById('nav-user-role');

        if (this.isAuthenticated()) {
            layout?.classList.remove('hidden');
            authViewport?.classList.add('hidden');
            if (authViewport) authViewport.innerHTML = '';
            if (navName) navName.textContent = this.user.username;
            if (navRole) navRole.textContent = `${this.user.role} • Lv.${this.user.level || 1}`;
            
            if (this.isAdmin()) {
                navAdminGroup?.classList.remove('hidden');
            } else {
                navAdminGroup?.classList.add('hidden');
            }
            this.loadDynamicMenu();
        } else {
            layout?.classList.add('hidden');
            authViewport?.classList.remove('hidden');
            const menuContainer = document.getElementById('dynamic-nav-modules');
            if (menuContainer) menuContainer.innerHTML = '';
        }
    },

    async loadDynamicMenu() {
        try {
            const res = await api.get('/api/modules/menu', { cache: true, ttl: 60000 });
            const menuContainer = document.getElementById('dynamic-nav-modules');
            const countBadge = document.getElementById('nav-modules-count');
            if (!menuContainer) return;

            const userLevel = Number(this.user?.level) || 1;
            const accessible = (res.data || []).filter(m => this.isAdmin() || Number(m.min_level) <= userLevel);

            if (countBadge) countBadge.textContent = accessible.length;

            if (!accessible.length) {
                menuContainer.innerHTML = '<span class="block px-3 py-1.5 text-xs text-slate-500">Tidak ada modul</span>';
                return;
            }

            menuContainer.innerHTML = accessible.map(m => `
                <a href="#/m/${m.name}" id="nav-mod-${m.name}" class="nav-item flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <span class="truncate">${escapeHtml(m.title)}</span>
                </a>
            `).join('');

            window.dispatchEvent(new CustomEvent('nav-updated'));
        } catch (_) {}
    }
};

export function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(str);
    return div.innerHTML;
}

export function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const toast = document.createElement('div');
    const bg = type === 'success' ? 'bg-teal-700 text-white' : 'bg-rose-700 text-white';
    toast.className = `${bg} px-4 py-3 rounded-lg shadow-lg text-sm flex items-center justify-between gap-3 pointer-events-auto fade-in`;
    toast.innerHTML = `<span>${escapeHtml(message)}</span><button class="opacity-70 hover:opacity-100 font-bold ml-2">&times;</button>`;
    toast.querySelector('button').onclick = () => toast.remove();
    container.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}
