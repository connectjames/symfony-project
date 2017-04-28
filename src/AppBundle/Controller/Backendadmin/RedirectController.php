<?php

namespace AppBundle\Controller\Backendadmin;

use AppBundle\Entity\Redirect;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
/**
 * @Route("/admin")
 * @Security("is_granted('ROLE_ADMIN')")
 */

class RedirectController extends Controller
{
    /**
     * @Route("/redirects", name="redirects")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $queryBuilder = $em->getRepository('AppBundle:Redirect')->createQueryBuilder('red');

        $query = $queryBuilder->getQuery();

        /**
         * @var $paginator \Knp\Component\Pager\Paginator
         */
        $paginator = $this->get('knp_paginator');

        $redirect = $paginator->paginate(
            $query,
            1,
            30,
            array('defaultSortFieldName' => 'red.lastAccessed', 'defaultSortDirection' => 'desc')
        );

        return $this->render('backendadmin/redirect.html.twig', array(
            'redirects' => $redirect
        ));
    }

    /**
     * @Route("/redirects/view/{id}", name="redirects_view")
     */
    public function redirectsViewAction($id, Request $request)
    {

    }

    /**
     * @Route("/redirects/new", name="redirects_new")
     */
    public function redirectsNewAction(Request $request)
    {
        $redirect = new Redirect();
        $formNewRedirect = $this->createForm('zenstruck_redirect', $redirect);

        return $this->render('backendadmin/newRedirect.html.twig', array(
            'formCheckoutDeliveryForm' => $formNewRedirect->createView()
        ));
    }

    /**
     * @Route("/redirects/edit/{id}", name="redirects_edit")
     */
    public function redirectsEditAction($id, Request $request)
    {

    }

    /**
     * @Route("/redirects/delete/{id}", name="redirects_delete")
     */
    public function redirectsDeleteAction($id)
    {

    }
}

