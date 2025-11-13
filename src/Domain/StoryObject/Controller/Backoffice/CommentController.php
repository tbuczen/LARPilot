<?php

declare(strict_types=1);

namespace App\Domain\StoryObject\Controller\Backoffice;

use App\Domain\Application\Repository\LarpApplicationChoiceRepository;
use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Comment;
use App\Domain\StoryObject\Entity\StoryObject;
use App\Domain\StoryObject\Form\Type\CommentType;
use App\Domain\StoryObject\Repository\CommentRepository;
use App\Domain\StoryObject\Service\StoryObjectMentionService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/larp/{larp}/story/{storyObject}', name: 'backoffice_larp_story_comment_')]
#[IsGranted('ROLE_USER')]
class CommentController extends BaseController
{
    #[Route('/comment/{comment}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Larp $larp,
        StoryObject $storyObject,
        Comment $comment,
        CommentRepository $commentRepository,
    ): Response {
        $form = $this->createForm(CommentType::class, $comment, ['larp' => $larp]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentRepository->save($comment, true);

            $this->addFlash('success', $this->translator->trans('comment.updated_successfully'));

            return $this->redirectToRoute('backoffice_larp_story_comment_discussions', [
                'larp' => $larp->getId(),
                'storyObject' => $storyObject->getId(),
            ]);
        }

        return $this->render('backoffice/larp/story/comment/form.html.twig', [
            'larp' => $larp,
            'storyObject' => $storyObject,
            'comment' => $comment,
            'form' => $form->createView(),
            'isEdit' => true,
        ]);
    }

    #[Route('/comment/{comment}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        Larp $larp,
        StoryObject $storyObject,
        Comment $comment,
        CommentRepository $commentRepository,
    ): Response {
        $commentRepository->remove($comment, true);

        $this->addFlash('success', $this->translator->trans('comment.deleted_successfully'));

        return $this->redirectToRoute('backoffice_larp_story_comment_discussions', [
            'larp' => $larp->getId(),
            'storyObject' => $storyObject->getId(),
        ]);
    }

    #[Route('/comment/{comment}/resolve', name: 'resolve', methods: ['POST'])]
    public function resolve(
        Larp $larp,
        StoryObject $storyObject,
        Comment $comment,
        CommentRepository $commentRepository,
    ): Response {
        $comment->setIsResolved(!$comment->isResolved());
        $commentRepository->save($comment, true);

        $message = $comment->isResolved()
            ? $this->translator->trans('comment.marked_as_resolved')
            : $this->translator->trans('comment.marked_as_unresolved');

        $this->addFlash('success', $message);

        return $this->redirectToRoute('backoffice_larp_story_comment_discussions', [
            'larp' => $larp->getId(),
            'storyObject' => $storyObject->getId(),
        ]);
    }

    /**
     * Discussions view with inline commenting (Google Docs-like)
     */
    #[Route('/discussions', name: 'discussions', methods: ['GET'])]
    public function discussions(
        Request $request,
        Larp $larp,
        StoryObject $storyObject,
        CommentRepository $commentRepository,
        StoryObjectMentionService $mentionService,
        LarpApplicationChoiceRepository $choiceRepository
    ): Response {
        $showResolved = $request->query->getBoolean('showResolved', false);

        $comments = $commentRepository->findTopLevelByStoryObjectWithFilter($storyObject, $showResolved);
        $commentCount = $commentRepository->countByStoryObject($storyObject);
        $unresolvedCount = $commentRepository->countUnresolvedByStoryObject($storyObject);
        if ($storyObject instanceof Character) {
            $applicantsCount = $choiceRepository->getApplicationsCountForCharacter($storyObject);
        }

        $commentThreads = $this->buildCommentThreads($comments, $commentRepository);
        $mentions = $mentionService->findMentions($storyObject);

        return $this->render('backoffice/larp/story/comment/discussions.html.twig', [
            'larp' => $larp,
            'storyObject' => $storyObject,
            'commentThreads' => $commentThreads,
            'commentCount' => $commentCount,
            'unresolvedCount' => $unresolvedCount,
            'storyObjectType' => strtolower($storyObject->getTargetType()->value),
            'mentionsCount' => count($mentions),
            'applicantsCount' => $applicantsCount ?? 0,
            'showResolved' => $showResolved,
        ]);
    }

    /**
     * Build comment threads by loading replies for each top-level comment
     *
     * @param Comment[] $comments
     * @return array<int, array{comment: Comment, replies: Comment[]}>
     */
    private function buildCommentThreads(array $comments, CommentRepository $commentRepository): array
    {
        $threads = [];
        foreach ($comments as $comment) {
            $threads[] = [
                'comment' => $comment,
                'replies' => $commentRepository->findReplies($comment),
            ];
        }
        return $threads;
    }
}
