<?php

use App\Repository\MyRepository;
use App\Domain\Service;

class Foo
{
    private MyRepository $repository;

    /** @var MyRepository */
    private $otherRepository;

    /** @var ServiceB  */
    private $serviceB;

    public function __construct(MyRepository $repository, Service $serviceB) {}
}
