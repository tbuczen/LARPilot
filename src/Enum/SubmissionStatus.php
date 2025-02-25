<?php

namespace App\Enum;

enum SubmissionStatus: string
{
    case NEW = 'new';
    case CONSIDER = 'consider';
    case REJECTED = 'rejected';
    case ACCEPTED = 'accepted';
}