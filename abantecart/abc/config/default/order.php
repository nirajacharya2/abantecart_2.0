<?php
/*
 * Additional information for order statuses.
 * Used by order editing process
 */
return [
    'statuses' => [
        'incomplete'           => [],
        'pending'              => [],
        'processing'           => [],
        'shipped'              => [],
        'canceled'             => [
            'actions' => [
                'refund',
                'return_to_stock',
            ],
        ],
        'completed'            => [],
        'denied'               => [],
        'canceled_reversal'    => [],
        'failed'               => [],
        'refunded'             => [
            'actions' => [
                'refund',
                'return_to_stock',
            ],
        ],
        'reversed'             => [],
        'chargeback'           => [],
        'canceled_by_customer' => [
            'actions' => [
                'refund',
                'return_to_stock',
            ],
        ],
    ],
];
