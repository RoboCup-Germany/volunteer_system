<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Renderer\Twig\Extensions;

use Volunteersystem\Helpers\Translation\Translator;
use Volunteersystem\Renderer\Twig\Extensions\Translation;
use PHPUnit\Framework\MockObject\MockObject;

class TranslationTest extends ExtensionTest
{
    /**
     * @covers \Volunteersystem\Renderer\Twig\Extensions\Translation::__construct
     * @covers \Volunteersystem\Renderer\Twig\Extensions\Translation::getFilters
     */
    public function testGetFilters(): void
    {
        /** @var Translator|MockObject $translator */
        $translator = $this->createMock(Translator::class);

        $extension = new Translation($translator);
        $filters = $extension->getFilters();

        $this->assertFilterExists('trans', [$translator, 'translate'], $filters);
    }

    /**
     * @covers \Volunteersystem\Renderer\Twig\Extensions\Translation::getFunctions
     */
    public function testGetFunctions(): void
    {
        /** @var Translator|MockObject $translator */
        $translator = $this->createMock(Translator::class);

        $extension = new Translation($translator);
        $functions = $extension->getFunctions();

        $this->assertExtensionExists('__', [$translator, 'translate'], $functions);
        $this->assertExtensionExists('_e', [$translator, 'translatePlural'], $functions);
    }
}
