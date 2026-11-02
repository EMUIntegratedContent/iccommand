<?php

namespace App\Entity\Ic;

enum IcContactType: string
{
    case PERSON = 'Person';
    case ORGANIZATION = 'Organization';
}
