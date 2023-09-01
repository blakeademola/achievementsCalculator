<?php

namespace App\Listeners;

use App\Models\User;
use App\Events\LessonWatched;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class LessonWatchedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(LessonWatched $event)
    {
        $user = $event->user;
        // Check and unlock lesson watched achievements
        $lessonsWatchedCount = $user->watched->count();
        if ($lessonsWatchedCount == 1) {
            $user->unlockAchievement(FIRST_LESSON);
        }
        if ($lessonsWatchedCount == 5) {
            $user->unlockAchievement(FIVE_LESSONS);
        }
        if ($lessonsWatchedCount == 10) {
            $user->unlockAchievement(TEN_LESSONS);
        }
        if ($lessonsWatchedCount == 25) {
            $user->unlockAchievement(TWENTY_FIVE_LESSONS);
        }

        if ($lessonsWatchedCount == 50) {
            $user->unlockAchievement(FIFTY_LESSONS);
        }
        
        // Check for badge upgrades
        $this->checkBadgeUpgrade($user);
    }

    //  method to check for badge upgrades
    private function checkBadgeUpgrade(User $user)
    {
        $unlockedAchievements = $user->unlockedAchievements()->count();
        if ($unlockedAchievements == 0) {
            $user->unlockBadge(BEGINNER);
        }elseif ($unlockedAchievements >= 10) {
            $user->unlockBadge(MASTER);
        } elseif ($unlockedAchievements >= 8) {
            $user->unlockBadge(ADVANCED);
        } elseif ($unlockedAchievements >= 4) {
            $user->unlockBadge(INTERMEDIATE);
        }
    }
}
