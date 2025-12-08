# 🚗 DriveMaster - Driving School CRM

Production-ready Laravel + GraphQL application for managing driving school operations.

[![Laravel](https://img.shields.io/badge/Laravel-10-red)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2-blue)](https://php.net)
[![GraphQL](https://img.shields.io/badge/GraphQL-15-E10098)](https://graphql.org)
[![Redis](https://img.shields.io/badge/Redis-7-DC382D)](https://redis.io)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

---

## 🌟 Features

- ✅ **Lesson Booking System** with distributed lock (prevents race conditions)
- ✅ **Event-Driven Architecture** for scalable asynchronous operations
- ✅ **Redis Caching** with intelligent invalidation
- ✅ **Queue Management** via Laravel Horizon
- ✅ **Automated Tasks** (reminders, auto-cancellation, reports)
- ✅ **PDF Report Generation**
- ✅ **Email Notifications** (async via queues)
- ✅ **GraphQL API** with role-based access control
- ✅ **Comprehensive Test Coverage**

---

## 🛠 Tech Stack

- **Backend:** Laravel 10, PHP 8.2
- **API:** GraphQL (rebing/graphql-laravel)
- **Database:** MySQL 8.0
- **Cache & Queues:** Redis 7
- **Queue Monitoring:** Laravel Horizon
- **Authentication:** Laravel Sanctum
- **PDF Generation:** DomPDF
- **Testing:** PHPUnit, Feature & Unit tests

---

## 🏗 Architecture

### Design Patterns
- **Repository Pattern** - Data access layer abstraction
- **Service Layer** - Business logic encapsulation
- **Observer Pattern** - Event-driven architecture

### Key Components
```
app/
├── Repositories/       # Database queries + caching
├── Services/          # Business logic
├── Events/            # Domain events
├── Listeners/         # Event handlers (async)
├── Jobs/             # Background tasks
├── GraphQL/          # API layer (thin mutations)
└── Notifications/    # Email templates
```

---

## 🚀 Installation

### Prerequisites
- PHP >= 8.2
- Composer
- MySQL >= 8.0
- Redis >= 7.0
- Node.js >= 18 (for Horizon assets)

### 1. Clone Repository
```bash
git clone https://github.com/martinakopyan/drivemaster.git
cd drivemaster
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

**Configure `.env`:**
```env
DB_CONNECTION=mysql
DB_DATABASE=drivemaster
DB_USERNAME=root
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

QUEUE_CONNECTION=redis
CACHE_DRIVER=redis

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
```

### 4. Database Setup
```bash
php artisan migrate --seed
```

### 5. Start Services
```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Queue worker
php artisan horizon

# Terminal 3: Scheduler (or setup cron)
php artisan schedule:work
```

---

## 🧪 Testing
```bash
# Run all tests
php artisan test

# With coverage
php artisan test --coverage

# Specific test
php artisan test --filter=BookLessonTest
```

**Test Coverage:**
- ✅ Lesson booking (with race condition tests)
- ✅ Instructor approval/rejection
- ✅ Authentication & authorization
- ✅ Scheduler jobs
- ✅ Cache invalidation

---

## 📚 Documentation

- **[API Documentation](docs/API.md)** - Complete GraphQL API reference
---

## 🎯 Usage

### Access GraphiQL
Visit: `http://localhost/graphiql`

### Login Example
```graphql
mutation {
  login(email: "admin@drivemaster.com", password: "password") {
    token
    user {
      name
      role
    }
  }
}
```

### Book Lesson
```graphql
mutation {
  bookLesson(
    instructor_id: 1
    date: "2025-12-15"
    slot: 1
  ) {
    id
    status
  }
}
```

**See [API.md](docs/API.md) for complete documentation.**

---

## 🔧 Development

### Code Style
```bash
# PHP CodeSniffer
./vendor/bin/phpcs

# PHP CS Fixer
./vendor/bin/php-cs-fixer fix
```


### Queues
```bash
# Monitor with Horizon
http://localhost/horizon

# Manually process queue
php artisan queue:work

# Failed jobs
php artisan queue:failed
php artisan queue:retry all
```

---

## 📊 Monitoring

### Laravel Horizon
Dashboard: `http://localhost/horizon`

- Real-time queue monitoring
- Job metrics & throughput
- Failed job management
- Memory usage tracking

### Logs
```bash
tail -f storage/logs/laravel.log
```

---

## 🔐 Security

- ✅ CSRF Protection
- ✅ SQL Injection Prevention (Eloquent ORM)
- ✅ XSS Protection
- ✅ Rate Limiting (60 req/min per user)
- ✅ Authentication via Sanctum tokens
- ✅ Role-based access control (RBAC)

---

## 🚀 Deployment

### Production Checklist
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure production database
- [ ] Setup Redis with password
- [ ] Configure mail server (not mailhog)
- [ ] Setup Supervisor for Horizon
- [ ] Configure cron for Scheduler
- [ ] Enable HTTPS
- [ ] Setup backups

### Supervisor Config
```ini
[program:horizon]
process_name=%(program_name)s
command=php /path/to/project/artisan horizon
autostart=true
autorestart=true
user=forge
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/horizon.log
stopwaitsecs=3600
```

### Cron Setup
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

---

## 📝 License

This project is licensed under the MIT License.

---

## 👨‍💻 Author

**Martin Akopyan**
- GitHub: [@martinakopyan](https://github.com/martinakopyan)
- Email: martin@example.com

---

## 🙏 Acknowledgments

- [Laravel](https://laravel.com) - The PHP Framework
- [GraphQL PHP](https://webonyx.github.io/graphql-php/) - GraphQL implementation
- [Laravel Horizon](https://laravel.com/docs/horizon) - Queue monitoring

---
