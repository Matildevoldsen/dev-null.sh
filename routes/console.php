<?php

declare(strict_types=1);

use App\Jobs\EmbedKnowledgeBase;
use App\Jobs\EndLiveStreams;
use Illuminate\Support\Facades\Schedule;

Schedule::command('commits:submit-scheduled')->everyMinute()->withoutOverlapping(5);
Schedule::job(new EmbedKnowledgeBase())->hourly();
Schedule::job(new EndLiveStreams())->everyFiveMinutes();
