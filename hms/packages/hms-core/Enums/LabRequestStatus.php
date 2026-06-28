<?php

namespace Hms\Core\Enums;

enum LabRequestStatus: string
{
    case Requested = 'requested';
    case Processing = 'processing';
    case Completed = 'completed';
}
