# CodeIgniter 4 Boilerplate

Minimal, secure, production-ready CodeIgniter 4 boilerplate with RBAC and clean Tailwind styling.

## Features

- **Auth System**: Native session authentication, BCRYPT password hashing, session regeneration.
- **RBAC**: Protected `admin` and `user` roles enforced at filter and controller levels.
- **User Management**: Admin CRUD interface with pagination, search, and self-lockout prevention.
- **Security Hardened**: Global CSRF protection, sanitized input validation, HTTP verb restrictions (`POST`/`DELETE`).
- **Clean UI**: Responsive layout using hairline data surfaces and accessible typography.

## Requirements

- PHP 8.1+ (extensions: `intl`, `mbstring`, `sqlite3` or `mysqli`)
- Composer

## Quickstart

```bash
composer install
cp env .env # jika belum ada
php spark migrate
php spark db:seed UserSeeder
php spark serve
```

### Default Credentials

- **Admin**: `admin@example.com` / `admin123`
- **User**: `user@example.com` / `user123`

## Routes

| Method | Route | Description | Auth |
|---|---|---|---|
| `GET` | `/` | Homepage | Public |
| `GET`, `POST` | `/login` | Login page & submission | Public |
| `GET`, `POST` | `/register` | Registration | Public |
| `GET` | `/logout` | Invalidate session | Auth |
| `GET` | `/dashboard` | User dashboard | Auth |
| `GET` | `/admin/users` | User list (searchable, paginated) | Admin |
| `GET`, `POST` | `/admin/users/create` | Create user | Admin |
| `GET`, `POST` | `/admin/users/edit/(:num)` | Edit user | Admin |
| `POST`, `DELETE` | `/admin/users/delete/(:num)` | Delete user | Admin |

## License

MIT
