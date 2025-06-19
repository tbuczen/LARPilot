<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;
use Gedmo\Loggable\Entity\Repository\LogEntryRepository;
use Gedmo\Loggable\Loggable;

#[ORM\Entity(repositoryClass: LogEntryRepository::class)]
#[ORM\Table(name: 'story_object_log_entry')]
#[ORM\Index(name: 'story_object_class_lookup_idx', columns: ['object_class'])]
#[ORM\Index(name: 'story_object_date_lookup_idx', columns: ['logged_at'])]
#[ORM\Index(name: 'story_object_user_lookup_idx', columns: ['username'])]
#[ORM\Index(name: 'story_object_version_lookup_idx', columns: ['object_id', 'object_class', 'version'])]
class StoryObjectLogEntry extends AbstractLogEntry
{
    /*
     * All required columns are mapped through inherited superclass
     */
}
