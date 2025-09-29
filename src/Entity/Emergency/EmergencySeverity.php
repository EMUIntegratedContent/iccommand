<?php

namespace App\Entity\Emergency;

enum EmergencySeverity: string
{
    case NOTICE = 'notice';
    case WARNING = 'warning';
    case DANGER = 'danger';
}
