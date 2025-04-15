<?php

namespace App\Controller\Backoffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;

class BaseBackofficeController extends AbstractController
{

    protected function showErrorsAsFlash(FormErrorIterator $errors): void
    {
        /** @var FormError $error */
        foreach ($errors as $error) {
            $fieldName = $error->getOrigin()?->getName();

            if ($fieldName) {
                $errorMessage = $error->getMessage();
                $this->addFlash('error', $fieldName . ': ' . $errorMessage);
            }
        }
    }
}