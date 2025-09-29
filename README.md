# One Lesson Up 🎯

**Transform online learning into an engaging journey.** Whether you're taking a Udemy course, YouTube series, or any structured program, One Lesson Up keeps you accountable and motivated.

## 🚀 What It Does

One Lesson Up gamifies online learning with:

- **🎯 Points System** - Earn 1 point per lesson, with smart deadline bonuses
- **⏱️ Smart Deadlines** - Complete courses on time for 50% bonus points
- **📝 Learning Summaries** - Reinforce knowledge by writing what you learned
- **🔥 Streak Tracking** - Visual calendar shows your active learning days
- **👥 Community Feed** - See what other ninjas are learning
- **🏆 Leaderboards** - Compete with the community and climb the ranks
- **🎯 Focus Mode** - One course at a time keeps you focused

## 🎮 How It Works

1. **Enroll in a Course** - Add your course name, modules, and lessons (one active course at a time)
2. **Complete & Reflect** - After each lesson, write a summary of what you learned
3. **Earn Points** - Get 1 point per lesson, with time-based bonuses for completing courses on schedule
4. **Climb the Leaderboard** - Accumulate points and compete with other ninjas
5. **Stay Consistent** - Build learning streaks and see your active days visualized

## 📊 Points System

- **Lesson completion**: 1 point
- **On-time course completion**: 50% bonus points
- **Late course completion**: 25% bonus points (still rewarding!)

**Deadline formula**: `lesson_count + (lesson_count ÷ 5 × 2)` days

**Example**: 30-lesson course = 42-day deadline → Finish on time = 30 + 15 bonus = 45 points ⚡

## 🛠️ Tech Stack

- **Backend**: Laravel 12 with PHP 8.3+
- **Frontend**: Vue.js 3 with Inertia.js
- **Database**: SQLite (development) / MySQL/PostgreSQL (production)
- **Authentication**: Laravel Fortify
- **UI Components**: Custom Vue components with modern design

## 🚀 Quick Start

### Prerequisites

- PHP 8.3 or higher
- Composer
- Node.js 18+ and npm
- SQLite (or MySQL/PostgreSQL for production)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/edriso/one-lesson-up.git
   cd one-lesson-up
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database setup**
   ```bash
   php artisan migrate
   ```

6. **Build assets**
   ```bash
   npm run build
   ```

7. **Start the development server**
   ```bash
   php artisan serve
   ```

   The application will be available at `http://localhost:8000`

### Development

For development with hot reloading:

```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server
npm run dev
```

## 📁 Project Structure

```
app/
├── Models/           # Eloquent models with relationships
├── Http/Controllers/ # API and web controllers
└── Providers/        # Service providers

database/
├── migrations/      # Database schema migrations
├── factories/      # Model factories for testing
└── seeders/         # Database seeders

resources/
├── js/              # Vue.js frontend
│   ├── components/  # Reusable Vue components
│   ├── pages/       # Page components
│   ├── layouts/     # Layout components
│   └── composables/ # Vue composables
└── css/             # Stylesheets
```

## 🗄️ Database Schema

### Core Models

- **Users** - Ninjas with profiles, points, and streaks
- **Courses** - Learning programs with modules and lessons
- **Modules** - Course sections containing lessons
- **Lessons** - Individual learning units
- **Enrollments** - User course participation
- **CompletedLessons** - Lesson completion records with summaries
- **Tags** - Course categorization
- **CourseTags** - Many-to-many course-tag relationships

### Key Features

- **Focus Mode**: One active enrollment per user
- **Points System**: Gamified learning with time-based bonuses
- **Progress Tracking**: Module and course completion percentages
- **Community**: Public profiles and leaderboards

## 🧪 Testing

Run the test suite:

```bash
php artisan test
```

## 🚀 Deployment

1. **Production environment setup**
   ```bash
   composer install --optimize-autoloader --no-dev
   npm run build
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

2. **Database migration**
   ```bash
   php artisan migrate --force
   ```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🎯 Perfect For

- Self-learners taking online courses (Udemy, Coursera, YouTube, etc.)
- Anyone struggling to finish courses they start
- People who want accountability and structure in their learning
- Competitive learners who thrive on gamification
- Students building a portfolio of completed courses

---

**Stop abandoning courses. Start leveling up.** 🚀
