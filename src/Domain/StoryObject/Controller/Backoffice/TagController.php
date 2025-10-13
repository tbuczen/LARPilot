<?php

namespace App\Domain\StoryObject\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\Tag;
use App\Domain\Core\Form\Filter\TagFilterType;
use App\Domain\Core\Form\TagType;
use App\Domain\Core\Repository\TagRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/larp/{larp}/story/tag/', name: 'backoffice_larp_story_tag_')]
class TagController extends BaseController
{
    #[Route('list', name: 'list', methods: ['GET', 'POST'])]
    public function list(Request $request, Larp $larp, TagRepository $repository): Response
    {
        $filterForm = $this->createForm(TagFilterType::class);
        $filterForm->handleRequest($request);
        $qb = $repository->createQueryBuilder('t');
        $this->filterBuilderUpdater->addFilterConditions($filterForm, $qb);
        $sort = $request->query->get('sort', 'title');
        $dir = $request->query->get('dir', 'asc');
        $qb->orderBy('t.' . $sort, $dir);
        $qb->andWhere('t.larp = :larp')->setParameter('larp', $larp);

        return $this->render('backoffice/larp/tag/list.html.twig', [
            'filterForm' => $filterForm->createView(),
            'tags' => $qb->getQuery()->getResult(),
            'larp' => $larp,
        ]);
    }

    #[Route('{tag}', name: 'modify', defaults: ['tag' => null], methods: ['GET', 'POST'])]
    public function modify(Request $request, Larp $larp, TagRepository $tagRepository, ?Tag $tag = null): Response
    {
        if (!$tag instanceof Tag) {
            $tag = new Tag();
            $tag->setLarp($larp);
        }

        $form = $this->createForm(TagType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tagRepository->save($tag);
            $this->addFlash('success', $this->translator->trans('backoffice.common.success_save'));

            return $this->redirectToRoute('backoffice_larp_story_tag_list', ['larp' => $larp->getId()]);
        }

        return $this->render('backoffice/larp/tag/modify.html.twig', [
            'form' => $form->createView(),
            'larp' => $larp,
            'tag' => $tag,
        ]);
    }

    #[Route('{tag}/delete', name: 'delete', methods: ['GET', 'POST'])]
    public function delete(Larp $larp, TagRepository $tagRepository, Tag $tag): Response
    {
        $tagRepository->remove($tag);
        $this->addFlash('success', $this->translator->trans('backoffice.common.success_delete'));

        return $this->redirectToRoute('backoffice_larp_story_tag_list', ['larp' => $larp->getId()]);
    }
}
