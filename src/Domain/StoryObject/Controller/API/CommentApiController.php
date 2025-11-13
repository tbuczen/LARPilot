<?php

namespace App\Domain\StoryObject\Controller\API;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use App\Domain\StoryObject\Entity\Comment;
use App\Domain\StoryObject\Entity\StoryObject;
use App\Domain\StoryObject\Repository\CommentRepository;
use App\Domain\StoryObject\Service\CommentMentionParser;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Spiriit\Bundle\FormFilterBundle\Filter\FilterBuilderUpdaterInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/larp/{larp}/story/{storyObject}', name: 'api_larp_story_comment_')]
#[IsGranted('ROLE_USER')]
class CommentApiController extends BaseController
{
    public function __construct(
        TranslatorInterface $translator,
        FilterBuilderUpdaterInterface $filterBuilderUpdater,
        PaginatorInterface $paginator,
        EntityManagerInterface $entityManager,
        private readonly CommentMentionParser $mentionParser,
    ) {
        parent::__construct($translator, $filterBuilderUpdater, $paginator, $entityManager);
    }

    /**
     * API endpoint to fetch comments as JSON for real-time updates
     */
    #[Route('/comments', name: 'fetch', methods: ['GET'])]
    public function fetch(
        Request $request,
        Larp $larp,
        StoryObject $storyObject,
        CommentRepository $commentRepository,
    ): JsonResponse {
        $sinceTimestamp = $request->query->get('since');
        $lastCommentId = $request->query->get('lastCommentId');

        // Get all comments for this story object ordered by creation date
        $qb = $commentRepository->createQueryBuilder('c')
            ->where('c.storyObject = :storyObject')
            ->setParameter('storyObject', $storyObject)
            ->orderBy('c.createdAt', 'ASC');

        // Filter by timestamp if provided
        if ($sinceTimestamp) {
            $since = new \DateTime((string) $sinceTimestamp);
            $qb->andWhere('c.createdAt > :since')
               ->setParameter('since', $since);
        }

        $allComments = $qb->getQuery()->getResult();

        // Filter out the last known comment if provided
        $newComments = [];
        $foundLastComment = false;
        foreach ($allComments as $comment) {
            if ($lastCommentId && !$foundLastComment) {
                if ((string) $comment->getId() === $lastCommentId) {
                    $foundLastComment = true;
                }
                continue;
            }
            $newComments[] = $comment;
        }

        // If no lastCommentId was provided, return all comments
        if (!$lastCommentId) {
            $newComments = $allComments;
        }

        // Build response with comment data
        $data = [
            'comments' => [],
            'count' => $commentRepository->countByStoryObject($storyObject),
            'unresolvedCount' => $commentRepository->countUnresolvedByStoryObject($storyObject),
            'lastCommentId' => $allComments ? (string) end($allComments)->getId() : null,
            'timestamp' => (new \DateTime())->format(\DateTime::ISO8601),
        ];

        foreach ($newComments as $comment) {
            $data['comments'][] = $this->serializeComment($comment, $larp);
        }

        return new JsonResponse($data);
    }

    /**
     * API endpoint to post a new comment or reply
     */
    #[Route('/comments', name: 'post', methods: ['POST'])]
    public function post(
        Request $request,
        Larp $larp,
        StoryObject $storyObject,
        CommentRepository $commentRepository,
    ): JsonResponse {
        $content = $request->request->get('content');
        $parentId = $request->request->get('parentId');

        if (empty($content) || !is_string($content)) {
            return new JsonResponse(['error' => 'Content is required'], 400);
        }

        $user = $this->getUser();
        if (!$user instanceof \App\Domain\Account\Entity\User) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $comment = new Comment();
        $comment->setStoryObject($storyObject);
        $comment->setAuthor($user);
        $comment->setContent($content);

        if ($parentId) {
            $parent = $commentRepository->find($parentId);
            if ($parent) {
                $comment->setParent($parent);
            }
        }

        $commentRepository->save($comment, true);

        return new JsonResponse([
            'success' => true,
            'comment' => $this->serializeComment($comment, $larp),
        ]);
    }

    /**
     * API endpoint to toggle comment resolved status
     */
    #[Route('/comments/{comment}/resolve', name: 'resolve', methods: ['POST'])]
    public function resolve(
        Comment $comment,
        CommentRepository $commentRepository,
    ): JsonResponse {
        $comment->setIsResolved(!$comment->isResolved());
        $commentRepository->save($comment, true);

        $message = $comment->isResolved()
            ? $this->translator->trans('comment.marked_as_resolved')
            : $this->translator->trans('comment.marked_as_unresolved');

        return new JsonResponse([
            'success' => true,
            'isResolved' => $comment->isResolved(),
            'message' => $message,
        ]);
    }

    /**
     * Serialize comment to array for JSON response
     *
     * @return array<string, mixed>
     */
    private function serializeComment(Comment $comment, Larp $larp): array
    {
        // Parse mentions in content and convert to hyperlinks
        $parsedContent = $this->mentionParser->parseMentions($comment->getContent(), $larp);

        $authorUsername = $comment->getAuthor()->getUsername() ?? 'Unknown';
        $createdAt = $comment->getCreatedAt();
        $updatedAt = $comment->getUpdatedAt();

        return [
            'id' => (string) $comment->getId(),
            'content' => $parsedContent,
            'authorName' => $authorUsername,
            'authorInitial' => strtoupper(substr($authorUsername, 0, 1)),
            'createdAt' => $createdAt ? $createdAt->format('Y-m-d H:i') : '',
            'updatedAt' => $updatedAt ? $updatedAt->format('Y-m-d H:i') : '',
            'isEdited' => $updatedAt && $createdAt && $updatedAt > $createdAt,
            'isResolved' => $comment->isResolved(),
            'parentId' => $comment->getParent() ? (string) $comment->getParent()->getId() : null,
            'isTopLevel' => $comment->isTopLevel(),
        ];
    }
}
