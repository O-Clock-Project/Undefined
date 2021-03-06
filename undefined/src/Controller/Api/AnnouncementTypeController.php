<?php

namespace App\Controller\Api;

use App\Entity\AnnouncementType;
use App\Form\AnnouncementTypeType;
use App\Services\ApiUtils;
use App\Repository\AnnouncementTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/api")
 */
class AnnouncementTypeController extends AbstractController
// route qui commence par /api pour toutes les routes du controller
{
    /**
     * @Route("/announcementTypes", name="listAnnouncementTypes", methods="GET")
     */
    public function getAnnouncementTypes(AnnouncementTypeRepository $announcementTypeRepo, Request $request, ApiUtils $utils )
    //Méthode permettant de renvoyer la liste de tous les items, avec filtres, ordre pagination et niveau de détail possible
    {
        
        $announcementType = new AnnouncementType; // On instancie un nouvel item temporaire et vide pour disposer de la liste de tous les propriétés possibles

        // On envoie à ApiUtils les outils et les informations dont il a besoin pour travailler et il nous renvoie une réponse
        $response = $utils->getItems($announcementType, $announcementTypeRepo, $request); 

        return $response; //On retourne la réponse formattée (liste d'items trouvés si réussi, message d'erreur sinon)
    }

    /**
     * @Route("/announcementTypes/{id}", name="showAnnouncementType", requirements={"id"="\d+"}, methods="GET")
     */
    public function getAnnouncementType(AnnouncementTypeRepository $announcementTypeRepo, $id, Request $request, ApiUtils $utils)
    //Méthode permettant de renvoyer l'item spécifié par l'id reçue et suivant un niveau de détail demandé
    {

        // On envoie à ApiUtils les outils et les informations dont il a besoin pour travailler et il nous renvoie une réponse
        $response = $utils->getItem($announcementTypeRepo, $id, $request);

        return $response; //On retourne la réponse formattée (item trouvé si réussi, message d'erreur sinon)
    }

    /**
     * @Route("/announcementTypes/{id}/{child}/{relation}", name="showAnnouncementTypeRelation", requirements={"id"="\d+","child"="[a-z-A-Z]+", "relation"="[a-z-A-Z_]+"}, methods="GET")
     */
    public function getAnnouncementTypeRelations($id, $relation, $child, Request $request, ApiUtils $utils)
    //Méthode permettant de renvoyer les items d'une relation de l'item spécifié par l'id reçue et suivant un niveau de détail demandé
    {
        

        // On envoie à ApiUtils les outils et les informations dont il a besoin pour travailler et il nous renvoie une réponse
        $response = $utils->getItemRelations( $id,  $child, $relation , $request);

        return $response; //On retourne la réponse formattée (item trouvé si réussi, message d'erreur sinon)
    }

    /**
     * @Route("/announcementTypes", name="postAnnouncementType", methods="POST")
     */
    public function postAnnouncementType (Request $request, ApiUtils $utils)
    //Méthode permettant de persister un nouvel item à partir des informations reçues dans la requête (payload) et de le renvoyer
    {
        $announcementType = new AnnouncementType(); // On instancie un nouvel item qui va venir être hydraté par les informations fournies dans la requête

        // On crée un formulaire "virtuel" qui va permettre d'utiliser le système de validation des forms Symfony pour checker les données reçues
        // Cf le fichier config/validator/validation.yaml pour les contraintes
        $form = $this->createForm(AnnouncementTypeType::class, $announcementType);


        
        // On envoie à ApiUtils les outils et les informations dont il a besoin pour travailler et il nous renvoie une réponse
        $response = $utils->postItem($announcementType, $form, $request);

        return $response; //On retourne la réponse formattée (item créé si réussi, message d'erreur sinon)
    }

    /**
     * @Route("/announcementTypes/{id}", name="updateAnnouncementType", requirements={"id"="\d+"}, methods="PUT")
     */
    public function updateAnnouncementType (Request $request, AnnouncementType $announcementType, ApiUtils $utils)
    //Méthode permettant de persister les modifications sur un item existant à partir des informations reçues dans la requête (payload) et de le renvoyer
    {
        
        // On crée un formulaire "virtuel" qui va permettre d'utiliser le système de validation des forms Symfony pour checker les données reçues
        // Cf le fichier config/validator/validation.yaml pour les contraintes
        $form = $this->createForm(AnnouncementTypeType::class, $announcementType);


        
        // On envoie à ApiUtils les outils et les informations dont il a besoin pour travailler et il nous renvoie une réponse
        $response = $utils->updateItem($announcementType, $form, $request);

        return $response; //On retourne la réponse formattée (item créé si réussi, message d'erreur sinon)
    }

    /**
     * @Route("/announcementTypes/{id}", name="deleteAnnouncementType", requirements={"id"="\d+"}, methods="DELETE")
     */
    public function deleteAnnouncementType (Request $request, AnnouncementType $announcementType, ApiUtils $utils)
    //Méthode permettant de persister les modifications sur un item existant à partir des informations reçues dans la requête (payload) et de le renvoyer
    {
     

        
        // On envoie à ApiUtils les outils et les informations dont il a besoin pour travailler et il nous renvoie une réponse
        $response = $utils->deleteItem($announcementType, $request);

        return $response; //On retourne la réponse formattée (item créé si réussi, message d'erreur sinon)
    }
}