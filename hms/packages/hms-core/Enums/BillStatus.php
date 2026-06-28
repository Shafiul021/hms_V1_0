<?php

namespace Hms\Core\Enums;

enum BillStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Partial = 'partial';
    case Paid = 'paid';
}
