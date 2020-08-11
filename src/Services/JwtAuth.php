<?php

namespace App\Services;
use Firebase\JWT\JWT;
use App\Entity\User;

class JwtAuth{

    public $manager;
    public $key;

    public function __construct ($manager){
        $this->manager = $manager;
        $this->key = 'clave_secreta_para_token1234567';
    }
    public function signup ($email, $password, $getToken = null) {
        // Comprobar si el usuario existe.
        $user = $this->manager->getRepository(User::class)->findOneBy([
            'email' => $email,
            'password' => $password
        ]);
        
        $signup = false;
        if(is_object($user)){
            $signup = true;
        }
        // Si existe el usuario generar el token.
        if($signup){
            $token = [
                'sub' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'description' => $user->getDescription(),                
                'image' => $user->getImage(),
                'role' => $user->getRole(),
                'iat' => time(),
                'exp' => time() + (7 * 24 * 60 * 60)
            ];
            $jwt = JWT::encode($token, $this->key, 'HS256');
            $decode = JWT::decode($jwt, $this->key, ['HS256']);
             // Devolver los datos decodificados o el token, en funcion de un parametro.
            if(is_null($getToken)){
                $data = $decode;                
            }else{
                $data = $jwt;
            }
        }else{
            $data = array(
                'status' => 'error',
                'code' => 400,
                'message' => 'Datos incorrectos'
            );
        }
        return $data;
    }
    // Funcion para checkear token
    public function checkToken ($jwt, $identity = false){
        $auth = false;
       
        try{
            $jwt = str_replace('"','',$jwt);
            $decoded = JWT::decode($jwt, $this->key, ['HS256']);
        }catch(\UnexpectedValueException $e){
            $auth = false;
        }catch(\DomainException $e){
            $auth = false;
        }
        if(!empty($decoded) && isset($decoded->sub) && is_object($decoded)){
            $auth = true;
        }else{
            $auth = false;
        }
        if($identity){
            $auth = $decoded;
        }
        return $auth;
    }
}