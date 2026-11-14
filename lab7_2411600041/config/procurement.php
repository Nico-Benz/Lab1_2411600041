<?php

/*
|--------------------------------------------------------------------------
| Lab 7 - Procurement settings
|--------------------------------------------------------------------------
| Change the numbers here and the whole system follows (no code changes).
*/

return [

    // Tax added on top of the purchase order subtotal (0.12 = 12% VAT)
    'tax_rate' => 0.12,

    // How many days of "stock out" history are used to find the average daily usage
    'usage_days' => 30,

    // How many days of stock we want to cover when we order (used for the suggested quantity)
    'cover_days' => 30,

    // Lead time used when a product has no supplier yet
    'default_lead_time' => 7,

    // How long the dashboard widgets are cached (seconds)
    'widget_cache_seconds' => 60,

    // Default terms printed on every new purchase order
    'default_terms' => 'Please deliver on or before the expected delivery date. Payment follows the supplier payment terms.',
];
