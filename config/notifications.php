<?php

return [

    'queues' => [
        'critical' => 'notifications.critical',
        'default' => 'notifications.default',
        'marketing' => 'notifications.marketing',
    ],

    'idempotency_ttl' => (int) env('IDEMPOTENCY_TTL', 86400),

    'mock' => [
        'force_transient_failure' => (bool) env('MOCK_FORCE_TRANSIENT_FAILURE', false),
        'force_permanent_failure' => (bool) env('MOCK_FORCE_PERMANENT_FAILURE', false),
        'permanent_failure_reason' => env('MOCK_PERMANENT_FAILURE_REASON', 'Recipient is unreachable'),
    ],

];
