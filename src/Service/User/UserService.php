<?php

namespace App\Service\User;

use App\Entity\Code;
use App\Entity\User;
use App\Repository\CodeRepository;
use App\Repository\UserRepository;
use App\Service\Generator\CodeGenerator;
use App\Service\Sender\SenderService;
use App\Service\User\Exception\IncorrectApproveCodeException;
use App\Service\User\Exception\LoginAlreadyExistsException;
use App\Service\User\Exception\ReferrerUserNotFoundException;
use App\Service\User\Exception\UserNotFoundException;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{

    /** @var EntityManagerInterface */
    private $em;

    /** @var UserRepository */
    private $users;

    /** @var CodeRepository */
    private $codes;

    /** @var SenderService */
    private $sender;

    /** @var CodeGenerator */
    private $generator;

    public function __construct(EntityManagerInterface $em, SenderService $sender, CodeGenerator $generator)
    {
        $this->em = $em;
        $this->users = $em->getRepository(User::class);
        $this->codes = $em->getRepository(Code::class);
        $this->sender = $sender;
        $this->generator = $generator;
    }

    /**
     * @param string $login
     * @param string $email
     * @param string|null $referrerLogin
     * @return User
     * @throws LoginAlreadyExistsException
     * @throws ReferrerUserNotFoundException
     */
    public function create(string $login, string $email, ?string $referrerLogin = null): User
    {
        $exists = $this->users->findOneByLogin($login);
        if ($exists) throw new LoginAlreadyExistsException();
        $referrer = null;
        if ($referrerLogin) {
            $referrer = $this->users->findOneByLogin($referrerLogin);
            if (!$referrer) throw new ReferrerUserNotFoundException();
        }
        $user = (new User())->setLogin($login)->setEmail($email)->setReferrer($referrer);
        $code = (new Code())->setEmail($email)->setCode($this->generator->generate());
        $this->sender->sendCode($code);
        $this->em->persist($user);
        $this->em->persist($code);
        $this->em->flush();
        return $user;
    }
}