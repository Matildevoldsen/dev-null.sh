<?php

declare(strict_types=1);

use App\Models\Commit;
use App\Models\User;

it('goes live with a stream that plays on their profile', function (string $url, string $provider): void {
    $user = User::factory()->create();

    $this->actingAs($user)->fromRoute('user-profile.edit')
        ->put(route('user-live.update'), ['url' => " {$url} "])
        ->assertRedirectToRoute('user-profile.edit');

    expect($user->refresh()->live_url)->toBe($url)
        ->and($user->live_since?->isToday())->toBeTrue();

    $this->get(route('user.show', $user))
        ->assertInertia(fn ($page) => $page
            ->where('user.live_url', $url)
            ->where('liveEmbed.provider', $provider)
            ->where('liveEmbed.kind', 'video'));
})->with([
    'twitch' => ['https://www.twitch.tv/octocat', 'Twitch'],
    'youtube' => ['https://www.youtube.com/live/dQw4w9WgXcQ', 'YouTube'],
]);

it('shows the live marker on their commits in the log', function (): void {
    $user = User::factory()->create(['live_url' => 'https://www.twitch.tv/octocat', 'live_since' => now()]);
    Commit::factory()->for($user)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('user.show', $user))
        ->assertInertia(fn ($page) => $page
            ->where('commits.data.0.user.live_url', 'https://www.twitch.tv/octocat')
            ->missing('commits.data.0.user.live_since'));
});

it('rejects links that do not play a stream in place', function (array $payload): void {
    $user = User::factory()->create();

    $this->actingAs($user)->putJson(route('user-live.update'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('url');

    expect($user->refresh()->live_url)->toBeNull();
})->with([
    'missing' => fn (): array => [],
    'not a link' => fn (): array => ['url' => 'twitch'],
    'not a stream' => fn (): array => ['url' => 'https://example.com/live'],
    'other provider' => fn (): array => ['url' => 'https://vimeo.com/76979871'],
    'twitch page' => fn (): array => ['url' => 'https://www.twitch.tv/directory'],
    'too long' => fn (): array => ['url' => 'https://www.twitch.tv/'.str_repeat('a', 2048)],
]);

it('ends the stream', function (): void {
    $user = User::factory()->create(['live_url' => 'https://www.twitch.tv/octocat', 'live_since' => now()]);

    $this->actingAs($user)->fromRoute('user-profile.edit')
        ->delete(route('user-live.destroy'))
        ->assertRedirectToRoute('user-profile.edit');

    expect($user->refresh()->live_url)->toBeNull()
        ->and($user->live_since)->toBeNull();

    $this->get(route('user.show', $user))
        ->assertInertia(fn ($page) => $page
            ->where('user.live_url', null)
            ->where('liveEmbed', null));
});

it('stops showing a stream its author forgot to end', function (): void {
    $user = User::factory()->create(['live_url' => 'https://www.twitch.tv/octocat', 'live_since' => now()]);

    $this->travel(User::LIVE_HOURS)->hours();
    $this->travel(1)->minute();

    $this->get(route('user.show', $user))
        ->assertInertia(fn ($page) => $page
            ->where('user.live_url', null)
            ->where('liveEmbed', null));
});

it('requires authentication', function (): void {
    $this->putJson(route('user-live.update'), ['url' => 'https://www.twitch.tv/octocat'])->assertUnauthorized();
    $this->deleteJson(route('user-live.destroy'))->assertUnauthorized();
});
