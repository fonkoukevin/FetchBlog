<?php
// src/Controller/CategoryController.php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{
    #[Route('/category', name: 'app_category', methods: ['GET', 'POST'])]
    public function index(CategoryRepository $categoryRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $categories = $categoryRepository->findAll();
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category->setCreatedAt(new \DateTimeImmutable());

            foreach ($category->getPosts() as $post) {
                $post->addCategory($category);
            }
            $entityManager->persist($category);
            $entityManager->flush();
            if ($request->isXmlHttpRequest()) {
                return new Response('Category added successfully', 200);
            }
            return $this->redirectToRoute('app_category');
        }
        return $this->render('category/index.html.twig', [
            'categories' => $categories,
            'form' => $form->createView(),
            'show_navbar' => true,
        ]);
    }

    #[Route('/category/edit/{id}', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(Category $category, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category->setUpdatedAt(new \DateTimeImmutable());

            foreach ($category->getPosts() as $post) {
                $post->addCategory($category);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_category');
        }

        return $this->render('category/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/category/delete/{id}', name: 'app_category_delete', methods: ['DELETE'])]
    public function delete(Category $category, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($category);
        $entityManager->flush();

        return new Response('Category deleted successfully', 200);
    }
}
