<?php

namespace App\Listeners;

use App\Events\AllLessonsCompleted;

class HandleAllLessonsCompleted
{
    /**
     * Handle the event.
     * 
     * Note: Daily activities are now tracked automatically via DailyActivity model.
     * This listener is kept for potential future features.
     */
    public function handle(AllLessonsCompleted $event): void
    {
        // Daily activities are now automatically tracked via DailyActivity model
        // when lessons are completed. No additional action needed here.
    }
}

