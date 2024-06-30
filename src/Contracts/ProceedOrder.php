<?php

namespace Fintech\Remit\Contracts;

interface ProceedOrder
{
    /**
     * @param  \Illuminate\Database\Eloquent\Model|\Fintech\Core\Abstracts\BaseModel  $order
     */
    public function processOrder($order): mixed;
}
