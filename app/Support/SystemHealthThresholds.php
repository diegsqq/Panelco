<?php

namespace App\Support;

class SystemHealthThresholds
{
    public const STUCK_PROCESSING_MINUTES = 30;

    public const STALE_QUEUE_JOB_MINUTES = 30;

    public const ALERT_COOLDOWN_MINUTES = 60;
}
