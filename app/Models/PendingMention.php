<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\PendingMentionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A commit's mention of a GitHub username nobody on /dev/null has, lowercased. It gives that developer a profile to
 * claim, and becomes a regular mention once they sign in.
 *
 * @property-read int $id
 * @property-read string $commit_id
 * @property-read string $username
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read Commit $commit
 */
final class PendingMention extends Model
{
    /** @use HasFactory<PendingMentionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Commit, $this>
     */
    public function commit(): BelongsTo
    {
        return $this->belongsTo(Commit::class);
    }
}
