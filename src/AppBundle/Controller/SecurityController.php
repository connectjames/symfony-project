<?php

namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Form\LoginForm;
use AppBundle\Form\UserForgotPassword;
use AppBundle\Form\UserRegistrationForm;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;


class SecurityController extends Controller
{
    /**
     * @Route("/login", name="security_login")
     */
    public function loginAction(Request $request)
    {
        $formRegister = $this->createForm(UserRegistrationForm::class);

        $formRegister->handleRequest($request);

        if ($formRegister->isValid()) {

            // Invoice address creation
            $invoiceAddress = array("firstName" => $formRegister['firstName']->getData(), "lastName" => $formRegister['lastName']->getData(), "company" => $formRegister['company']->getData(), "address1" => $formRegister['address1']->getData(), "address2" => $formRegister['address2']->getData(), "city" => $formRegister['city']->getData(), "postcode" => $formRegister['postcode']->getData(), "phone" => $formRegister['phone']->getData());

            $user = new User();
            $user->setCreatedAt(new \DateTime("now"));
            $user->setFirstName($formRegister['firstName']->getData());
            $user->setLastName($formRegister['lastName']->getData());
            $user->setEmail($formRegister['email']->getData());
            $user->setInvoiceAddress(json_encode($invoiceAddress));
            $user->setPlainPassword($formRegister['plainPassword']->getData());
            $user->setNewsletter($formRegister['newsletter']->getData());

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            $message = \Swift_Message::newInstance()
                ->setSubject('Registration Complete')
                ->setFrom($this->getParameter('mailer_user'))
                ->setTo($user->getEmail())
                ->setBody(
                    $this->renderView(
                    // app/Resources/views/Emails/registration.html.twig
                        'email/newAccount.html.twig',
                        array(
                            'user' => $user
                        )

                    ),
                    'text/html'
                );
            $this->get('mailer')->send($message);

            $message = \Swift_Message::newInstance()
                ->setSubject('New user')
                ->setFrom($this->getParameter('mailer_user'))
                ->setTo($this->getParameter('mailer_user'))
                ->setBody('New user in la cabana boy!')
            ;

            $this->get('mailer')->send($message);

            $this->addFlash('success', 'Welcome '.$user->getEmail());

            return $this->get('security.authentication.guard_handler')
                ->authenticateUserAndHandleSuccess(
                    $user,
                    $request,
                    $this->get('app.security.login_form_authenticator'),
                    'main'
                );
        }

        $authenticationUtils = $this->get('security.authentication_utils');

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $formLogin = $this->createForm(LoginForm::class, [
            '_username' => $lastUsername,
        ]);

        // Makes the products, products qty and the basket total available to the view if a basket exists
        $session = $request->getSession();

        $basketProducts = $session->get('basketProducts');
        $basketQty = $session->get('basketQty');
        $basketTotal = $session->get('basketTotal');

        return $this->render(
            'security/login.html.twig',
            array(
                'formLogin' => $formLogin->createView(),
                'formRegister' => $formRegister->createView(),
                'error' => $error,
                'basketProducts' => $basketProducts,
                'basketQty' => $basketQty,
                'basketTotal' => $basketTotal
            )
        );
    }

    /**
     * @Route("/logout", name="security_logout")
     */
    public function logoutAction()
    {
        throw new \Exception('this should not be reached!');
    }

    /**
     * @Route("/retrieve-password/{email}/{token}", name="retrieve_password")
     */
    public function retrievePasswordAction($email, $token, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        if ($token) {

            /** @var User $user */
            $user = $em->getRepository('AppBundle:User')
                ->findOneBy(array(
                    'email' => $email,
                    'token' => $token
                ));

            $formForgotPassword = $this->createForm(UserForgotPassword::class);

            if ($user) {

                $formForgotPassword->handleRequest($request);

                if ($formForgotPassword->isValid()) {

                    $userPassword = $formForgotPassword->getData();

                    $userPassword = $userPassword["plainPassword"];

                    $user->setPlainPassword($userPassword);
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($user);
                    $em->flush();

                    $this->addFlash('success', 'Welcome back ' . $user->getFullName());

                    return $this->get('security.authentication.guard_handler')
                        ->authenticateUserAndHandleSuccess(
                            $user,
                            $request,
                            $this->get('app.security.login_form_authenticator'),
                            'main'
                        );
                }

                // Makes the products, products qty and the basket total available to the view if a basket exists
                $session = $request->getSession();

                $basketProducts = $session->get('basketProducts');
                $basketQty = $session->get('basketQty');
                $basketTotal = $session->get('basketTotal');

                return $this->render(
                    'security/newPassword.html.twig',
                    array(
                        'formForgotPassword' => $formForgotPassword->createView(),
                        'user' => $user,
                        'basketProducts' => $basketProducts,
                        'basketQty' => $basketQty,
                        'basketTotal' => $basketTotal
                    )
                );
            } else {
                $this->addFlash('danger', 'This email is not registered with us or the token has expired');
                return $this->redirectToRoute('security_login');
            }

        } else if ($email) {

            $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
            $newToken = '';
            $max = strlen($characters) - 1;
            for ($i = 0; $i < 100; $i++) {
                $newToken .= $characters[mt_rand(0, $max)];
            }

            /** @var User $user */
            $user = $em->getRepository('AppBundle:User')
                ->findOneBy(array(
                    'email' => $email
                ));

            if (!$user) {
                throw $this->createNotFoundException('email not found');
            }

            if ($user) {
                $user->setToken($newToken);
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                $message = \Swift_Message::newInstance()
                    ->setSubject('Set up new password')
                    ->setFrom('info@oxgn.co.uk')
                    ->setTo($email)
                    ->setBody(
                        $this->renderView(
                        // app/Resources/views/Emails/registration.html.twig
                            'email/password.html.twig',
                            array(
                                'token' => $newToken,
                                'email' => $email
                            )

                        ),
                        'text/html'
                    )
                    /*
                     * If you also want to include a plaintext version of the message
                    ->addPart(
                        $this->renderView(
                            'Emails/registration.txt.twig',
                            array('name' => $name)
                        ),
                        'text/plain'
                    )
                    */
                ;
                $this->get('mailer')->send($message);

                $this->addFlash('success', 'An email has been sent through to your email address, don\'t forget to check your junk email!');
                return $this->redirectToRoute('security_login');

            } else {

                $this->addFlash('info', 'This email is not registered with us');
                return $this->redirectToRoute('security_login');

            }

        } else {

            $this->addFlash('danger', 'Please enter an email to retrieve your password...');
            return $this->redirectToRoute('security_login');

        }
    }
}