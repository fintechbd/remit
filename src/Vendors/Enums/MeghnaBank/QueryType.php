<?php

namespace Fintech\Remit\Vendors\Enums\MeghnaBank;

enum QueryType: string
{
    case CurrentRate = '1';
    case Balance = '2';
    case Amendment = '3';
    case Cancellation = '4';
}
