# One Lesson Up 🎯

> **Stop abandoning courses. Start finishing them.**

Transform online learning into an engaging journey with gamification, accountability, and community support. Whether you're taking a Udemy course, YouTube series, or any structured program, One Lesson Up keeps you accountable and motivated.

## 🚀 What It Does

One Lesson Up gamifies online learning with:

- **🎯 Points System** - Earn 1 point per lesson, with smart deadline bonuses
- **⏱️ Smart Deadlines** - Complete courses on time for 50% bonus points
- **📝 Learning Summaries** - Reinforce knowledge by writing what you learned
- **🔥 Activity Calendar** - Visual calendar shows your learning activity and progress
- **👥 Community Feed** - See what other ninjas are learning
- **🏆 Leaderboards** - Compete with the community and climb the ranks
- **🎯 Focus Mode** - One course at a time keeps you focused
- **🏷️ Smart Tag System** - Categorize classes with tags, autocomplete from existing tags, or create new ones

## 🎮 How It Works

1. **Create or Join a Class** - Add your course name, modules, and lessons with smart tags (one active course at a time)
2. **Complete & Reflect** - After each lesson, write a summary of what you learned
3. **Earn Points** - Get 1 point per lesson, with time-based bonuses for completing courses on schedule
4. **Climb the Leaderboard** - Accumulate points and compete with other ninjas
5. **Track Progress** - See your learning activity and progress over time
6. **Discover & Organize** - Find relevant classes through tags and organize your learning path

## 📊 Points System

- **Lesson completion**: 1 point
- **On-time course completion**: 50% bonus points
- **Late course completion**: 25% bonus points (still rewarding!)

**Smart Deadline Formula**: `lesson_count + (lesson_count ÷ 5 × 2)` days

This simulates a **5-day work week** learning schedule:
- **5 days per week** of consistent learning
- **2 days off** for rest and reflection
- **Realistic pacing** that prevents burnout while maintaining momentum

**Example**: 30-lesson course = 42-day deadline (6 weeks) → Finish on time = 30 + 15 bonus = 45 points ⚡

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

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   php artisan migrate
   ```

5. **Build assets**
   ```bash
   npm run build
   ```

6. **Start the development server**
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
- Students

---

**Consistency beats intensity. One day, one lesson at a time.** 🥷🏆
