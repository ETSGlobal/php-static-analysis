<?php

declare(strict_types=1);

namespace Api;

use App\Tests\Support\ApiTester;
use Codeception\Util\HttpCode;

class ListCest
{
    public function testNoResult(ApiTester $I): void
    {
        $I->sendGet('tests/');

        $I->seeResponseCodeIs(HttpCode::OK);
    }
}
