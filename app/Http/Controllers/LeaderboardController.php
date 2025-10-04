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
                'today' => $this->getTodayLeaderboard(20),
                'yesterday' => $this->getYesterdayLeaderboard(20),
                'this_month' => $this->getThisMonthLeaderboard(20),
                'overall' => $this->getOverallLeaderboard(20),
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

    public function loadMore(Request $request)
    {
        $period = $request->get('period', 'overall');
        $page = $request->get('page', 1);
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $leaderboard = match($period) {
            'today' => $this->getTodayLeaderboard($perPage, $offset),
            'yesterday' => $this->getYesterdayLeaderboard($perPage, $offset),
            'this_month' => $this->getThisMonthLeaderboard($perPage, $offset),
            'overall' => $this->getOverallLeaderboard($perPage, $offset),
            default => $this->getOverallLeaderboard($perPage, $offset),
        };

        // Check if there are more records by trying to get one more record
        $hasMore = $this->checkHasMore($period, $offset + $perPage);

        return response()->json([
            'leaderboard' => $leaderboard,
            'has_more' => $hasMore,
        ]);
    }

    private function getTodayLeaderboard($limit = 50, $offset = 0)
    {
        // For today, show users who completed lessons (without points)
        return DailyActivity::with(['user:id,username,full_name,avatar'])
            ->whereDate('activity_date', Carbon::today())
            ->where('lessons_completed', '>', 0)
            ->join('users', 'daily_activities.user_id', '=', 'users.id')
            ->orderByDesc('lessons_completed')
            ->orderByDesc('time_bonus_earned')
            ->offset($offset)
            ->limit($limit)
            ->get(['daily_activities.*'])
            ->map(function ($activity, $index) use ($offset) {
                return [
                    'id' => $activity->user_id,
                    'rank' => $offset + $index + 1,
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

    private function getYesterdayLeaderboard($limit = 50, $offset = 0)
    {
        // For yesterday, show users who completed lessons (without points)
        return DailyActivity::with(['user:id,username,full_name,avatar'])
            ->whereDate('activity_date', Carbon::yesterday())
            ->where('lessons_completed', '>', 0)
            ->join('users', 'daily_activities.user_id', '=', 'users.id')
            ->orderByDesc('lessons_completed')
            ->orderByDesc('time_bonus_earned')
            ->offset($offset)
            ->limit($limit)
            ->get(['daily_activities.*'])
            ->map(function ($activity, $index) use ($offset) {
                return [
                    'id' => $activity->user_id,
                    'rank' => $offset + $index + 1,
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

    private function getThisMonthLeaderboard($limit = 50, $offset = 0)
    {
        return $this->getLeaderboardForPeriod(
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth(),
            $limit,
            $offset
        );
    }

    private function getOverallLeaderboard($limit = 50, $offset = 0)
    {
        return $this->getLeaderboardForPeriod(null, null, $limit, $offset);
    }

    private function getLeaderboardForPeriod($startDate = null, $endDate = null, $limit = 50, $offset = 0)
    {
        if ($startDate && $endDate) {
            // For this month, use a more efficient approach with raw SQL
            $userPointsQuery = DailyActivity::selectRaw('
                user_id,
                SUM(lessons_completed) as total_lessons,
                SUM(CASE 
                    WHEN time_bonus_earned = 1 THEN 1 
                    ELSE 0 
                END) as time_bonus_days
            ')
            ->whereBetween('activity_date', [$startDate, $endDate])
            ->where('lessons_completed', '>', 0)
            ->groupBy('user_id')
            ->havingRaw('total_lessons > 0');

            // Get total count for pagination
            $totalUsers = $userPointsQuery->count();
            
            // Apply pagination
            $results = $userPointsQuery
                ->orderByDesc('total_lessons')
                ->orderByDesc('time_bonus_days')
                ->offset($offset)
                ->limit($limit)
                ->get();

            // Get user details
            $userIds = $results->pluck('user_id');
            $users = User::whereIn('id', $userIds)
                ->get(['id', 'username', 'full_name', 'avatar'])
                ->keyBy('id');

            return $results->map(function ($result, $index) use ($offset, $users) {
                $user = $users->get($result->user_id);
                // Calculate points: 1 point per lesson + 1 point per time bonus day
                $totalPoints = $result->total_lessons + $result->time_bonus_days;
                
                return [
                    'id' => $result->user_id,
                    'rank' => $offset + $index + 1,
                    'user' => [
                        'id' => $user->id,
                        'full_name' => $user->full_name,
                        'username' => $user->username,
                        'avatar' => $user->avatar,
                    ],
                    'points' => $totalPoints,
                ];
            });
        } else {
            // For overall, use actual points from users table
            $query = User::select('id', 'username', 'full_name', 'avatar', 'points')
                ->where('points', '>', 0);

            $results = $query->orderByDesc('points')
                ->offset($offset)
                ->limit($limit)
                ->get();

            // Add ranking
            return $results->map(function ($user, $index) use ($offset) {
                return [
                    'id' => $user->id,
                    'rank' => $offset + $index + 1,
                    'user' => [
                        'id' => $user->id,
                        'full_name' => $user->full_name,
                        'username' => $user->username,
                        'avatar' => $user->avatar,
                    ],
                    'points' => $user->points,
                ];
            });
        }
    }

    private function checkHasMore($period, $offset)
    {
        switch ($period) {
            case 'today':
                return DailyActivity::whereDate('activity_date', Carbon::today())
                    ->where('lessons_completed', '>', 0)
                    ->skip($offset)
                    ->limit(1)
                    ->exists();

            case 'yesterday':
                return DailyActivity::whereDate('activity_date', Carbon::yesterday())
                    ->where('lessons_completed', '>', 0)
                    ->skip($offset)
                    ->limit(1)
                    ->exists();

            case 'this_month':
                return DailyActivity::whereBetween('activity_date', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ])
                ->where('lessons_completed', '>', 0)
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) > 0')
                ->skip($offset)
                ->limit(1)
                ->exists();

            case 'overall':
                return User::where('points', '>', 0)
                    ->skip($offset)
                    ->limit(1)
                    ->exists();

            default:
                return false;
        }
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
                // Get user's monthly stats
                $userStats = DailyActivity::selectRaw('
                    SUM(lessons_completed) as total_lessons,
                    SUM(CASE 
                        WHEN time_bonus_earned = 1 THEN 1 
                        ELSE 0 
                    END) as time_bonus_days
                ')
                ->where('user_id', $user->id)
                ->whereBetween('activity_date', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ])
                ->where('lessons_completed', '>', 0)
                ->first();
                
                $userMonthlyPoints = ($userStats->total_lessons ?? 0) + ($userStats->time_bonus_days ?? 0);
                if ($userMonthlyPoints <= 0) {
                    return 0;
                }
                
                // Count users with more points this month
                $usersWithMorePoints = DailyActivity::selectRaw('
                    user_id,
                    SUM(lessons_completed) + SUM(CASE 
                        WHEN time_bonus_earned = 1 THEN 1 
                        ELSE 0 
                    END) as total_points
                ')
                ->whereBetween('activity_date', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ])
                ->where('lessons_completed', '>', 0)
                ->groupBy('user_id')
                ->havingRaw('total_points > ?', [$userMonthlyPoints])
                ->count();
                
                return $usersWithMorePoints + 1;

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
