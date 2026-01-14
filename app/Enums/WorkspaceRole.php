<?php

namespace App\Enums;

enum WorkspaceRole: string
{
    case Owner = 'OWNER';
    case Admin = 'ADMIN';
    case Manager = 'MANAGER';
    case Dev = 'DEV';
    case Viewer = 'VIEWER';
}

