<?php

namespace Fintech\Remit\Contracts;

interface OrderInquiry
{
    /**
     * @param  \Illuminate\Database\Eloquent\Model|\Fintech\Core\Abstracts\BaseModel  $order
     */
    public function orderStatus($order): mixed;
}
