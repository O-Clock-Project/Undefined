<?php

namespace App\Controller\Api;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Services\ApiUtils;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/api")
 */
class CommentController extends AbstractController
// route qui commence par /api pour toutes les routes du controller
{
    /**
     * @Route("/comments", name="listComments", methods="GET")
     */
    public function getComments(CommentRepository $commentRepo, Request $request , ApiUtils $utils)
    //Méthode permettant de renvoyer la liste de tous les items, avec filtres, ordre pagination et niveau de détail possible
    {
        
        $comment = new Comment; // On instancie un nouvel item temporaire et vide pour disposer de la liste de tous les propriétés possibles

        // On envoie à ApiUtils les outils et les informations dont il a besoin pour travailler et il nous renvoie une réponse
        $response = $utils->getItems($comment, $commentRepo, $request); 

        return $response; //On retourne la réponse formattée (liste d'items trouvés si réussi, message d'erreur sinon)
    }

    /**
     * @Route("/comments/{id}", name="showComment", requirements={"id"="\d+"}, methods="GET")
     */
    public function getComment(CommentRepository $commentRepo, $id, Request $request, ApiUtils $utils)
    //Méthode permettant de renvoyer l'item spécifié par l'id reçue et suivant un niveau de détail demandé
    {

        // On envoie à ApiUtils les outils et les informations dont il a besoin pour travailler et il nous renvoie une réponse
        $response = $utils->getItem($commentRepo, $id, $request);

        return $response; //On retourne la réponse formattée (item trouvé si réussi, message d'erreur sinon)
    }


    /**
     * @Route("/comments/{id}/{child}/{relation}", name="showCommentRelation", requirements={"id"="\d+","child"="[a-z-A-Z]+", "relation"="[a-z-A-Z_]+"}, methods="GET")
     */
    public function getCommentRelations( $id, $relation, $child, Request $request, ApiUtils $utils)
    //Méthode permettant de renvoyer les items d'une relation de l'item spécifié par l'id reçue et suivant un niveau de détail demandé
    {
        

        // On envoie à ApiUtils les outils et les informations dont il a besoin pour travailler et il nous renvoie une réponse
        $response = $utils->getItemRelations( $id,  $child, $relation , $request);

        return $response; //On retourne la réponse formattée (item trouvé si réussi, message d'erreur sinon)
    }

    
    /**
     * @Route("/comments", name="postComment", methods="POST")
     */
    public function postComment (Request $request, ApiUtils $utils)
    //Méthode permettant de persister un nouvel item à partir des informations reçues dans la requête (payload) et de le renvoyer
    {
        $comment = new Comment(); // On instancie un nouvel item qui va venir être hydraté par les informations fournies dans la requête

        // On crée un formulaire "virtuel" qui va permettre d'utiliser le système de validation des forms Symfony pour checker les données reçues
        // Cf le fichier config/validator/validation.yaml pour les contraintes
        $form = $this->createForm(CommentType::class, $comment);


        
        // On envoie à ApiUtils les outils et les informations dont il a besoin pour travailler et il nous renvoie une réponse
        $response = $utils->postItem($comment, $form, $request);

        return $response; //On retourne la réponse formattée (item créé si réussi, message d'erreur sinon)
    }

    /**
     * @Route("/comments/{id}", name="updateComment", requirements={"id"="\d+"}, methods="PUT")
     */
    public function updateComment (Request $request, Comment $comment, ApiUtils $utils)
    //Méthode permettant de persister les modifications sur un item existant à partir des informations reçues dans la requête (payload) et de le renvoyer
    {
  
        // On crée un formulaire "virtuel" qui va permettre d'utiliser le système de validation des forms Symfony pour checker les données reçues
        // Cf le fichier config/validator/validation.yaml pour les contraintes
        $form = $this->createForm(CommentType::class, $comment);


        
        // On envoie à ApiUtils les outils et les informations dont il a besoin pour travailler et il nous renvoie une réponse
        $response = $utils->updateItem($comment, $form, $request);

        return $response; //On retourne la réponse formattée (item créé si réussi, message d'erreur sinon)
    }

    /**
     * @Route("/comments/{id}", name="deleteComment", requirements={"id"="\d+"}, methods="DELETE")
     */
    public function deleteComment ( Request $request, Comment $comment, ApiUtils $utils)
    //Méthode permettant de persister les modifications sur un item existant à partir des informations reçues dans la requête (payload) et de le renvoyer
    {

        
        // On envoie à ApiUtils les outils et les informations dont il a besoin pour travailler et il nous renvoie une réponse
        $response = $utils->deleteItem($comment, $request);

        return $response; //On retourne la réponse formattée (item créé si réussi, message d'erreur sinon)
    }
}