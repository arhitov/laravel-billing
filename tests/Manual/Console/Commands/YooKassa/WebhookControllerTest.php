<?php

namespace Arhitov\LaravelBilling\Tests\Manual\Console\Commands\YooKassa;

use Arhitov\LaravelBilling\Http\Controllers\WebhookController;
use Arhitov\LaravelBilling\Models\Operation;
use Arhitov\LaravelBilling\OmnipayGateway;
use Arhitov\LaravelBilling\Tests\ConsoleCommandsTestCase;
use Illuminate\Http\Request;

class WebhookControllerTest extends ConsoleCommandsTestCase
{
    const GATEWAY = 'yookassa';

    public function testRequestByYookassa()
    {
        $owner = $this->createOwner();
        $balance = $owner->getBalanceOrCreate();
        $amount = '123.45';

        $this->assertEquals(0, $balance->amount);

        $omnipayGateway = new OmnipayGateway(self::GATEWAY);

        $this
            ->artisan('billing:create-payment', [
                'balance'   => $balance->getKey(),
                'amount'    => $amount,
                '--gateway' => self::GATEWAY,
            ])
            ->assertSuccessful();

        /** @var Operation|null $operation */
        $operation = $balance->operation()->first();

        $this->assertNotEmpty($operation->gateway_payment_id);
        $this->assertEquals('pending', $operation->gateway_payment_state);

        // Fixture incoming notification.payment.succeeded.json
        $notification = json_decode(file_get_contents(__DIR__ . '/../../../../../vendor/arhitov/omnipay-yookassa/tests/Fixtures/fixture/notification.payment.succeeded.json'), true);

        $notification['object']['id'] = $operation->gateway_payment_id;
        $notification['object']['amount']['value'] = $amount;

        $httpRequest = new Request(
            content: json_encode($notification, JSON_UNESCAPED_UNICODE)
        );
        $response = (new WebhookController())->webhookNotification($httpRequest, 'yookassa');

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals($omnipayGateway->getConfig('webhook.response.status', 201), $response->getStatusCode());

        $operation->refresh();

        $this->assertEquals('succeeded', $operation->state->value);
        $this->assertEquals('success', $operation->gateway_payment_state);

        $balance->refresh();

        $this->assertEquals($amount, $balance->amount);
    }
}
