<?php

declare(strict_types=1);

namespace ScssPhp\ScssPhp;

enum DeprecationStatus
{
    case active;
    case user;
    case future;
    case obsolete;
}
