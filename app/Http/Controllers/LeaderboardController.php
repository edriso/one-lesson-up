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

        return response()->json([
            'leaderboard' => $leaderboard,
            'has_more' => count($leaderboard) === $perPage,
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
        // For monthly and overall, use actual points from users table
        $query = User::select('id', 'username', 'full_name', 'avatar', 'points')
            ->where('points', '>', 0);

        // For now, show all users with points (threshold can be added later)
        // if (class_exists(\App\Enums\PointThreshold::class)) {
        //     $query->where('points', '>=', PointThreshold::LEADERBOARD_VISIBILITY->value);
        // }

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
