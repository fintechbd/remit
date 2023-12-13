<?php

namespace Fintech\Remit\Contracts;

interface BankTransfer
{
    public function transfer(array $orderInfo);
    public function status(array $orderInfo);
    public function cancel(array $orderInfo);
}
