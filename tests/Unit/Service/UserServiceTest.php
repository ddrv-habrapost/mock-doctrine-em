<?php

namespace Tests\Unit\Service;

use App\Entity\Code;
use App\Entity\User;
use App\Repository\CodeRepository;
use App\Repository\UserRepository;
use App\Service\Generator\CodeGenerator;
use App\Service\Sender\SenderService;
use App\Service\User\Exception\LoginAlreadyExistsException;
use App\Service\User\Exception\ReferrerUserNotFoundException;
use App\Service\User\UserService;
use Tests\TestCase;
use Doctrine\ORM\EntityManagerInterface;

class UserServiceTest  extends TestCase
{

    /**
     * @var UserService
     */
    protected $service;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function setUp(): void
    {
        parent::setUp();
        $this->em = $this->getEntityManager();
        $this->service = new UserService($this->em, new SenderService(), new CodeGenerator());
    }

    /**
     * @throws LoginAlreadyExistsException
     * @throws ReferrerUserNotFoundException
     */
    public function testCreateSuccessWithoutReferrer()
    {
        $login = 'case1';
        $email = $login . '@localhost';
        $user = $this->service->create($login, $email);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($login, $user->getLogin());
        $this->assertSame($email, $user->getEmail());
        $this->assertFalse($user->isApproved());
        // Убедимся, что пользователь добавлен в базу
        /** @var UserRepository $userRepo */
        $userRepo = $this->em->getRepository(User::class);
        $u = $userRepo->findOneByLogin($login);
        $this->assertInstanceOf(User::class, $u);
        $this->assertSame($login, $u->getLogin());
        $this->assertSame($email, $u->getEmail());
        $this->assertFalse($u->isApproved());
        // Убедимся, что код подтверждения добавлен в базу
        /** @var CodeRepository $codeRepo */
        $codeRepo = $this->em->getRepository(Code::class);
        $c = $codeRepo->findOneBy(['email' => $email]);
        $this->assertInstanceOf(Code::class, $c);
    }

    /**
     * @throws LoginAlreadyExistsException
     * @throws ReferrerUserNotFoundException
     */
    public function testCreateSuccessWithReferrer()
    {
        // Добавим в БД реферера
        $referrerLogin  = 'referer';
        $referrer = new User();
        $referrer
            ->setLogin($referrerLogin)
            ->setEmail($referrerLogin.'@localhost')
        ;
        $this->em->persist($referrer);
        $this->em->flush();

        $login = 'case2';
        $email = $login . '@localhost';
        $user = $this->service->create($login, $email, $referrerLogin);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($login, $user->getLogin());
        $this->assertSame($email, $user->getEmail());
        $this->assertFalse($user->isApproved());
        $this->assertSame($referrer, $user->getReferrer());
        // Убедимся, что пользователь добавлен в базу
        /** @var UserRepository $userRepo */
        $userRepo = $this->em->getRepository(User::class);
        $u = $userRepo->findOneByLogin($login);
        $this->assertInstanceOf(User::class, $u);
        $this->assertSame($login, $u->getLogin());
        $this->assertSame($email, $u->getEmail());
        $this->assertFalse($u->isApproved());
        // Убедимся, что код подтверждения добавлен в базу
        /** @var CodeRepository $codeRepo */
        $codeRepo = $this->em->getRepository(Code::class);
        $c = $codeRepo->findOneBy(['email' => $email]);
        $this->assertInstanceOf(Code::class, $c);
    }

    /**
     * @throws LoginAlreadyExistsException
     * @throws ReferrerUserNotFoundException
     */
    public function testCreateFailWithNonexistentReferrer()
    {
        $this->expectException(ReferrerUserNotFoundException::class);

        $referrerLogin  = 'nonexistent-referer';
        $login = 'case3';
        $email = $login . '@localhost';
        $this->service->create($login, $email, $referrerLogin);
    }

    /**
     * @throws LoginAlreadyExistsException
     * @throws ReferrerUserNotFoundException
     */
    public function testCreateFailWithExistentLogin()
    {
        $this->expectException(LoginAlreadyExistsException::class);

        $referrerLogin  = 'case4';
        $referrer = new User();
        $referrer
            ->setLogin($referrerLogin)
            ->setEmail($referrerLogin.'@localhost')
        ;
        $this->em->persist($referrer);
        $this->em->flush();

        $login = 'case4';
        $email = $login . '@localhost';
        $this->service->create($login, $email, null);
    }
}