<?php

namespace App\Controller;

use App\Repository\EffectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FightSimulationController extends AbstractController
{
    #[Route('/fight/simulation', name: 'app_fight_simulation')]
    public function index(EffectRepository $effectRepository): Response
    {
        return $this->render('fight_simulation/index.html.twig', [
            'effects' => $effectRepository->findAll(),
        ]);
    }
}
