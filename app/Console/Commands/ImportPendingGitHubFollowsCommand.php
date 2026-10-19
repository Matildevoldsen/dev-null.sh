<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\CreatePendingFollows;
use App\Actions\CreateUserGitHubFollows;
use App\Models\User;
use App\Services\GitHub;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

#[Signature('follows:pending {--after= : Only the users after this ID, to resume a run GitHub rate limited}')]
#[Description('Remember, for every user whose GitHub follows were imported, the accounts they follow on GitHub that are not on /dev/null yet')]
final class ImportPendingGitHubFollowsCommand extends Command
{
    public function handle(GitHub $github, CreatePendingFollows $createPendingFollows): int
    {
        $imported = 0;
        $failed = 0;
        $lastId = null;

        $after = $this->option('after');

        $users = User::query()
            ->whereNotNull('github_follows_imported_at')
            ->when(is_string($after), fn (Builder $query): Builder => $query->where('id', '>', $after));

        foreach ($users->lazyById() as $user) {
            try {
                $login = $github->login($user->github_id);

                if ($login !== null) {
                    $createPendingFollows->handle($user, $github->accountIds("users/{$login}/following", CreateUserGitHubFollows::MaxPages));
                }

                $imported++;
            } catch (RequestException $exception) {
                if (in_array($exception->response->status(), [403, 429], true)) {
                    $resume = $lastId === null ? '' : " with --after={$lastId}";

                    $this->components->error("GitHub rate limit reached after {$imported} users. Run the command again{$resume} later.");

                    return self::FAILURE;
                }

                $this->failed($user, $exception);

                $failed++;
            } catch (ConnectionException $exception) {
                $this->failed($user, $exception);

                $failed++;
            }

            $lastId = $user->id;
        }

        $this->components->info("Remembered the pending GitHub follows of {$imported} users.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function failed(User $user, RequestException|ConnectionException $exception): void
    {
        report($exception);

        $this->components->warn("Could not read whom @{$user->username} follows on GitHub.");
    }
}
