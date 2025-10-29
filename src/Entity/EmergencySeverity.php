<?php

namespace App\Entity;

enum EmergencySeverity: string
{
    case NOTICE = 'notice';
    case WARNING = 'warning';
    case DANGER = 'danger';
}
