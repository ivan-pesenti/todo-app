<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    // GET: api/users
    public function getAll(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository
                    ->findAll();

        return $this->json($users);
    }

    // GET: api/users?numChars={numChars:int}
    public function getAllLongerThan(Request $request, UserRepository $userRepository) : JsonResponse
    {
        $numChars = $request->query->get('numChars');

        $users = $userRepository
                    ->findAllLastNameLongerThan($numChars);

        return $this->json($users);
    }
    
    // GET: api/users?numChars={role:string&isSortedAsc}
    public function getAllByRole(Request $request, UserRepository $userRepository) : JsonResponse
    {
        $role = $request->query->get('role');
        $isSortedAsc = $request->query->get('isSortedAsc') ?? 1;

        $users = $userRepository
                    ->findAllByRole($role, $isSortedAsc);

        return $this->json($users);
    }

    // GET: api/users/{id:int}
    public function getById(int $id): JsonResponse
    {
        // plain implementation (without repository)
        $user = $this->getDoctrine()
                    ->getRepository(User::class)
                    ->find($id);

        if(!$user) {
            throw new NotFoundHttpException("The user with id {$id} has not been found in db!");
        }

        return $this->json($user);
    }

    // POST: api/users
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        // get payload data
        $parameters = json_decode($request->getContent(), true);
        
        $entityManager = $this->getDoctrine()->getManager();
        $user = new User();
        $user->setFirstName($parameters['firstName'] ?? '');
        $user->setLastName($parameters['lastName'] ?? '');
        $user->setEmail($parameters['email'] ?? '');
        $user->setRole($parameters['role'] ?? '');
        
        // check if the input is correct
        $errors = $validator->validate($user);
        if(count($errors) > 0){
            throw new BadRequestException((string)$errors);
        }
        
        $entityManager->persist($user);
        $entityManager->flush();
        
        return $this->json(['message' => "The user with id {$user->getId()} has been saved to db!"]);
    }
    
    // PUT: api/users/{id:int}
    public function updateById(int $id, Request $request, UserRepository $userRepository) : JsonResponse
    {
        $user = $userRepository
                ->find($id);
        
        if(!$user){
            throw new NotFoundHttpException("The user with id {$id} has not been found in db!");
        }
        
        $parameters = json_decode($request->getContent(), true);
        // update only fields present
        if(array_key_exists('firstName', $parameters))
            $user->setFirstName($parameters['firstName']);
        if(array_key_exists('lastName', $parameters))
            $user->setLastName($parameters['lastName']);
        if(array_key_exists('email', $parameters))
            $user->setEmail($parameters['email']);
        if(array_key_exists('role', $parameters))
            $user->setEmail($parameters['role']);
        
        $this->getDoctrine()->getManager()->flush();
        
        return $this->json($user);
    }
    
    // DELETE: api/users/{id:int}
    public function removeById(int $id, UserRepository $userRepository) : JsonResponse
    {
        $user = $userRepository
        ->find($id);
        
        if(!$user){
            throw new NotFoundHttpException("The user with id {$id} has not been found in db!");
        }

        $this->getDoctrine()->getManager()->remove($user);
        $this->getDoctrine()->getManager()->flush();
        
        return $this->json('', Response::HTTP_NO_CONTENT);
    }
}
