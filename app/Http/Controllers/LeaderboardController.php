<?php

namespace App\Http\Controllers;

use App\Models\LearningActivity;
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
        return $this->getLeaderboardForPeriod(
            Carbon::today(),
            Carbon::today()->endOfDay()
        );
    }

    private function getYesterdayLeaderboard()
    {
        return $this->getLeaderboardForPeriod(
            Carbon::yesterday(),
            Carbon::yesterday()->endOfDay()
        );
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
        $query = LearningActivity::with(['user:id,username,full_name,avatar'])
            ->select('user_id')
            ->selectRaw('SUM(COALESCE(points_earned, 0)) as total_points')
            ->selectRaw('COUNT(*) as activities_count')
            ->groupBy('user_id');

        // Add date filters if provided
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $results = $query->orderByDesc('total_points')
            ->limit(50) // Top 50 users
            ->get();

        // Filter users who meet the leaderboard visibility threshold and have points > 0
        $visibleResults = $results->filter(function ($result) {
            return $result->total_points > 0 && 
                   $result->total_points >= PointThreshold::LEADERBOARD_VISIBILITY->value;
        });

        // Add ranking
        return $visibleResults->values()->map(function ($result, $index) {
            return [
                'id' => $result->user_id,
                'rank' => $index + 1,
                'user' => [
                    'id' => $result->user->id,
                    'full_name' => $result->user->full_name,
                    'username' => $result->user->username,
                    'avatar' => $result->user->avatar,
                ],
                'points' => (int) $result->total_points,
                'activities_count' => $result->activities_count,
            ];
        })->take(20); // Show top 20
    }

    private function getCurrentUserRank($user, $period)
    {
        if (!$user) {
            return 0;
        }

        // Get all users for the period, ranked by points (without visibility filter for ranking calculation)
        $leaderboard = match ($period) {
            'today' => $this->getAllUsersForPeriodUnfiltered(Carbon::today(), Carbon::today()->endOfDay()),
            'yesterday' => $this->getAllUsersForPeriodUnfiltered(Carbon::yesterday(), Carbon::yesterday()->endOfDay()),
            'this_month' => $this->getAllUsersForPeriodUnfiltered(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()),
            'overall' => $this->getAllUsersForPeriodUnfiltered(null, null),
            default => collect(),
        };

        // Find the current user's position
        $userPosition = $leaderboard->search(function ($entry) use ($user) {
            return $entry['user_id'] === $user->id;
        });

        return $userPosition !== false ? $userPosition + 1 : 0;
    }

    private function getAllUsersForPeriod($startDate = null, $endDate = null)
    {
        $query = LearningActivity::select('user_id')
            ->selectRaw('SUM(points_earned) as total_points')
            ->groupBy('user_id');

        // Add date filters if provided
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query->orderByDesc('total_points')
            ->get()
            ->filter(function ($result) {
                return $result->total_points >= PointThreshold::LEADERBOARD_VISIBILITY->value;
            })
            ->values();
    }

    private function getAllUsersForPeriodUnfiltered($startDate = null, $endDate = null)
    {
        $query = LearningActivity::select('user_id')
            ->selectRaw('SUM(points_earned) as total_points')
            ->groupBy('user_id');

        // Add date filters if provided
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query->orderByDesc('total_points')
            ->get()
            ->values();
    }
}