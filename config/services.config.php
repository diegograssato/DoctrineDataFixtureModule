<?php

use DoctrineDataFixtureModule\Service\FixtureFactory;

return [
    'aliases'   => [
        'doctrine.configuration.fixtures' => 'Doctrine\ORM\Configuration\Fixure',
    ],
    'factories' => [
        'Doctrine\ORM\Configuration\Fixture' => FixtureFactory::class,
    ],
];
