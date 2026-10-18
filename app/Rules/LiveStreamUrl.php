<?php

declare(strict_types=1);

namespace App\Rules;

use App\Dto\Embed;
use App\Services\Embeds;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The stream itself stays on the platform, so the link has to be one that plays in place.
 */
final readonly class LiveStreamUrl implements ValidationRule
{
    public const array PROVIDERS = ['YouTube', 'Twitch'];

    public function __construct(private Embeds $embeds)
    {
        //
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $embed = is_string($value) ? $this->embeds->for($value) : null;

        if (! $embed instanceof Embed || ! in_array($embed->provider, self::PROVIDERS, true)) {
            $fail('Use a YouTube or Twitch link to your stream.');
        }
    }
}
