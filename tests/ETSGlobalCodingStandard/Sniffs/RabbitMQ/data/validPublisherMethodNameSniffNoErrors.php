<?php

class MyPublisher
{
    public function __construct() {}

    abstract public function somethingBadHappened();

    public function somethingHappened() {}

    private function privateMethod() {}
}

function invalidName() {
    return function () {};
}
