<?php

namespace App\Twig\Components;

use App\Entity\Larp;
use App\Entity\SavedFormFilter;
use App\Repository\SavedFormFilterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class SaveFilterForm extends AbstractController
{
    use DefaultActionTrait;

    private UserInterface $user;

    #[LiveProp(writable: true)]
    public string $name = '';

    #[LiveProp]
    public string $larpId = '';

    #[LiveProp]
    public string $formName = '';

    #[LiveProp]
    public array $parameters = [];

    public array $internalSavedFilters = [];

    #[LiveProp(writable: true, dehydrateWith: 'dehydrateSavedFilterArray')]
    public array $savedFilters = [];

    public function __construct(
        private readonly SavedFormFilterRepository $repository,
        private readonly Security $security
    )
    {
        $this->user = $this->security->getUser();
    }

    public function mount(string $formName): void
    {
        $this->formName = $formName;
        $this->refreshFilters();
    }

    #[LiveAction]
    public function save(EntityManagerInterface $entityManager): void
    {
        if (trim($this->name) === '') {
            throw new \RuntimeException('Filter name is required.');
        }

        $filter = new SavedFormFilter();
        $filter->setName($this->name);
        $filter->setFormName($this->formName);
        $filter->setParameters($this->parameters);
        $filter->setLarp($entityManager->getReference(Larp::class, Uuid::fromString($this->larpId)));

        $this->repository->save($filter);

        $this->refreshFilters();
        $this->name = ''; // reset input
    }

    #[LiveAction]
    public function delete(#[LiveArg] string $id): void
    {
        $filter = $this->repository->findOneBy([
            'id' => $id,
            'formName' => $this->formName,
            'createdBy' => $this->user,
        ]);

        if (!$filter) {
            throw new \RuntimeException('Filter not found or unauthorized.');
        }

        $this->repository->remove($filter);


        $this->refreshFilters();
    }

    #[LiveAction]
    public function rename(#[LiveArg] string $id, #[LiveArg] string $name): void
    {
        $filter = $this->repository->findOneBy([
            'id' => $id,
            'formName' => $this->formName,
            'createdBy' => $this->user,
        ]);

        if (!$filter) {
            throw new \RuntimeException('Filter not found or unauthorized.');
        }

        $filter->setName($name);

        $this->repository->save($filter);


        $this->refreshFilters();
    }

    /**
     * @param SavedFormFilter[] $savedFormFilters
     * @return array
     */
    public function dehydrateSavedFilterArray(array $savedFormFilters): array
    {
        $data = [];

        foreach ($savedFormFilters as $savedFormFilter) {
            $data[] = $this->dehydrateSavedFilter($savedFormFilter);
        }
        return $data;
    }

    private function dehydrateSavedFilter(SavedFormFilter|array $savedFormFilter): array
    {
        if(is_array($savedFormFilter)){
            return $savedFormFilter;
        }

        return [
            'id' => $savedFormFilter->getId()->toRfc4122(),
            'name' => $savedFormFilter->getName(),
            'parameters' => http_build_query($savedFormFilter->getParameters()),
        ];
    }

    private function refreshFilters(): void
    {
        $this->internalSavedFilters = $this->repository->findByFormNameAndUser($this->formName, $this->user);
        $this->savedFilters = $this->internalSavedFilters;
    }
}