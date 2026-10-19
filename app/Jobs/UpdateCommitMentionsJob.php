<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\UpdateCommitMentions;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Tries;

#[Tries(3)]
final class UpdateCommitMentionsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user)
    {
        //
    }

    public function handle(UpdateCommitMentions $updateCommitMentions): void
    {
        $updateCommitMentions->handle($this->user);
    }
}
