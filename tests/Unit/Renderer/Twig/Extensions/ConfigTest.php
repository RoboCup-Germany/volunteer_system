<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Renderer\Twig\Extensions;

use Volunteersystem\Config\Config as VolunteersystemConfig;
use Volunteersystem\Renderer\Twig\Extensions\Config;
use PHPUnit\Framework\MockObject\MockObject;

class ConfigTest extends ExtensionTest
{
    /**
     * @covers \Volunteersystem\Renderer\Twig\Extensions\Config::__construct
     * @covers \Volunteersystem\Renderer\Twig\Extensions\Config::getFunctions
     */
    public function testGetFunctions(): void
    {
        /** @var VolunteersystemConfig|MockObject $config */
        $config = $this->createMock(VolunteersystemConfig::class);

        $extension = new Config($config);
        $functions = $extension->getFunctions();

        $this->assertExtensionExists('config', [$config, 'get'], $functions);
    }
}
