<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\Email;
use App\Services\JwtAuth;
use App\Entity\User;



class UserController extends AbstractController
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

    public function register(Request $request){
        // Recoger datos
        $json = $request->get('json', null); 
        // Decodificar el json
        $params = json_decode($json);
        $params_array = json_decode($json, true);
        
        $data = array(
            'status' => 'error',
            'code'  => 400,
            'message' => 'El usuario no se ha creado'
        );
        if(!empty($params)){

            $name = !empty($params->name) ? $params->name : null;
            $surname = !empty($params->surname) ? $params->surname : null;
            $role = !empty($params->role) ? $params->role : null;
            $email = !empty($params->email) ? $params->email : null;
            $password = !empty($params->password) ? $params->password : null;
            $address = !empty($params->address) ? $params->address : null;
            $phone = !empty($params->phone) ? $params->phone : null;
            $district = !empty($params->district) ? $params->district : null;

            $validate = Validation::createValidator();
            $validate_email = $validate->validate($email, [
                new Email()
            ]);
            if(!empty($email) && count($validate_email) == 0 && !empty($password) && !empty($name) && !empty($surname)){

                $pwh = hash('sha256', $password);

                $user = new User;
                $user->setName($name);
                $user->setSurname($surname);
                $user->setEmail($email);
                $user->setRole($role);
                $user->setPassword($pwh);
                $user->setCreatedAt(new \Datetime('now'));
                $user->setUpdateAt(new \Datetime('now'));
                $user->setAddress($address);
                $user->setPhone($phone);
                $user->setDistrict($district);

                $doctrine = $this->getDoctrine();
                $em = $doctrine->getManager();
                $is_exist = $doctrine->getRepository(User::class)->findOneBy(array(
                    'email' => $email
                ));  

                if(empty($is_exist)){
                    $em->persist($user);
                    $em->flush();
                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Usuario creado correctamente',
                        'usuario' => $user
                    );                
                }
            }
        }
        return $this->resJson($data);
    }

    public function login(Request $request, JwtAuth $jwtAuth){
        $json = $request->get('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        $data = array(
            'status' => 'error',
            'code' => 400,
            'message' => 'Usuario o contrasena incorrecta'
        );

        if(!empty($params)){
            $email = !empty($params_array['email']) ? $params_array['email'] : null;
            $password = !empty($params_array['password']) ? $params_array['password'] : null;
            $get_token = !empty($params_array['getToken']) ? $params_array['getToken'] : null;

            $validate = Validation::createValidator();
            $validate_email = $validate->validate($email,[
                new Email()
            ]);       
            if(count($validate_email) == 0 && !empty($email) && !empty($password)){

                $pwh = hash('sha256', $password);

                if($get_token){
                    $data = $jwtAuth->signup($email, $pwh, $get_token);
                }else{
                    $data = $jwtAuth->signup($email, $pwh);
                }
            }

        }

        return new JsonResponse($data);
    }

    public function update(Request $request, JwtAuth $jwtAuth){

        $token = $request->headers->get('Authorization');
        $json = $request->get('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        $checkToken = $jwtAuth->checkToken($token);

        $data = array(
            'status' => $checkToken,
            'code' => 400,
            'message' => 'Error al modificar usuario'
        );
        if($checkToken && !empty($params)){

            $identity = $jwtAuth->checkToken($token, true);
            
            $name = !empty($params->name) ? $params->name : null;
            $surname = !empty($params->surname) ? $params->surname : null;
            $email = !empty($params->email) ? $params->email : null;
            $description = !empty($params->description) ? $params->description : null;
            $images = !empty($params->image) ? $params->image : null;
            $address = !empty($params->address) ? $params->address : null;
            $phone = !empty($params->phone) ? $params->phone : null;
            $district = !empty($params->district) ? $params->district : null;

            $is_exist_img = $this->getDoctrine()->getRepository(User::class)->findOneBy([
                'image' => $images
            ]);
            if(is_object($is_exist_img)){
                $image = $images;
            }else{
                $image = date("d-m-Y").$images;
            }                     
            
            $doctrine = $this->getDoctrine();
            $em = $doctrine->getManager();
            $user = $doctrine->getRepository(User::class)->findOneBy([
                'id' => $identity->sub
            ]);

            $validate = Validation::createValidator();
            $validate_email = $validate->validate($email,[
                new Email()
            ]);

            if(is_object($user) && count($validate_email) == 0){

                $user->setName($name);
                $user->setSurname($surname);
                $user->setEmail($email);
                $user->setDescription($description);
                $user->setImage($image);
                $user->setUpdateAt(new \Datetime('now'));
                $user->setAddress($address);
                $user->setPhone($phone);
                $user->setDistrict($district);
                
                $is_exist = $doctrine->getRepository(User::class)->findBy(array(
                    'email' => $email
                ));
                if(count($is_exist) == 0 || $identity->email == $email){
                    $em->persist($user);
                    $em->flush();
                    
                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Usuario actualizado',
                        'changes' => $user
                    );
                }                
            }           
        }       

        return $this->resJson($data);
    }

    public function getUsers($id)
    {
        $user = $this->getDoctrine()->getRepository(User::class)->findBy(array(
            'id' => $id
        ));

        if(!empty($user)){
            $data = array(
                'status' => 'success',
                'code' => 200,
                'user' => $user
            );
        }else{
            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'El usuario no existe'
            );
        }

        return $this->resJson($data);
    }

    public function upload(Request $request, JwtAuth $jwtAuth){

        $token = $request->headers->get('Authorization');
        $tokenCheck = $jwtAuth->checkToken($token);
        $image_name = $request->files->get('file0');
        if($image_name && $tokenCheck){
            $destination = $this->getParameter('kernel.project_dir').'/public/images/user';
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
        $destination = $this->getParameter('kernel.project_dir').'/public/images/user/'.$file;
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
}