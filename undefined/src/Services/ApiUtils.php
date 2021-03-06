<?php

namespace App\Services;

use App\Services\ApiUtilsTools;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;



class ApiUtils
// Service qui me permet de factoriser tout le code qui serait redondant dans mes controllers
{
    private $tools;
    private $em;

    public function __construct(EntityManagerInterface $em, ApiUtilsTools $tools){
        $this->tools = $tools; //à l'instanciation de ApiUtils j'instancie également le service "boite à outils" qui va avec
        $this->em = $em;
    }


    public function getItems($object, $repo, $request )
    // Méthode qui permet de récupérer tous les items d'une entité, avec filtres, ordre, pagination et niveau de détails configurables
    {

        // je passe les paramètres nécessaires au traitement de la requête et des paramètres demandés
        $result = $this->tools->handleRequestWithParams($object, $repo, $request);

        // je vérifie si j'ai eu une erreur en retour, si oui je la return au controller
        if($result['error'] !== null ){
            return new Response($result['error'], Response::HTTP_NOT_FOUND);
        }
        // si pas d'erreur je récupère les objets retournés par la requête et le groupe de sérialization
        $objects = $result['objects'];
        $group = $result['group'];
        

        // On passe les objets reçus à la méthode handleSerialization qui s'occupe de transformer tout ça en json
        $jsonContent = $this->tools->handleSerialization($objects, $group);
        // on crée une Réponse avec le code http 200 ("réussite")
        $response =  new Response($jsonContent, 200);
        // On set le header Content-Type sur json et utf-8
        $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response; //On renvoie la réponse
    }


    public function getItem($repo, $id, $request )
    //Méthode qui permet de trouver un item par son id passé dans l'url
    {
        // On cherche avec l'id grace au repo si on trouve l'objet correspondant
        $object = $repo->findOneById($id);
        // Si $object est vide on retourne une erreur 404 et un message d'erreur
        if (empty($object)){
            return new JsonResponse(['error' => 'Item non trouvé'], Response::HTTP_NOT_FOUND);
        };

        $group = 'concise'; //valeur par défaut de $group
        // Si dans la requête on a la clé displayGroup on met sa value dans $group
        foreach($request->query as $key => $value){
            if($key === 'displayGroup'){
                $group = $value;
            }
        }

        // On passe l'objet reçu à la méthode handleSerialization qui s'occupe de transformer tout ça en json
        $jsonContent = $this->tools->handleSerialization($object, $group);
        // on crée une Réponse avec le code http 200 ("réussite")
        $response =  new Response($jsonContent, 200);
        // On set le header Content-Type sur json et utf-8
        $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response; //On renvoie la réponse
    }


  

    public function getItemRelations($id, $child, $relation, $request )
    //Méthode qui permet de trouver un item par son id passé dans l'url et d'aller chercher les éléments de la relation spécifiée (avec filtres, etc comme getItems)
    {
        $childClass= substr(ucfirst($child),0,-1); //On trouve la classe des objets enfants en enlevant la dernière lettre (s) et en mettant la première lettre en majuscule
        if(substr($childClass,-2) === 'ie'){ //Si la classe ci-dessus finie par ie une fois mise au singulier, on remplace ie par y 
            $childClass = substr($childClass, 0, -2).'y'; //cas de (difficulties/Difficulty) et (specialities/Speciality)
        }
        //On va chercher la classe de l'entité-enfant reçue
        $childClass = 'App\Entity\\' .$childClass; //on met la première lettre en majuscule et on enlève le s à la fin
        $childObject = new $childClass; // On instancie un objet vide à partir 
        $childObjectRepo = $this->em->getRepository($childClass); // On récupère le repo correspondant à l'entité-enfant pour faire la requête


        //On passe à la méthode handleRequestWithParams ce qu'elle a besoin pour nous ramener les éléments demandés dans la requête
        $result = $this->tools->handleRequestWithParams($childObject, $childObjectRepo, $request, $id, $relation);
        
        

        // je vérifie si j'ai eu une erreur en retour, si oui je la return au controller
        if($result['error'] !== null ){
            return new Response($result['error'], Response::HTTP_NOT_FOUND);
        }
        // si pas d'erreur je récupère les objets retournés par la requête et le groupe de sérialization
        $objects = $result['objects'];
        $group = $result['group'];
        

        // On passe l'objet reçu à la méthode handleSerialization qui s'occupe de transformer tout ça en json
        $jsonContent = $this->tools->handleSerialization($objects, $group);
        // on crée une Réponse avec le code http 200 ("réussite")
        $response =  new Response($jsonContent, 200);
        // On set le header Content-Type sur json et utf-8
        $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response; //On renvoie la réponse
    }



    public function postItem($object, $form, $request)
    // Méthode permettant de persister un nouvel objet en BDD après avoir fait les tests sur les datas reçus grace au validator des forms symfony
    // Et après avoir créé les relations passées dans la payload en json
    {
        //Exemple de json à recevoir
        // {        
        //     "label": "Ruby on Rails",   <= champ simple de l'objet à créer
        //     "add":[                      <= partie "ajout" de relation si besoin
        //         {"id": 69,               <= id de l'objet enfant à rattacher à l'objet créé
        //         "entity": "bookmark",    <= nom de classe de l'objet enfant à rattacher à l'objet créé (naturellement au singulier et sans majuscule)
        //         "property": "bookmark"   <= nom de la propriété de l'objet créé ("parent") qui réfère à l'objet enfant (mis au singulier)
        //         },
        //         {"id": 70,
        //         "entity": "bookmark",
        //         "property": "bookmark"
        //         }]
        // }
        $parametersAsArray = []; //On prépare un array pour recevoir tous les paramètres de la requêtes sous forme php depuis le json
       
        if ($content = $request->getContent()) { //Si requête pas vide, on met dans $content
            $parametersAsArray = json_decode($content, true); //Et on decode en json
        }
        // Comme on veut que les dates qu'on reçoit dans le json en payload soient converti en Datetime on parcourt le tableau de paramètres
        // Et on instancie un new DateTime si la string est au format date (et on évite les arrays car ils contiennent )
        foreach($parametersAsArray as $key => $value){
            if(!is_array($value) && strtotime($value) ) {
                $value = new \Datetime($value);
            }
        }
        if(isset($parametersAsArray['add'])){
            $actionsAsArray = $this->tools->prepareAddRelationsActions($object, $parametersAsArray);
            unset($parametersAsArray['add']);
        }

        $form->submit($parametersAsArray); // Validation des données par les forms symfony (cf config/validator/validation.yaml et l'EntityType correspondant)
        
        // Si le "form virtuel" n'est pas valide on renvoie un code http bad request et un message d'erreur
        if(!$form->isValid()){
            $errors = [];
            foreach($form as $field => $error){
                if ((string)$error->getErrors(true) !== ''){
               $errors[$field] = substr(substr(((string)$error->getErrors(true)), 0, -1), 7);
                }
            }
        
            return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
        }
        //L'objet parent étant maintenant correctement hydraté par le form symfony, on peut lui ajouter les relations voulues
        //Pour chaque action de notre tableau
        if(isset($actionsAsArray)){
            if(isset($actionsAsArray['error'])){
                return new JsonResponse($actionsAsArray['error'], Response::HTTP_BAD_REQUEST);
            }
            else{
                foreach($actionsAsArray as $action){
                    $actionMethod = $action['method']; //On 
                    $actionChild = $action['child'];
                    $object->$actionMethod($actionChild);
                }
            }
        }
        // Si le "form virtuel" est valide, on persiste l'objet en BDD
            $this->em->persist($object);
            $this->em->flush();
            
            // On passe l'objet reçu à la méthode handleSerialization qui s'occupe de transformer tout ça en json
            $jsonContent = $this->tools->handleSerialization($object);
            // on crée une Réponse avec le code http 201 ("created")
            $response =  new Response($jsonContent, Response::HTTP_CREATED);
            // On set le header Content-Type sur json et utf-8
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Access-Control-Allow-Origin', '*');

            return $response; //On renvoie la réponse
        
    
    }

    public function updateItem($object, $form, $request, $encoder=null)
    // Méthode permettant de persister un nouvel objet en BDD après avoir fait les tests sur les datas reçus grace au validator des forms symfony
    {

        $parametersAsArray = []; //On prépare un array pour recevoir tous les paramètres de la requêtes sous forme php depuis le json
        
        if ($content = $request->getContent()) { //Si requête pas vide, on met dans $content

            $parametersAsArray = json_decode($content, true); //Et on decode en json
        }
            

        // Comme on veut que les dates qu'on reçoit dans le json en payload soient converti en Datetime on parcourt le tableau de paramètres
        // Et on instancie un new DateTime si la string est au format date (et on évite les arrays car ils contiennent )
        foreach($parametersAsArray as $key => $value){
            if(!is_array($value) && strtotime($value) ) {
                $value = new \Datetime($value);
            }
        }
        
        // si l'utilisateur veut changer de mot de passe, je le récupère et l'encode directement
        if(isset($parametersAsArray["password"])){
            if(isset($parametersAsArray["old_password"])){ //Pour accepter le changement je dois recevoir aussi l'ancien mdp
                if (!$encoder->isPasswordValid($object, $parametersAsArray["old_password"])){ //je teste que l'ancien mdp reçu correspond à celui en BDD
                    return new JsonResponse(array('error' => 'Ancien mot de passe ne correspond pas'), Response::HTTP_BAD_REQUEST); //sinon erreur
                }
            }
            else{
                return new JsonResponse(array('error' => 'Ancien mot de passe ne correspond pas'), Response::HTTP_BAD_REQUEST);//si pas d'ancien mdp reçu: error aussi
            }
            if(strlen($parametersAsArray["password"])>=8){
                $newPassword = $encoder->encodePassword($object, $parametersAsArray["password"]);//si tout est ok: on encode le nouveau mdp
            } 
            else{
                return new JsonResponse(array('error' => 'Le mot de passe doit faire au moins 8 caractères'), Response::HTTP_BAD_REQUEST);//si pas d'ancien mdp reçu: error aussi
            }
              unset($parametersAsArray["password"]); //On unsette la clé nouveau mdp pour ne pas l'envoyer au form
        }
        if(isset($parametersAsArray["old_password"])){
            unset($parametersAsArray["old_password"]); //On unsette la clé ancien mdp pour ne pas l'envoyer au form
        }
        

        if(isset($parametersAsArray['remove'])){
            $actionsRemoveAsArray = $this->tools->prepareRemoveRelationsActions($object, $parametersAsArray);
            unset($parametersAsArray['remove']);
        }
        if(isset($actionsRemoveAsArray['error'])){
            return new JsonResponse($actionsRemoveAsArray['error'], Response::HTTP_NOT_FOUND);
        }
     
        if(isset($parametersAsArray['add'])){
            $actionsAddAsArray = $this->tools->prepareAddRelationsActions($object, $parametersAsArray);       
            unset($parametersAsArray['add']);
        }
        if(isset($actionsAddAsArray['error'])){
            return new JsonResponse($actionsAddAsArray['error'], Response::HTTP_NOT_FOUND);
        }
        $form->submit($parametersAsArray, false); // Validation des données par les forms symfony (cf config/validator/validation.yaml et l'EntityType correspondant)
        // Si le "form virtuel" n'est pas valide on renvoie un code http bad request et un message d'erreur
        if(!$form->isValid()){
            $errors = [];
            foreach($form as $field => $error){
                if ((string)$error->getErrors(true) !== ''){
               $errors[$field] = substr(substr(((string)$error->getErrors(true)), 0, -1), 7);
                }
            }
        
            return new JsonResponse($errors, Response::HTTP_BAD_REQUEST);
        }

        if(isset($newPassword)){ //Si on a créé un nouveau mdp (déjà encodé) on le set au User
            // J'enregiste le nouveau mot de passe en bdd
            $object->setPassword($newPassword);
        }

        //L'objet parent étant maintenant correctement hydraté par le form symfony, on peut lui ajouter les relations voulues
        //Pour chaque action de notre tableau
        
        if(isset($actionsRemoveAsArray)){
            if(isset($actionsRemoveAsArray['error'])){
                return new JsonResponse($actionsRemoveAsArray['error'], Response::HTTP_NOT_FOUND);
            }
            else{
                foreach($actionsRemoveAsArray as $actionRemove){
                    $actionRemoveMethod = $actionRemove['method']; //On 
                    $actionRemoveChild = $actionRemove['child'];
                    $object->$actionRemoveMethod($actionRemoveChild);
                }
            }
        }


        if(isset($actionsAddAsArray)){
            if(isset($actionsAddAsArray['error'])){
                return new JsonResponse($actionsAddAsArray['error'], Response::HTTP_NOT_FOUND);
            }
            else{
                foreach($actionsAddAsArray as $actionAdd){
                    $actionAddMethod = $actionAdd['method']; //On 
                    $actionAddChild = $actionAdd['child'];
                    $object->$actionAddMethod($actionAddChild);
                }
            }
        }


        // Si le "form virtuel" est valide, on persiste l'objet en BDD
            $this->em->persist($object);
            $this->em->flush();
            
            // On passe l'objet reçu à la méthode handleSerialization qui s'occupe de transformer tout ça en json
            $jsonContent = $this->tools->handleSerialization($object);
            // on crée une Réponse avec le code http 201 ("created")
            $response =  new Response($jsonContent, Response::HTTP_CREATED);
            // On set le header Content-Type sur json et utf-8
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Access-Control-Allow-Origin', '*');

            return $response; //On renvoie la réponse
    
    }

    public function deleteItem($object, $request)
    // Méthode permettant de persister un nouvel objet en BDD après avoir fait les tests sur les datas reçus grace au validator des forms symfony
    {

        $parametersAsArray = []; //On prépare un array pour recevoir tous les paramètres de la requêtes sous forme php depuis le json
        
        if ($content = $request->getContent()) { //Si requête pas vide, on met dans $content

            $parametersAsArray = json_decode($content, true); //Et on decode en json
        }
            

        // Comme on veut que les dates qu'on reçoit dans le json en payload soient converti en Datetime on parcourt le tableau de paramètres
        // Et on instancie un new DateTime si la string est au format date (et on évite les arrays car ils contiennent )
        foreach($parametersAsArray as $key => $value){
            if(!is_array($value) && strtotime($value) ) {
                $value = new \Datetime($value);
            }
        }
        

        if(isset($parametersAsArray['remove'])){
            $actionsRemoveAsArray = $this->tools->prepareRemoveRelationsActions($object, $parametersAsArray);
            unset($parametersAsArray['remove']);
        }
        if(isset($actionsRemoveAsArray['error'])){
            return new JsonResponse($actionsRemoveAsArray['error'], Response::HTTP_NOT_FOUND);
        }
     

        //L'objet parent étant maintenant correctement hydraté par le form symfony, on peut lui ajouter les relations voulues
        //Pour chaque action de notre tableau
        

        if(isset($actionsRemoveAsArray)){
            if(isset($actionsRemoveAsArray['error'])){
                return new JsonResponse($actionsRemoveAsArray['error'], Response::HTTP_BAD_REQUEST);
            }
            else{
                foreach($actionsRemoveAsArray as $actionRemove){
                    $actionRemoveMethod = $actionRemove['method']; //On 
                    $actionRemoveChild = $actionRemove['child'];
                    $object->$actionRemoveMethod($actionRemoveChild);
                }
            }
        }

        // Si le "form virtuel" est valide, on persiste l'objet en BDD
            $this->em->remove($object);
            $this->em->flush();
            
            // On passe l'objet reçu à la méthode handleSerialization qui s'occupe de transformer tout ça en json
            $jsonContent = $this->tools->handleSerialization(array('success' => 'Item effacé'));
            // on crée une Réponse avec le code http 201 ("created")
            $response =  new Response($jsonContent, Response::HTTP_OK);
            // On set le header Content-Type sur json et utf-8
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Access-Control-Allow-Origin', '*');

            return $response; //On renvoie la réponse
    
    }
    
}