<?php

namespace App\Modules\Core\Enums;

/**
 * Represents possible Order source values.
 */
enum OrderSource: string
{
    case Pos = 'pos';
    case Online = 'online';
}
