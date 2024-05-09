<?php
/**
 * Billing module for laravel projects
 *
 * @link      https://github.com/arhitov/laravel-billing
 * @package   arhitov/laravel-billing
 * @license   MIT
 * @copyright Copyright (c) 2024, Alexander Arhitov, clgsru@gmail.com
 */

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
            /** Fiscal receipts table */
            'fiscal_receipt' => 'billing_fiscal_receipt',
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
     * Gateways by Omnipay
     * The list contains example data. If you don't use gateways, you can delete this list.
     */
    'omnipay_gateway' => [
        'default' => 'dummy',
        'gateways' => [
            'dummy' => [
                'omnipay_class' => 'Dummy',
                'card_required' => true,
            ],
            'dummy-omnireceipt-full' => [
                'omnipay_class'       => 'Dummy',
                'card_required'       => true,
                'omnireceipt_gateway' => 'dummy_full',
            ],
            'yookassa' => [
                'omnipay_class' => 'YooKassa',
                'omnipay_initialize' => [
                    'shop_id' => 54401,
                    'secret' => 'test_Fh8hUAVVBGUGbjmlzba6TB0iyUbos_lueTHE-axOwM0',
                ],
                'return_url' => 'https://www.example.com/pay',
                'webhook' => [
                    'response' => [
                        'content' => null,
                        'status' => 200,
                    ],
                    /** Trust incoming data. Otherwise, a request will be sent to the gateway API. */
                    'trust_input_data' => true,
                ],
            ],
            'yookassa-two-step' => [
                'omnipay_class' => 'YooKassa',
                'omnipay_initialize' => [
                    'shop_id' => 54401,
                    'secret' => 'test_Fh8hUAVVBGUGbjmlzba6TB0iyUbos_lueTHE-axOwM0',
                ],
                'return_url' => 'https://www.example.com/pay',
                'capture' => false,
            ],
            'yookassa-two-step-omnireceipt-full' => [
                'omnipay_class' => 'YooKassa',
                'omnipay_initialize' => [
                    'shop_id' => 54401,
                    'secret' => 'test_Fh8hUAVVBGUGbjmlzba6TB0iyUbos_lueTHE-axOwM0',
                ],
                'return_url' => 'https://www.example.com/pay',
                'capture' => false,
                'omnireceipt_gateway' => 'dummy_full',
            ],
            'yookassa-use-route-name' => [
                'omnipay_class' => 'YooKassa',
                'omnipay_initialize' => [
                    'shop_id' => 54401,
                    'secret' => 'test_Fh8hUAVVBGUGbjmlzba6TB0iyUbos_lueTHE-axOwM0',
                ],
                'return_route' => [
                    'name' => 'yookassa-return-url',
                    /**
                     * If you specify "operation_uuid" with a null value, the value will be substituted automatically.
                     * Other parameters will be added to the address bar.
                     */
                    'parameters' => ['operation_uuid' => null, 'order' => 123],
                ],
            ]
        ],
        'payment' => [
            'default_description' => 'Payment for site services',
        ],
    ],

    /**
     * Gateways by Omnireceipt Fiscal
     * array|null
     */
    'omnireceipt_gateway' => [
        'default' => 'dummy',
        'gateways' => [
            'dummy' => [
                'omnireceipt_class' => 'Dummy',
                'omnireceipt_initialize' => [
                    'auth' => 'ok',
                    'fixture' => false,
                    'default_properties' => [
                        'receipt_item' => [
                            'name'         => 'Information Services',
                            'code'         => 'info_goods',
                            'product_type' => 'SERVICE',
                            'quantity'     => 1,
                            'currency'     => 'RUB',
                            'unit'         => 'pc',
                            'unit_uuid'    => 'bd72d926-55bc-11d9-848a-00112f43529a',
                            'vat_rate'     => 13,
                        ],
                    ],
                ],

            ],
            'dummy_full' => [
                'omnireceipt_class' => 'Dummy',
                'omnireceipt_initialize' => [
                    'auth' => 'ok',
                    'default_properties' => [
                        'seller' => [
                            'uuid' => 'cb4ed5f7-8b1b-11df-be16-e04a65ecb60f',
                            'name' => 'www.LeanGroup.Ru',
                        ],
                        'customer' => [
                            'type' => 2, // Number	Тип покупателя: 0 - юр.лицо, 1 - индивидуальный предприниматель, 2 - физ.лицо
                        ],
                        'receipt' => [
                            'type'     => 'payment',
                        ],
                        'receipt_item' => [
                            'name'         => 'Information Services',
                            'code'         => 'info_goods',
                            'product_type' => 'SERVICE',
                            'quantity'     => 1,
                            'unit'         => 'pc',
                            'unit_uuid'    => 'bd72d926-55bc-11d9-848a-00112f43529a',
                            'vat_rate'     => 13,
                        ],
                    ],
                ],
            ],
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
         * Total, of which "precision" digits may be after the decimal point.
         */
        'total' => 18,

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
