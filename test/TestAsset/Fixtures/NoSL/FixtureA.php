<?php

namespace DoctrineDataFixtureTest\TestAsset\Fixtures\NoSL;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;

class FixtureA implements FixtureInterface
{

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
    }
}
