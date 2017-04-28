<?php

namespace AppBundle\Service;

use AppBundle\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class Basket extends Controller
{

    public function add($data, Request $request)
    {
        $session = $request->getSession();

        // Makes the data of this product available to add to the basket
        $em = $this->getDoctrine()->getManager();

        /** @var Product $product */
        $product = $em->getRepository('AppBundle\Entity\Product')
            ->find($data["product"]);

        // If a basket exists already then...
        if (count(json_decode($session->get('basketProducts'), true)) > 1) {

            $basketJson = json_decode($session->get('basketProducts'), true);

            $productPresent = false;

            // Checking if the product already exists in the basket and if yes, adds to it, if no then else
            for ($x = 0; $x <= (count($basketJson) - 2); $x++) {
                if ($basketJson[$x]["id"] == $product->getId()) {
                    $basketJson[$x]["quantity"] += intval($data["quantity"]);
                    $basketJson[$x]["total"] = $basketJson[$x]["quantity"] * $basketJson[$x]["price"];
                    $productPresent = true;
                }
            }

            if ($productPresent) {

                // The product was already in the basket and now that its qty is added, the basket can be put back in the session
                $basketJson = json_encode($basketJson);
                $session->set('basketProducts', $basketJson);

            } else {

                // The product does not exist in the basket, adding a new array to the session
                $total = intval($data["quantity"]) * floatval($product->getPrice());

                $newBasketJson = array(
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'price' => floatval($product->getPrice()),
                    'quantity' => intval($data["quantity"]),
                    'image'  => $product->getImage(),
                    'weight' => $product->getWeight(),
                    'total'  => floatval($total),
                );

                $basketJson[] = $newBasketJson;

                $basketJson = json_encode($basketJson);

                $session->set('basket', $basketJson);
            }

        } else {
            // If a basket does not already exist then...
            $basketJson = array(
                'productId' => array(
                    'id'  => "id here",
                    'name'  => "name here",
                    'price'  => "price here",
                    'quantity'  => "quantity here",
                    'image'  => "image here",
                    'weight'  => "weight here",
                    'total'  => "total here",
                )
            );

            $total = intval($data["quantity"]) * floatval($product->getPrice());

            $newBasketJson = array(
                'id'  => $product->getId(),
                'name'  => $product->getName(),
                'price'  => floatval($product->getPrice()),
                'quantity'  => intval($data["quantity"]),
                'image'  => $product->getImage(),
                'weight' => $product->getWeight(),
                'total'  => floatval($total),
            );

            $basketJson[] = $newBasketJson;

            $session->set('basketProducts', json_encode($basketJson));

        }

        // Adding now the products of the basket (array), the basket quantity and the basket total to the session
        $basketContent = json_decode($session->get('basket'), true);

        $basketProducts = [];
        $basketQty = 0;
        $basketTotal = 0;

        for ($x = 0; $x <= (count($basketContent) - 2); $x++) {
            $basketProducts[] = $basketContent[$x];
            $basketQty += $basketContent[$x]["quantity"];
            $basketTotal += $basketContent[$x]["price"] * $basketContent[$x]["quantity"];
        }

        $session->set('basketProducts', json_encode($basketProducts));

        $session->set('basketQty', $basketQty);

        $session->set('basketTotal', $basketTotal);

        return;

    }

}