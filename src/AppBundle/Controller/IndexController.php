<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Product;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class IndexController extends Controller
{
    /**
     * @Route("/", name="index")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        // Find the featured products and their details

        /** @var Product $featuredProducts */
        $featuredProducts = $em->getRepository('AppBundle:Product')->findBy(
            array('featured' => 1)
        );

        // Makes the products, products qty and the basket total available to the view if a basket exists
        $session = $request->getSession();

        $basketProducts = $session->get('basketProducts');
        $basketQty = $session->get('basketQty');
        $basketTotal = $session->get('basketTotal');

        return $this->render('index/index.html.twig', array(
            'featuredProducts' => $featuredProducts,
            'basketProducts' => $basketProducts,
            'basketQty' => $basketQty,
            'basketTotal' => $basketTotal

        ));
    }

}


