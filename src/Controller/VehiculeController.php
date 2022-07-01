<?php

namespace App\Controller;

use DateTime;
use App\Entity\Vehicule;
use App\Form\VehiculeType;
use App\Repository\VehiculeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class VehiculeController extends AbstractController
{
    
#[Route('/admin/vehicules', name: 'admin_app_vehicules')]
    public function listeVehicules(ManagerRegistry $doctrine): Response
    {
        $vehicules = $doctrine->getRepository(Vehicule::class)->findAll();

        return $this->render("vehicule/admin/gestionVehicules.html.twig", ["vehicules" => $vehicules]);
    }


#[Route('/admin/allVehicules', name: 'admin_app_allVehicules')]
    public function gestionVehicules(ManagerRegistry $doctrine): Response
    {
        $vehicules = $doctrine->getRepository(Vehicule::class)->findAll();

        return $this->render("vehicule/admin/gestionVehicules.html.twig", ["vehicules" => $vehicules]);
    }


#[Route('/admin/vehicule-ajout', name: 'admin_ajout_vehicule')]
    public function ajout(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger)
    {
        // si l'autilisateur n'est pas authntofifé
        if ( !$this->isGranted('IS_AUTHENTICATED_FULLY') ) {
            $this->addFlash('error', "Veuillez vous connecté avant de pouvoir accéder à cette page !");
            return $this->redirectToRoute('app_login');
        }

            // si l'utilisateur est connexté, mais n'est pas ADMIN.
        if(!$this->isGranted('ROLE_ADMIN')){
            $this->addFlash('error', "Vous êtes un intrus, que faites-vous ici !!!");
            return $this->redirectToRoute('app_home');
        }

        $vehicule = new Vehicule();

        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            // on recupere l'image depuis le formulaire
            $file = $form->get('imageForm')->getData();
            //dd($file);
            //dd($vehicule);
            // le slug permet de modifier une chaine de caractéres : mot clé => mot-cle
            $fileName = $slugger->slug( $vehicule->getTitre() ) . uniqid() . '.' . $file->guessExtension();

            try{
                // on deplace le fichier image recuperé depuis le formulaire dans le dossier parametré dans la partie Parameters du fichier config/service.yaml, avec pour nom $fileName
                $file->move($this->getParameter('photos_vehicules'),  $fileName);
            }catch(FileExeption $e)
            {
                // gérer les exeptions en cas d'erreur durant l'upload
            }

            $vehicule->setPhoto($fileName);

            $vehicule->setDateEnregistrement(new DateTime("now"));

            $manager = $doctrine->getManager();
            $manager->persist($vehicule);
            $manager->flush();

            $this->f-addFlash('success', 'Le véhicule a bien été ajouté.');

            return $this->redirectToRoute("admin_app_vehicule");
        }

        return $this->render("vehicule/admin/formulaire.html.twig", [
            "formVehicule" => $form->createView()
        ]);

    }

#[Route('/admin/update-vehicule/{id<\d+>}', name: 'admin_update_vehicule')]
    public function update(ManagerRegistry $doctrine, $id, Request $request, SluggerInterface $slugger)
    {
        $vehicule = $doctrine->getRepository(Vehicule::class)->find($id);

        $form = $this->createForm(VehiculeType::class, $vehicule);
        $form->handleRequest($request);

        // on stock l'image du vehicule à mettre à jour
        $image = $vehicule->getPhoto();

        if($form->isSubmitted() && $form->isValid())
        {
            // si une image a bien été ajouté au formulaire
            if($form->get('imageForm')->getData() )
            {
                // on recupere l'image du formulaire
                $imageFile = $form->get('imageForm')->getData();
    
                //on crée un nouveau nom pour l'image
                $fileName = $slugger->slug($vehicule->getTitre()) . uniqid() . '.' . $imageFile->guessExtension();
    
                //on deplace l'image dans le dossier parametré dans service.yaml
                try{
                    $imageFile->move($this->getParameter('photos_vehicules'), $fileName);
                }catch(FileException $e){
                    // gestion des erreur upload
                }
                $vehicule->setPhoto($fileName);
                
            }
                $manager= $doctrine->getManager();
                $manager->persist($vehicule);
                $manager->flush();

                $this->addFlash('success', 'Le véhicule a bien été mis à jour.');
                
                return $this->redirectToRoute('admin_app_vehicules');
        }

        return $this->render("vehicule/admin/formulaire.html.twig", [
            'formVehicule' => $form->createView()
        ]);
    }

      #[Route('/vehicule_{id}', name: 'app_vehicule')]
      public function showVehicule($id, ManagerRegistry $doctrine) {

        $vehicule = $doctrine->getRepository(Vehicule::class)->find($id);

        return $this->render("home/index.html.twig", [
            'vehicule' => $vehicule
        ]);

      }

      #[Route('/admin/delete-vehicule/{id<\d+>}', name: 'admin_delete_vehicule')]
    public function delete($id, VehiculeRepository $repo)
    {

        if ( !$this->isGranted('IS_AUTHENTICATED_FULLY') ) {
            $this->addFlash('error', "Veuillez vous connecté avant de pouvoir accéder à cette page !");
            return $this->redirectToRoute('app_login');
        }

            // si l'utilisateur est connexté, mais n'est pas ADMIN.
        if(!$this->isGranted('ROLE_ADMIN')){
            $this->addFlash('error', "Vous êtes un intrus, que faites-vous ici !!!");
            return $this->redirectToRoute('app_home');
        }
        
        $vehicule = $repo->find($id);

        $repo->remove($vehicule, 1);

        $this->f-addFlash('success', 'Le véhicule a bien été supprimé.');

        return $this->redirectToRoute('admin_app_vehicules');
    }
}