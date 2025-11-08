<?php

declare(strict_types=1);

namespace App\Domain\StoryObject\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\StoryObject\Entity\Comment;
use App\Domain\StoryObject\Entity\StoryObject;
use App\Domain\StoryObject\Form\Type\CommentType;
use App\Domain\StoryObject\Repository\CommentRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/larp/{larp}/story/{storyObject}', name: 'backoffice_larp_story_comment_')]
#[IsGranted('ROLE_USER')]
class CommentController extends BaseController
{
    #[Route('/comments', name: 'list', methods: ['GET'])]
    public function list(
        Larp $larp,
        StoryObject $storyObject,
        CommentRepository $commentRepository,
    ): Response {
        $comments = $commentRepository->findTopLevelByStoryObject($storyObject);
        $commentCount = $commentRepository->countByStoryObject($storyObject);
        $unresolvedCount = $commentRepository->countUnresolvedByStoryObject($storyObject);

        // Load replies for each top-level comment
        $commentThreads = [];
        foreach ($comments as $comment) {
            $commentThreads[] = [
                'comment' => $comment,
                'replies' => $commentRepository->findReplies($comment),
            ];
        }

        return $this->render('backoffice/larp/story/comment/list.html.twig', [
            'larp' => $larp,
            'storyObject' => $storyObject,
            'commentThreads' => $commentThreads,
            'commentCount' => $commentCount,
            'unresolvedCount' => $unresolvedCount,
        ]);
    }

    #[Route('/comment/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        Larp $larp,
        StoryObject $storyObject,
        CommentRepository $commentRepository,
    ): Response {
        $comment = new Comment();
        $comment->setStoryObject($storyObject);
        $comment->setAuthor($this->getUser());

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentRepository->save($comment, true);

            $this->addFlash('success', $this->translator->trans('comment.created_successfully'));

            return $this->redirectToRoute('backoffice_larp_story_comment_list', [
                'larp' => $larp->getId(),
                'storyObject' => $storyObject->getId(),
            ]);
        }

        return $this->render('backoffice/larp/story/comment/form.html.twig', [
            'larp' => $larp,
            'storyObject' => $storyObject,
            'form' => $form->createView(),
            'isEdit' => false,
        ]);
    }

    #[Route('/comment/{comment}/reply', name: 'reply', methods: ['GET', 'POST'])]
    public function reply(
        Request $request,
        Larp $larp,
        StoryObject $storyObject,
        Comment $parentComment,
        CommentRepository $commentRepository,
    ): Response {
        $comment = new Comment();
        $comment->setStoryObject($storyObject);
        $comment->setAuthor($this->getUser());
        $comment->setParent($parentComment);

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentRepository->save($comment, true);

            $this->addFlash('success', $this->translator->trans('comment.reply_created_successfully'));

            return $this->redirectToRoute('backoffice_larp_story_comment_list', [
                'larp' => $larp->getId(),
                'storyObject' => $storyObject->getId(),
            ]);
        }

        return $this->render('backoffice/larp/story/comment/form.html.twig', [
            'larp' => $larp,
            'storyObject' => $storyObject,
            'parentComment' => $parentComment,
            'form' => $form->createView(),
            'isEdit' => false,
            'isReply' => true,
        ]);
    }

    #[Route('/comment/{comment}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Larp $larp,
        StoryObject $storyObject,
        Comment $comment,
        CommentRepository $commentRepository,
    ): Response {
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentRepository->save($comment, true);

            $this->addFlash('success', $this->translator->trans('comment.updated_successfully'));

            return $this->redirectToRoute('backoffice_larp_story_comment_list', [
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

        return $this->redirectToRoute('backoffice_larp_story_comment_list', [
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

        return $this->redirectToRoute('backoffice_larp_story_comment_list', [
            'larp' => $larp->getId(),
            'storyObject' => $storyObject->getId(),
        ]);
    }

    /**
     * API endpoint to fetch comments as JSON for real-time updates
     */
    #[Route('/comments/api', name: 'api', methods: ['GET'])]
    public function api(
        Request $request,
        Larp $larp,
        StoryObject $storyObject,
        CommentRepository $commentRepository,
    ): JsonResponse {
        $since = $request->query->get('since');
        $lastCommentId = $request->query->getInt('lastCommentId', 0);

        // Get all comments for this story object
        $allComments = $commentRepository->findByStoryObject($storyObject);

        // Filter comments created after the last known comment
        $newComments = [];
        foreach ($allComments as $comment) {
            if ($comment->getId() > $lastCommentId) {
                $newComments[] = $comment;
            }
        }

        // Build response with comment data
        $data = [
            'comments' => [],
            'count' => $commentRepository->countByStoryObject($storyObject),
            'unresolvedCount' => $commentRepository->countUnresolvedByStoryObject($storyObject),
            'lastCommentId' => $allComments ? max(array_map(fn($c) => $c->getId(), $allComments)) : 0,
        ];

        foreach ($newComments as $comment) {
            $data['comments'][] = $this->serializeComment($comment);
        }

        return new JsonResponse($data);
    }

    /**
     * API endpoint to post a quick message (for real-time chat)
     */
    #[Route('/comments/post', name: 'post', methods: ['POST'])]
    public function post(
        Request $request,
        Larp $larp,
        StoryObject $storyObject,
        CommentRepository $commentRepository,
    ): JsonResponse {
        $content = $request->request->get('content');
        $parentId = $request->request->getInt('parentId', 0);

        if (empty($content)) {
            return new JsonResponse(['error' => 'Content is required'], 400);
        }

        $comment = new Comment();
        $comment->setStoryObject($storyObject);
        $comment->setAuthor($this->getUser());
        $comment->setContent($content);

        if ($parentId > 0) {
            $parent = $commentRepository->find($parentId);
            if ($parent) {
                $comment->setParent($parent);
            }
        }

        $commentRepository->save($comment, true);

        return new JsonResponse([
            'success' => true,
            'comment' => $this->serializeComment($comment),
        ]);
    }

    /**
     * Serialize comment to array for JSON response
     */
    private function serializeComment(Comment $comment): array
    {
        return [
            'id' => $comment->getId(),
            'content' => $comment->getContent(),
            'authorName' => $comment->getAuthor()->getName(),
            'authorInitial' => strtoupper(substr($comment->getAuthor()->getName(), 0, 1)),
            'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i'),
            'updatedAt' => $comment->getUpdatedAt()->format('Y-m-d H:i'),
            'isEdited' => $comment->getUpdatedAt() > $comment->getCreatedAt(),
            'isResolved' => $comment->isResolved(),
            'parentId' => $comment->getParent()?->getId(),
            'isTopLevel' => $comment->isTopLevel(),
        ];
    }
}
