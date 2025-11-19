<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Models;

use Volunteersystem\Test\Unit\HasDatabase;
use Volunteersystem\Test\Unit\TestCase;

abstract class ModelTest extends TestCase
{
    use HasDatabase;

    /**
     * Prepare test
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->initDatabase();
    }
}
