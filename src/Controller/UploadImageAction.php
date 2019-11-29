<?php

namespace App\Controller;

use ApiPlatform\Core\Validator\Exception\ValidationException;
use App\Entity\Image;
use App\Form\ImageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UploadImageAction
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(
        FormFactoryInterface $formFactory,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    )
    {
        $this->formFactory = $formFactory;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    public function __invoke(Request $request)
    {
        //Create a new Image instance
        $image = new Image();
        //Validate the form
        $form = $this->formFactory->create(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isValid() && $form->isSubmitted()) {
            //Persist the new Image Entity
            $this->entityManager->persist($image);
            $this->entityManager->flush();

            $image->setFile(null);

            return $image;
        }
        //Uploading don for us in background by VichUploader

        //Throw an validation exception
        throw new ValidationException(
            $this->validator->validate($image)
        );
    }
}