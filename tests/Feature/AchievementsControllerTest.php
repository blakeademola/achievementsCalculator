<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Lesson;
use App\Models\Comment;
use App\Events\LessonWatched;
use App\Events\CommentWritten;
use Illuminate\Support\Facades\Event;
use App\Listeners\LessonWatchedListener;
use App\Listeners\CommentWrittenListener;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AchievementsControllerTest extends TestCase
{

    use RefreshDatabase;
    public $user, $lesson, $comment;

    public function setUp():void
    {
        parent::setUp();
        Event::fake();
        $this->user = User::factory()->create();
        $this->lesson = Lesson::factory()->create();
        $this->comment = Comment::factory()->create(['user_id' => $this->user->id]);

    }

    /**
     * Assert that we can hit achievements endpoint and can return current badge 
     * as beginner for first time user with 0 achievements.
     */
    public function testIndex(): void
    {
        $response = $this->getJson(route("achievements.index",[$this->user->id]));
        $response->assertJsonFragment(['current_badge' =>  "Beginner"]);
        $response->assertOk();
    }

     /**
     * Assert that we can listen for  lesson watched event 
     */
    public function testLessonWatchedEventAndListenerDispatched(): void
    {
        //save the watched lesson on lesson_user pivot
        $this->user->watched()->sync([$this->lesson->id => ['watched' => true]]);

        // Dispatch the LessonWatched event
        event(new LessonWatched($this->lesson, $this->user));

        // Assert that the LessonWatched event was dispatched
        Event::assertDispatched(LessonWatched::class);

        $listener = new LessonWatchedListener();
        $listener->handle(new LessonWatched($this->lesson, $this->user));
        
    }

     /**
     * Assert that we can listen for first lesson watched achievement 
     */
    public function testFirstLessonWatchedAchievementUnlocked(): void
    {
        //saave the watched lesson on lesson_user pivot
        $this->user->watched()->sync([$this->lesson->id => ['watched' => true]]);

        // Dispatch the LessonWatched event
        event(new LessonWatched($this->lesson, $this->user));

        // Assert that the LessonWatched event was dispatched
        Event::assertDispatched(LessonWatched::class);

        $listener = new LessonWatchedListener();
        $listener->handle(new LessonWatched($this->lesson, $this->user));

        // Assert that the first lesson watched achievements was unlocked for the user
        $this->assertTrue($this->user->hasUnlockedAchievement(FIRST_LESSON));
        
    }


    /**
     * Assert that we can listen for  comment written event and listener 
     */
    public function testCommentWrittenEventAndCommentWrittenListenerDispatched(): void
    {
        // Dispatch the comment written event
        event(new CommentWritten($this->comment));

        // Assert that the comment written event was dispatched
        Event::assertDispatched(CommentWritten::class);

        $listener = new CommentWrittenListener();
        $listener->handle(new CommentWritten($this->comment));

        
    }

    /**
     *  Assert that the first comment written achievement was unlocked for the user
     */
    public function testFirstCommentWrittenAchievementUnlocked(): void
    {
        // Dispatch the comment written event
        event(new CommentWritten($this->comment));

        // Assert that the comment written event was dispatched
        Event::assertDispatched(CommentWritten::class);

        $listener = new CommentWrittenListener();
        $listener->handle(new CommentWritten($this->comment));

        $this->assertTrue($this->user->hasUnlockedAchievement(FIRST_COMMENT));
    }


    /**
     *  Assert that all achievements was unlocked
     * A total of 91 lessons must be watched  and 
     * 39 comments written to unlock all achievements
     * A total of 10 achievements unlocks all badges
     */
    public function testAllAchievementsUnlocked(): void
    {
        //create 39 comments
        Comment::factory()->count(39)->create(['user_id' => $this->user->id]);

        $lessons = Lesson::factory()->count(91)->create();
        $lessonIds = $lessons->pluck('id')->toArray();
        $array = [];
        foreach ($lessonIds as $id) {
            $array[$id] = ['watched' => true];
        }
        //attach 91 watched lessons to the user
        $this->user->watched()->sync($array);

        $this->assertEquals($this->user->unlockedAchievements()->count(), 10);
        $this->assertEquals($this->user->unlockedAchievements()->toArray(), 
        [
            FIRST_LESSON,FIVE_LESSONS,
            TEN_LESSONS,TWENTY_FIVE_LESSONS,
            FIFTY_LESSONS, FIRST_COMMENT,
            THREE_COMMENTS,FIVE_COMMENTS,
            TEN_COMMENTS,TWENTY_COMMENTS
        ]);

        $this->assertEquals($this->user->getNextAvailableAchievements()->toArray(),[]);
        $this->assertEquals($this->user->getCurrentBadge(),MASTER);
        $this->assertEquals($this->user->getNextBadge(),null);
        $this->assertEquals($this->user->remainingAchievementsToUnlockNextBadge(),0);
           
    }

    //Assert that the expected achievement was unlocked
    public function testAchievementUnlocked(){
        // Create a user with certain achievements
        Comment::factory()->count(10)->create(['user_id' => $this->user->id]);
        $lessons = Lesson::factory()->count(10)->create();
        $lessonIds = $lessons->pluck('id')->toArray();
        $array = [];
        foreach ($lessonIds as $id) {
            $array[$id] = ['watched' => true];
        }
        $this->user->watched()->sync($array);

        $this->user->unlockAchievement(TEN_LESSONS);
        $this->user->unlockAchievement(TEN_COMMENTS);

        // Assert that the unlocked achievements match the expected ones
        $this->assertTrue(in_array( TEN_LESSONS, $this->user->unlockedAchievements()->toArray()));
        $this->assertTrue(in_array( TEN_COMMENTS, $this->user->unlockedAchievements()->toArray()));
    }

    /**
     * Assert that the next badge after unlocking 4 achievements is Advanced
     */
    public function testGetNextBadgeAdvanced()
    {
        //watch 6 lessons and write 4 comments to get 4 achievements

        Comment::factory()->count(4)->create(['user_id' => $this->user->id]);
        $lessons = Lesson::factory()->count(6)->create();
        $lessonIds = $lessons->pluck('id')->toArray();
        $array = [];
        foreach ($lessonIds as $id) {
            $array[$id] = ['watched' => true];
        }
        $this->user->watched()->sync($array);

        //Next badge should be advanced since we have unlock 4 achievemnts for intermediate
        
        $this->assertEquals(INTERMEDIATE, $this->user->getCurrentBadge());
        $this->assertEquals(ADVANCED, $this->user->getNextBadge());
    }

}
