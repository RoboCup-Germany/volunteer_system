<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http\Stub;

use Volunteersystem\Http\MessageTrait;
use Psr\Http\Message\MessageInterface;
use Symfony\Component\HttpFoundation\Request;

class MessageTraitRequestImplementation extends Request implements MessageInterface
{
    use MessageTrait;
}
