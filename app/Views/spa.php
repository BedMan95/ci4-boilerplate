<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CI4 High-Performance Single-Page Application</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0fdfa',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .fade-in { animation: fadeIn 0.15s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="h-full text-slate-800 antialiased font-sans">
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"></div>

    <!-- Backdrop Mobile Overlay -->
    <div id="sidebar-backdrop" class="fixed inset-0 bg-slate-900/50 z-40 hidden md:hidden"></div>

    <div id="app-layout" class="min-h-full flex flex-col md:flex-row hidden">
        <!-- SIDEBAR -->
        <aside id="app-sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-300 flex flex-col transition-transform duration-200 -translate-x-full md:translate-x-0 md:static md:z-auto">
            <!-- Brand -->
            <div class="h-14 px-5 flex items-center justify-between border-b border-slate-800 bg-slate-950/40">
                <a href="#/dashboard" class="flex items-center gap-2.5 font-semibold text-white tracking-tight">
                    <span class="w-7 h-7 rounded-lg bg-teal-500 text-slate-950 text-xs font-mono flex items-center justify-center font-bold shadow-xs">CI</span>
                    <span class="text-sm font-bold">SPA Platform</span>
                </a>
                <button id="btn-sidebar-close" class="md:hidden text-slate-400 hover:text-white text-xl leading-none">&times;</button>
            </div>

            <!-- Navigation Links -->
            <div class="flex-1 overflow-y-auto px-3 py-4 space-y-5">
                <div>
                    <span class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">Menu Utama</span>
                    <nav class="mt-1.5 space-y-0.5">
                        <a href="#/dashboard" id="nav-dashboard" class="nav-item flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span>Dashboard</span>
                        </a>
                    </nav>
                </div>

                <!-- DYNAMIC MODULES GROUP -->
                <div>
                    <div class="px-3 flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Modul Aplikasi</span>
                        <span id="nav-modules-count" class="text-[10px] font-mono bg-slate-800 text-teal-400 px-1.5 py-0.2 rounded">0</span>
                    </div>
                    <nav id="dynamic-nav-modules" class="mt-1.5 space-y-0.5 max-h-60 overflow-y-auto pr-1">
                        <span class="block px-3 py-1.5 text-xs text-slate-500">Memuat modul...</span>
                    </nav>
                </div>

                <!-- ADMIN GROUP -->
                <div id="nav-admin-group" class="hidden">
                    <span class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500">Administrator</span>
                    <nav class="mt-1.5 space-y-0.5">
                        <a href="#/users" id="nav-users" class="nav-item flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            <span>User Management</span>
                        </a>
                        <a href="#/modules" id="nav-modules" class="nav-item flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium text-slate-300 hover:bg-slate-800 hover:text-white transition">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            <span>Manage Modules</span>
                        </a>
                    </nav>
                </div>
            </div>

            <!-- User Info Footer -->
            <div class="p-3 border-t border-slate-800 bg-slate-950/50">
                <div class="flex items-center justify-between">
                    <div class="truncate">
                        <div id="nav-user-name" class="text-xs font-semibold text-white truncate"></div>
                        <div id="nav-user-role" class="text-[10px] text-teal-400 font-mono"></div>
                    </div>
                    <button id="btn-logout" title="Logout" class="p-1.5 text-rose-400 hover:text-rose-300 hover:bg-rose-950/40 rounded transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT WRAPPER -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Mobile Header Bar -->
            <header class="bg-white border-b border-slate-200 h-14 px-4 flex items-center justify-between sticky top-0 z-30">
                <div class="flex items-center gap-3">
                    <button id="btn-sidebar-toggle" class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 md:hidden">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <span class="text-sm font-semibold text-slate-800 md:hidden">CI4 SPA</span>
                </div>
                <div class="flex items-center gap-2">
                    <button id="btn-cache-sync" title="Sync data" class="text-xs text-slate-500 hover:text-teal-600 px-2.5 py-1 rounded-md border border-slate-200 flex items-center gap-1.5 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span class="hidden sm:inline">Sync Cache</span>
                    </button>
                </div>
            </header>

            <main id="app-viewport" class="flex-1 max-w-6xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6"></main>

            <footer class="border-t border-slate-200 py-3 text-center text-xs text-slate-400 bg-white">
                CodeIgniter 4 SPA Engine &bull; Modular ES Client Architecture
            </footer>
        </div>
    </div>

    <!-- Auth Viewport (Login / Register without Sidebar) -->
    <div id="auth-viewport" class="flex-1 flex flex-col justify-center px-4 py-8 hidden"></div>

    <script>
        // Server state hydration
        window.__INITIAL_STATE__ = {
            user: <?= json_encode($initialUser) ?>
        };
    </script>
    <script type="module" src="/assets/js/app.js"></script>
</body>
</html>
