<?php

namespace AppBundle\Service;

use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class Delivery extends Controller
{

    public function updateWithoutUser($deliveryName, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        // Find the products in the basket
        $session = $request->getSession();
        $basketProducts = json_decode($session->get('basketProducts'), true);

        // Calculate the total weight of the basket
        $totalWeight = 0;
        for ($x = 0; $x <= (count($basketProducts) - 1); $x++) {
            $totalWeight += $basketProducts[$x]["weight"];
        }

        // Find the county used in the basket

        /** @var County $county */
        $county = $em->getRepository('AppBundle:User')
            ->find($deliveryName);

        $deliveryAmount = 0;

        // Calculate the total shipping amount depending on the county of the user's delivery address
        foreach ($county->getDelivery()->getAmount() as $x => $x_value) {
            if ($totalWeight <= intval($x)) {
                $deliveryAmount = $x_value;
                break;
            }
        }

        $session->set('delivery', array($deliveryName, $deliveryAmount));

        return;

    }

    public function updateWithUser($user, Request $request)
    {
        // Find the products in the basket
        $session = $request->getSession();
        $basketProducts = json_decode($session->get('basketProducts'), true);

        // Calculate the total weight of the basket
        $totalWeight = 0;
        for ($x = 0; $x <= (count($basketProducts) - 1); $x++) {
            $totalWeight += $basketProducts[$x]["weight"];
        }

        $deliveryAmount = 0;

        /** @var User $user */
        $user = $this->getUser();

        // Calculate the total shipping amount depending on the county of the user's delivery address
        foreach ($user->getDelivery()->getAmount() as $x => $x_value) {
            if ($totalWeight <= intval($x)) {
                $deliveryAmount = $x_value;
                break;
            }
        }

        // Find the Id of the user's county, this id will be then used in the drop down in the view
        $deliveryName = json_decode($user->getDeliveryAddress(), true)["county"];

        // Inserts the delivery Id and its amount in the session
        $session->set('delivery', array($deliveryName, $deliveryAmount));

        return;

    }

}