<?php

namespace Fintech\Remit\Contracts;

interface OrderQuotation
{
    /**
     * @param  \Illuminate\Database\Eloquent\Model|\MongoDB\Laravel\Eloquent\Model  $order
     */
    public function requestQuotation($order): mixed;
}
