<?php

declare(strict_types=1);

namespace Volunteersystem\Renderer\Twig\Extensions;

use Volunteersystem\Config\Config as VolunteersystemConfig;
use Twig\Extension\AbstractExtension as TwigExtension;
use Twig\TwigFunction;

class Config extends TwigExtension
{
    public function __construct(protected VolunteersystemConfig $config)
    {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('config', [$this->config, 'get']),
        ];
    }
}
