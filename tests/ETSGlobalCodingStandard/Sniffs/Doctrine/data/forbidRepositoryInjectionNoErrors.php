<?php

use Allowed\ServiceA;
use Allowed\FooRepository;

class Foo
{
    private ServiceA $serviceA;

    private FooRepository $repository;

    public function __construct(ServiceA $serviceA, FooRepository $repository) {
        $this->serviceA = $serviceA;
        $this->repository = $repository;
    }

    public function setRepository(FooRepository $repository)
    {
        $this->repository = $repository;
    }
}
