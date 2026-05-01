<?php

namespace Tests\Unit;

use App\Domains\Tasks\Enums\TaskParticipantProfile;
use App\Domains\Tasks\Models\TaskParticipant;
use App\Domains\Users\Models\ProfessionalUser;
use App\Domains\Users\Models\SimpleUser;
use App\Domains\Users\Models\User;
use App\Support\Users\UserProfileMerger;
use Tests\TestCase;

class UserProfileMergerTest extends TestCase
{
    public function test_it_merges_user_with_profile_without_internal_keys(): void
    {
        $user = new User([
            'username' => 'lucas',
            'full_name' => 'Lucas',
            'email' => 'lucas@example.com',
        ]);
        $user->id = 10;

        $profile = new SimpleUser([
            'birth_date' => '1990-01-01',
            'about_me' => 'Simple profile',
            'profile_photo' => 'photo.jpg',
        ]);
        $profile->id = 20;
        $profile->user_id = 10;
        $profile->laravel_through_key = 10;

        $result = UserProfileMerger::merge($user, $profile);

        $this->assertSame(10, $result['id']);
        $this->assertSame('lucas', $result['username']);
        $this->assertSame('Simple profile', $result['about_me']);
        $this->assertArrayNotHasKey('profiles', $result);
        $this->assertArrayNotHasKey('user_id', $result);
        $this->assertArrayNotHasKey('laravel_through_key', $result);
    }

    public function test_requested_by_profile_uses_loaded_requester_profile(): void
    {
        $participant = new TaskParticipant([
            'participant_profile' => TaskParticipantProfile::SIMPLE,
        ]);

        $requester = new User([
            'username' => 'pro',
            'full_name' => 'Professional Requester',
            'email' => 'pro@example.com',
        ]);
        $requester->id = 30;

        $requesterProfile = new ProfessionalUser([
            'birth_date' => '1985-01-01',
            'about_me' => 'Professional requester profile',
            'profile_photo' => 'pro.jpg',
        ]);
        $requesterProfile->id = 40;
        $requesterProfile->user_id = 30;

        $participant->setRelation('requestedBy', $requester);
        $requester->setRelation('professionalUser', $requesterProfile);
        $requester->setRelation('simpleUser', null);

        $result = $participant->requested_by_profile;

        $this->assertSame('pro', $result['username']);
        $this->assertSame('Professional requester profile', $result['about_me']);
    }

    public function test_participant_profile_uses_declared_profile_type(): void
    {
        $participant = new TaskParticipant([
            'participant_profile' => TaskParticipantProfile::SIMPLE,
        ]);

        $user = new User([
            'username' => 'simple',
            'full_name' => 'Simple User',
            'email' => 'simple@example.com',
        ]);
        $user->id = 50;

        $simpleProfile = new SimpleUser([
            'birth_date' => '1992-01-01',
            'about_me' => 'Selected simple profile',
        ]);
        $simpleProfile->id = 60;
        $simpleProfile->user_id = 50;

        $professionalProfile = new ProfessionalUser([
            'birth_date' => '1980-01-01',
            'about_me' => 'Other profile',
        ]);
        $professionalProfile->id = 70;
        $professionalProfile->user_id = 50;

        $participant->setRelation('user', $user);
        $user->setRelation('simpleUser', $simpleProfile);
        $user->setRelation('professionalUser', $professionalProfile);

        $result = $participant->profile;

        $this->assertSame('simple', $result['username']);
        $this->assertSame('Selected simple profile', $result['about_me']);
    }
}
