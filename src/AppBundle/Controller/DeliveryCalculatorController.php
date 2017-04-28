<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Delivery;
use AppBundle\Entity\Surcharge;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DeliveryCalculatorController extends Controller
{
    /**
     * @Route("/delivery-calculator", name="delivery_calculator")
     */
    public function deliveryCalculatorAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $session = $request->getSession();

        $postcodeEntry = $request->query->get('postcodeEntry');

        $session->set('deliveryEntry', $postcodeEntry);

        $postcode = $request->query->get('postcode');

        if (!$postcode) {
            $postcode = $session->get('deliveryPostcodeCalculate');
        }

        $session->set('deliveryPostcodeCalculate', $postcode);

        $basketProducts = $session->get('basketProducts');

        $totalWeight = 0;
        for ($x = 0; $x <= (count($basketProducts) - 1); $x++) {
            $totalWeight += $basketProducts[$x]["weight"] * $basketProducts[$x]["quantity"];
        }

        /** @var Delivery $delivery */
        $delivery = $em->getRepository('AppBundle:Delivery')
            ->findOneBy(array(
                'id' => 1
            ));

        $deliveryAmount = json_decode($delivery->getAmount(), true);

        $standardDeliveryAmount = 0;
        foreach($deliveryAmount as $key => $value) {
            if ($totalWeight <= intval($key)) {
                $standardDeliveryAmount = $value;
                break;
            }
        }

        if (!$standardDeliveryAmount) {
            /** @var Delivery $deliveryMultiple */
            $deliveryMultiple = $em->getRepository('AppBundle:Delivery')
                ->findOneBy(array(
                    'id' => 2
                ));

            $deliveryMultipleAmount = json_decode($deliveryMultiple->getAmount(), true);

            $standardMultipleDeliveryAmount = 0;
            $standardDeliveryAmount = 0;
            foreach($deliveryMultipleAmount as $key => $value) {
                $standardMultipleDeliveryAmount = $key;
                $standardDeliveryAmount = $value;
                break;
            }

            $standardDeliveryAmount = $standardMultipleDeliveryAmount * $standardDeliveryAmount * $totalWeight;
        }

        /** @var Delivery $deliverySurcharge */
        $deliverySurcharge = $em->getRepository('AppBundle:Delivery')
            ->findOneBy(array(
                'id' => 3
            ));

        $deliverySurchargeAmount = json_decode($deliverySurcharge->getAmount(), true);

        foreach($deliverySurchargeAmount as $key => $value) {
            $length = strlen($key);

            if (substr($postcode, 0, $length) === $key) {
                $standardSurchargeDeliveryAmount = $value;

                $standardDeliveryAmount = $standardDeliveryAmount + $standardSurchargeDeliveryAmount;

                break;
            }
        }

        $session->set('delivery', $standardDeliveryAmount);

        $basketDetails = $session->get('basketDetails');

        if ($basketDetails) {
            $basketDetails["deliveryEntry"] = $session->get('deliveryEntry');
            $basketDetails["deliveryAmount"] = $session->get('delivery');
            $session->set('basketDetails', $basketDetails);
        }

        return $this->redirectToRoute('basket');
    }
}


