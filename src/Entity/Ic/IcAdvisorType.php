<?php

namespace App\Entity\Ic;

enum IcAdvisorType: string
{
    case PERSON = 'Person';
    case ORGANIZATION = 'Organization';
}
