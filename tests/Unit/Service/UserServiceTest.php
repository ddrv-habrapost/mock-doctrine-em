<?php

namespace Tests\Unit\Service;

use App\Entity\Code;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Generator\CodeGenerator;
use App\Service\Sender\SenderService;
use App\Service\User\Exception\LoginAlreadyExistsException;
use App\Service\User\Exception\ReferrerUserNotFoundException;
use App\Service\User\UserService;
use Tests\BaseTestCase;

class UserServiceTest  extends BaseTestCase
{

    /** @var UserService */
    protected $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = new UserService($this->em, new SenderService(), new CodeGenerator());
    }

    /**
     * @throws LoginAlreadyExistsException
     * @throws ReferrerUserNotFoundException
     */
    public function testCreateSuccessWithoutReferrer()
    {
        $login = uniqid('phpunit.');
        $email = $login . '@localhost';
        $user = $this->service->create($login, $email);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame($login, $user->getLogin());
        $this->assertSame($email, $user->getEmail());
        $this->assertFalse($user->isApproved());
        // Убедимся, что пользователь добавлен в базу
        /** @var UserRepository $repo */
        $repo = $this->em->getRepository(User::class);
        $u = $repo->findOneByLogin($login);
        $this->assertInstanceOf(User::class, $u);
        $this->assertSame($login, $u->getLogin());
        $this->assertSame($email, $u->getEmail());
        $this->assertFalse($u->isApproved());
        // Удалим за собой созданные данные
        $this->em->remove($u);
        foreach ($this->em->getRepository(Code::class)->findBy(['email' => $email]) as $code) {
            $this->em->remove($code);
        }
        $this->em->flush();
    }

    private function clearDB($emails)
    {
        $users = [];
        $codes = [];
        foreach ($emails as $email) {
            $users += $this->em->getRepository(User::class)->findBy(['email' => $email]);
            $codes += $this->em->getRepository(Code::class)->findBy(['email' => $email]);
        }
        foreach ($users as $user) {
            $this->em->remove($user);
        }
        foreach ($codes as $code) {
            $this->em->remove($code);
        }
        $this->em->flush();
    }
}