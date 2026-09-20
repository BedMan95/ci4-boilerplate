# CodeIgniter 4 High-Performance SPA & Dynamic HMVC Platform

Boilerplate CodeIgniter 4 full-stack performa tinggi: arsitektur Single-Page Application (SPA) native ES modular tanpa bundler/node build step, database MySQL/MariaDB dengan session handler database, role & level-based access control (1-10), serta auto-scaffolding modul dinamis HMVC.

## Features

- **Single-Page Application (Zero Bundler)**: Arsitektur Native ES Modules (`<script type="module">`) dengan routing client-side hash, in-memory caching layer, in-flight request deduplication, dan responsif drawer sidebar.
- **Database MySQL / MariaDB**: Migrasi schema penuh dari SQLite ke MySQL dengan session handler database (`ci_sessions`).
- **Granular Permissions & Levels**: Skema role (`admin`/`user`), user level numerik (`1-10`), dan hak akses berbasis JSON permissions (`*`, `read`, `create`, `update`, `delete`).
- **Dynamic HMVC Scaffolder**: Pembuatan modul runtime via UI/API yang meng-generate tabel database MySQL otomatis, controller, model, dan HMVC route tanpa perlu restart server.
- **Security Hardened**: Otentikasi berbasis session dengan filter `apiAuth`, validasi request boundary, password BCRYPT, dan isolasi proteksi API.

## Requirements

- PHP 8.1+ (ekstensi: `intl`, `mbstring`, `mysqli`)
- MySQL atau MariaDB Server running
- Composer

## Quickstart

```bash
# 1. Install dependensi
composer install

# 2. Konfigurasi environment
cp env .env

# Pastikan setting database pada .env sudah mengarah ke MySQL/MariaDB:
# database.default.hostname = 127.0.0.1
# database.default.database = ci4
# database.default.username = root
# database.default.password = 
# database.default.DBDriver = MySQLi

# 3. Jalankan migrasi & seeder
php spark migrate
php spark db:seed UserSeeder

# 4. Jalankan local server
php spark serve --port 8081
```

### Akun Default

- **Admin**: `admin@example.com` / `admin123` (Role: `admin`, Level: 10, Perms: `["*"]`)
- **User**: `user@example.com` / `user123` (Role: `user`, Level: 1, Perms: `["read"]`)

## Project Structure

```
app/
├── Controllers/
│   └── Api/
│       ├── AuthController.php        # Otentikasi & session JSON
│       ├── BaseApiController.php    # Standarisasi JSON response envelope
│       ├── DashboardController.php   # Metrik & agregasi dashboard
│       ├── ModuleController.php      # Auto-scaffolding modul dinamis
│       └── UserController.php        # CRUD user, level, permissions
├── Modules/                          # Modul dynamic yang di-generate runtime
├── Views/
│   └── spa.php                       # Root HTML shell & responsive layout
public/
└── assets/js/
    ├── app.js                        # Client router & UI bootstrap
    ├── core/
    │   ├── api.js                    # Fetch client, cache, request dedupe
    │   └── state.js                  # Reactive state & helper methods
    └── views/
        ├── auth.view.js              # Login & registrasi views
        ├── dashboard.view.js         # Dashboard overview view
        ├── users.view.js             # User management view
        └── modules.view.js           # Dynamic module & CRUD pages view
```

## API Endpoints

| Method | Endpoint | Keterangan | Akses Minimal |
|---|---|---|---|
| `POST` | `/api/auth/login` | Login user & set session | Public |
| `POST` | `/api/auth/register` | Pendaftaran akun baru | Public |
| `POST` | `/api/auth/logout` | Invalidate session | Authenticated |
| `GET` | `/api/auth/me` | Ambil data profil & permissions | Authenticated |
| `GET` | `/api/dashboard/stats` | Statistik total user, admin, aktif | Authenticated |
| `GET` | `/api/modules/menu` | Daftar modul yang diizinkan per level | Authenticated |
| `GET`, `POST` | `/api/users` | List (paginasi/search) & buat user | Admin (Lv. 10) |
| `GET`, `PUT`, `DELETE` | `/api/users/(:num)` | Detail, update, hapus user | Admin (Lv. 10) |
| `GET`, `POST` | `/api/modules` | List & auto-scaffold modul baru | Admin (Lv. 10) |
| `DELETE` | `/api/modules/(:num)` | Hapus modul & drop tabel DB | Admin (Lv. 10) |
| `GET`, `POST` | `/api/{modul_name}` | CRUD data pada modul dynamic | Sesuai `min_level` modul |
| `GET`, `PUT`, `DELETE` | `/api/{modul_name}/(:num)` | Read/Update/Delete item modul | Sesuai `min_level` modul |

## License

MIT
