<?php

namespace App\Entity\Enum;

enum KanbanStatus: string
{
    case TODO = 'TODO';
    case IN_PROGRESS = 'IN_PROGRESS';
    case DONE = 'DONE';
}
