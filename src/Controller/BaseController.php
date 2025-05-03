<?php

namespace App\Controller;

use Spiriit\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Contracts\Translation\TranslatorInterface;

class BaseController extends AbstractController
{

    public function __construct(
        protected readonly TranslatorInterface $translator,
        protected readonly FilterBuilderUpdaterInterface $filterBuilderUpdater
    )
    {
    }

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