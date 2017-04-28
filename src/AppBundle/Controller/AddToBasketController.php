<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Product;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class AddToBasketController extends Controller
{
    /**
     * @Route("/add-to-basket/{id}", name="add_to_basket")
     */
    public function addToBasketAction($id, Request $request)
    {
        $session = $request->getSession();

        // Makes the data of this product available to add to the basket
        $em = $this->getDoctrine()->getManager();

        /** @var Product $product */
        $product = $em->getRepository('AppBundle\Entity\Product')
            ->find($id);

        // If a basket exists already then...
        if (count($session->get('basketProducts'))) {

            $basketProducts = $session->get('basketProducts');

            $productPresent = false;

            // Checking if the product already exists in the basket and if yes, adds to it, if no then else
            for ($x = 0; $x <= (count($basketProducts) - 1); $x++) {
                if ($basketProducts[$x]["id"] == $product->getId()) {
                    $basketProducts[$x]["quantity"] += $request->query->get('quantity');
                    $basketProducts[$x]["total"] = $basketProducts[$x]["quantity"] * $basketProducts[$x]["price"];
                    $productPresent = true;
                }
            }

            if ($productPresent) {

                // The product was already in the basket and now that its qty is added, the basket can be put back in the session
                $session->set('basketProducts', $basketProducts);

            } else {

                // The product does not exist in the basket, adding a new array to the session
                $total = $product->getPrice() * floatval($request->query->get('quantity'));

                $newBasketProduct = array(
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'price' => floatval($product->getPrice()),
                    'quantity' => $request->query->get('quantity'),
                    'imageName' => $product->getImageName(),
                    'weight' => $product->getWeight(),
                    'total' => $total,
                    'url' => $product->getSlug()
                );

                $basketProducts[] = $newBasketProduct;

                $session->set('basketProducts', $basketProducts);
            }

        } else {
            // If a basket does not already exist then...

            $basketProducts = array(
                array(
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'price' => $product->getPrice(),
                    'quantity' => $request->query->get('quantity'),
                    'imageName' => $product->getImageName(),
                    'weight' => $product->getWeight(),
                    'total' => $product->getPrice(),
                    'url' => $product->getSlug()
                ),
            );

            $basketQty = $request->query->get('quantity');
            $basketTotal = $product->getPrice() * floatval($request->query->get('quantity'));

            $session->set('basketProducts', $basketProducts);
            $session->set('basketQty', $basketQty);
            $session->set('basketTotal', $basketTotal);

            return $this->render('topbasket/topbasket.html.twig', array(
                'basketProducts' => $basketProducts,
                'basketQty' => $basketQty,
                'basketTotal' => $basketTotal
            ));
        }

        // Adding now the products of the basket (array), the basket quantity and the basket total to the session
        $basketContent = $session->get('basketProducts');

        $basketProducts = [];
        $basketQty = 0;
        $basketTotal = 0;

        for ($x = 0; $x <= (count($basketContent) - 1); $x++) {
            $basketProducts[] = $basketContent[$x];
            $basketQty += $basketContent[$x]["quantity"];
            $basketTotal += $basketContent[$x]["price"] * floatval($basketContent[$x]["quantity"]);
        }

        $session->set('basketProducts', $basketProducts);
        $session->set('basketQty', $basketQty);
        $session->set('basketTotal', $basketTotal);

        return $this->render('topbasket/topbasket.html.twig', array(
            'basketProducts' => $basketProducts,
            'basketQty' => $basketQty,
            'basketTotal' => $basketTotal
        ));
    }

    /**
     * @Route("/add-to-basket/in-basket/{id}", name="add_to_basket_in_basket")
     */
    public function addToBasketInBasketAction($id, Request $request)
    {
        $session = $request->getSession();

        // Makes the data of this product available to add to the basket
        $em = $this->getDoctrine()->getManager();

        /** @var Product $product */
        $product = $em->getRepository('AppBundle\Entity\Product')
            ->find($id);

        $basketProducts = $session->get('basketProducts');

        // Checking if the product already exists in the basket and if yes, adds to it, if no then else
        for ($x = 0; $x <= (count($basketProducts) - 1); $x++) {
            if ($basketProducts[$x]["id"] == $product->getId()) {
                $basketProducts[$x]["quantity"] = $request->query->get('quantity');
                $basketProducts[$x]["total"] = $basketProducts[$x]["quantity"] * $basketProducts[$x]["price"];
            }
        }

        // The product was already in the basket and now that its qty is added, the basket can be put back in the session
        $session->set('basketProducts', $basketProducts);

        // Adding now the products of the basket (array), the basket quantity and the basket total to the session
        $basketContent = $session->get('basketProducts');

        $basketProducts = [];
        $basketQty = 0;
        $basketTotal = 0;

        for ($x = 0; $x <= (count($basketContent) - 1); $x++) {
            $basketProducts[] = $basketContent[$x];
            $basketQty = $basketContent[$x]["quantity"];
            $basketTotal += $basketContent[$x]["price"] * floatval($basketContent[$x]["quantity"]);
        }

        $session->set('basketProducts', $basketProducts);
        $session->set('basketQty', $basketQty);
        $session->set('basketTotal', $basketTotal);

        if ($session->get('delivery')) {
            $response = $this->forward('AppBundle:DeliveryCalculator:deliveryCalculator');
            return $response;
        }

        return $this->redirectToRoute('basket');
    }
}