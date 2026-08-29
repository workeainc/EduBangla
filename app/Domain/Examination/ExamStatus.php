<?php

namespace App\Domain\Examination;

enum ExamStatus: string
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case ONGOING = 'ongoing';
    case COMPLETED = 'completed';
    case LOCKED = 'locked';
    case PUBLISHED = 'published';

    public static function next(self $s): ?self
    {
        return match ($s) {
            self::DRAFT => self::SCHEDULED,self::SCHEDULED => self::ONGOING,self::ONGOING => self::COMPLETED,self::COMPLETED => self::LOCKED,self::LOCKED => self::PUBLISHED,default => null
        };
    }
}
