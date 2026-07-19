<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('attendance:remind')->everyMinute();
