<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\Email;
use App\Entity\Subscriber;

class SuscriberController extends AbstractController
{

    public function subscribe(Request $request, \Swift_Mailer $mailer){

        $json = $request->get('json', null);
        $params = json_decode($json);

        $data = array(
            'status' => 'error',
            'code' => 400,
            'message' => 'Error al suscribirse'
        );

        if($params){
            $email = !empty($params->email) ? $params->email : null;
            $name = !empty($params->name) ? $params->name : null;
            $validate = Validation::createValidator();
            $validate_email = $validate->validate($email, [
                new Email()
            ]);

            $is_exits = $this->getDoctrine()->getRepository(Subscriber::class)->findOneBy([
                'email' => $email
            ]);

            if(count($validate_email) == 0 && !empty($email) && empty($is_exits)){                
                
                $user = new Subscriber;
                $user->setName($name); 
                $user->setEmail($email);         
                $user->setCreatedAt(new \Datetime('now'));
                $user->setUpdateAt(new \Datetime('now'));                      

                $doctrine = $this->getDoctrine();
                $em = $doctrine->getManager();
                $em->persist($user);
                $em->flush();
                
                // Enviar email.
                $message = (new \Swift_Message('Hello Email'))
                    ->setFrom('sramirezfu@gmail.com')
                    ->setTo($email)
                    ->setBody(
                    $this->renderView(
                        // templates/emails/registration.html.twig
                        'emails/registration.html.twig',
                        ['name' => $name]
                        ),
                        'text/html'
                        )
                        // you can remove the following code if you don't define a text version for your emails
                        ->addPart($name
                    );
                $mailer->send($message);

                $data = array(
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Se ha suscribido correctamente',
                    'usuario' => $user
                );
            }
        }        

        return $this->json($data);
    }

    public function getUserSubscribe(){

        $users = $this->getDoctrine()->getRepository(Subscriber::class)->findAll();

        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'No existen usuarios subscritos'
        ];

        if($users){

            $data = [
                'status' => 'success',
                'code' => 200,
                'users' => $users
            ];

        }

        return $this->json($data);
    }
}
