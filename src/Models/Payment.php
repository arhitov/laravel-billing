<?php

namespace Arhitov\LaravelBilling\Models;

use Arhitov\LaravelBilling\Increase;
use Omnipay\Common\Message\ResponseInterface;

/**
 * Data object
 */
readonly class Payment
{

    public function __construct(
        private Increase          $increase,
        private ResponseInterface $response,
    ) {
    }

    /**
     * @return \Arhitov\LaravelBilling\Increase
     */
    public function getIncrease(): Increase
    {
        return $this->increase;
    }

    /**
     * @return \Omnipay\Common\Message\ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
