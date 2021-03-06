<?php

namespace App\Controller;

use App\Data\SearchData;
use App\Entity\Image;
use App\Entity\Property;
use App\Form\PropertyFormType;
use App\Form\SearchForm;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class PropertyController extends AbstractController
{
    /**
     * @var PropertyRepository
     */
    private $repo;

    /**
     * @var PaginatorInterface
     */
    private $paginator;

    /**
     * @var EntityManagerInterface
     */
    private $manager;


    public function __construct(PropertyRepository $propertyRepository, PaginatorInterface $paginator, EntityManagerInterface $manager)
    {
        $this->repo = $propertyRepository;
        $this->paginator = $paginator;
        $this->manager = $manager;
    }


//    /**
//     * @param Symfony\Component\HttpFoundation\Request
//     * @return Symfony\Component\HttpFoundation\Response
//     */
//    public function index(Request $request): Response
//    {
//
//        $properties = $this->paginator->paginate(
//            $this->repo->findAll(),
//            $request->query->getInt('page', 1),
//            3
//        );
//
//        // $data = new SearchData;
//
//        // $data->page = $request->get('page', 1);
//
//        // $form = $this->createForm(SearchForm::class, $data);
//        // $form->handleRequest($request);
//
//        // $properties = $propertyRepository->findSearch($data);
//
//        return $this->render('backend/admin/property/index.html.twig', [
//            'properties' => $properties,
//            // 'form' => $form->createView(),
//        ]);
//    }


    /**
     * @Route("admin/property/new", name="app_property_new", methods={"GET", "POST"})
     * 
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function new_property(Request $request): Response
    {
        $property = new Property;

        $property_create_form = $this->createForm(PropertyFormType::class, $property);
        $property_create_form->handleRequest($request);

        if ($property_create_form->isSubmitted() && $property_create_form->isValid()) {
            // Upload Image multiple
            $images = $property_create_form->get('images')->getData();

            // on boucle sur les images
            foreach($images as $image) {
                // on recupere un nouveau nom de fichier
                $fichier = md5(uniqid()). '.' . $image->guessExtension();
                // On copie le fichier dans le dosiier uploads
                $image->move(
                    $this->getParameter('images_galerie'),
                    $fichier
                );
                // On stock l'image dans la base de donn??e
                $img = new Image;
                $img->setUrl($fichier);
                $property->addImage($img);
            }

            $this->manager->persist($property);
            $this->manager->flush();

            $this->addFlash('success', 'Your property is added successfully');

            return $this->redirectToRoute('app_property_list');
        }

        return $this->render('property/create.html.twig', [
            'property_create_form' => $property_create_form->createView(),
        ]);
    }

    /**
     * @Route("admin/property/edit/{id}", name="app_property_edit")
     * @param Symfony\Component\HttpFoundation\Request
     * @param App\Entity\Property
     */
    public function edit_property(Property $property, Request $request): Response
    {

        $property_edit_form = $this->createForm(PropertyFormType::class, $property);
        $property_edit_form->handleRequest($request);

        if ($property_edit_form->isSubmitted() && $property_edit_form->isValid()) {

            // Upload Image multiple
            $images = $property_edit_form->get('images')->getData();

            // on boucle sur les images
            foreach($images as $image) {
                // on recupere un nouveau nom de fichier
                $fichier = md5(uniqid()). '.' . $image->guessExtension();
                // On copie le fichier dans le dosiier uploads
                $image->move(
                    $this->getParameter('images_galerie'),
                    $fichier
                );
                // On stock l'image dans la base de donn??e
                $img = new Image;
                $img->setUrl($fichier);
                $property->addImage($img);
            }

            $this->manager->flush();

            $this->addFlash('success', 'Your property is updated successfully');
            return $this->redirectToRoute('app_admin_index');
        }

        return $this->render('backend/admin/property/edit.html.twig', [
            'property' => $property,
            'property_edit_form' => $property_edit_form->createView(),
        ]);
    }

    /**
     * @Route("/property/show/{id}", name="app_property_show")
     */
    public function show_property(Property $property)
    {
        return $this->render('property/show.html.twig', [
            'property' => $property
        ]);
    }

    /**
     * @Route("/property/img/delete/{id}", name="app_property_galerie", methods={"DELETE"})
     */
    public function delete_image(Image $image, Request $request)
    {
        $data = json_decode($request->getContent(), true);

        // On verifie si le token est valide
        // On recupere le nom de l'image
        // On supprime l'image
        if ($this->isCsrfTokenValid('delete'.$image->getId(), $data['_token'])) {
            $url = $image->getUrl();

            unlink($this->getParameter('images_galerie').'/'.$url);

            $em = $this->getDoctrine()->getManager();
            $em->remove($image);
            $em->flush();

            // On repond en JSON
            return new JsonResponse(['success' => 1]); 

        } else {
            return new JsonResponse(['error' => 'Token invalid'], 400);
        }
    }


    /**
     * @Route("/search/results", name="app_search_results")
     */
    public function search_results(Request $request): Response
    {
        $data = new SearchData;

        $data->page = $request->get('page', 1);

        $form = $this->createForm(SearchForm::class, $data);
        $form->handleRequest($request);

        // [$min, $max] = $repo->findMinMax($data);

        $properties = $this->repo->findSearch($data);

        // if ($request->get('ajax')) {
        //     return new JsonResponse([
        //         'content' => $this->renderView('search/_properties.html.twig', ['properties' => $properties]),
        //         'sorting' => $this->renderView('search/_sorting.html.twig', ['properties' => $properties]),
        //         'pagination' => $this->renderView('search/_pagination.html.twig', ['properties' => $properties])
        //     ]);
        // }


        return $this->render('search/index.html.twig', [
            'properties' => $properties,
            'form' => $form->createView(),
            // 'min' => $min,
            // 'max' => $max
        ]);
    }

    /**
     * @Route("/property/{id}", name="app_property_delete", methods={"POST"})
     */
    public function delete_property(Request $request, Property $property)
    {
        if ($this->isCsrfTokenValid('delete'.$property->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($property);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_property_list');
    }


    // /**
    //  * @Route("/search")
    //  */
    // public function search(Request $request, PropertyRepository $propertyRepository)
    // {
    //     $data = new SearchData;

    //     $data->page = $request->get('page', 1);

    //     $form = $this->createForm(SearchForm::class, $data);
    //     $form->handleRequest($request);

    //     $properties = $propertyRepository->findSearch($data);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         return $this->redirectToRoute('app_search_results');
    //     }

    //     return $this->render('search/init.html.twig', [
    //         'form' => $form->createView(),
    //         'properties' => $properties
    //     ]);
    // }
}
