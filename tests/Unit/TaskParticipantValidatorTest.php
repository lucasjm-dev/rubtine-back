<?php

namespace Tests\Unit;

use App\Domains\Tasks\Enums\TaskParticipantRole;
use App\Domains\Tasks\Enums\TaskParticipantStatus;
use App\Domains\Tasks\Models\Task;
use App\Domains\Tasks\Validators\CreateParticipantContext;
use App\Domains\Tasks\Validators\TaskParticipantValidator;
use App\Domains\Users\Models\ProfessionalUser;
use App\Domains\Users\Models\SimpleUser;
use App\Domains\Users\Models\User;
use Mockery;
use Tests\TestCase;

class TaskParticipantValidatorTest extends TestCase
{
    private TaskParticipantValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new TaskParticipantValidator();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ─── Helpers ─────────────────────────────────────────────

    private function makeUser(array $overrides = []): User
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->id = $overrides['id'] ?? rand(100, 9999);

        $loggedAs = $overrides['logged_as'] ?? null;
        $user->shouldReceive('isLoggedAsProfessional')
            ->andReturn($loggedAs === User::TYPE_PROFESSIONAL);
        $user->shouldReceive('isLoggedAsSimple')
            ->andReturn($loggedAs === User::TYPE_SIMPLE);

        if (!empty($overrides['simpleUser'])) {
            $simple = Mockery::mock(SimpleUser::class);
            $user->shouldReceive('getAttribute')->with('simpleUser')->andReturn($simple);
        } else {
            $user->shouldReceive('getAttribute')->with('simpleUser')->andReturn(null);
        }

        if (!empty($overrides['professionalUser'])) {
            $prof = Mockery::mock(ProfessionalUser::class)->makePartial();
            $prof->subcategory_id = $overrides['subcategory_id'] ?? 1;
            $user->shouldReceive('getAttribute')->with('professionalUser')->andReturn($prof);
        } else {
            $user->shouldReceive('getAttribute')->with('professionalUser')->andReturn(null);
        }

        return $user;
    }

    private function makeTask(User $owner, int $subcategoryId = 1, array $options = []): Task
    {
        $task = Mockery::mock(Task::class)->makePartial();
        $task->id = rand(100, 9999);
        $task->subcategory_id = $subcategoryId;

        $task->shouldReceive('isOwnedBy')
            ->andReturnUsing(function (User $u) use ($owner) {
                return $u->id === $owner->id;
            });

        $hasAcceptedSimple = $options['has_accepted_simple'] ?? false;
        $participantsQuery = Mockery::mock();
        $participantsQuery->shouldReceive('where')
            ->with('role', TaskParticipantRole::SIMPLE)
            ->andReturnSelf();
        $participantsQuery->shouldReceive('where')
            ->with('status', TaskParticipantStatus::ACCEPTED)
            ->andReturnSelf();
        $participantsQuery->shouldReceive('exists')
            ->andReturn($hasAcceptedSimple);

        $task->shouldReceive('participants')
            ->andReturn($participantsQuery);

        return $task;
    }

    private function buildCtx(Task $task, User $actor, User $target, string $role): CreateParticipantContext
    {
        return new CreateParticipantContext($task, $actor, $target, $role);
    }

    // ─── Success Cases ───────────────────────────────────────

    /** @test */
    public function owner_can_create_professional_participant_with_matching_subcategory()
    {
        $owner = $this->makeUser(['id' => 1, 'logged_as' => User::TYPE_PROFESSIONAL]);
        $target = $this->makeUser([
            'id' => 2,
            'professionalUser' => true,
            'subcategory_id' => 5,
        ]);
        $task = $this->makeTask($owner, 5);

        $ctx = $this->buildCtx($task, $owner, $target, TaskParticipantRole::PROFESSIONAL);

        $this->assertNull($this->validator->validateCreate($ctx));
    }

    /** @test */
    public function owner_can_create_simple_participant_for_user_with_simple_profile()
    {
        $owner = $this->makeUser(['id' => 1, 'logged_as' => User::TYPE_PROFESSIONAL]);
        $target = $this->makeUser([
            'id' => 2,
            'simpleUser' => true,
        ]);
        $task = $this->makeTask($owner);

        $ctx = $this->buildCtx($task, $owner, $target, TaskParticipantRole::SIMPLE);

        $this->assertNull($this->validator->validateCreate($ctx));
    }

    /** @test */
    public function simple_user_can_create_participant_for_self_with_simple_role()
    {
        $owner = $this->makeUser(['id' => 1]);
        $simpleUser = $this->makeUser([
            'id' => 2,
            'logged_as' => User::TYPE_SIMPLE,
            'simpleUser' => true,
        ]);
        $task = $this->makeTask($owner);

        $ctx = $this->buildCtx($task, $simpleUser, $simpleUser, TaskParticipantRole::SIMPLE);

        $this->assertNull($this->validator->validateCreate($ctx));
    }

    // ─── Failure Cases ───────────────────────────────────────

    /** @test */
    public function cannot_create_participant_for_task_owner()
    {
        $owner = $this->makeUser([
            'id' => 1,
            'logged_as' => User::TYPE_PROFESSIONAL,
            'professionalUser' => true,
            'subcategory_id' => 5,
        ]);
        $task = $this->makeTask($owner, 5);

        $ctx = $this->buildCtx($task, $owner, $owner, TaskParticipantRole::PROFESSIONAL);
        $error = $this->validator->validateCreate($ctx);

        $this->assertEquals('task_participant_target_is_owner', $error);
    }

    /** @test */
    public function professional_logged_in_can_join_simple_owned_task_with_professional_role()
    {
        $owner = $this->makeUser(['id' => 1]);
        $profUser = $this->makeUser([
            'id' => 2,
            'logged_as' => User::TYPE_PROFESSIONAL,
            'professionalUser' => true,
            'subcategory_id' => 5,
        ]);
        $task = $this->makeTask($owner, 5);

        $ctx = $this->buildCtx($task, $profUser, $profUser, TaskParticipantRole::PROFESSIONAL);
        $this->assertNull($this->validator->validateCreate($ctx));
    }

    /** @test */
    public function simple_user_cannot_create_with_non_simple_role()
    {
        $owner = $this->makeUser(['id' => 1]);
        $simpleUser = $this->makeUser([
            'id' => 2,
            'logged_as' => User::TYPE_SIMPLE,
            'simpleUser' => true,
            'professionalUser' => true,
            'subcategory_id' => 5,
        ]);
        $task = $this->makeTask($owner, 5);

        $ctx = $this->buildCtx($task, $simpleUser, $simpleUser, TaskParticipantRole::PROFESSIONAL);
        $error = $this->validator->validateCreate($ctx);

        $this->assertEquals('task_participant_role_mismatch', $error);
    }

    /** @test */
    public function cannot_create_second_simple_when_one_is_accepted()
    {
        $owner = $this->makeUser(['id' => 1, 'logged_as' => User::TYPE_PROFESSIONAL]);
        $target = $this->makeUser([
            'id' => 3,
            'simpleUser' => true,
        ]);
        $task = $this->makeTask($owner, 1, ['has_accepted_simple' => true]);

        $ctx = $this->buildCtx($task, $owner, $target, TaskParticipantRole::SIMPLE);
        $error = $this->validator->validateCreate($ctx);

        $this->assertEquals('task_participant_simple_slot_taken', $error);
    }

    /** @test */
    public function cannot_create_simple_for_user_without_simple_profile()
    {
        $owner = $this->makeUser(['id' => 1, 'logged_as' => User::TYPE_PROFESSIONAL]);
        $target = $this->makeUser([
            'id' => 2,
            'simpleUser' => false,
            'professionalUser' => true,
            'subcategory_id' => 5,
        ]);
        $task = $this->makeTask($owner);

        $ctx = $this->buildCtx($task, $owner, $target, TaskParticipantRole::SIMPLE);
        $error = $this->validator->validateCreate($ctx);

        $this->assertEquals('task_participant_target_missing_profile', $error);
    }

    /** @test */
    public function non_owner_cannot_create_for_another_user()
    {
        $owner = $this->makeUser(['id' => 1]);
        $profUser = $this->makeUser([
            'id' => 2,
            'logged_as' => User::TYPE_PROFESSIONAL,
            'professionalUser' => true,
            'subcategory_id' => 5,
        ]);
        $anotherUser = $this->makeUser([
            'id' => 3,
            'professionalUser' => true,
            'subcategory_id' => 5,
        ]);
        $task = $this->makeTask($owner, 5);

        $ctx = $this->buildCtx($task, $profUser, $anotherUser, TaskParticipantRole::PROFESSIONAL);
        $error = $this->validator->validateCreate($ctx);

        $this->assertEquals('task_participant_forbidden', $error);
    }

    /** @test */
    public function professional_subcategory_mismatch_is_rejected()
    {
        $owner = $this->makeUser(['id' => 1, 'logged_as' => User::TYPE_PROFESSIONAL]);
        $target = $this->makeUser([
            'id' => 2,
            'professionalUser' => true,
            'subcategory_id' => 99,
        ]);
        $task = $this->makeTask($owner, 5);

        $ctx = $this->buildCtx($task, $owner, $target, TaskParticipantRole::PROFESSIONAL);
        $error = $this->validator->validateCreate($ctx);

        $this->assertEquals('task_participant_subcategory_mismatch', $error);
    }
}
