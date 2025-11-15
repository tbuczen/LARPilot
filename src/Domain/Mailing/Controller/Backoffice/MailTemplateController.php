<?php

namespace App\Domain\Mailing\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Mailing\Entity\MailTemplate;
use App\Domain\Mailing\Form\MailTemplateFilterType;
use App\Domain\Mailing\Form\MailTemplateFormType;
use App\Domain\Mailing\Repository\MailTemplateRepository;
use App\Domain\Mailing\Service\MailTemplateManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/backoffice/larp/{larp}/mailing', name: 'backoffice_larp_mailing_')]
class MailTemplateController extends BaseController
{
    #[Route('/templates', name: 'templates', methods: ['GET', 'POST'])]
    public function templates(
        Request $request,
        Larp $larp,
        MailTemplateRepository $repository,
        MailTemplateManager $manager
    ): Response {
        $manager->ensureTemplatesForLarp($larp);

        $filterForm = $this->createForm(MailTemplateFilterType::class);
        $filterForm->handleRequest($request);

        $qb = $repository->createQueryBuilder('template');
        $qb->andWhere('template.larp = :larp')
            ->setParameter('larp', $larp)
            ->orderBy('template.name', 'ASC');
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);

        return $this->render('backoffice/larp/mailing/list.html.twig', [
            'larp' => $larp,
            'templates' => $qb->getQuery()->getResult(),
            'filterForm' => $filterForm->createView(),
            'definitions' => $manager->getTemplateDefinitions(),
        ]);
    }

    #[Route('/templates/{mailTemplate}', name: 'template_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Larp $larp,
        MailTemplate $mailTemplate,
        MailTemplateManager $manager,
        MailTemplateRepository $repository
    ): Response {
        $this->assertTemplateBelongsToLarp($larp, $mailTemplate);

        $form = $this->createForm(MailTemplateFormType::class, $mailTemplate, [
            'definition' => $manager->getDefinitionForType($mailTemplate),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $repository->save($mailTemplate, true);
            $this->addFlash('success', $this->translator->trans('mail_template.saved'));

            return $this->redirectToRoute('backoffice_larp_mailing_templates', [
                'larp' => $larp->getId(),
            ]);
        }

        return $this->render('backoffice/larp/mailing/edit.html.twig', [
            'larp' => $larp,
            'form' => $form->createView(),
            'template' => $mailTemplate,
            'definition' => $manager->getDefinitionForType($mailTemplate),
        ]);
    }

    private function assertTemplateBelongsToLarp(Larp $larp, MailTemplate $template): void
    {
        if ($template->getLarp()?->getId()->toRfc4122() !== $larp->getId()->toRfc4122()) {
            throw new AccessDeniedException('Template does not belong to this LARP');
        }
    }
}
