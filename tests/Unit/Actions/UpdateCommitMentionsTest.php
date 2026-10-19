<?php

declare(strict_types=1);

use App\Actions\UpdateCommitMentions;
use App\Models\Commit;
use App\Models\PendingMention;
use App\Models\User;

it('turns the mentions made before the user signed in into mentions of them', function (): void {
    $taylor = User::factory()->create(['username' => 'taylor']);
    $commit = Commit::factory()->create([
        'body' => 'Thanks @taylor and @OndrejMirtes!',
        'mentions' => ['taylor' => $taylor->id],
        'updated_at' => now()->subDay(),
    ]);
    $deleted = Commit::factory()->trashed()->create(['body' => '@ondrejmirtes', 'mentions' => []]);
    $unrelated = PendingMention::factory()->create(['username' => 'someoneelse']);

    PendingMention::factory()->for($commit)->create(['username' => 'ondrejmirtes']);
    PendingMention::factory()->for($deleted)->create(['username' => 'ondrejmirtes']);

    $user = User::factory()->create(['username' => 'OndrejMirtes']);
    $updatedAt = $commit->updated_at;

    resolve(UpdateCommitMentions::class)->handle($user);

    expect($commit->refresh()->mentions)->toBe(['taylor' => $taylor->id, 'ondrejmirtes' => $user->id])
        ->and($commit->updated_at->equalTo($updatedAt))->toBeTrue()
        ->and($deleted->refresh()->mentions)->toBe(['ondrejmirtes' => $user->id])
        ->and(PendingMention::query()->pluck('id')->all())->toBe([$unrelated->id]);
});
