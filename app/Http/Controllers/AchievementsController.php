<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AchievementsController extends Controller
{
    /**
     * return json response containing achievements payload
     *
     * @param User $user
     * 
     * @return Json
     */
    public function index(User $user)
    {
        try{
            $unlockedAchievements       = $user->unlockedAchievements();
            $nextAvailableAchievements  = $user->getNextAvailableAchievements();
            $currentBadge               = $user->getCurrentBadge();
            $nextBadge                  = $user->getNextBadge();
            $remainingToUnlockNextBadge = $user->remainingAchievementsToUnlockNextBadge();

            return response()->json([
                'unlocked_achievements'         => $unlockedAchievements,
                'next_available_achievements'   => $nextAvailableAchievements,
                'current_badge'                 => $currentBadge,
                'next_badge'                    => $nextBadge,
                'remaing_to_unlock_next_badge'  => $remainingToUnlockNextBadge
            ]);
        }catch(Exception $ex){
            return response()->json(['Something went wrong'],Response::HTTP_BAD_REQUEST);
        }
    }
}
