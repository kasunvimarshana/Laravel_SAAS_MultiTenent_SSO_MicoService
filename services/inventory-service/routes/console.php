<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Daily reorder level notification
Schedule::command('inventory:check-reorder-levels')->dailyAt('08:00');
