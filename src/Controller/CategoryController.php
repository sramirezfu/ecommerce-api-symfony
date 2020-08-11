<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Services\JwtAuth;
use App\Entity\Category;


class CategoryController extends AbstractController
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

        $tokenCheck = $jwt_auth->checkToken($token);

        $data = array(
            'status' => 'error',
            'code' => 400,
            'message' => 'Error al crear categoria'
        );

        if($tokenCheck && !empty($params)){
            $name = !empty($params->name) ? $params->name : null;
            $category = new Category;

            $category->setName($name);
            $category->setCreatedAt(new \Datetime('now'));
            $category->setUpdateAt(new \Datetime('now'));

            $doctrine = $this->getDoctrine();
            $em = $doctrine->getManager();

            $em->persist($category);
            $em->flush();

            $data = array(
                'status' => 'success',
                'code' => 200,
                'message' => 'Categori creada correctamente',
                'category' => $category
            );
        }

        return $this->resJson($data);
    }

    public function update(Request $request, JwtAuth $jwt_auth, $id){

        $token = $request->headers->get('Authorization');
        $json = $request->get('json', null);
        
        $params = json_decode($json);
        $tokenCheck = $jwt_auth->checkToken($token);

        $data = array(
            'status' => 'error',
            'code' => 400,
            'message' => 'Error al modificar usuario'
        );
        
        $category = $this->getDoctrine()->getRepository(Category::class)->findOneBy([
            'id' => $id
        ]);

        if($tokenCheck && !empty($params) && !empty($category)){

            $name = !empty($params->name) ? $params->name : null;

            $category->setName($params->name);
            $category->setUpdateAt(new \Datetime('now'));            

            $doctrine = $this->getDoctrine();
            $em = $doctrine->getManager();

            $em->persist($category);
            $em->flush();

            $data = array(
                'status' => 'success',
                'code' => 200,
                'message' => 'Categoria modificada correctamente',
                'changes' => $category
            );
        }

        return $this->resJson($data);
    }

    public function destroy(Request $request, JwtAuth $jwt_auth, $id){

        $token = $request->headers->get('Authorization');
        $tokenCheck = $jwt_auth->checkToken($token);

        $category = $this->getDoctrine()->getRepository(Category::class)->findOneBy([
            'id' => $id
        ]);
        $data = array(
            'status' => 'error',
            'code' => 400,
            'message' => 'Error la categoria no se elimino'
        );
        if($tokenCheck && !empty($category)){

            $em = $this->getDoctrine()->getManager();
            $em->remove($category);
            $em->flush();

            $data = array(
                'status' => 'success',
                'code' => 200,
                'message' => 'La categoria se ha eliminado',
                'category' => $category
            );
        }

        return $this->resJson($data);
    }

    public function getCategory($id){

        $category = $this->getDoctrine()->getRepository(Category::class)->findOneBy([
            'id' => $id
        ]);

        if(!empty($category)){
            $data = [
                'status' => 'success',
                'code' => 200,
                'category' => $category
            ];
        }else{
            $data = [
                'status' => 'error',
                'code' => 400,
                'message' => 'La categoria no existe'
            ];
        }
        
        return $this->resJson($data);
    }

    public function getCategories(){

        $categories = $this->getDoctrine()->getRepository(Category::class)->findAll();

        if(!empty($categories)){
            $data = [
                'status' => 'success',
                'code' => 200,
                'categories' => $categories
            ];
        }else{
            $data = [
                'status' => 'error',
                'code' => 400,
                'message' => 'No existen categorias'
            ];
        }
        
        return $this->resJson($data);
    }
}
