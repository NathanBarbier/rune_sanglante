<?php

namespace App\Controller;

use App\Entity\Effect;
use App\Form\EffectType;
use App\Repository\EffectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/effect')]
class EffectController extends AbstractController
{
    protected SluggerInterface $slugger;

    public function __construct (
        SluggerInterface $slugger,
    ) {
        $this->slugger = $slugger;
    }

    #[Route('/', name: 'app_effect_index', methods: ['GET'])]
    public function index(EffectRepository $effectRepository): Response
    {
        return $this->render('effect/index.html.twig', [
            'effects' => $effectRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_effect_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EffectRepository $effectRepository): Response
    {
        $effect = new Effect();
        $form = $this->createForm(EffectType::class, $effect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            // this condition is needed because the 'image' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                // Move the file to the directory where images are stored
                try {
                    $imageFile->move(
                        $this->getParameter('upload_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                    $this->addFlash('danger', "Impossible d'uploader le fichier");
                    return $this->redirectToRoute('app_marque');
                }

                // updates the 'imageFilename' property to store the PDF file name
                // instead of its contents
                $effect->setImage($newFilename);
            }

            $effectRepository->save($effect, true);

            return $this->redirectToRoute('app_effect_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('effect/new.html.twig', [
            'effect' => $effect,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_effect_show', methods: ['GET'])]
    public function show(Effect $effect): Response
    {
        return $this->render('effect/show.html.twig', [
            'effect' => $effect,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_effect_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Effect $effect, EffectRepository $effectRepository): Response
    {
        $form = $this->createForm(EffectType::class, $effect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $effectRepository->save($effect, true);

            return $this->redirectToRoute('app_effect_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('effect/edit.html.twig', [
            'effect' => $effect,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_effect_delete', methods: ['POST'])]
    public function delete(Request $request, Effect $effect, EffectRepository $effectRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$effect->getId(), $request->request->get('_token'))) {
            $effectRepository->remove($effect, true);
        }

        return $this->redirectToRoute('app_effect_index', [], Response::HTTP_SEE_OTHER);
    }
}
