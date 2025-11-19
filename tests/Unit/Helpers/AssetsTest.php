<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Helpers;

use Volunteersystem\Helpers\Assets;
use Volunteersystem\Test\Unit\TestCase;

class AssetsTest extends TestCase
{
    /**
     * @covers \Volunteersystem\Helpers\Assets::__construct
     * @covers \Volunteersystem\Helpers\Assets::getAssetPath
     */
    public function testGetAssetPath(): void
    {
        $assets = new Assets('/foo/bar');
        $this->assertEquals('lorem.bar', $assets->getAssetPath('lorem.bar'));

        $assets = new Assets(__DIR__ . '/Stub/files');
        $this->assertEquals('something.xyz', $assets->getAssetPath('something.xyz'));
        $this->assertEquals('lorem-hashed.ipsum', $assets->getAssetPath('foo.bar'));
    }
}
