<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Renderer\Twig\Extensions;

use Volunteersystem\Renderer\Twig\Extensions\StringExtension;

class StringExtensionTest extends ExtensionTest
{
    /**
     * @covers \Volunteersystem\Renderer\Twig\Extensions\StringExtension::getFilters
     */
    public function testGetFilters(): void
    {
        $extension = new StringExtension();
        $filters = $extension->getFilters();

        $this->assertFilterExists('slug', null, $filters);
    }

    /**
     * @covers \Volunteersystem\Renderer\Twig\Extensions\StringExtension::getFilters
     */
    public function testGetFiltersSlug(): void
    {
        $extension = new StringExtension();
        $filters = $extension->getFilters();

        foreach ($filters as $filter) {
            if ($filter->getName() != 'slug') {
                continue;
            }

            $this->assertEquals('foo-at-bar', $filter->getCallable()(' Foo @Bar!'));
            return;
        }

        $this->fail(sprintf('Filter %s not found', 'slug'));
    }
}
