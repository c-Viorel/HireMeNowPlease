<?php

namespace App\Enums;

enum WorkplaceType: string
{
    case Remote = 'remote';
    case Hybrid = 'hybrid';
    case OnSite = 'on_site';
}
