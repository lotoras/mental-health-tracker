# Mental Health Tracker PWA

A modern, mobile-first Progressive Web Application (PWA) for tracking daily mental health states built with Laravel and Vue 3.

## 🎯 Project Overview

Mental Health Tracker helps users monitor their mental well-being by tracking daily states through an intuitive calendar interface. The app provides insightful statistics to help users understand patterns in their mental health journey.

## ✨ Features

### 📅 Calendar Tab
- Interactive calendar with selectable date bubbles
- Add and track daily mental health states
- States are saved to user profile
- Mobile-first, touch-friendly interface

### 📊 Statistics Tab
- Monthly breakdown statistics
- Days since last breakdown (worst day)
- Breakdown duration tracking
- Monthly breakdown frequency
- Visual state distribution per month

### 🔐 Authentication
- User registration
- User login
- Profile management
- Edit account credentials

## 🛠️ Tech Stack

### Backend
- **Laravel** (latest) - PHP Framework
- **PHP 8.4** - Server-side language
- Laravel Breeze/Sanctum - Authentication
- SQLite/MySQL - Database

### Frontend
- **Vue 3** (Composition API) - JavaScript Framework
- **Inertia.js** - SPA bridge for Laravel & Vue
- **Tailwind CSS** - Utility-first CSS framework
- **Chart.js/ApexCharts** - Data visualization

### PWA Features
- Service Workers
- Offline functionality
- App manifest
- Push notifications (optional)

## 📋 Requirements

- PHP >= 8.4
- Node.js >= 24.x
- Composer
- npm/yarn/pnpm
- MySQL/PostgreSQL/SQLite

## 🚀 Installation

### 1. Clone the repository
```bash
git clone <repository-url>
cd mental-health-tracker
```

### 2. Install PHP dependencies
```bash
composer install
```

### 3. Install Node.js dependencies
```bash
npm install
# or
pnpm install
# or
yarn install
```

### 4. Environment setup
```bash
cp .env.example .env
php artisan key:generate
```

### 5. Configure database
Edit `.env` file with your database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mental_health_tracker
DB_USERNAME=root
DB_PASSWORD=
```

### 6. Run migrations
```bash
php artisan migrate
```

### 7. Build frontend assets
```bash
# Development
npm run dev

# Production
npm run build
```

### 8. Start development server
```bash
php artisan serve
```

Visit `http://localhost:8000` in your browser.

## 📱 PWA Setup

The app includes PWA capabilities for mobile installation:

1. **Manifest file**: `public/manifest.json`
2. **Service Worker**: `public/sw.js`
3. **Icons**: `public/icons/` directory

### Testing PWA locally
1. Build production assets: `npm run build`
2. Serve with HTTPS (required for PWA)
3. Use Chrome DevTools > Application > Manifest to test

## 🎨 Design System

### Color Palette
The app uses a modern, playful, and colorful design with:
- Primary colors for positive states
- Warm colors for neutral states
- Cool colors for negative states
- High contrast for accessibility

### Components Structure
```
resources/
├── css/
│   ├── app.css              # Main Tailwind imports
│   └── components/          # Component-specific styles
├── js/
│   ├── app.js               # Main Vue app
│   ├── components/          # Vue components
│   │   ├── Calendar/
│   │   ├── Statistics/
│   │   └── Auth/
│   └── Pages/               # Inertia pages
│       ├── Calendar.vue
│       ├── Statistics.vue
│       ├── Login.vue
│       └── Register.vue
```

### Tailwind Configuration
Custom configuration in `tailwind.config.js` includes:
- Custom color schemes
- Mobile-first breakpoints
- Custom animations
- Component utilities

## 📊 Database Schema

### Users Table
- Standard Laravel users table
- Email verification
- Password management

### Mental States Table
```sql
- id
- user_id (foreign key)
- date
- state (enum: excellent, good, okay, bad, worst)
- notes (optional text)
- created_at
- updated_at
```

### State Types
- 😄 Excellent
- 🙂 Good
- 😐 Okay
- 😟 Bad
- 😢 Worst

## 🔧 Development

### Folder Structure
```
mental-health-tracker/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── CalendarController.php
│   │       └── StatisticsController.php
│   ├── Models/
│   │   ├── User.php
│   │   └── MentalState.php
│   └── Services/
│       └── StatisticsService.php
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
├── routes/
│   ├── web.php
│   └── api.php
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
│   ├── manifest.json
│   ├── sw.js
│   └── icons/
└── tests/
```

### Key Commands
```bash
# Run development server
php artisan serve

# Watch for frontend changes
npm run dev

# Run tests
php artisan test

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Generate optimized autoloader
composer dump-autoload -o
```

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

## 📦 Deployment

### Production checklist
- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure production database
- [ ] Set up HTTPS (required for PWA)
- [ ] Run `composer install --optimize-autoloader --no-dev`
- [ ] Run `npm run build`
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Set up task scheduler: `* * * * * php /path/artisan schedule:run`
- [ ] Configure queue worker for jobs

### PWA Deployment Notes
- Ensure HTTPS is enabled
- Configure service worker caching strategy
- Test offline functionality
- Validate manifest.json
- Test on actual mobile devices

## 🔒 Security

- CSRF protection enabled
- SQL injection prevention via Eloquent ORM
- XSS protection via Blade templating
- Authentication via Laravel Sanctum
- Password hashing with bcrypt
- Rate limiting on routes

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 🆘 Support

For issues, questions, or contributions, please open an issue in the GitHub repository.

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Vue 3 Documentation](https://vuejs.org/)
- [Inertia.js Documentation](https://inertiajs.com/)
- [Tailwind CSS Documentation](https://tailwindcss.com/)
- [PWA Documentation](https://web.dev/progressive-web-apps/)

---

Built with ❤️ for mental health awareness
