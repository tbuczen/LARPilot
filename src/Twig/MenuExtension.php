<?php

namespace App\Twig;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class MenuExtension extends AbstractExtension implements GlobalsInterface
{
    private array $menuItems;

    public function __construct(
        private readonly UrlGeneratorInterface $router,
        private readonly Security              $security,
        private readonly TranslatorInterface   $translator,
    )
    {
        $this->menuItems = [
            [
                'label' => $this->translator->trans('common.larps'),
                'url' => $this->router->generate('public_larp_list'),
            ]
        ];
    }

    public function getGlobals(): array
    {
        $user = $this->security->getUser();

        if ($user) {
            $this->menuItems[] = [
                'label' => $this->translator->trans('account.settings'),
                'url' => $this->router->generate('account_settings'),
                'children' => [
                    [
                        'label' => $this->translator->trans('account.connected_accounts'),
                        'url' => $this->router->generate('account_social_accounts'),
                    ],
                    [
                        'label' => $this->translator->trans('common.logout'),
                        'url' => $this->router->generate('_logout_main'),
                    ],
                ],
            ];

            $this->menuItems[] = [
                'label' => $this->translator->trans('common.incidents'),
                'url' => '#',
                'children' => [
                    [
                        'label' => $this->translator->trans('common.create'),
                        'url' => $this->router->generate('incident_create'),
                    ],
                    [
                        'label' => $this->translator->trans('common.list'),
                        'url' => $this->router->generate('incident_list'),
                    ],
                ],
            ];

        }

        return [
            'menuItems' => $this->menuItems,
        ];
    }
}
