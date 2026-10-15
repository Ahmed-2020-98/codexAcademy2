<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('crm:check-sla')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('academy:session-reminders')->everyTenMinutes()->withoutOverlapping();
Schedule::command('academy:weekly-task-reminders')->weeklyOn(0, '09:00'); // Sunday morning
