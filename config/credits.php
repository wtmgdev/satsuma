<?php

return [

    // Credits consumed per unit of each metered action.
    'costs' => [
        'pagespeed_analysis' => 1,
        'ai_summary' => 1,
        'crawl_page' => 1,
    ],

    // Our estimated external cost per unit (USD) — powers spend analytics.
    'estimated_unit_cost' => [
        'pagespeed_analysis' => 0.002,
        'ai_summary' => 0.004,
        'crawl_page' => 0.01,
    ],

    // Monthly allowance when an organization has no explicit one set.
    'default_allowance' => 500,

    // Low-balance warning threshold (fraction of allowance).
    'low_balance_ratio' => 0.2,

    // Record usage but never enforce balances (local development).
    'dry_run' => env('METERED_ACTIONS_DRY_RUN', false),
];
