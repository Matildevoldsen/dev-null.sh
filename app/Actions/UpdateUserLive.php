<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

final readonly class UpdateUserLive
{
    public function handle(User $user, string $url): void
    {
        $user->update(['live_url' => $url, 'live_since' => now()]);
    }
}
