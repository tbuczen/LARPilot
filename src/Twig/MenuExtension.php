<?php

namespace App\Twig;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class MenuExtension extends AbstractExtension implements GlobalsInterface
{
    private array $menuItems = [];

    public function __construct(
        private readonly UrlGeneratorInterface $router,
        private readonly Security              $security,
        private readonly TranslatorInterface   $translator,
    ) {
    }

    public function getGlobals(): array
    {
        $user = $this->security->getUser();

        $this->menuItems = [
            [
                'label' => $this->translator->trans('common.larps', domain: 'messages'),
                'url' => $this->router->generate('public_larp_list'),
            ]
        ];

        if ($user) {
            $this->menuItems[] = [
                'label' => $this->translator->trans('common.account'),
                'url' => '#',
                'children' => [
                    [
                        'label' => $this->translator->trans('account.settings'),
                        'url' => $this->router->generate('account_settings'),
                    ],
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


            $this->menuItems[] = [
                'label' => $this->translator->trans('common.backoffice'),
                'url' => '#',
                'children' => [
                    [
                        'label' => $this->translator->trans('backoffice.larp.list'),
                        'url' => $this->router->generate('backoffice_larp_list'),
                    ],
                    [
                        'label' => $this->translator->trans('backoffice.larp.create'),
                        'url' => $this->router->generate('backoffice_larp_create'),
                    ],
                ],
            ];
        } else {
            $this->menuItems[] = [
                'label' => $this->translator->trans('common.login'),
                'url' => $this->router->generate('sso_connect'),
            ];
        }

        //add service to check if user is organizer in any larp that is not cancelled - of so - add link to backoffice

        return [
            'menuItems' => $this->menuItems,
        ];
    }
}
