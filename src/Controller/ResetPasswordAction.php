<?php
namespace App\Controller;

use ApiPlatform\Core\Validator\ValidatorInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ResetPasswordAction
{
    /**
     * @var ValidatorInterface
     */
    private $validator;
    /**
     * @var UserPasswordEncoderInterface
     */
    private $encoder;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var JWTTokenManagerInterface
     */
    private $JWTTokenManager;

    public function __construct(
        ValidatorInterface $validator,
        UserPasswordEncoderInterface $encoder,
        EntityManagerInterface $entityManager,
        JWTTokenManagerInterface $JWTTokenManager
    )
    {
        $this->validator = $validator;
        $this->encoder = $encoder;
        $this->entityManager = $entityManager;
        $this->JWTTokenManager = $JWTTokenManager;
    }

    public function __invoke(User $data)
    {
        // $reset = new ResetPasswordAction();
        // $reset();
        $this->validator->validate($data);

        $data->setPassword(
            $this->encoder->encodePassword(
                $data, $data->getNewPassword()
            )
        );

        //After password change, old tokens are still valid
        $data->setPasswordChangeDate(time());

        $this->entityManager->flush();

        $token = $this->JWTTokenManager->create($data);

        return new JsonResponse(['token' => $token]);

        //validator is only called after we return the data from this action!
        //only hear it checks for user current password, but we've just modified it!

        //Entity is persisted automatically, only if validation pass
    }
}