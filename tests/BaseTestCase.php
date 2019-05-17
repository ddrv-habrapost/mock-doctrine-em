<?php

namespace Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use PHPUnit\Framework\TestCase;

class BaseTestCase extends TestCase
{

    /** @var EntityManagerInterface */
    protected $em;

    public function setUp(): void
    {
        parent::setUp();
        $this->em = $this->getEntityManager();
    }

    protected function getEntityManager(): EntityManagerInterface
    {

        $paths = [
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Entity',
        ];
        $cache = new ArrayCache();
        $driver = new AnnotationDriver(new AnnotationReader(), $paths);

        $config = Setup::createAnnotationMetadataConfiguration($paths, false);
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);
        $config->setMetadataDriverImpl($driver);
        $connection = array(
            'driver' => 'pdo_sqlite',
            'path' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'testing.sqlite',
        );
        $em = EntityManager::create($connection, $config);
        $schema = new SchemaTool($em);
        $schema->updateSchema($em->getMetadataFactory()->getAllMetadata(), true);
        return $em;
    }
}