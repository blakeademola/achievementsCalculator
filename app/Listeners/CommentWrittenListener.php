<?php

namespace App\Listeners;

use App\Models\User;
use App\Events\CommentWritten;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CommentWrittenListener
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
    public function handle(CommentWritten $event)
    {
        $comment = $event->comment;
        $user = User::find($comment->user_id);
     
        // Check and unlock comment written achievements
        $commentsWrittenCount = $user->comments->count();
        if ($commentsWrittenCount == 1) {
            $user->unlockAchievement(FIRST_COMMENT);
        }
        if ($commentsWrittenCount >= 3) {
            $user->unlockAchievement(THREE_COMMENTS);
        }
        if ($commentsWrittenCount >= 5) {
            $user->unlockAchievement(FIVE_COMMENTS);
        }
        if ($commentsWrittenCount >= 10) {
            $user->unlockAchievement(TEN_COMMENTS);
        }
        if ($commentsWrittenCount >= 20) {
            $user->unlockAchievement(TWENTY_COMMENTS);
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
