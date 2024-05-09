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
    "state"              => "created",
    "state_pending_at"   => null,
    "state_paid_at"      => null,
    "state_send_at"      => null,
    "state_succeeded_at" => null,
    "state_canceled_at"  => null,
    "operation_uuid"     => "829aa559-c1c6-4ff7-83fd-94e3ae72a3cc",
    "gateway"            => 'unknown',
    "owner_id"           => 1,
    "owner_type"         => "Arhitov\LaravelBilling\Models\User",
    "amount"             => 2.12,
    "currency"           => "USD",
    "receipt_data"       => [
        "type"       => "payment",
        "state"      => "pending",
        "uuid"       => "succeeded-2da5c87d-0384-50e8-a7f3-8d5646dd9e10",
        "date"       => "2024-05-08 19:50:47",
        'payment_id' => '24b94598-000f-5000-9000-1b68e7b15f3f',
        'info'       => 'Lego Bricks',
        "@seller"    => [
            "uuid" => "cb4ed5f7-8b1b-11df-be16-e04a65ecb60f",
            'name' => 'LLC "HORNS AND HOOVES"',
        ],
        "@customer"  => [
            "type"  => 2,
            "uuid"  => "b037c24d-8c89-48a9-87eb-ffc6023bec19",
            'name'  => 'Alexander Arhitov',
            'email' => 'clgsru@gmail.com',
        ],
        "@itemList"  => [
            0 => [
                'name'     => 'FLAG, W/ 2 HOLDERS, NO. 22',
                'code'     => '6446963/104515',
                'type'     => 'product',
                'amount'   => 2.12,
                'currency' => 'USD',
                'quantity' => 2,
                'unit'     => 'pc',
                'tax'      => 0,
            ],
        ],
    ],
];
