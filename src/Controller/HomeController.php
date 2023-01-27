<?php

namespace App\Controller;

use OA\Items;
use OA\Schema;
use OA\JsonContent;
use App\Entity\User;
use App\Entity\Product;
use OpenApi\Attributes as OA;
use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;




class HomeController extends AbstractController
{
    private $user;

    private $product;

    private $manager;


    public function __construct(UserRepository $user, ProductRepository $product, EntityManagerInterface $manager)
    {
        $this->user=$user;

        $this->product=$product;

        $this->manager=$manager;
    }


    
    /**
     *Création d'un utilisateur admin
     * @param Request $request
     * @return JsonResponse
     */
    
    #[Route('/api/createUserAdmin', name: 'create_user_admin', methods:'POST')]
    #[OA\Response(
        response: 200,
        description: 'Renvoie la création d\'un utilisateur Adminstrateur',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: User::class))
        )
        
    )]

    #[OA\RequestBody(  
        description: 'Envoie de l\'email et nom du nouveau admistrateur',    
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'email', type:'string'),
                new OA\Property(property: 'nom', type:'string'),

            ]
        )
    )]
   
    #[OA\Tag(name: 'userAdmin')]
    public function createUserAdmin(Request $request): JsonResponse
    {
       
        $data=json_decode($request->getContent(), true);

        $email=$data['email'];

        $nom=$data['nom'];

        $password=sha1(str_shuffle('HLKIP@PNNFR6677UU0__9!po?'));

        //Vérifie si l'utilisateur existe

        $user_exist=$this->user->findOneByEmail($email);

        if($user_exist)
        {
           return new JsonResponse(
            [
                'status'=>false,
                'response'=>'L\'email existe déjà, veuillez changer'
            ]
            );
        }

        else
        {
            $new_user= new User();

            $new_user->setEmail($email)
                     ->setNom($nom)
                     ->setRoles(['ROLE_ADMIN'])
                     ->setPassword($password);

            $this->manager->persist($new_user);

            $this->manager->flush();

            //Envoie d'un email contenant les informations d'accès à l'utilisateur créé

            return new JsonResponse(
                [
                    'status'=>true,
                    'response'=>'Utilisateur créé avec succès'
                ]
                );
        }
        
        
        
      
    } 
    
   
     /**
      * Création d'un simple utilisateur
      *
      * @param Request $request
      * @return JsonResponse
      */
    //Création d'un simple utilisateur
    //On a deux choix, soit on spécifie ici via la ROLE_ADMIN annotation soit on le fait manuellement
    #[Route('/api/createUser', name: 'create_user', methods:'POST')]
    #[OA\Response(
        response: 200,
        description: 'Renvoie la création d\'un simple utilisateur ',
        content: new Model(type: User::class)
        
    )]
    #[OA\RequestBody(  
        description: 'Envoie de l\'email et nom du nouveau admistrateur',    
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'email', type:'string'),
                new OA\Property(property: 'nom', type:'string'),

            ]
        )
    )]
   
    #[OA\Tag(name: 'user')]
    #[Security(name: 'BearerAuth')]

    public function createUser(Request $request): JsonResponse
    {
        $role=$this->getUser()->getRoles();
        

        if($role[0] === 'ROLE_ADMIN')
        {
           $data=json_decode($request->getContent(), true);

          

            $email=$data['email'];

            $nom=$data['nom'];

            $password=sha1(str_shuffle('HLKIP@PNNFR6677UU0__9!po?'));

            //Vérifie si l'utilisateur existe

            $user_exist=$this->user->findOneByEmail($email);

            if($user_exist)
            {
                return new JsonResponse(
                    [
                        'status'=>false,
                        'response'=>'L\email existe déjà, veuillez changer'
                    ]
                    );
            }

            else
            {
                $new_user= new User();

                $new_user->setEmail($email)
                        ->setNom($nom)
                        ->setPassword($password);

                $this->manager->persist($new_user);

                $this->manager->flush();

                //Envoie d'un email contenant les informations d'accès à l'utilisateur créé

                return new JsonResponse(
                    [
                        'status'=>true,
                        'response'=>'Utilisateur créé avec succès'
                    ]
                    );
            }
       
       
        }

        else
        {
            return new JsonResponse(
                [
                    'status'=>false,
                    'response'=>'Vous devez être administrateur'
                ]
                );
        }
        
    }
     
      /**
      * Création d'un produit // NB Seul l'admin peut ajouter un produit
      *
      * @param Request $request
      * @return JsonResponse
      */
     
     #[IsGranted("ROLE_ADMIN")]
     #[Route('/api/createProduct', name: 'create_product',methods:'POST')]
     #[OA\Response(
        response: 200,
        description: 'Renvoie la création d\'un nouveau produit ',
        content: new Model(type: Product::class)
        
    )]

    #[OA\RequestBody(
        required:true,
        description:"Envoie les champs de création d'un produit",
        content: [new OA\MediaType(mediaType: "multipart/form-data",
            schema: new OA\Schema(
                properties: [
                    new OA\Property(property: "nom", type: "string"),
                    new OA\Property(property: "type", type: "string"),
                    new OA\Property(property: "prix", type: "float"),
                    new OA\Property(property: "qte", type: "int"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "image", type: "file", format:"binary")
                    
                ]
            )
        )]

    )]
  
    #[OA\Tag(name: 'product')]
    #[Security(name: 'BearerAuth')]
    public function createProduct(Request $request): JsonResponse
     {
        
        $nom=$request->request->get('nom');
        $type=$request->request->get('type');
        $prix=$request->request->get('prix');
        $qte=$request->request->get('qte');
        $description=$request->request->get('description');

      
        //traitement de l'image

            $file = $request->files->get('image');
        
            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $filename = str_replace(" ", "_", $filename);


            $filename = uniqid() . "." . $file->getClientOriginalExtension();
            $file->move($this->getParameter('image_product'), $filename);

        //Création du produit    
        $product=new Product();
        $product->setNom($nom)
                ->setType($type)
                ->setPrix($prix)
                ->setQte($qte)
                ->setDescription($description)
                ->setImage($filename);

        $this->manager->persist($product);

        $this->manager->flush();

        return new JsonResponse(
            [
                'status'=>true,
                'response'=>'Produit ajouté avec succès'
            ]
            );
     }

    

      /**
       * liste de tous les produits
       *
       * @return Response
       */
      #[Route('/api/getAllProducts', name: 'get_all_products',methods:'GET')]
      #[OA\Response(
        response: 200,
        description: 'Renvoie la liste des produits ',
        content: new Model(type: Product::class)
      )]
      #[OA\Tag(name: 'productList')]

      #[Security(name: 'BearerAuth')]

      public function getAllProducts(): Response
      {
          
         $products=$this->product->findAll();

         $results=
         [
            'status'=>true,
            'products'=>$products
         ];



        return $this->json($results,200);
       
      }

     
        /**
         * liste de produits le plus vendus
         *
         * @return Response
         */ 
        #[Route('/api/getProductsMostSold', name: 'get_products_most_sold',methods:'GET')]
        #[OA\Response(
            response: 200,
            description: 'Renvoie la liste des produits le plus vendus ',
            content: new Model(type: Product::class)
          )]
          #[OA\Tag(name: 'productMostSold')]
    
          #[Security(name: 'BearerAuth')]
        public function getProductsMostSold(): Response
        {
            
            $productsHauteVente=$this->product->findProductsMostSold();
           
            
            $results=
            [
               'status'=>true,
               'products'=>$productsHauteVente
            ];
   
           return $this->json($results,200);
            
            
          
        }

      /**
       * Récupérer un produit
       */
      #[Route('/api/getOneProduct/{id}', name: 'get_one_product' ,methods:'GET')]
      #[OA\Response(
        response: 200,
        description: 'Renvoie un produit ',
        content: new Model(type: Product::class)
      )]
      #[OA\Tag(name: 'oneProduct')]

      #[Security(name: 'BearerAuth')]
      public function getOneProduct($id): Response
      {
            $product=$this->product->find($id);

            $results=
            [
               'status'=>true,
               'products'=>$product
            ];
   
           return $this->json($results,200);
      }


    /**
     * Modifier un produit
     */
    #[IsGranted("ROLE_ADMIN")]
    #[Route('/api/editProduct/{id}', name: 'edit_product', methods:'POST')]
    #[OA\Response(
    response: 200,
    description: 'Renvoie la modification d\'un produit',
    content: new Model(type: Product::class)
    
    )]

    #[OA\RequestBody(
        required:true,
        description:"Envoie les champs de création d'un produit",
        content: [new OA\MediaType(mediaType: "multipart/form-data",
            schema: new OA\Schema(
                properties: [
                    new OA\Property(property: "nom", type: "string"),
                    new OA\Property(property: "type", type: "string"),
                    new OA\Property(property: "prix", type: "float"),
                    new OA\Property(property: "qte", type: "int"),
                    new OA\Property(property: "description", type: "string"),
                    new OA\Property(property: "image", type: "file", format:"binary")
                    
                ]
            )
        )]

    )]

   
    #[OA\Tag(name: 'editProduct')]
    #[Security(name: 'BearerAuth')]
    public function editProduct(Request $request,$id): JsonResponse
    {
       
       

        
        $nom=$request->request->get('nom');
        $type=$request->request->get('type');
        $prix=$request->request->get('prix');
        $qte=$request->request->get('qte');
        $description=$request->request->get('description');

      
        //traitement de l'image

            $file = $request->files->get('image');
            
        
            $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $filename = str_replace(" ", "_", $filename);

            $filename = uniqid() . "." . $file->getClientOriginalExtension();
            $file->move($this->getParameter('image_product'), $filename);


        $product=$this->product->find($id);

        $product->setNom($nom)
                ->setType($type)
                ->setPrix($prix)
                ->setQte($qte)
                ->setDescription($description)
                ->setImage($filename);

        $this->manager->persist($product);

        $this->manager->flush();

        return new JsonResponse(
            [
                'status'=>true,
                'response'=>'Produit mis à jour avec succès'
            ]
            );
    }

      /**
       * Supprimer un produit
       */
      #[IsGranted("ROLE_ADMIN")]
      #[Route('/api/deleteProduct/{id}', name: 'delete_product', methods:'DELETE')]
      #[OA\Response(
        response: 200,
        description: 'Suppression d\'un produit ',
        content: new Model(type: Product::class)
      )]
      #[OA\Tag(name: 'deleteProduct')]

      #[Security(name: 'BearerAuth')]
      public function deleteProduct($id): Response
    {
        $product=$this->product->find($id);

        $this->manager->remove($product);

        $this->manager->flush();

        return new JsonResponse(
            [
                'status'=>true,
                'response'=>'Produit supprimé avec succès'
            ]
            );
        
    }

      









}
