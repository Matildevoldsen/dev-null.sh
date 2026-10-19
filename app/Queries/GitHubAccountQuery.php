<?php

declare(strict_types=1);

namespace App\Queries;

use App\Dto\GitHubAccount;
use App\Services\GitHub;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final readonly class GitHubAccountQuery
{
    public function __construct(private GitHub $github)
    {
        //
    }

    /**
     * The personal GitHub account behind a username, cached for a day. Null when GitHub has no such account, or it
     * belongs to an organization, which nobody can sign in as; that is cached too, so a mention of "@everyone" doesn't
     * ask GitHub on every visit. Null as well when GitHub can't be reached, which isn't cached, so the next visit asks
     * again.
     */
    public function get(string $username): ?GitHubAccount
    {
        /** @var array<array-key, mixed>|false|null $account */
        $account = Cache::remember('github:account:'.Str::lower($username), 86400, function () use ($username): array|false|null {
            try {
                $response = $this->github->request()->get('users/'.rawurlencode($username));

                if ($response->notFound()) {
                    return false;
                }

                $account = $response->throw()->json();
            } catch (HttpClientException) {
                return null;
            }

            return is_array($account) && is_int($account['id'] ?? null) && is_string($account['login'] ?? null) && ($account['type'] ?? null) === 'User' ? $account : false;
        });

        return is_array($account) ? GitHubAccount::fromAccount($account) : null;
    }
}
