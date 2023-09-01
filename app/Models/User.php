<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Events\BadgeUnlocked;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Collection;
use App\Events\AchievementUnlocked;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * A user watched many lessons
     *
     * @return Lesson model
     */
    public function watched() {
        return $this->belongsToMany(Lesson::class, 'lesson_user', 'user_id', 'lesson_id')
        ->whereWatched(true)
        ->withPivot('watched');
    }

    /**
     * A user has written many comments
     *
     * @return Comment model
     */
    public function comments() {
        return $this->hasMany(Comment::class); 
    }

    /**
     * trigger achievement unlocked event
     */
    public function unlockAchievement($achievementName)
    {
        event(new AchievementUnlocked($achievementName, $this));
    }


    /**
     * trigger badge unlocked event
     */
    public function unlockBadge($badgeName)
    {
        event(new BadgeUnlocked($badgeName, $this));
    }

    /**
     * Get user's unlocked achievements
     *
     * @return collection
     */
    public function unlockedAchievements()
    {
        $unlockedAchievements = [];

        $watchedCount = $this->watched->count();
        $commentsCount = $this->comments->count();

        // Define achievement thresholds and comments
        $watchedAchievements = [
            1 => FIRST_LESSON,
            5 => FIVE_LESSONS,
            10 => TEN_LESSONS,
            25 => TWENTY_FIVE_LESSONS,
            50 => FIFTY_LESSONS,
        ];
        $commentAchievements = [
            1 => FIRST_COMMENT,
            3 => THREE_COMMENTS,
            5 => FIVE_COMMENTS,
            10 => TEN_COMMENTS,
            20 => TWENTY_COMMENTS,
        ];

        // Check and add watched achievements
        foreach ($watchedAchievements as $threshold => $message) {
            if ($watchedCount >= $threshold) {
                $unlockedAchievements[] = $message;
            } else {
                break; // Stop checking once the threshold is not met
            }
        }

        // Check and add comment achievements
        foreach ($commentAchievements as $threshold => $message) {
            if ($commentsCount >= $threshold) {
                $unlockedAchievements[] = $message;
            } else {
                break; // Stop checking once the threshold is not met
            }
        }

        return new Collection($unlockedAchievements);
    }

    /**
     *  Method to get the next available achievements
     *
     * @return collection
     */
    public function getNextAvailableAchievements()
    {
        $nextAvailableAchievements = [];
        $unlockedAchievements = $this->unlockedAchievements()->toArray();

        // Define available achievements
        $lessonAchievements = [
            FIVE_LESSONS,
            TEN_LESSONS,
            TWENTY_FIVE_LESSONS,
            FIFTY_LESSONS,
        ];
        $commentAchievements = [
            THREE_COMMENTS,
            FIVE_COMMENTS,
            TEN_COMMENTS,
            TWENTY_COMMENTS,
        ];

        // Check and add lesson achievements
        foreach ($lessonAchievements as $lessonAchievement) {
            if (!in_array($lessonAchievement, $unlockedAchievements)) {
                $nextAvailableAchievements[] = $lessonAchievement;
                break; // Stop after finding the first available lesson achievement
            }
        }

        // Check and add comment achievements
        foreach ($commentAchievements as $commentAchievement) {
            if (!in_array($commentAchievement, $unlockedAchievements)) {
                $nextAvailableAchievements[] = $commentAchievement;
                break; // Stop after finding the first available comment achievement
            }
        }

        return new Collection($nextAvailableAchievements);
    }

    /**
     * Method to get the current badge
     *
     * @return string
     */ 
    public function getCurrentBadge()
    {
        $unlockedAchievements = $this->unlockedAchievements()->count();

            if ($unlockedAchievements >= 10) {
                return MASTER;
            } elseif ($unlockedAchievements >= 8) {
                return ADVANCED;
            } elseif ($unlockedAchievements >= 4) {
                return INTERMEDIATE;
            } else {
                return BEGINNER;
            }
    }

    /**
     * Method to get the next badge
     *
     * @return mixed string | null
     */ 
    public function getNextBadge()
    {
        $unlockedAchievements = $this->unlockedAchievements()->count();

        if ($unlockedAchievements < 4) {
            return INTERMEDIATE;
        } elseif ($unlockedAchievements < 8) {
            return ADVANCED;
        } elseif ($unlockedAchievements < 10) {
            return MASTER;
        } else {
            return null; // No next badge after Master
        }
    }

    /**
     * calculate remaining achievements to unlock the next badge
     *
     * @return int
     */  
    public function remainingAchievementsToUnlockNextBadge()
    {
        $unlockedAchievements = $this->unlockedAchievements()->count();
        $nextBadgeAchievementsRequired = 0;

        if ($unlockedAchievements < 4) {
            $nextBadgeAchievementsRequired =  4 - $unlockedAchievements;
        } elseif ($unlockedAchievements < 8) {
            $nextBadgeAchievementsRequired =  8 - $unlockedAchievements;
        } elseif ($unlockedAchievements < 10) {
            $nextBadgeAchievementsRequired =  10 - $unlockedAchievements;
        } 

        return max($nextBadgeAchievementsRequired, 0); // Ensure it's non-negative
    }

    /**
     * Check if user has unlocked an achievement
     *
     * @param string | $achievementName
     * @return boolean
     */
    public function hasUnlockedAchievement($achievementName)
    {
        return in_array($achievementName, $this->unlockedAchievements()->toArray());
    }
}

