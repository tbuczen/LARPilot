<?php

namespace App\Domain\Account\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/backoffice/account', name: 'backoffice_account_')]
class AccountController extends BaseController
{
    #[Route('/pending-approval', name: 'pending_approval')]
    public function pendingApproval(): Response
    {
        return $this->render('account/pending_approval.html.twig');
    }
}
