<?php

namespace App\Http\Controllers;

use App\Models\DailyActivity;
use App\Models\User;
use App\Enums\PointThreshold;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();

        return Inertia::render('Leaderboard', [
            'leaderboards' => [
                'today' => $this->getTodayLeaderboard(),
                'yesterday' => $this->getYesterdayLeaderboard(),
                'this_month' => $this->getThisMonthLeaderboard(),
                'overall' => $this->getOverallLeaderboard(),
            ],
            'current_user_rank' => [
                'today' => $this->getCurrentUserRank($currentUser, 'today'),
                'yesterday' => $this->getCurrentUserRank($currentUser, 'yesterday'),
                'this_month' => $this->getCurrentUserRank($currentUser, 'this_month'),
                'overall' => $this->getCurrentUserRank($currentUser, 'overall'),
            ],
            'user' => $currentUser ? [
                'id' => $currentUser->id,
                'full_name' => $currentUser->full_name,
                'username' => $currentUser->username,
            ] : null,
        ]);
    }

    private function getTodayLeaderboard()
    {
        // For today, show users who completed lessons (without points)
        return DailyActivity::with(['user:id,username,full_name,avatar'])
            ->whereDate('activity_date', Carbon::today())
            ->where('lessons_completed', '>', 0)
            ->join('users', 'daily_activities.user_id', '=', 'users.id')
            ->orderByDesc('lessons_completed')
            ->orderByDesc('time_bonus_earned')
            ->limit(50)
            ->get(['daily_activities.*'])
            ->map(function ($activity, $index) {
                return [
                    'id' => $activity->user_id,
                    'rank' => $index + 1,
                    'user' => [
                        'id' => $activity->user->id,
                        'full_name' => $activity->user->full_name,
                        'username' => $activity->user->username,
                        'avatar' => $activity->user->avatar,
                    ],
                    'lessons_completed' => $activity->lessons_completed,
                    'has_time_bonus' => $activity->time_bonus_earned,
                    'bonus_type' => $activity->time_bonus_type,
                    'activity_date' => $activity->activity_date,
                ];
            });
    }

    private function getYesterdayLeaderboard()
    {
        // For yesterday, show users who completed lessons (without points)
        return DailyActivity::with(['user:id,username,full_name,avatar'])
            ->whereDate('activity_date', Carbon::yesterday())
            ->where('lessons_completed', '>', 0)
            ->join('users', 'daily_activities.user_id', '=', 'users.id')
            ->orderByDesc('lessons_completed')
            ->orderByDesc('time_bonus_earned')
            ->limit(50)
            ->get(['daily_activities.*'])
            ->map(function ($activity, $index) {
                return [
                    'id' => $activity->user_id,
                    'rank' => $index + 1,
                    'user' => [
                        'id' => $activity->user->id,
                        'full_name' => $activity->user->full_name,
                        'username' => $activity->user->username,
                        'avatar' => $activity->user->avatar,
                    ],
                    'lessons_completed' => $activity->lessons_completed,
                    'has_time_bonus' => $activity->time_bonus_earned,
                    'bonus_type' => $activity->time_bonus_type,
                    'activity_date' => $activity->activity_date,
                ];
            });
    }

    private function getThisMonthLeaderboard()
    {
        return $this->getLeaderboardForPeriod(
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth()
        );
    }

    private function getOverallLeaderboard()
    {
        return $this->getLeaderboardForPeriod(null, null);
    }

    private function getLeaderboardForPeriod($startDate = null, $endDate = null)
    {
        // For monthly and overall, use actual points from users table
        $query = User::select('id', 'username', 'full_name', 'avatar', 'points')
            ->where('points', '>', 0);

        // For now, show all users with points (threshold can be added later)
        // if (class_exists(\App\Enums\PointThreshold::class)) {
        //     $query->where('points', '>=', PointThreshold::LEADERBOARD_VISIBILITY->value);
        // }

        $results = $query->orderByDesc('points')
            ->limit(50) // Top 50 users
            ->get();

        // Add ranking
        return $results->map(function ($user, $index) {
            return [
                'id' => $user->id,
                'rank' => $index + 1,
                'user' => [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'username' => $user->username,
                    'avatar' => $user->avatar,
                ],
                'points' => $user->points,
            ];
        })->take(20); // Show top 20
    }

    private function getCurrentUserRank($user, $period)
    {
        if (!$user) {
            return 0;
        }

        switch ($period) {
            case 'today':
                $activities = DailyActivity::whereDate('activity_date', Carbon::today())
                    ->where('lessons_completed', '>', 0)
                    ->orderByDesc('lessons_completed')
                    ->orderByDesc('time_bonus_earned')
                    ->pluck('user_id');
                
                $rank = $activities->search($user->id);
                return $rank !== false ? $rank + 1 : 0;

            case 'yesterday':
                $activities = DailyActivity::whereDate('activity_date', Carbon::yesterday())
                    ->where('lessons_completed', '>', 0)
                    ->orderByDesc('lessons_completed')
                    ->orderByDesc('time_bonus_earned')
                    ->pluck('user_id');
                
                $rank = $activities->search($user->id);
                return $rank !== false ? $rank + 1 : 0;

            case 'this_month':
            case 'overall':
                $users = User::where('points', '>', 0)
                    ->orderByDesc('points')
                    ->pluck('id');
                
                $rank = $users->search($user->id);
                return $rank !== false ? $rank + 1 : 0;

            default:
                return 0;
        }
    }
}
