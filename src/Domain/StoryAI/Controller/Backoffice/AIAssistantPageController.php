<?php

namespace App\Domain\StoryAI\Controller\Backoffice;

use App\Domain\Core\Controller\BaseController;
use App\Domain\Core\Entity\Larp;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/larp/{larp}/ai-assistant', name: 'backoffice_larp_ai_assistant_')]
#[IsGranted('ROLE_USER')]
class AIAssistantPageController extends BaseController
{
    #[Route('', name: 'chat', methods: ['GET'])]
    #[IsGranted('VIEW_BO_AI_ASSISTANT', subject: 'larp')]
    public function chat(Larp $larp): Response
    {
        return $this->render('domain/story_ai/assistant/chat.html.twig', [
            'larp' => $larp,
        ]);
    }
}
