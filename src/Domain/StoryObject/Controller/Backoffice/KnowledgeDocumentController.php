<?php

namespace App\Domain\StoryObject\Controller\Backoffice;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\StoryObject\Entity\KnowledgeDocument;
use App\Domain\StoryObject\Form\Filter\KnowledgeDocumentFilterType;
use App\Domain\StoryObject\Form\Type\KnowledgeDocumentType;
use App\Domain\StoryObject\Repository\KnowledgeDocumentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/knowledge/', name: 'backoffice_larp_knowledge_')]
class KnowledgeDocumentController extends BaseController
{
    #[Route('list', name: 'list', methods: ['GET', 'POST'])]
    public function list(Request $request, Larp $larp, KnowledgeDocumentRepository $repository): Response
    {
        $filterForm = $this->createForm(KnowledgeDocumentFilterType::class, null, ['larp' => $larp]);
        $filterForm->handleRequest($request);

        $qb = $repository->createQueryBuilder('kd');
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);

        $sortBy = $request->query->get('sortBy', 'title');
        $sortOrder = $request->query->get('sortOrder', 'ASC');

        switch ($sortBy) {
            case 'category':
                $qb->orderBy('kd.category', $sortOrder);
                break;
            case 'updated':
                $qb->orderBy('kd.updatedAt', $sortOrder);
                break;
            case 'title':
            default:
                $qb->orderBy('kd.title', $sortOrder);
                break;
        }

        $qb->andWhere('kd.larp = :larp')->setParameter('larp', $larp);

        $pagination = $this->getPagination($qb, $request);

        return $this->render('domain/story_object/knowledge_document/list.html.twig', [
            'filterForm' => $filterForm->createView(),
            'documents' => $pagination,
            'larp' => $larp,
        ]);
    }

    #[Route('{document}', name: 'modify', defaults: ['document' => null], methods: ['GET', 'POST'])]
    public function modify(
        Request $request,
        Larp $larp,
        KnowledgeDocumentRepository $repository,
        ?KnowledgeDocument $document = null
    ): Response {
        if (!$document instanceof KnowledgeDocument) {
            $document = new KnowledgeDocument();
            $document->setLarp($larp);
            $currentUser = $this->getUser();
            if ($currentUser instanceof User) {
                $document->setCreatedBy($currentUser);
            }
        }

        $form = $this->createForm(KnowledgeDocumentType::class, $document, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $repository->save($document);
            $this->addFlash('success', $this->translator->trans('success_save'));

            return $this->redirectToRoute('backoffice_larp_knowledge_list', ['larp' => $larp->getId()]);
        }

        return $this->render('domain/story_object/knowledge_document/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
            'document' => $document,
        ]);
    }

    #[Route('{document}/delete', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(Larp $larp, KnowledgeDocumentRepository $repository, KnowledgeDocument $document): Response
    {
        $repository->remove($document);
        $this->addFlash('success', $this->translator->trans('success_delete'));

        return $this->redirectToRoute('backoffice_larp_knowledge_list', ['larp' => $larp->getId()]);
    }
}
