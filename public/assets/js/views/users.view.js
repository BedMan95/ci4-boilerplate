import { api, ApiCache } from '../core/api.js';
import { AppState, escapeHtml, showToast } from '../core/state.js';

export async function renderUsers() {
    const viewport = document.getElementById('app-viewport');
    viewport.innerHTML = `
        <div class="space-y-6 fade-in">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Manajemen Pengguna</h1>
                    <p class="text-xs text-slate-500 mt-1">Kelola data otentikasi, hak akses, dan level akun</p>
                </div>
                <div class="flex items-center gap-2">
                    <span id="user-cache-badge" class="text-[11px] font-mono px-2.5 py-1 rounded bg-slate-100 text-slate-600 border border-slate-200">Checking Cache...</span>
                    <button id="btn-open-user-modal" class="px-3.5 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-semibold text-xs transition flex items-center gap-1.5 shadow-xs">
                        <span>+ Tambah User</span>
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between gap-4">
                    <input type="text" id="user-search-input" placeholder="Cari username atau email..." class="w-full max-w-xs px-3 py-1.5 text-xs rounded-lg border border-slate-300 focus:border-teal-500 bg-white">
                    <span class="text-xs text-slate-400 font-mono">Real-time Search</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider">
                            <tr>
                                <th class="px-5 py-3">ID</th>
                                <th class="px-5 py-3">Username</th>
                                <th class="px-5 py-3">Email</th>
                                <th class="px-5 py-3">Role & Level</th>
                                <th class="px-5 py-3">Hak Akses</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="users-tbody" class="divide-y divide-slate-100 font-medium text-slate-700">
                            <tr><td colspan="7" class="px-5 py-6 text-center text-slate-400">Memuat data pengguna...</td></tr>
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
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Role</label>
                            <select id="form-role" name="role" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:border-teal-500">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Level (1-10)</label>
                            <input type="number" id="form-level" name="level" min="1" max="10" value="1" class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:border-teal-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Hak Akses (Permissions)</label>
                        <div class="flex flex-wrap gap-2 text-xs text-slate-700 pt-1">
                            <label class="inline-flex items-center gap-1.5"><input type="checkbox" value="*" id="perm-all" class="rounded text-teal-600"> Full (*)</label>
                            <label class="inline-flex items-center gap-1.5"><input type="checkbox" value="read" class="perm-chk rounded text-teal-600"> Read</label>
                            <label class="inline-flex items-center gap-1.5"><input type="checkbox" value="create" class="perm-chk rounded text-teal-600"> Create</label>
                            <label class="inline-flex items-center gap-1.5"><input type="checkbox" value="update" class="perm-chk rounded text-teal-600"> Update</label>
                            <label class="inline-flex items-center gap-1.5"><input type="checkbox" value="delete" class="perm-chk rounded text-teal-600"> Delete</label>
                        </div>
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
            tbody.innerHTML = '<tr><td colspan="7" class="px-5 py-6 text-center text-slate-400">Tidak ada data pengguna yang sesuai.</td></tr>';
            return;
        }

        tbody.innerHTML = users.map(u => {
            const perms = Array.isArray(u.permissions) ? u.permissions : (typeof u.permissions === 'string' && u.permissions ? JSON.parse(u.permissions) : []);
            const permBadges = perms.map(p => `<span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-mono text-[10px] mr-1">${escapeHtml(p)}</span>`).join('');

            return `
            <tr class="hover:bg-slate-50 transition">
                <td class="px-5 py-3.5 font-mono text-slate-500">#${u.id}</td>
                <td class="px-5 py-3.5 font-semibold text-slate-900">${escapeHtml(u.username)}</td>
                <td class="px-5 py-3.5 text-slate-600">${escapeHtml(u.email)}</td>
                <td class="px-5 py-3.5">
                    <span class="px-2 py-0.5 rounded text-[11px] font-mono ${u.role === 'admin' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-100 text-slate-600 border border-slate-200'}">
                        ${escapeHtml(u.role)}
                    </span>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-blue-50 text-blue-700 border border-blue-200 ml-1">
                        Lv.${u.level || 1}
                    </span>
                </td>
                <td class="px-5 py-3.5">
                    ${permBadges || '<span class="text-slate-400 text-xs">-</span>'}
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
        `;
        }).join('');
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

    document.getElementById('user-search-input').oninput = (e) => {
        const q = e.target.value.toLowerCase().trim();
        if (!q) return renderTable(localUsers);
        const filtered = localUsers.filter(u =>
            u.username.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
        );
        renderTable(filtered);
    };

    const modal = document.getElementById('user-modal');
    const openModal = (editData = null) => {
        const form = document.getElementById('user-form');
        form.reset();
        document.querySelectorAll('#user-modal input[type="checkbox"]').forEach(c => c.checked = false);

        if (editData) {
            document.getElementById('modal-title').textContent = 'Edit Pengguna #' + editData.id;
            document.getElementById('form-user-id').value = editData.id;
            document.getElementById('form-username').value = editData.username;
            document.getElementById('form-email').value = editData.email;
            document.getElementById('form-role').value = editData.role;
            document.getElementById('form-level').value = editData.level || 1;
            document.getElementById('form-password').required = false;
            document.getElementById('help-password').classList.remove('hidden');

            const perms = Array.isArray(editData.permissions) ? editData.permissions : (typeof editData.permissions === 'string' && editData.permissions ? JSON.parse(editData.permissions) : []);
            if (perms.includes('*')) {
                document.getElementById('perm-all').checked = true;
            }
            ['read', 'create', 'update', 'delete'].forEach(p => {
                if (perms.includes(p)) {
                    const chk = document.querySelector(`.perm-chk[value="${p}"]`);
                    if (chk) chk.checked = true;
                }
            });
        } else {
            document.getElementById('modal-title').textContent = 'Tambah Pengguna Baru';
            document.getElementById('form-user-id').value = '';
            document.getElementById('form-level').value = '1';
            document.getElementById('form-password').required = true;
            document.getElementById('help-password').classList.add('hidden');
            const readChk = document.querySelector('.perm-chk[value="read"]');
            if (readChk) readChk.checked = true;
        }
        modal.classList.remove('hidden');
    };

    const closeModal = () => modal.classList.add('hidden');
    document.getElementById('btn-open-user-modal').onclick = () => openModal();
    document.getElementById('btn-close-modal').onclick = closeModal;
    document.getElementById('btn-cancel-modal').onclick = closeModal;

    document.getElementById('user-form').onsubmit = async (e) => {
        e.preventDefault();
        const saveBtn = document.getElementById('btn-save-user');
        saveBtn.disabled = true;
        saveBtn.textContent = 'Menyimpan...';

        const perms = [];
        if (document.getElementById('perm-all').checked) {
            perms.push('*');
        } else {
            document.querySelectorAll('.perm-chk:checked').forEach(c => perms.push(c.value));
        }

        const id = document.getElementById('form-user-id').value;
        const payload = {
            username: document.getElementById('form-username').value,
            email: document.getElementById('form-email').value,
            role: document.getElementById('form-role').value,
            level: Number(document.getElementById('form-level').value) || 1,
            permissions: perms,
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
