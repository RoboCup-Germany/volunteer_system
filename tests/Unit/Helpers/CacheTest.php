<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Helpers;

use Volunteersystem\Helpers\Cache;
use Volunteersystem\Test\Unit\TestCase;
use stdClass;

class CacheTest extends TestCase
{
    protected string $cacheDir = __DIR__ . '/Stub/cache';

    /**
     * @covers \Volunteersystem\Helpers\Cache::__construct
     * @covers \Volunteersystem\Helpers\Cache::get
     */
    public function testGetDefaultValues(): void
    {
        $cache = new Cache($this->cacheDir);
        $value = new stdClass();

        $this->assertNull($cache->get('this-is-not-there'));
        $this->assertEquals('', $cache->get('give-me-my-string', ''));
        $this->assertEquals(42, $cache->get('meaning-of-life', 42));
        $this->assertEquals(['key' => 'value'], $cache->get('array-data', ['key' => 'value']));
        $this->assertEquals($value, $cache->get('some-object', $value));

        $this->assertCount(0, $this->listCacheFiles());
    }

    /**
     * @covers \Volunteersystem\Helpers\Cache::get
     */
    public function testGetDefaultCallback(): void
    {
        $cache = new Cache($this->cacheDir);

        $this->assertEquals('some test', $cache->get('this-has-a-callback', fn() => 'some test'));
        $this->assertEquals('some test', $cache->get('this-has-a-callback', 'unused-default-value'));

        $cacheFile = $this->cacheDir . '/this-has-a-callback.cache';
        $this->assertFileExists($cacheFile);
        $this->assertEquals(serialize('some test'), file_get_contents($cacheFile));

        $this->assertCount(1, $this->listCacheFiles());
    }

    /**
     * @covers \Volunteersystem\Helpers\Cache::get
     * @covers \Volunteersystem\Helpers\Cache::cacheFilePath
     */
    public function testGetReadFile(): void
    {
        $cache = new Cache($this->cacheDir);

        $cacheFile = $this->cacheDir . '/cached.cache';
        file_put_contents($cacheFile, serialize('cached-value'));

        $this->assertEquals('cached-value', $cache->get('cached'));
    }

    /**
     * @covers \Volunteersystem\Helpers\Cache::forget
     */
    public function testForgetNotExisting(): void
    {
        $cache = new Cache($this->cacheDir);
        $cache->forget('not-cached-value');

        $this->assertCount(0, $this->listCacheFiles());
    }

    /**
     * @covers \Volunteersystem\Helpers\Cache::forget
     * @covers \Volunteersystem\Helpers\Cache::cacheFilePath
     */
    public function testForget(): void
    {
        $cache = new Cache($this->cacheDir);

        $cacheFile = $this->cacheDir . '/rm-cache.cache';
        file_put_contents($cacheFile, serialize('removed-cache-value'));

        $cache->forget('rm-cache');
        $this->assertNull($cache->get('rm-cache'));
        $this->assertFileDoesNotExist($cacheFile);
    }

    /**
     * @covers \Volunteersystem\Helpers\Cache::get
     * @covers \Volunteersystem\Helpers\Cache::forget
     * @covers \Volunteersystem\Helpers\Cache::cacheFilePath
     */
    public function testGetForgetOld(): void
    {
        $cache = new Cache($this->cacheDir);

        $cacheFile = $this->cacheDir . '/cached.cache';
        file_put_contents($cacheFile, serialize('cached-value'));
        touch($cacheFile, time() - 60 * 60 * 2);

        $this->assertEquals('default', $cache->get('cached', 'default'));
        $this->assertFileDoesNotExist($cacheFile);
    }

    public function tearDown(): void
    {
        foreach ($this->listCacheFiles() as $file) {
            unlink($this->cacheDir . '/' . $file);
        }
    }

    protected function listCacheFiles(): array
    {
        return array_diff(scandir($this->cacheDir), ['..', '.', '.gitignore']);
    }
}
