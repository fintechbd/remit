<?php

namespace Fintech\Remit\Contracts;

interface OrderQuotation
{
    /**
     * @param \Illuminate\Database\Eloquent\Model|\MongoDB\Laravel\Eloquent\Model $order
     * @return mixed
     */
    public function requestQuotation($order): mixed;
}
