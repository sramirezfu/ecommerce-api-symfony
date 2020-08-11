<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Knp\Component\Pager\PaginatorInterface;
use App\Services\JwtAuth;
use App\Entity\Product;
use App\Entity\User;
use App\Entity\Category;

class ProductController extends AbstractController
{   

     // Metodo para devolver json
     public function resJson($data){

        // Serializar datos con servicio serealizer
        $json = $this->get('serializer')->serialize($data, 'json');

        // Response con httpfoundation
        $response = new response();

        // Asignar contenido a la respuesta.
        $response->setContent($json);

        // Indicar formato de respuesta.
        $response->headers->set('Content-Type', 'application/json');

        // Devolver respuesta
        return $response;

    }

    public function create(Request $request, JwtAuth $jwt_auth){

        $token = $request->headers->get('Authorization');
        $json = $request->get('json', null);

        $params = json_decode($json);
        $checkToken = $jwt_auth->checkToken($token);

        $data = array(
            'status' => 'error',
            'code' => 400,
            'message' => 'Error al crear el producto'
        );

        if($checkToken && !empty($params)){

            $identity = $jwt_auth->checkToken($token, true);

            $user_id = $identity->sub;
            $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
                'id' => $user_id
            ]);
            $category_id = !empty($params->category_id) ? $params->category_id : null;
            $category = $this->getDoctrine()->getRepository(Category::class)->findOneBy([
                'id' => $category_id
            ]);
            $name = !empty($params->name) ? $params->name : null;
            $description = !empty($params->description) ? $params->description : null;
            $images = !empty($params->image) ? $params->image : null;
            $image = date("d-m-Y").$images; 
            $status = !empty($params->status) ? $params->status : null;
            $price = !empty($params->price) ? $params->price : null;
            $stock = !empty($params->stock) ? $params->stock : null;
            
            $product = new Product;

            $product->setUser($user);
            $product->setCategory($category);
            $product->setName($name);
            $product->setDescription($description);
            $product->setImage($image);
            $product->setStatus($status);
            $product->setPrice($price);
            $product->setStock($stock);
            $product->setCreatedAt(new \Datetime('now'));
            $product->setUpdateAt(new \Datetime('now'));

            $doctrine = $this->getDoctrine();
            $em = $doctrine->getManager();

            $em->persist($product);
            $em->flush();

            $data = array(
                'status' => 'success',
                'code' => 200,
                'message' => 'El post se creo correctamente',
                'product' => $product
            ); 

        }

        return $this->json($data);
    }

    public function update(Request $request, JwtAuth $jwt_auth, $id){

        $token = $request->headers->get('Authorization');
        $json = $request->get('json', null);

        $checkToken = $jwt_auth->checkToken($token);
        $params = json_decode($json);

        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'error al modificar el producto'
        ];

        if($checkToken && !empty($params)){
            
            $identity = $jwt_auth->checkToken($token, true);
            $doctrine = $this->getDoctrine();
            $product = $doctrine->getRepository(Product::class)->findOneBy([
                'id' => $id,
                'user' => $identity->sub
            ]);

            if($identity && !empty($product)){
                
                $category_id = !empty($params->category_id) ? $params->category_id : null;
                $category = $this->getDoctrine()->getRepository(Category::class)->findOneBy([
                    'id' => $category_id
                ]);
                $name = !empty($params->name) ? $params->name : null;
                $description = !empty($params->description) ? $params->description : null;
                $images = !empty($params->image) ? $params->image : null;                
                $status = !empty($params->status) ? $params->status : null;
                $price = !empty($params->price) ? $params->price : null;
                $stock = !empty($params->stock) ? $params->stock : null;
                
                $is_exist_img = $this->getDoctrine()->getRepository(Product::class)->findOneBy([
                    'image' => $images
                ]);
                if(is_object($is_exist_img)){
                    $image = $images;
                }else{
                    $image = date("d-m-Y").$images;
                }
                
                $product->setCategory($category);
                $product->setName($name);
                $product->setDescription($description);
                $product->setImage($image);
                $product->setStatus($status);
                $product->setPrice($price);
                $product->setStock($stock);
                $product->setUpdateAt(new \Datetime('now'));

                $em = $doctrine->getManager();
                $em->persist($product);
                $em->flush();

                $data = [
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Producto actualizado',
                    'changes' => $product
                ];
            }
        }

        return $this->resJson($data);
    }
    
    public function destroy(Request $request, JwtAuth $jwt_auth, $id){

        $token = $request->headers->get('Authorization');
        $checkToken = $jwt_auth->checkToken($token);

        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'error al modificar el producto'
        ];

        if($checkToken){

            $identity = $jwt_auth->checkToken($token, true);
            $product = $this->getDoctrine()->getRepository(Product::class)->findOneBy([
                'id' => $id,
                'user' => $identity->sub
            ]);
            
            if($identity && !empty($product)){

                $em = $this->getDoctrine()->getManager();
                $em->remove($product);
                $em->flush();

                $data = [
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'El producto se elimino',
                    'producto' => $product
                ];

            }

        }

        return $this->resJson($data);
    }

    public function getProduct($id){

        $product = $this->getDoctrine()->getRepository(Product::class)->findOneBy([
            'id' => $id
        ]);

        if($product){

            $data = [
                'status' => 'success',
                'code' => 200,
                'product' => $product
            ];

        }else{
            $data = [
                'status' => 'error',
                'code' => 400,
                'message' => 'No existe el producto'
            ];
        }

        return $this->resJson($data);
    }

    public function getProducts(Request $request, PaginatorInterface $paginator){

        $page = $request->query->getInt('page', 1);
        $type = $request->query->get('type', 'all');
        if($type == 'all'){
            $products = $this->getDoctrine()->getRepository(Product::class)->findAll();
        }else{                           
            $products = $this->getDoctrine()->getRepository(Product::class)->findBy([
                'status' => $type
            ]);       
        }
        $items_per_page = 6;
        $pagination = $paginator->paginate($products, $page, $items_per_page);
        $total = $pagination->getTotalItemCount();

        if($products){

            $data = [
                'status' => 'success',
                'code' => 200,
                'total_items_count' => $total,
                'page_actual' => $page,
                'items_per_page' => $items_per_page,
                'total_pages' => ceil($total / $items_per_page),
                'products' => $pagination
            ];

        }else{

            $data = [
                'status' => 'error',
                'code' => 400,
                'message' => 'No existe el producto'
            ];

        }

        return $this->resJson($data);
    }

    public function getProductsByCategory(Request $request, PaginatorInterface $paginator, $id){

        $products = $this->getDoctrine()->getRepository(Product::class)->findBy([
            'category' => $id
        ]);

        $page = $request->query->getInt('page', 1);
        $items_per_page = 6;
        $pagination = $paginator->paginate($products, $page, $items_per_page);
        $total = $pagination->getTotalItemCount();

        if(!empty($products)){
            $data = [
                'status' => 'success',
                'code' => 200,
                'total_items_count' => $total,
                'page_actual' => $page,
                'items_per_page' => $items_per_page,
                'total_pages' => ceil($total / $items_per_page),
                'products' => $pagination
            ];
        }else{
            $data = [
                'status' => 'error',
                'code' => 400,
                'message' => 'No existe el producto'
            ];
        }

        return $this->resJson($data);

    }

    public function getProductsByUser(Request $request, PaginatorInterface $paginator, $id){

        $products = $this->getDoctrine()->getRepository(Product::class)->findBy([
            'user' => $id
        ]);

        $page = $request->query->getInt('page', 1);
        $items_per_page = 6;
        $pagination = $paginator->paginate($products, $page, $items_per_page);
        $total = $pagination->getTotalItemCount();

        if(!empty($products)){
            $data = [
                'status' => 'success',
                'code' => 200,
                'total_items_count' => $total,
                'page_actual' => $page,
                'items_per_page' => $items_per_page,
                'total_pages' => ceil($total / $items_per_page),
                'products' => $pagination
            ];
        }else{
            $data = [
                'status' => 'error',
                'code' => 400,
                'message' => 'El usuario no tiene productos'
            ];
        }

        return $this->resJson($data);
    }

    public function upload(Request $request, JwtAuth $jwtAuth){

        $token = $request->headers->get('Authorization');
        $tokenCheck = $jwtAuth->checkToken($token);
        $image_name = $request->files->get('file0');
        if($image_name && $tokenCheck){
            $destination = $this->getParameter('kernel.project_dir').'/public/images/product';
            $image_name->move($destination, date("d-m-Y").$image_name->getClientOriginalName());
            $data = array(
                'status' => 'success',
                'code' => 200,
                'image' => $image_name->getClientOriginalName()
            );
        }else{
            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'error al guardar imagen'  
            );
        }
        
        return $this->resJson($data);
    }

    public function getImages($file){

        // ruta completa del archivo.
        $destination = $this->getParameter('kernel.project_dir').'/public/images/product/'.$file;
        // verificar si el archivo existe.
        $file_exist = file_exists($destination);
        if($file_exist){
            $file_final = file_get_contents($destination);

            return new Response($file_final);
        }else{
            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'La imagen no existe'
            );

            return $this->resJson($data);
        }       
    }

    public function searchProduct(Request $request, PaginatorInterface $paginator, $letter){
        
        $em = $this->getDoctrine()->getManager();
        $dql = "SELECT p FROM App\Entity\Product p  WHERE p.name LIKE '%{$letter}%'";
        $query = $em->createQuery($dql);
        $products = $query->getResult();

        $page = $request->query->get('page', 1);
        $items_per_page = 6;
        $pagination = $paginator->paginate($products, $page, $items_per_page);
        $total = $pagination->getTotalItemCount();

        if(!empty($products)){
            $data = [
                'status' => 'success',
                'code' => 200,
                'total_items_count' => $total,
                'page_actual' => $page,
                'items_per_page' => $items_per_page,
                'total_pages' => ceil($total / $items_per_page),
                'products' => $pagination
            ];
        }else{
            $data = [
                'status' => 'error',
                'code' => 400,
                'message' => 'No existe el producto'
            ];
        }

        return $this->resJson($data);
    }

}
