<?php

declare(strict_types=1);

namespace App\Actions\Ads;

use App\Data\Moderation\ModerationResult;
use App\Models\Ad;
use App\Services\Moderation\ModerationRulesService;

/**
 * Runs an ad's title + description through the three moderation rule families
 * and returns a structured outcome. Kept as a thin invokable action because
 * it composes one service call and has no side effects — easy to unit-test
 * and to dispatch from synchronous AND queued contexts.
 *
 * If moderation is disabled (`config('moderation.enabled') === false`) we
 * return a clean result immediately so the publish flow short-circuits to the
 * pre-Wave-B behaviour. This is the single kill-switch the operations team
 * can flip when a regex misfires in production.
 */
class ModerateAdAction
{
    public function __construct(
        private readonly ModerationRulesService $rules,
    ) {}

    public function __invoke(Ad $ad): ModerationResult
    {
        if (! (bool) config('moderation.enabled', true)) {
            return ModerationResult::clean();
        }

        $combined = trim($ad->title . "\n" . $ad->description);

        $flags = [];
        $details = [];

        $bannedHits = $this->rules->containsBannedWords($combined);
        if ($bannedHits !== []) {
            $flags[] = 'banned_words';
            $details['banned_words'] = $bannedHits;
        }

        if ($this->rules->containsPhone($combined)) {
            $flags[] = 'phone';
            $details['phone'] = true;
        }

        $linkHits = $this->rules->containsExternalLink($combined);
        if ($linkHits !== []) {
            $flags[] = 'external_link';
            $details['external_link'] = $linkHits;
        }

        if ($flags === []) {
            return ModerationResult::clean();
        }

        return ModerationResult::rejected($flags, $details);
    }
}
