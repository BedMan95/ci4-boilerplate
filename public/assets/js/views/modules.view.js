import { api, ApiCache } from '../core/api.js';
import { AppState, escapeHtml, showToast } from '../core/state.js';

export async function renderModules() {
    const viewport = document.getElementById('app-viewport');
    viewport.innerHTML = `
        <div class="space-y-6 fade-in">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Dynamic Module Manager</h1>
                    <p class="text-xs text-slate-500 mt-1">Buat fitur & tabel baru secara dinamis tanpa sentuh coding backend</p>
                </div>
                <div class="flex items-center gap-2">
                    <button id="btn-open-module-modal" class="px-3.5 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-semibold text-xs transition flex items-center gap-1.5 shadow-xs">
                        <span>+ Modul Baru</span>
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3">Modul</th>
                                <th class="px-5 py-3">Slug / Table</th>
                                <th class="px-5 py-3">Min Level</th>
                                <th class="px-5 py-3">Endpoint API</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="modules-tbody" class="divide-y divide-slate-100 font-medium text-slate-700">
                            <tr><td colspan="6" class="px-5 py-6 text-center text-slate-400">Memuat daftar modul...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL TAMBAH MODUL -->
        <div id="module-modal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-md rounded-xl shadow-xl border border-slate-200 p-6 fade-in">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-bold text-slate-900">Buat Dynamic Module Baru</h3>
                    <button id="btn-close-mod-modal" class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
                </div>
                <form id="module-create-form" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Modul (UI Title)</label>
                        <input type="text" id="form-mod-title" name="title" required placeholder="Contoh: Projects, Products, Task List" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:border-teal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Slug / Nama Tabel (Huruf kecil & underscore)</label>
                        <input type="text" id="form-mod-name" name="name" required pattern="^[a-z0-9_]+$" placeholder="contoh: project_tracker" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:border-teal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Singkat</label>
                        <textarea id="form-mod-desc" name="description" rows="2" placeholder="Keterangan fungsi modul..." class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:border-teal-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Minimal Akses Level (1-10)</label>
                        <input type="number" id="form-mod-level" name="min_level" min="1" max="10" value="1" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:border-teal-500">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" id="btn-cancel-mod-modal" class="px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                        <button type="submit" id="btn-save-module" class="px-4 py-2 text-xs font-semibold bg-teal-600 hover:bg-teal-700 text-white rounded-lg">Generate Modul</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    let modulesList = [];

    const renderTable = (mods) => {
        const tbody = document.getElementById('modules-tbody');
        if (!mods.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="px-5 py-6 text-center text-slate-400">Belum ada modul kustom. Klik "+ Modul Baru" untuk auto-generate.</td></tr>';
            return;
        }

        tbody.innerHTML = mods.map(m => `
            <tr class="hover:bg-slate-50 transition">
                <td class="px-5 py-3.5 font-semibold text-slate-900">
                    <a href="#/m/${m.name}" class="text-teal-600 hover:underline flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        <span>${escapeHtml(m.title)}</span>
                    </a>
                    <div class="text-[10px] text-slate-400 font-normal mt-0.5">${escapeHtml(m.description || 'Tidak ada deskripsi')}</div>
                </td>
                <td class="px-5 py-3.5 font-mono text-slate-600">${escapeHtml(m.name)}</td>
                <td class="px-5 py-3.5">
                    <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-blue-50 text-blue-700 border border-blue-200">
                        Level &ge; ${m.min_level}
                    </span>
                </td>
                <td class="px-5 py-3.5 font-mono text-slate-500 text-[11px]">/api/${escapeHtml(m.name)}</td>
                <td class="px-5 py-3.5">
                    <span class="inline-flex items-center gap-1 text-[11px] text-teal-700 font-medium">
                        <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span> Active HMVC
                    </span>
                </td>
                <td class="px-5 py-3.5 text-right">
                    <button data-action="delete" data-id="${m.id}" data-name="${m.name}" class="text-rose-600 hover:text-rose-800 font-semibold text-xs">Hapus</button>
                </td>
            </tr>
        `).join('');
    };

    const loadModules = async () => {
        try {
            const res = await api.get('/api/modules');
            modulesList = res.data || [];
            renderTable(modulesList);
        } catch (err) {
            showToast(err.message, 'error');
        }
    };

    await loadModules();

    const modModal = document.getElementById('module-modal');
    const openModModal = () => {
        document.getElementById('module-create-form').reset();
        document.getElementById('form-mod-level').value = '1';
        modModal.classList.remove('hidden');
    };
    const closeModModal = () => modModal.classList.add('hidden');

    document.getElementById('btn-open-module-modal').onclick = openModModal;
    document.getElementById('btn-close-mod-modal').onclick = closeModModal;
    document.getElementById('btn-cancel-mod-modal').onclick = closeModModal;

    document.getElementById('module-create-form').onsubmit = async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btn-save-module');
        btn.disabled = true;
        btn.textContent = 'Auto-scaffolding...';

        const payload = {
            title: document.getElementById('form-mod-title').value,
            name: document.getElementById('form-mod-name').value.trim().toLowerCase(),
            description: document.getElementById('form-mod-desc').value,
            min_level: Number(document.getElementById('form-mod-level').value) || 1,
        };

        try {
            await api.post('/api/modules', payload);
            showToast(`Modul "${payload.title}" berhasil di-scaffold & tabel dibuat!`);
            closeModModal();
            ApiCache.clear();
            await AppState.loadDynamicMenu();
            await loadModules();
        } catch (err) {
            showToast(err.message, 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Generate Modul';
        }
    };

    document.getElementById('modules-tbody').onclick = async (e) => {
        const btn = e.target.closest('button');
        if (!btn) return;
        const id = btn.dataset.id;
        const name = btn.dataset.name;

        if (confirm(`PERINGATAN: Hapus modul "${name}" beserta file source code controller/model dan database table-nya?`)) {
            try {
                await api.delete(`/api/modules/${id}`);
                showToast(`Modul "${name}" berhasil dihapus.`);
                ApiCache.clear();
                await AppState.loadDynamicMenu();
                await loadModules();
            } catch (err) {
                showToast(err.message, 'error');
            }
        }
    };
}

export async function renderModulePage(slug) {
    const viewport = document.getElementById('app-viewport');
    viewport.innerHTML = `
        <div class="space-y-6 fade-in">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                        <span id="mod-page-title" class="capitalize">${escapeHtml(slug)}</span>
                        <span class="text-xs px-2 py-0.5 rounded font-mono font-normal bg-slate-100 text-slate-600 border border-slate-200">Dynamic HMVC</span>
                    </h1>
                    <p id="mod-page-desc" class="text-xs text-slate-500 mt-1">Memuat informasi modul...</p>
                </div>
                <div>
                    <button id="btn-mod-page-add" class="px-3.5 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-semibold text-xs transition shadow-xs">
                        + Tambah Data
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3">ID</th>
                                <th class="px-5 py-3">Judul / Title</th>
                                <th class="px-5 py-3">Content</th>
                                <th class="px-5 py-3">Dibuat Pada</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="mod-page-tbody" class="divide-y divide-slate-100 font-medium text-slate-700">
                            <tr><td colspan="5" class="px-5 py-6 text-center text-slate-400">Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL CRUD ITEM -->
        <div id="mod-page-modal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-md rounded-xl shadow-xl border border-slate-200 p-6 fade-in">
                <div class="flex items-center justify-between mb-4">
                    <h3 id="mod-page-modal-title" class="text-base font-bold text-slate-900">Tambah Data</h3>
                    <button id="btn-mod-page-modal-close" class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
                </div>
                <form id="mod-page-form" class="space-y-4">
                    <input type="hidden" id="mod-item-id">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Judul (Title)</label>
                        <input type="text" id="mod-item-title" required class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:border-teal-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi / Konten</label>
                        <textarea id="mod-item-content" rows="4" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:border-teal-500"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" id="btn-mod-page-cancel" class="px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded-lg">Batal</button>
                        <button type="submit" id="btn-mod-page-save" class="px-4 py-2 text-xs font-semibold bg-teal-600 hover:bg-teal-700 text-white rounded-lg">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    `;

    let items = [];

    const loadItems = async () => {
        try {
            const res = await api.get(`/api/${slug}`);
            items = res.data || [];
            const tbody = document.getElementById('mod-page-tbody');
            if (!items.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="px-5 py-6 text-center text-slate-400">Belum ada rekaman data pada modul ini.</td></tr>';
                return;
            }

            tbody.innerHTML = items.map(it => `
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-5 py-3.5 font-mono text-slate-500">#${it.id}</td>
                    <td class="px-5 py-3.5 font-semibold text-slate-900">${escapeHtml(it.title)}</td>
                    <td class="px-5 py-3.5 text-slate-600 max-w-xs truncate">${escapeHtml(it.content || '-')}</td>
                    <td class="px-5 py-3.5 font-mono text-slate-400 text-[11px]">${escapeHtml(it.created_at || '-')}</td>
                    <td class="px-5 py-3.5 text-right space-x-2">
                        <button data-action="edit" data-id="${it.id}" class="text-teal-600 hover:text-teal-800 font-semibold text-xs">Edit</button>
                        <button data-action="delete" data-id="${it.id}" class="text-rose-600 hover:text-rose-800 font-semibold text-xs">Hapus</button>
                    </td>
                </tr>
            `).join('');
        } catch (err) {
            showToast(err.message, 'error');
        }
    };

    await loadItems();

    const modal = document.getElementById('mod-page-modal');
    const form = document.getElementById('mod-page-form');

    const openModal = (item = null) => {
        form.reset();
        if (item) {
            document.getElementById('mod-page-modal-title').textContent = 'Edit Data #' + item.id;
            document.getElementById('mod-item-id').value = item.id;
            document.getElementById('mod-item-title').value = item.title;
            document.getElementById('mod-item-content').value = item.content || '';
        } else {
            document.getElementById('mod-page-modal-title').textContent = 'Tambah Data Baru';
            document.getElementById('mod-item-id').value = '';
        }
        modal.classList.remove('hidden');
    };

    const closeModal = () => modal.classList.add('hidden');

    document.getElementById('btn-mod-page-add').onclick = () => openModal();
    document.getElementById('btn-mod-page-modal-close').onclick = closeModal;
    document.getElementById('btn-mod-page-cancel').onclick = closeModal;

    form.onsubmit = async (e) => {
        e.preventDefault();
        const saveBtn = document.getElementById('btn-mod-page-save');
        saveBtn.disabled = true;
        saveBtn.textContent = 'Menyimpan...';

        const id = document.getElementById('mod-item-id').value;
        const payload = {
            title: document.getElementById('mod-item-title').value,
            content: document.getElementById('mod-item-content').value,
        };

        try {
            if (id) {
                await api.put(`/api/${slug}/${id}`, payload);
                showToast('Data berhasil diperbarui');
            } else {
                await api.post(`/api/${slug}`, payload);
                showToast('Data baru berhasil ditambahkan');
            }
            closeModal();
            await loadItems();
        } catch (err) {
            showToast(err.message, 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Simpan';
        }
    };

    document.getElementById('mod-page-tbody').onclick = async (e) => {
        const btn = e.target.closest('button');
        if (!btn) return;
        const action = btn.dataset.action;
        const id = Number(btn.dataset.id);

        if (action === 'edit') {
            const item = items.find(it => Number(it.id) === id);
            if (item) openModal(item);
        } else if (action === 'delete') {
            if (confirm(`Hapus data #${id}?`)) {
                try {
                    await api.delete(`/api/${slug}/${id}`);
                    showToast('Data berhasil dihapus');
                    await loadItems();
                } catch (err) {
                    showToast(err.message, 'error');
                }
            }
        }
    };
}
