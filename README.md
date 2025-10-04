# One Lesson Up 🎯

> **Turn Learning Into a Game. Level up your learning.**

Transform any structured learning into an engaging journey with gamification, accountability, and community support. Whether it's Udemy courses, YouTube series, bootcamps, or any structured learning path - turn it into a game and actually finish what you start.

## 🌟 Why One Lesson Up?

Most learners abandon their courses, classes, and learning roadmaps within the first few lessons. One Lesson Up solves this by:

- **🎯 Gamification** - Turn learning into a game with points, deadlines, and achievements
- **👥 Community** - Learn alongside others and see what they're studying
- **📊 Progress Tracking** - Visualize your learning journey with GitHub-style activity calendars
- **⏰ Accountability** - Smart deadlines and reflection requirements keep you on track
- **🏆 Competition** - Leaderboards and achievements motivate continued learning

## 🚀 What It Does

One Lesson Up gamifies online learning with:

- **🎯 Points System** - Earn points for learning, with time bonuses and course completion bonuses
- **⏱️ Learning Deadlines** - Complete classes on time for bonus points
- **📝 Learning Summaries** - Reinforce knowledge by writing what you learned
- **📖 Course Reflections** - Write and edit reflections when completing courses
- **🔒 Privacy Controls** - Create public or private classes
- **🔥 Activity Calendar** - GitHub-style calendar with week/month/year views and customizable week start
- **👥 Community Feed** - See what other ninjas are learning
- **🏆 Leaderboards** - Compete with the community and climb the ranks
- **🎯 Focus Mode** - One course at a time keeps you focused
- **🏷️ Smart Tag System** - Categorize classes with tags, autocomplete from existing tags, or create new ones
- **📱 Infinite Scroll** - Seamless browsing with infinite scroll on feeds, classes, and activities
- **🔒 User Privacy** - Control profile visibility and activity sharing with privacy settings

## 🎮 How It Works

1. **Create or Join a Class** - Add your course name, modules, and lessons with smart tags (one active course at a time)
2. **Complete & Reflect** - After each lesson, write a summary of what you learned
3. **Course Reflection** - When you complete all lessons, write a reflection about the entire course
4. **Earn Points** - Get points for learning, with time bonuses and course completion bonuses
5. **Climb the Leaderboard** - Accumulate points and compete with other ninjas
6. **Track Progress** - See your learning activity and progress over time
7. **Discover & Organize** - Find relevant classes through tags and organize your learning path

## 📊 Points System

- **Active day bonus**: 1 point for first lesson of the day (regardless of how many lessons completed)
- **Time bonus**: 1 point for morning (5-8 AM) or evening (8-11 PM) learning (maximum 1 per day)
- **Course completion bonus**: Variable based on course size and completion time
- **Learning deadline**: Calculated based on lesson count for maximum bonus points

**Time-Based Bonuses**:
- **Morning Bonus**: Learn between 5-8 AM for extra points
- **Evening Bonus**: Learn between 8-11 PM for extra points
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

**Example**: 2-lesson course completed on time = 1 active day bonus + 1 course completion bonus = 2 points ⚡

**Perfect For**:
- Self-learners taking online courses (Udemy, Coursera, YouTube, etc.)
- Anyone struggling to finish courses they start
- People who want accountability and structure in their learning
- Competitive learners who thrive on gamification
- Students and professionals pursuing structured learning paths

## 🛠️ Tech Stack

### Backend
- **Laravel 12** - Modern PHP framework with elegant syntax
- **PHP 8.3+** - Latest PHP features and performance improvements
- **Laravel Fortify** - Authentication and security features
- **Eloquent ORM** - Beautiful database interactions

### Frontend
- **Vue.js 3** - Progressive JavaScript framework
- **Inertia.js** - Modern monolith approach with SPA feel
- **TypeScript** - Type-safe JavaScript development
- **Tailwind CSS** - Utility-first CSS framework

### Database
- **SQLite** - Development and testing
- **MySQL/PostgreSQL** - Production ready
- **Migrations** - Version-controlled database schema
- **Seeders** - Sample data for development

### Development Tools
- **Vite** - Fast build tool and dev server
- **ESLint** - Code quality and consistency
- **Pest** - Elegant PHP testing framework
- **Factory Pattern** - Realistic test data generation

## 🚀 Quick Start

### Prerequisites

- **PHP 8.3+** - Latest PHP version
- **Composer** - PHP dependency manager
- **Node.js 18+** - JavaScript runtime
- **npm** - Package manager
- **SQLite** - Database (or MySQL/PostgreSQL for production)

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

### Run Tests
```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test file
php artisan test --filter=PointSystemTest
```

### Test Features
- **Feature Tests** - Full application workflow testing
- **Unit Tests** - Individual component testing
- **Database Tests** - Model and relationship testing
- **Factory Tests** - Data generation and seeding

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

We welcome contributions! Here's how to get started:

### Development Setup
1. **Fork the repository** and clone your fork
2. **Create a feature branch**: `git checkout -b feature/your-feature-name`
3. **Install dependencies**: `composer install && npm install`
4. **Set up environment**: Copy `.env.example` to `.env` and configure
5. **Run migrations**: `php artisan migrate:fresh --seed`
6. **Start development**: `php artisan serve` and `npm run dev`

### Code Standards
- **Follow PSR-12** coding standards
- **Write tests** for new features
- **Update documentation** for significant changes
- **Use meaningful commit messages**

### Pull Request Process
1. **Test your changes**: `php artisan test`
2. **Check code quality**: `npm run lint`
3. **Commit changes**: `git commit -m 'Add amazing feature'`
4. **Push to branch**: `git push origin feature/your-feature-name`
5. **Open Pull Request** with detailed description

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🙏 Acknowledgments

- **Laravel Community** - For the amazing framework
- **Vue.js Team** - For the progressive JavaScript framework
- **Tailwind CSS** - For the utility-first CSS framework
- **All Contributors** - Thank you for making this project better!

---

**Consistency beats intensity. One day, one lesson at a time.** 🥷🏆
