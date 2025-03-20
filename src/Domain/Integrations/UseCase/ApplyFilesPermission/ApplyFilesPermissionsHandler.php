<?php

namespace App\Domain\Integrations\UseCase\ApplyFilesPermission;

use App\Service\Integrations\LarpIntegrationManager;

readonly class ApplyFilesPermissionsHandler
{

    public function __construct(
//        private LarpIntegrationManager $integrationManager
    )
    {
    }
    public function handle(ApplyFilesPermissionsCommand $command): void
    {

        //"permissions" => array:4 [▼
        //        "1jpmkA7uaU9F2vmwUhDuV3H8GUiYJkTR9" => "edit"
        //        "1hOJFQxD6lMXgZ3ciG3acBZhfM04jbeJof2-bW5i0_c4" => "view"
        //        "1BhfSSgtK_Y3eBTX_9yT1E_kJmLZzgkxT" => "view"
        //        "1lA9e6bnfQarFg53ClGLjC5nHGsiNVk-pWh6o5Kxk0tQ" => "view"
        //      ]

        dump($command);
//        $this->integrationManager->

    }
}