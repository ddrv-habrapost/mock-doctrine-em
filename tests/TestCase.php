<?php

namespace Tests;

use App\Entity\Code;
use App\Entity\User;
use App\Repository\CodeRepository;
use App\Repository\UserRepository;
use Closure;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase as BaseTestCase;
use ReflectionClass;

abstract class TestCase extends BaseTestCase
{

    /**
     * @var MockObject[]
     */
    private $_mock = [];

    private $_data = [
        User::class => [],
        Code::class => [],
    ];

    private $_persist = [
        User::class => [],
        Code::class => [],
    ];

    /**
     * @var Closure[][]
     */
    private $_fn = [];

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->initFn();
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        $emulate = (int)getenv('EMULATE_BD');
        return $emulate ? $this->getMockEntityManager() : $this->getRealEntityManager();

    }

    protected function getRealEntityManager(): EntityManagerInterface
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
            'driver'   => getenv('DB_DRIVER'),
            'path'     => getenv('DB_PATH'),
            'user'     => getenv('DB_USER'),
            'password' => getenv('DB_PASSWORD'),
            'dbname'   => getenv('DB_NAME'),
        );
        $em = EntityManager::create($connection, $config);
        /*
         * Для каждого теста будем использовать пустую БД.
         * Для этого можно удалить схему и создать её заново
         */
        $schema = new SchemaTool($em);
        $schema->dropSchema($em->getMetadataFactory()->getAllMetadata());
        $schema->createSchema($em->getMetadataFactory()->getAllMetadata());
        return $em;
    }

    protected function getMockEntityManager(): EntityManagerInterface
    {
        return $this->mock(EntityManagerInterface::class);
    }

    protected function mock($class)
    {
        if (!array_key_exists($class, $this->_mock)) {
            /*
             * Создаем мок для класса
             */
            $mock = $this->getMockBuilder($class)
                ->disableOriginalConstructor()
                ->getMock()
            ;

            /*
             * задаем логику методам мока
             */
            foreach ($this->_fn[$class] as $method => $fn) {
                $mock
                    /* При каждом вызове  */
                    ->expects($this->any())
                    /* метода $method */
                    ->method($method)
                    /* с (не важно какими) переменными */
                    ->with()
                    /* возвращаем результат выполнения функции */
                    ->will($this->returnCallback($fn))
                ;
            }
            $this->_mock[$class] = $mock;
        }
        return $this->_mock[$class];
    }

    /*
     * Инициализируем логику наших моков.
     * Массив методов имеет формат $fn_[ИмяКлассаИлиИнтерфейса][ИмяМетода]
     */
    private function initFn()
    {
        /*
         * EntityManagerInterface::persist($object) - добавляет сущность во временное хранилище
         */
        $this->_fn[EntityManagerInterface::class]['persist'] = function ($object)
        {
            $entity = get_class($object);
            switch ($entity) {
                case User::class:
                    /** @var User $object */
                    if (!$object->getId()) {
                        $id = count($this->_persist[$entity]) + 1;
                        $reflection = new ReflectionClass($object);
                        $property = $reflection->getProperty('id');
                        $property->setAccessible(true);
                        $property->setValue($object, $id);
                    }
                    $id = $object->getId();
                    break;
                case Code::class:
                    /** @var Code $object */
                    if (!$object->getId()) {
                        $id = count($this->_persist[$entity]) + 1;
                        $reflection = new ReflectionClass($object);
                        $property = $reflection->getProperty('id');
                        $property->setAccessible(true);
                        $property->setValue($object, $id);
                    }
                    $id = $object->getId();
                    break;
                default:
                    $id = spl_object_hash($object);
            }
            $this->_persist[$entity][$id] = $object;
        };

        /*
         * EntityManagerInterface::flush() - скидывает временное хранилище в БД
         */
        $this->_fn[EntityManagerInterface::class]['flush'] = function ()
        {
            $this->_data = array_replace_recursive($this->_data, $this->_persist);
        };

        /*
         * EntityManagerInterface::getRepository($className) - возвращает репозиторий сущности
         */
        $this->_fn[EntityManagerInterface::class]['getRepository'] = function ($className)
        {
            switch ($className) {
                case User::class:
                    return $this->mock(UserRepository::class);
                    break;
                case Code::class:
                    return $this->mock(CodeRepository::class);
                    break;
            }
            return null;
        };

        /*
         * UserRepository::findOneByLogin($login) - ищет одну сущность пользователя по логину
         */
        $this->_fn[UserRepository::class]['findOneByLogin'] = function ($login) {
            foreach ($this->_data[User::class] as $user) {
                /** @var User $user
                 */
                if ($user->getLogin() == $login) return $user;
            }
            return null;
        };

        /*
         * CodeRepository::findOneByCodeAndEmail - ищет одну сущность кода подтверждения
         * по секретному коду и адресу электронной почты
         */
        $this->_fn[CodeRepository::class]['findOneByCodeAndEmail'] = function ($code, $email) {
            $result = [];
            foreach ($this->_data[Code::class] as $c) {
                /** @var Code $c */
                if ($c->getEmail() == $email && $c->getCode() == $code) {
                    $result[$c->getId()] = $c;
                }
            }
            if (!$result) return null;
            return array_shift($result);
        };

        /*
         * CodeRepository::findLastByEmail($email) - одну (последнюю) сущность кода подтверждения
         * по адресу электронной почты
         */
        $this->_fn[CodeRepository::class]['findLastByEmail'] = function ($email) {
            $result = [];
            foreach ($this->_data[Code::class] as $c) {
                /** @var Code $c */
                if ($c->getEmail() == $email) {
                    $result[$c->getId()] = $c;
                }
            }
            if (!$result) return null;
            return array_shift($result);
        };
    }
}