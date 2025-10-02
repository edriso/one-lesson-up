# One Lesson Up 🎯

> **Stop abandoning courses. Start finishing them.**

Transform online learning into an engaging journey with gamification, accountability, and community support. Whether you're taking a Udemy course, YouTube series, or any structured program, One Lesson Up keeps you accountable and motivated.

## 🚀 What It Does

One Lesson Up gamifies online learning with:

- **🎯 Points System** - Earn 1 point per lesson, with learning deadline bonuses
- **⏱️ Learning Deadlines** - Complete courses on time for bonus points
- **📝 Learning Summaries** - Reinforce knowledge by writing what you learned
- **🔥 Activity Calendar** - GitHub-style calendar with week/month/year views and customizable week start
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

- **Lesson completion**: 1 point per lesson
- **Active day bonus**: 1 point for first lesson of the day
- **Time bonus**: 1 point for morning (6-9 AM) or evening (6-9 PM) learning
- **Course completion bonus**: Variable based on course size and completion time
- **Learning deadline**: Calculated based on lesson count for maximum bonus points

**Time-Based Bonuses**:
- **Morning Bonus**: Learn between 6-9 AM for extra points
- **Evening Bonus**: Learn between 6-9 PM for extra points
- **One bonus per day**: Maximum 1 time bonus per day across all courses

**Course Completion Bonuses**:
- **On-time completion**: Full bonus points (within learning deadline)
- **Late completion**: Reduced bonus points (after learning deadline)
- **Learning deadline formula**: `lesson_count + (lesson_count ÷ 5 × 2)` days
- **Bonus scales with course size**: Larger courses = larger bonuses

**Learning Deadline Examples**:
- **10 lessons**: 14 days (10 + 4)
- **30 lessons**: 42 days (30 + 12) 
- **50 lessons**: 70 days (50 + 20)

**Example**: 30-lesson course completed on time = 30 lesson points + 30 active day points + time bonuses + course completion bonus = 60+ points ⚡

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
   php artisan migrate:fresh --seed
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
