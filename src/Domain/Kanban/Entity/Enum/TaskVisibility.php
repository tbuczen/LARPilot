<?php

namespace App\Domain\Kanban\Entity\Enum;

enum TaskVisibility: string
{
    case ALL = 'ALL';
    case PRIVATE = 'PRIVATE';
    case HR = 'HR';
    case STORY = 'STORY';
}
