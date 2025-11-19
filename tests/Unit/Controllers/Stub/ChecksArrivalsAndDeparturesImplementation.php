<?php

declare(strict_types=1);

namespace Volunteersystem\Test\Unit\Controllers\Stub;

use Volunteersystem\Controllers\ChecksArrivalsAndDepartures;

class ChecksArrivalsAndDeparturesImplementation
{
    use ChecksArrivalsAndDepartures;

    public function checkArrival(?string $arrival, ?string $departure): bool
    {
        return $this->isArrivalDateValid($arrival, $departure);
    }

    public function checkDeparture(?string $arrival, ?string $departure): bool
    {
        return $this->isDepartureDateValid($arrival, $departure);
    }
}
