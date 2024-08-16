<?php

namespace Fintech\Remit\Contracts;

use Fintech\Core\Abstracts\BaseModel;

interface MoneyTransfer
{

    /**
     * Method to make a request to the remittance service provider
     * for a quotation of the order. that include charge, fee,
     * commission and other information related to order.
     *
     * @param BaseModel $order
     * @return mixed
     * @throws \ErrorException
     */
    public function requestQuote(BaseModel $order): mixed;

    /**
     * Method to make a request to the remittance service provider
     * for an execution of the order.
     *
     * @param BaseModel $order
     * @return mixed
     * @throws \ErrorException
     */
    public function executeOrder(BaseModel $order): mixed;

    /**
     * Method to make a request to the remittance service provider
     * for the progress status of the order.
     *
     * @param BaseModel $order
     * @return mixed
     * @throws \ErrorException
     */
    public function orderStatus(BaseModel $order): mixed;

    /**
     * Method to make a request to the remittance service provider
     * for the cancellation of the order.
     *
     * @param BaseModel $order
     * @return mixed
     * @throws \ErrorException
     */
    public function cancelOrder(BaseModel $order): mixed;

    /**
     * Method to make a request to the remittance service provider
     * for the amendment of the order.
     *
     * @param BaseModel $order
     * @return mixed
     * @throws \ErrorException
     */
    public function amendmentOrder(BaseModel $order): mixed;
}
