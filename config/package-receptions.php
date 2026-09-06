<?php

return [
    'pickup_warning_hours' => (int) env('PACKAGE_PICKUP_WARNING_HOURS', 24),
    'pickup_critical_hours' => (int) env('PACKAGE_PICKUP_CRITICAL_HOURS', 72),
];
