<?php

declare (strict_types=1);
namespace Scss_Php\Scss_Php;

enum Deprecation_Status
{
    case active;
    case user;
    case future;
    case obsolete;
}