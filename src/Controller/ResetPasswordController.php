<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\User;
use App\Entity\ResetPassword;
use App\Form\ResetPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ResetPasswordController extends AbstractController
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/mot-de-passe-oublie", name="forgetted_password")
     */
    public function index(Request $request)
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        if ($request->get('email')) {
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($request->get('email'));

            if ($user) {
                $resetPassword = new ResetPassword();
                $resetPassword->setUser($user)
                    ->setToken(uniqid())
                    ->setCreatedAt(new \DateTime());

                $this->entityManager->persist($resetPassword);
                $this->entityManager->flush();

                $content = sprintf(
                    "Bonjour %s,<br> Afin de réinialiser votre mot de passe, merci de bien vouloir cliquer sur le lien suivant :<br> <a href='%s'>Réinitialiser votre mot de passe</a>",
                    $user->getFirstName(),
                    $this->generateUrl('reset_password', ['token' => $resetPassword->getToken()])
                );
                (new Mail())->send($user->getEmail(), sprintf('%s %s', $user->getFirstName(), $user->getLastName()), 'Réinialiser votre mot de passe', $content);
                $this->addFlash("notice", "Un email vient de vous être envoyé !");
            } else {
                $this->addFlash("notice", "Cette adresse email n'existe pas !");
            }
        }

        return $this->render('reset_password/index.html.twig');
    }

    /**
     * @Route("/modifier-mot-de-passe/{token}", name="reset_password")
     */
    public function reset($token, Request $request, UserPasswordEncoderInterface $encoder)
    {
        $resetPassword = $this->entityManager->getRepository(ResetPassword::class)->findOneByToken($token);

        if (!$resetPassword) {
            return $this->redirectToRoute('forgetted_password');
        } else {
            if ($resetPassword->getCreatedAt()->modify('+ 30 minute') < (new \DateTime())) {
                $this->addFlash("notice", "Votre demande de mot de passe a expiré. Merci de la renouveler");
                return $this->redirectToRoute('forgetted_password');
            }
        }

        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $newPassword = $form->get('new_password')->getData();
            
            $resetPassword->getUser()->setPassword(
                $encoder->encodePassword(
                    $resetPassword->getUser(),
                    $newPassword
                )
            );
            // $this->entityManager->persist($user); //n'est pas utile pour une MàJ
            $this->entityManager->flush();

            $this->addFlash('notice', 'Votre mot de passe a bien été mis à jour !');
            return $this->redirectToRoute('app_login');
        }


        return $this->render('reset_password/reset.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
