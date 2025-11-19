<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Http\Validation;

use Volunteersystem\Application;
use Volunteersystem\Http\Validation\ValidationServiceProvider;
use Volunteersystem\Http\Validation\Validator;
use Volunteersystem\Test\Unit\Http\Validation\Stub\ValidatesRequestImplementation;
use Volunteersystem\Test\Unit\ServiceProviderTest;
use stdClass;

class ValidationServiceProviderTest extends ServiceProviderTest
{
    /**
     * @covers \Volunteersystem\Http\Validation\ValidationServiceProvider::register
     */
    public function testRegister(): void
    {
        $app = new Application();

        $serviceProvider = new ValidationServiceProvider($app);
        $serviceProvider->register();

        $this->assertTrue($app->has(Validator::class));
        $this->assertTrue($app->has('validator'));

        /** @var ValidatesRequestImplementation $validatesRequest */
        $validatesRequest = $app->make(ValidatesRequestImplementation::class);
        $this->assertTrue($validatesRequest->hasValidator());

        // Test afterResolving early return
        $app->make(stdClass::class);
    }
}
