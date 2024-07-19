<?php

declare(strict_types=1);

namespace Tests\Support\Fixture;

use MongoDB\BSON\UTCDateTime;

trait FixtureTest
{
    public function getBasicTest(): array
    {
        return [
            '_id' => 12,
            'createdAt' => new UTCDateTime(new \DateTime('-1 days')),
        ];
    }
}
