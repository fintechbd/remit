<?php

namespace Fintech\Remit\Contracts;

interface OrderQuotation
{
    /**
     * @param  \Illuminate\Database\Eloquent\Model|\Fintech\Core\Abstracts\BaseModel  $order
     */
    public function requestQuote($order): mixed;
}
