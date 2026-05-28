<?php

namespace App\Enums;

enum JobStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Closed = 'closed';
    case Rejected = 'rejected';
}
