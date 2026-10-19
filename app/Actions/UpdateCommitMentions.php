<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PendingMention;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class UpdateCommitMentions
{
    /**
     * Turns the mentions of the user's username made before they signed in into mentions of them, which link to their
     * profile like any other, a few at a time, since a well-known developer may have been mentioned a lot. The commits
     * keep their update time, since nobody edited them.
     */
    public function handle(User $user): void
    {
        $username = Str::lower($user->username);

        PendingMention::query()
            ->where('username', $username)
            ->with(['commit' => fn (BelongsTo $query): BelongsTo => $query->withTrashed()])
            ->chunkById(200, function (Collection $pending) use ($username, $user): void {
                DB::transaction(function () use ($pending, $username, $user): void {
                    foreach ($pending as $mention) {
                        $commit = $mention->commit;
                        $commit->timestamps = false;

                        $commit->update(['mentions' => [...$commit->mentions ?? [], $username => $user->id]]);
                    }

                    PendingMention::query()->whereKey($pending->modelKeys())->delete();
                });
            });
    }
}
