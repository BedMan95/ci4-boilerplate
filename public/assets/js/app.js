import { api, ApiCache } from './core/api.js';
import { AppState, showToast } from './core/state.js';
import { renderLogin, renderRegister } from './views/auth.view.js';
import { renderDashboard } from './views/dashboard.view.js';
import { renderUsers } from './views/users.view.js';
import { renderModules, renderModulePage } from './views/modules.view.js';

/**
 * CLIENT-SIDE ROUTER
 */
export const Router = {
    routes: {
        '/': () => AppState.isAuthenticated() ? renderDashboard() : renderLogin(),
        '/login': () => AppState.isAuthenticated() ? Router.navigate('/dashboard') : renderLogin(),
        '/register': () => AppState.isAuthenticated() ? Router.navigate('/dashboard') : renderRegister(),
        '/dashboard': () => AppState.isAuthenticated() ? renderDashboard() : Router.navigate('/login'),
        '/users': () => AppState.isAuthenticated() ? renderUsers() : Router.navigate('/login'),
        '/modules': () => AppState.isAuthenticated() ? renderModules() : Router.navigate('/login'),
    },

    init() {
        window.addEventListener('hashchange', () => this.handleRoute());
        window.addEventListener('nav-updated', () => this.updateNavHighlight());
        this.handleRoute();
    },

    navigate(path) {
        window.location.hash = path;
    },

    updateNavHighlight() {
        const hash = window.location.hash.slice(1) || '/';
        const cleanPath = hash.split('?')[0];

        document.querySelectorAll('.nav-item').forEach(el => {
            el.classList.remove('bg-slate-800', 'text-white', 'text-teal-400');
            el.classList.add('text-slate-300');
        });
        if (cleanPath === '/dashboard') {
            document.getElementById('nav-dashboard')?.classList.add('bg-slate-800', 'text-white');
        } else if (cleanPath === '/users') {
            document.getElementById('nav-users')?.classList.add('bg-slate-800', 'text-white');
        } else if (cleanPath === '/modules') {
            document.getElementById('nav-modules')?.classList.add('bg-slate-800', 'text-white');
        } else if (cleanPath.startsWith('/m/')) {
            const slug = cleanPath.replace('/m/', '');
            document.getElementById(`nav-mod-${slug}`)?.classList.add('bg-slate-800', 'text-teal-400');
        }
    },

    handleRoute() {
        const hash = window.location.hash.slice(1) || '/';
        const cleanPath = hash.split('?')[0];

        closeMobileSidebar();
        this.updateNavHighlight();

        if (cleanPath.startsWith('/m/')) {
            if (!AppState.isAuthenticated()) {
                return Router.navigate('/login');
            }
            const slug = cleanPath.replace('/m/', '');
            return renderModulePage(slug);
        }

        const handler = this.routes[cleanPath] || this.routes['/'];
        handler();
    }
};

// Sidebar Mobile Controls
const sidebar = document.getElementById('app-sidebar');
const backdrop = document.getElementById('sidebar-backdrop');
function openMobileSidebar() {
    sidebar?.classList.remove('-translate-x-full');
    backdrop?.classList.remove('hidden');
}
function closeMobileSidebar() {
    sidebar?.classList.add('-translate-x-full');
    backdrop?.classList.add('hidden');
}

document.getElementById('btn-sidebar-toggle')?.addEventListener('click', openMobileSidebar);
document.getElementById('btn-sidebar-close')?.addEventListener('click', closeMobileSidebar);
backdrop?.addEventListener('click', closeMobileSidebar);

// Global Actions
document.getElementById('btn-logout')?.addEventListener('click', async () => {
    try {
        await api.post('/api/auth/logout');
    } catch (_) {}
    AppState.setUser(null);
    ApiCache.clear();
    showToast('Anda telah logout');
    Router.navigate('/login');
});

document.getElementById('btn-cache-sync')?.addEventListener('click', () => {
    ApiCache.clear();
    showToast('Cache dibersihkan. Menyinkronkan data...');
    Router.handleRoute();
});

// App Initialization
AppState.syncHeader();
Router.init();
