<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Import;

use App\Domain\Account\Entity\User;
use App\Domain\Integrations\Entity\Enum\ReferenceRole;
use App\Domain\Integrations\Entity\ExternalSource;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardItemRecorder;
use App\Domain\Integrations\Service\ExternalReferenceLinker;
use App\Domain\StoryObject\Entity\Relation;
use App\Domain\StoryObject\Entity\StoryObject;
use Doctrine\ORM\EntityManagerInterface;

final readonly class MiroImportApplier
{
    private const TITLE_MAX_LENGTH = 255;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ExternalReferenceLinker $linker,
        private MiroBoardItemRecorder $itemRecorder,
    ) {
    }

    /**
     * @param list<string> $confirmedTokens
     */
    public function apply(ExternalSource $board, MiroImportPlan $plan, array $confirmedTokens, User $user): MiroImportResult
    {
        $confirmedSuggestions = [];
        foreach (array_unique($confirmedTokens) as $token) {
            $suggestion = $plan->suggestionWithToken($token);
            if ($suggestion instanceof MiroImportSuggestion) {
                $confirmedSuggestions[] = $suggestion;
            }
        }

        $result = new MiroImportResult();
        if ($confirmedSuggestions === []) {
            return $result;
        }

        $this->entityManager->wrapInTransaction(function () use ($board, $confirmedSuggestions, $user, $result): void {
            $objectsByTargetKey = [];
            foreach ($confirmedSuggestions as $suggestion) {
                if ($suggestion->action === MiroImportAction::CreateRelation || !$suggestion->target instanceof MiroImportTarget) {
                    continue;
                }
                $storyObject = $this->resolveObject($board, $suggestion->target, $objectsByTargetKey, $user, $result);
                $objectsByTargetKey[$suggestion->target->key()] = $storyObject;
                $this->linker->linkItem($storyObject, $this->itemRecorder->record($board, $suggestion->entry), ReferenceRole::Primary, $user);
                ++$result->linkedItems;
            }

            $createdRelationKeys = [];
            foreach ($confirmedSuggestions as $suggestion) {
                if ($suggestion->action !== MiroImportAction::CreateRelation) {
                    continue;
                }
                $from = $this->existingOrCreated($suggestion->from, $objectsByTargetKey);
                $to = $this->existingOrCreated($suggestion->to, $objectsByTargetKey);
                if (!$from instanceof StoryObject || !$to instanceof StoryObject || $suggestion->relationType === null) {
                    ++$result->skippedRows;
                    continue;
                }

                $relationKey = $from->getId()->toRfc4122() . '|' . $to->getId()->toRfc4122() . '|' . $suggestion->relationType->value;
                $relation = $createdRelationKeys[$relationKey] ?? null;
                if (!$relation instanceof Relation) {
                    $relation = $this->createRelation($from, $to, $suggestion, $user);
                    $createdRelationKeys[$relationKey] = $relation;
                    ++$result->createdRelations;
                }
                $this->linker->linkItem($relation, $this->itemRecorder->record($board, $suggestion->entry), ReferenceRole::Primary, $user);
            }
        });

        return $result;
    }

    /**
     * @param array<string, StoryObject> $objectsByTargetKey
     */
    private function resolveObject(ExternalSource $board, MiroImportTarget $target, array $objectsByTargetKey, User $user, MiroImportResult $result): StoryObject
    {
        $storyObject = $this->existingOrCreated($target, $objectsByTargetKey);
        if ($storyObject instanceof StoryObject) {
            return $storyObject;
        }

        $storyObjectClass = $target->type->getEntityClass();
        $storyObject = new $storyObjectClass();
        if (!$storyObject instanceof StoryObject) {
            throw new \LogicException(sprintf('"%s" is not a story object.', $storyObjectClass));
        }
        $storyObject->setTitle(mb_substr($target->title, 0, self::TITLE_MAX_LENGTH));
        $storyObject->setLarp($board->getIntegration()->getLarp());
        $storyObject->setCreatedBy($user);
        $this->entityManager->persist($storyObject);
        $this->entityManager->flush();
        ++$result->createdObjects;

        return $storyObject;
    }

    /**
     * @param array<string, StoryObject> $objectsByTargetKey
     */
    private function existingOrCreated(?MiroImportTarget $target, array $objectsByTargetKey): ?StoryObject
    {
        if (!$target instanceof MiroImportTarget) {
            return null;
        }

        return $target->storyObject ?? $objectsByTargetKey[$target->key()] ?? null;
    }

    private function createRelation(StoryObject $from, StoryObject $to, MiroImportSuggestion $suggestion, User $user): Relation
    {
        $caption = MiroTitleNormalizer::exact($suggestion->entry->text);
        $relation = (new Relation())
            ->setFrom($from)
            ->setTo($to)
            ->setRelationType($suggestion->relationType ?? throw new \LogicException('A relation needs a type.'));
        $relation->setTitle(mb_substr(sprintf('%s — %s → %s', $from->getTitle(), $caption, $to->getTitle()), 0, self::TITLE_MAX_LENGTH));
        $relation->setLarp($from->getLarp());
        $relation->setCreatedBy($user);
        $this->entityManager->persist($relation);
        $this->entityManager->flush();

        return $relation;
    }
}
