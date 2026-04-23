# Laravel Task API

![CI](https://github.com/hijrahassalam/laravel-task-api/actions/workflows/tests.yml/badge.svg)

A production-ready REST API built with Laravel 13, featuring token auth,
role-based access control, task management with audit trail, and full test coverage.

## Tech Stack
Laravel 13 · PHP 8.4 · MySQL · PHPUnit · Sanctum · Docker · GitHub Actions

## Features
- Token-based authentication (Laravel Sanctum)
- Role-based access control (admin / member)
- Task management with status workflow & priority
- Soft deletes & activity logs (audit trail)
- Advanced filtering, sorting, and search
- Batch operations
- Request validation & API Resources
- Dockerised local environment
- CI: automated tests on every push

## Quick Start

```bash
git clone https://github.com/hijrahassalam/laravel-task-api.git
cd laravel-task-api
cp .env.example .env
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed
```

API available at `http://localhost:8000`

## Running Tests

Tests use SQLite in-memory (no MySQL needed):

```bash
php artisan test
```

Or with PHPUnit directly:

```bash
vendor/bin/phpunit
```

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | /api/v1/register | No | Register |
| POST | /api/v1/login | No | Login |
| POST | /api/v1/logout | Yes | Logout |
| GET | /api/v1/me | Yes | Current user |
| GET | /api/v1/health | No | Health check |
| GET | /api/v1/tasks | Yes | List (filter, sort, search) |
| POST | /api/v1/tasks | Yes | Create task |
| GET | /api/v1/tasks/{id} | Yes | Get single task |
| PUT | /api/v1/tasks/{id} | Yes | Update task |
| DELETE | /api/v1/tasks/{id} | Yes | Soft delete |
| PATCH | /api/v1/tasks/{id}/status | Yes | Update status |
| PATCH | /api/v1/tasks/batch-status | Yes | Batch update status |
| GET | /api/v1/tasks/{id}/activity | Yes | Activity log |

## Filter & Sort Examples

```
GET /api/v1/tasks?status=done&priority=high
GET /api/v1/tasks?sort=-created_at,priority
GET /api/v1/tasks?search=keyword
GET /api/v1/tasks?assigned_to=me
```

## License

MIT
