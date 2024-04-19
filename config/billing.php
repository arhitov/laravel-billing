<?php

return [
    /**
     * Setting database
     */
    'database' => [
        /**
         * This connection will be used by models to work with the database.
         * Note: migration does not use this setting.
         * If you are going to use a connection other than the default, you need to manually specify it in the migration files.
         */
        'connection' => null,

        /**
         * Table for storing billing transactions.
         */
        'tables' => [
            /** Balance storage table */
            'balance' => 'billing_balances',
            /** Transaction table */
            'operation' => 'billing_operations',
            /** Saved credit cards table */
            'credit_card' => 'billing_credit_cards',
            /** Subscription table */
            'subscription' => 'billing_subscriptions',
        ],

        /**
         * Relationship.
         * Delete all associated data when the owner is deleted.
         */
        'delete_cascade' => true,


        /**
         * Use a transaction to ensure data integrity in the event of an error.
         */
        'use_transaction' => true,

        /**
         * Locking rows during operation.
         * If your database table supports row-level locking, then use this mechanism to reduce the load on the database.
         * If you disable this option, some events may not fire.
         * Works in conjunction with "use_transaction".
         */
        'use_lock_line' => true,
    ],

    /**
     * This is the default currency that will be used when generating charges from your application.
     */
    'currency' => env('BILLING_CURRENCY', 'RUB'),

    /**
     * Root owner balance.
     * Replenishments are made from it and funds are received when paying for services.
     */
    'root_owner' => [
        /** Name root owner table or "null" when using a custom model  */
        'table' => 'billing_root_owners',
        'owner_type' => 'Arhitov\\LaravelBilling\\Models\\RootOwner',
        'owner_id' => 1,
        /** Model data, if automatic model creation is required. Or "null". */
        'create_model_data' => [
            'name' => env('APP_NAME', 'Laravel'),
        ],
    ],

    /**
     * Gateway for createPayment
     * The list contains example data. If you don't use gateways, you can delete this list.
     */
    'omnipay_gateway' => [
        'default' => 'dummy',
        'gateways' => [
            'dummy' => [
                'omnipay_class' => 'Dummy',
            ],
            'yookassa' => [
                'omnipay_class' => 'YooKassa',
                'omnipay_initialize' => [
                    'shop_id' => 54401,
                    'secret' => 'test_Fh8hUAVVBGUGbjmlzba6TB0iyUbos_lueTHE-axOwM0',
                ],
                'returnUrl' => 'https://www.example.com/pay',
                'capture' => true,
            ]
        ],
    ],

    /**
     * Using cache
     * array|null
     */
    'cache' => [
        'keys' => [
            'owner_balance_amount' => [
                'prefix' => 'owner_balance_amount',
                'ttl' => '10 minutes',
            ],
        ],
    ],

    /**
     * Rounding.
     */
    'rounding' => [

        /**
         * Precision of float value.
         */
        'precision' => 4
    ],

    /**
     * Message logging channel.
     */
    'logger' => env('BILLING_LOGGER'),
];
