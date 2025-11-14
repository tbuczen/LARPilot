<?php

namespace App\Twig;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
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
                'label' => $this->translator->trans('larp.plural', domain: 'messages'),
                'url' => $this->router->generate('public_larp_list'),
            ]
        ];

        $this->menuItems[] =
            [
                'label' => $this->translator->trans('larp.location.list'),
                'url' => $this->router->generate('public_location_list'),
            ]
        ;

        if ($user instanceof UserInterface) {
            $this->menuItems[] = [
                'label' => $this->translator->trans('account.singular'),
                'url' => '#',
                'children' => [
                    [
                        'label' => $this->translator->trans('account.settings'),
                        'url' => $this->router->generate('account_settings'),
                    ],
                    [
                        'label' => $this->translator->trans('account.my_larps'),
                        'url' => $this->router->generate('public_larp_my_larps'),
                    ],
                    [
                        'label' => $this->translator->trans('account.connected_accounts'),
                        'url' => $this->router->generate('account_social_accounts'),
                    ],
                    [
                        'label' => $this->translator->trans('logout'),
                        'url' => $this->router->generate('_logout_main'),
                    ],
                ],
            ];

            $this->menuItems[] = [
                'label' => $this->translator->trans('incidents'),
                'url' => '#',
                'children' => [
                    [
                        'label' => $this->translator->trans('create'),
                        'url' => $this->router->generate('incident_create'),
                    ],
                    [
                        'label' => $this->translator->trans('list'),
                        'url' => $this->router->generate('incident_list'),
                    ],
                ],
            ];

            $adminDropdown = [
                [
                    'label' => $this->translator->trans('larp.list'),
                    'url' => $this->router->generate('backoffice_larp_list'),
                ],
                [
                    'label' => $this->translator->trans('larp.create'),
                    'url' => $this->router->generate('backoffice_larp_create'),
                ],
            ];

            if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
                $adminDropdown[] = [
                    'label' => $this->translator->trans('larp.location.list'),
                    'url' => $this->router->generate('backoffice_location_list'),
                ];
            }

            $this->menuItems[] = [
                'label' => $this->translator->trans('backoffice_title'),
                'url' => '#',
                'children' => $adminDropdown,
            ];


            // Add Super Admin menu for users with ROLE_SUPER_ADMIN
            if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
                $this->menuItems[] = [
                    'label' => $this->translator->trans('super_admin.users.list'),
                    'url' => $this->router->generate('super_admin_users_list'),
                ];
            }
        } else {
            $this->menuItems[] = [
                'label' => $this->translator->trans('login'),
                'url' => $this->router->generate('sso_connect'),
            ];
        }

        //add service to check if user is organizer in any larp that is not cancelled - of so - add link to backoffice

        return [
            'menuItems' => $this->menuItems,
        ];
    }
}
