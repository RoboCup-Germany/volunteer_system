<?php

declare(strict_types=1);

namespace Volunteersystem\Config;

enum GoodieType : string
{
    case None = 'none';
    case Goodie = 'goodie';
    case Tshirt = 'tshirt';
}
