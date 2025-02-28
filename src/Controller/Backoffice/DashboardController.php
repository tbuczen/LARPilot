<?php
// src/Controller/Backoffice/DashboardController.php
namespace App\Controller\Backoffice;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'backoffice_dashboard')]
class DashboardController extends AbstractController
{
    public function __invoke(): Response
    {
        // Render a backoffice dashboard template
        return $this->render('backoffice/dashboard/index.html.twig');
    }
}
