# Mental Health Tracker - Claude Code Instructions

This file contains project-specific instructions for Claude Code when working on the Mental Health Tracker PWA.

## Project Context

This is a Laravel + Vue 3 PWA for tracking mental health states. The app is mobile-first and helps users monitor their daily mental well-being through a calendar interface with statistics.

## Tech Stack Specifics

- **PHP**: 8.4 (use modern PHP syntax and features)
- **Node.js**: 24.x
- **Laravel**: Latest version with Inertia.js
- **Vue 3**: Composition API (prefer `<script setup>` syntax)
- **Tailwind CSS**: For all styling
- **Inertia.js**: SPA without API
- **Charts**: ApexCharts or Chart.js for statistics visualization

## Code Style Guidelines

### PHP/Laravel
- Use strict types: `declare(strict_types=1);`
- Use typed properties and return types
- Follow PSR-12 coding standards
- Use Laravel's built-in features (Eloquent, Validation, etc.)
- Keep controllers thin, use service classes for business logic
- Use resource controllers for CRUD operations
- Prefer constructor property promotion (PHP 8.x feature)

### Vue/JavaScript
- Use Composition API with `<script setup>`
- Use TypeScript types where possible
- Prefer `ref` and `reactive` for state management
- Use `defineProps` and `defineEmits` with TypeScript types
- Keep components small and focused
- Use composition functions for reusable logic

### CSS/Tailwind
- **NEVER** write inline styles in `<style>` tags
- Use Tailwind utility classes exclusively
- Create component CSS files in `resources/css/components/` only when Tailwind utilities are insufficient
- Structure: Component CSS should be organized by feature (Calendar, Statistics, Auth)
- Use Tailwind's `@apply` directive sparingly, prefer utility classes
- Follow mobile-first approach (default styles for mobile, then `md:`, `lg:`, etc.)

### File Organization
```
resources/
├── css/
│   ├── app.css                    # Tailwind imports only
│   └── components/
│       ├── calendar.css           # Calendar-specific styles
│       ├── statistics.css         # Statistics-specific styles
│       └── auth.css               # Auth-specific styles
├── js/
│   ├── app.js                     # Vue app setup
│   ├── Components/
│   │   ├── Calendar/
│   │   │   ├── DateBubble.vue
│   │   │   ├── StateSelector.vue
│   │   │   └── CalendarGrid.vue
│   │   ├── Statistics/
│   │   │   ├── StateChart.vue
│   │   │   ├── BreakdownStats.vue
│   │   │   └── MonthlyOverview.vue
│   │   ├── Navigation/
│   │   │   └── BottomNav.vue
│   │   └── UI/
│   │       ├── Button.vue
│   │       ├── Card.vue
│   │       └── Modal.vue
│   └── Pages/
│       ├── Calendar.vue
│       ├── Statistics.vue
│       ├── Auth/
│       │   ├── Login.vue
│       │   ├── Register.vue
│       │   └── Profile.vue
│       └── Welcome.vue
```

## Design Principles

### Color Scheme
Use a modern, playful, colorful palette:
- **Excellent**: Bright green/cyan (`emerald-400`, `teal-400`)
- **Good**: Light blue/sky (`sky-400`, `blue-300`)
- **Okay**: Yellow/amber (`amber-400`, `yellow-300`)
- **Bad**: Orange/coral (`orange-400`, `red-300`)
- **Worst**: Deep red/pink (`red-500`, `rose-500`)

### UI Components
- Use rounded corners (`rounded-2xl`, `rounded-full` for bubbles)
- Add smooth transitions (`transition-all duration-300`)
- Include hover and active states
- Use shadows for depth (`shadow-lg`, `shadow-xl`)
- Ensure touch-friendly sizing (min 44px touch targets on mobile)

### Mobile-First
- Default styles for mobile (320px+)
- Tablet breakpoint: `md:` (768px+)
- Desktop breakpoint: `lg:` (1024px+)
- Test on mobile viewports first

## Database Schema

### mental_states table
```php
Schema::create('mental_states', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->date('date')->unique(); // One state per day per user
    $table->enum('state', ['excellent', 'good', 'okay', 'bad', 'worst']);
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->unique(['user_id', 'date']);
    $table->index(['user_id', 'date']);
});
```

## Feature Implementation Guidelines

### Calendar Component
- Display current month with navigable prev/next
- Show dates as round bubbles with state colors
- Allow tapping date to add/edit state
- Show modal/drawer for state selection
- Persist state immediately on selection
- Show visual feedback on save

### Statistics Component
- Calculate from stored mental_states records
- Show monthly aggregations
- Calculate "days since last breakdown" (days since last 'worst' state)
- Calculate breakdown duration (consecutive 'worst' days)
- Display with charts (bar, line, or pie charts)
- Allow date range filtering

### Authentication
- Use Laravel Breeze with Inertia.js stack
- Customize Breeze views to match app design
- Add profile editing (email, password, name)
- Include email verification
- Add password reset flow

## PWA Requirements

### Manifest (public/manifest.json)
```json
{
  "name": "Mental Health Tracker",
  "short_name": "MH Tracker",
  "description": "Track your mental health journey",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#6366f1",
  "icons": [
    {
      "src": "/icons/icon-192.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "/icons/icon-512.png",
      "sizes": "512x512",
      "type": "image/png"
    }
  ]
}
```

### Service Worker
- Cache static assets
- Implement offline fallback
- Use workbox or vanilla service worker
- Cache API responses for offline access

## Common Tasks

### Adding a new state type
1. Update migration enum values
2. Update MentalState model casts
3. Add color to Tailwind config
4. Update StateSelector component
5. Update statistics calculations

### Creating a new page
1. Create Vue component in `resources/js/Pages/`
2. Add route in `routes/web.php`
3. Create controller method
4. Add navigation link if needed
5. Test responsive design

### Adding a chart
1. Install chart library: `npm install apexcharts vue3-apexcharts`
2. Create chart component in `resources/js/Components/Statistics/`
3. Fetch data from backend via Inertia props
4. Configure chart options for mobile-first
5. Add loading and error states

## Testing

### Feature Tests
- Test authentication flows
- Test state creation and updates
- Test statistics calculations
- Test API endpoints

### Browser Tests (Dusk)
- Test calendar interactions
- Test state selection flow
- Test navigation between tabs
- Test PWA installation

## Dependencies to Install

### PHP/Composer
```bash
composer require laravel/breeze
composer require inertiajs/inertia-laravel
```

### Node/npm
```bash
npm install vue@next @inertiajs/inertia @inertiajs/vue3
npm install @vitejs/plugin-vue
npm install tailwindcss postcss autoprefixer
npm install apexcharts vue3-apexcharts
# or
npm install chart.js vue-chartjs
```

## When Implementing Features

1. **Read existing code first** before making changes
2. **Use Laravel conventions** (resource controllers, form requests, etc.)
3. **Keep components modular** and reusable
4. **Test on mobile viewport** as you develop
5. **Use Tailwind utilities** instead of custom CSS
6. **Follow component structure** defined above
7. **Add proper TypeScript types** for Vue components
8. **Include loading states** for async operations
9. **Handle errors gracefully** with user-friendly messages
10. **Commit frequently** with clear messages

## Avoid

- ❌ Inline styles in Vue components
- ❌ jQuery or other DOM manipulation libraries
- ❌ Multiple CSS frameworks (only Tailwind)
- ❌ Class-based Vue components (use Composition API)
- ❌ Direct DOM manipulation (use Vue reactivity)
- ❌ Mixing API and Inertia approaches
- ❌ Complex state management (Vuex/Pinia) - use Inertia props
- ❌ Desktop-first CSS (always mobile-first)

## Helpful Laravel Artisan Commands

```bash
# Create controller
php artisan make:controller CalendarController --resource

# Create model with migration
php artisan make:model MentalState -m

# Create service class
php artisan make:class Services/StatisticsService

# Create form request
php artisan make:request StoreMentalStateRequest

# Install Breeze with Inertia + Vue
php artisan breeze:install vue
```

## Questions to Ask Before Implementing

1. Is this component reusable or page-specific?
2. Should this logic be in a service class or controller?
3. Can I use an existing Laravel feature instead of custom code?
4. Is this mobile-friendly with proper touch targets?
5. Am I using Tailwind utilities or creating custom CSS?
6. Does this need loading and error states?
7. Should this be server-side or client-side logic?

## Additional Context

- The app focuses on privacy and security (user data is sensitive)
- Offline functionality is important for PWA
- Performance matters on mobile devices
- Accessibility should be considered (proper ARIA labels, keyboard navigation)
- The design should feel supportive and non-judgmental

---

When in doubt, prioritize mobile experience, use Laravel/Vue best practices, and keep the UI playful but professional.
