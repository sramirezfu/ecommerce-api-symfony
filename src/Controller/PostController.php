<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Knp\Component\Pager\PaginatorInterface;
use App\Services\JwtAuth;
use App\Entity\Post;
use App\Entity\User;
use App\Entity\Category;

class PostController extends AbstractController
{   
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
        $params_array = json_decode($json);
        $tokenCheck = $jwt_auth->checkToken($token);
        
        $data = array(
            'status' => 'error',
            'code' => 400,
            'message' => 'Error al crear el articulo' 
        );

        if($tokenCheck && !empty($params)){

            $identity = $jwt_auth->checkToken($token, true);
            $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
                'id' => $identity->sub
            ]);
            $category = !empty($params->category_id) ? $params->category_id : null;
            $category_id = $this->getDoctrine()->getRepository(Category::class)->findOneBy([
                'id' => $category
            ]);
            $title = !empty($params->title) ? $params->title : null;
            $content = !empty($params->content) ? $params->content : null;
            $url = !empty($params->url) ? $params->url : null;
            $status = !empty($params->status) ? $params->status : null;

            $post = new Post;
            $post->setUser($user);
            $post->setCategory($category_id);
            $post->setTitle($title);
            $post->setContent($content);
            $post->setUrl($url);
            $post->setStatus($status);
            $post->setCreatedAt(new \Datetime('now'));
            $post->setUpdateAt(new \Datetime('now'));

            $doctrine = $this->getDoctrine();
            $em = $doctrine->getManager();

            $em->persist($post);
            $em->flush();

            $data = array(
                'status' => 'success',
                'code' => 200,
                'message' => 'Articulo creado',
                'post' => $post
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
            'message' => 'Error al actualizar el articulo'
        );

        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();
        $post = $doctrine->getRepository(Post::class)->findOneBy(array(
            'id' => $id
        ));
    
        if($tokenCheck && $params && !empty($post)){

            $category_id = !empty($params->category_id) ? $params->category_id : null;
            $category = $doctrine->getRepository(Category::class)->findOneBY([
                'id' => $category_id
            ]);
            $title = !empty($params->title) ? $params->title : null;
            $content = !empty($params->content) ? $params->content : null;
            $url = !empty($params->url) ? $params->url : null;
            $status = !empty($params->status) ? $params->status : null;

            $post->setCategory($category);
            $post->setTitle($title);
            $post->setContent($content);
            $post->setUrl($url);
            $post->setStatus($status);
            $post->setUpdateAt(new \Datetime('now'));
            
            $em->persist($post);
            $em->flush();

            $data = array(
                'status' => 'success',
                'code' => 200,
                'message' => 'Articulo actualizado correctamente',
                'changes' => $post
            );
        }

        return $this->resJson($data);
    }

    public function destroy(Request $request, JwtAuth $jwt_auth, $id){

        $token = $request->headers->get('Authorization');

        $tokenCheck = $jwt_auth->checkToken($token);

        $data = array(
            'status' => 'error',
            'code' => 400,
            'message' => 'Error al eliminar el post'
        );

        $doctrine = $this->getDoctrine();

        $post = $doctrine->getRepository(Post::class)->findOneBy([
            'id' => $id
        ]);

        if($tokenCheck && !empty($post)){
            
            $em =  $doctrine->getManager();
            $em->remove($post);
            $em->flush();

            $data = array(
                'status' => 'success',
                'code' => 200,
                'message' => 'Se elimino el post',
                'post' => $post
            );
        }

        return $this->resJson($data);
    }

    public function getPost($id){

        $post = $this->getDoctrine()->getRepository(Post::class)->findOneBy([
            'id' =>$id
        ]);

        if(!empty($post)){

            $data = array(
                'status' => 'success',
                'code' => 200,
                'post' => $post
            );

        }else{

            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'El post no existe'
            );
        }

        return $this->resJson($data);
    }

    public function getPosts(Request $request, PaginatorInterface $paginator){

        $posts = $this->getDoctrine()->getRepository(Post::class)->findAll();

        $page = $request->query->getInt('page', 1);
        $items_per_page = 6;
        // Invocar la paginacion.
        $pagination = $paginator->paginate($posts, $page, $items_per_page);
        $total = $pagination->getTotalItemCount();        
        if(count($posts) > 0){

            $data = array(
                'status' => 'success',
                'code' => 200,
                'total_items_count' => $total,
                'page_actual' => $page,
                'items_per_page' => $items_per_page,
                'total_pages' => ceil($total / $items_per_page),
                'posts' => $pagination
            );

        }else{

            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'No existen posts'
            );
        }

        return $this->resJson($data);
    }

    public function getPostsByCategory(Request $request, PaginatorInterface $paginator, $id){
        
        $posts = $this->getDoctrine()->getRepository(Post::class)->findBy([
            'category' => $id
        ]);

        $page = $request->query->getInt('page',1);
        $items_per_page = 6;
        $pagination = $paginator->paginate($posts, $page, $items_per_page);
        $total = $pagination->getTotalItemCount();

        if(count($posts) > 0){

            $data = array(
                'status' => 'success',
                'code' => 200,
                'total_items_count' => $total,
                'page_actual' => $page,
                'items_per_page' => $items_per_page,
                'total_pages' => ceil($total / $items_per_page),
                'posts' => $pagination
            );

        }else{

            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'No existen posts'
            );
        }

        return $this->resJson($data);
    }

    public function getPostsByUser(Request $request, PaginatorInterface $paginator, $id){


        $posts = $this->getDoctrine()->getRepository(Post::class)->findBy([
            'user' => $id
        ]);

        $items_per_page = 6;
        $page = $request->query->getInt('page', 1);
        $pagination = $paginator->paginate($posts, $page, $items_per_page);
        $total = $pagination->getTotalItemCount();

        if(count($posts) > 0){

            $data = array(
                'status' => 'success',
                'code' => 200,
                'total_items_count' => $total,
                'page_actual' => $page,
                'items_per_page' => $items_per_page,
                'total_pages' => ceil($total / $items_per_page),
                'posts' => $pagination
            );

        }else{

            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'No existen posts'
            );
        }

        return $this->resJson($data);
    }
}
