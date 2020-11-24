<?php

class MyPublisher
{
    abstract public function somethingBadHappened();

    public function somethingHappened() {}

    private function privateMethod() {}
}

function invalidName() {
    return function () {};
}
