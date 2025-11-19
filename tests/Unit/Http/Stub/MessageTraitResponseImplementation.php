<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http\Stub;

use Volunteersystem\Http\MessageTrait;
use Psr\Http\Message\MessageInterface;
use Symfony\Component\HttpFoundation\Response;

class MessageTraitResponseImplementation extends Response implements MessageInterface
{
    use MessageTrait;
}
