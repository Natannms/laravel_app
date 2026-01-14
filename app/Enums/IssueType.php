<?php

namespace App\Enums;

enum IssueType: string
{
    case Epic = 'EPIC';
    case Story = 'STORY';
    case Task = 'TASK';
    case Subtask = 'SUBTASK';
    case Bug = 'BUG';
}

