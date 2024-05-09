<?php

namespace Fintech\Remit\Contracts;

use MongoDB\Laravel\Eloquent\Model;

interface OrderQuotation
{
    /**
     * @param \Illuminate\Database\Eloquent\Model|Model $order
     */
    public function requestQuotation($order): mixed;
}
