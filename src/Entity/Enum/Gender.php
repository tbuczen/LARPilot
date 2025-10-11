<?php

namespace App\Entity\Enum;

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
    case Other = 'other';
}
