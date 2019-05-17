<?php

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$paths = [
    __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Entity',
];
$cache = new ArrayCache();
$driver = new AnnotationDriver(new AnnotationReader(), $paths);

$config = Setup::createAnnotationMetadataConfiguration($paths, false);
$config->setMetadataCacheImpl($cache);
$config->setQueryCacheImpl($cache);
$config->setMetadataDriverImpl($driver);
$connection = array(
    'driver' => 'pdo_sqlite',
    'path' => __DIR__ . '/var/db.sqlite',
);
$entityManager = EntityManager::create($connection, $config);