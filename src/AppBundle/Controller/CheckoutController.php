<?php

namespace AppBundle\Controller;

use AppBundle\Entity\County;
use AppBundle\Entity\Dropshipper;
use AppBundle\Entity\Order;
use AppBundle\Entity\Product;
use AppBundle\Entity\Status;
use AppBundle\Entity\User;
use AppBundle\Form\CheckoutDeliveryForm;
use AppBundle\Form\CheckoutInvoiceForm;
use AppBundle\Form\OrderForm;
use AppBundle\Service\Delivery;
use AppBundle\Service\Basket;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CheckoutController extends Controller
{
    /**
     * @Route("/checkout-invoice", name="checkout_invoice")
     */
    public function checkoutInvoiceAction(Request $request)
    {
        $session = $request->getSession();

        $formCheckoutInvoiceForm = $this->createForm(CheckoutInvoiceForm::class);

        $formCheckoutInvoiceForm->handleRequest($request);

        if ($formCheckoutInvoiceForm->isValid()) {

            // Invoice address creation
            $invoiceDetails['invoiceAddress'] = array("firstName" => $formCheckoutInvoiceForm['firstName']->getData(), "lastName" => $formCheckoutInvoiceForm['lastName']->getData(), "company" => $formCheckoutInvoiceForm['company']->getData(), "address1" => $formCheckoutInvoiceForm['address1']->getData(), "address2" => $formCheckoutInvoiceForm['address2']->getData(), "city" => $formCheckoutInvoiceForm['city']->getData(), "postcode" => $formCheckoutInvoiceForm['postcode']->getData(), "phone" => $formCheckoutInvoiceForm['phone']->getData());

            $invoiceDetails['email'] = $formCheckoutInvoiceForm['email']->getData();
            $invoiceDetails['firstName'] = $formCheckoutInvoiceForm['firstName']->getData();
            $invoiceDetails['lastName'] = $formCheckoutInvoiceForm['lastName']->getData();
            $invoiceDetails['purchaseOrderNumber'] = $formCheckoutInvoiceForm['purchaseOrderNumber']->getData();

            $session->set('invoiceDetails', $invoiceDetails);

            return $this->redirectToRoute('checkout_delivery');

        }

        // Makes the products, products qty and the basket total available to the view if a basket exists

        $basketProducts = $session->get('basketProducts');

        if (!$basketProducts) {
            return $this->redirectToRoute('basket');
        }

        $basketDetails["basketProducts"] = $session->get('basketProducts');
        $basketDetails["basketQty"] = $session->get('basketQty');
        $basketDetails["basketTotal"] = $session->get('basketTotal');
        $basketDetails["deliveryEntry"] = $session->get('deliveryEntry');
        $basketDetails["deliveryAmount"] = $session->get('delivery');

        $session->set('basketDetails', $basketDetails);

        $invoiceDetails = $session->get('invoiceDetails');

        return $this->render('frontend/checkout-invoice.html.twig', array(
            'formCheckoutInvoiceForm' => $formCheckoutInvoiceForm->createView(),
            'basketDetails' => $basketDetails,
            'invoiceDetails' => $invoiceDetails
        ));
    }

    /**
     * @Route("/checkout-delivery", name="checkout_delivery")
     */
    public function checkoutDeliveryAction(Request $request)
    {
        $session = $request->getSession();

        $formCheckoutDeliveryForm = $this->createForm(CheckoutDeliveryForm::class);

        $formCheckoutDeliveryForm->handleRequest($request);

        if ($formCheckoutDeliveryForm->isValid()) {

            // Invoice address creation
            $deliveryDetails['deliveryAddress'] = array("firstName" => $formCheckoutDeliveryForm['firstName']->getData(), "lastName" => $formCheckoutDeliveryForm['lastName']->getData(), "company" => $formCheckoutDeliveryForm['company']->getData(), "address1" => $formCheckoutDeliveryForm['address1']->getData(), "address2" => $formCheckoutDeliveryForm['address2']->getData(), "city" => $formCheckoutDeliveryForm['city']->getData(), "postcode" => $formCheckoutDeliveryForm['postcode']->getData(), "phone" => $formCheckoutDeliveryForm['phone']->getData());

            $deliveryDetails['comment'] = $formCheckoutDeliveryForm['comment']->getData();

            $session->set('deliveryDetails', $deliveryDetails);

            return $this->redirectToRoute('checkout_review');

        }

        // Makes the products, products qty and the basket total available to the view if a basket exists

        $basketDetails = $session->get('basketDetails');
        $invoiceDetails = $session->get('invoiceDetails');
        $deliveryDetails = $session->get('deliveryDetails');

        if (!$basketDetails) {
            return $this->redirectToRoute('basket');
        }

        if (!$invoiceDetails) {
            return $this->redirectToRoute('checkout_invoice');
        }

        return $this->render('frontend/checkout-delivery.html.twig', array(
            'formCheckoutDeliveryForm' => $formCheckoutDeliveryForm->createView(),
            'basketDetails' => $basketDetails,
            'invoiceDetails' => $invoiceDetails,
            'deliveryDetails' => $deliveryDetails
        ));
    }

    /**
     * @Route("/checkout-review", name="checkout_review")
     */
    public function checkoutReviewAction(Request $request)
    {
        $session = $request->getSession();

        // Makes the products, products qty and the basket total available to the view if a basket exists

        $basketDetails = $session->get('basketDetails');
        $invoiceDetails = $session->get('invoiceDetails');
        $deliveryDetails = $session->get('deliveryDetails');

        if (!$basketDetails) {
            return $this->redirectToRoute('basket');
        }

        if (!$invoiceDetails) {
            return $this->redirectToRoute('checkout_invoice');
        }

        if (!$deliveryDetails) {
            return $this->redirectToRoute('checkout_delivery');
        }

        return $this->render('frontend/checkout-review.html.twig', array(
            'basketDetails' => $basketDetails,
            'invoiceDetails' => $invoiceDetails,
            'deliveryDetails' => $deliveryDetails
        ));
    }

    /**
     * @Route("/checkout-order", name="checkout_order")
     */
    public function checkoutOrderPlacedAction(Request $request)
    {
        $session = $request->getSession();

        // Makes the products, products qty and the basket total available to the view if a basket exists

        $basketDetails = $session->get('basketDetails');
        $invoiceDetails = $session->get('invoiceDetails');
        $deliveryDetails = $session->get('deliveryDetails');
        $merchantSession = $session->get('merchantSession');

        if (!$basketDetails) {
            return $this->redirectToRoute('basket');
        }

        if (!$invoiceDetails) {
            return $this->redirectToRoute('checkout_invoice');
        }

        if (!$deliveryDetails) {
            return $this->redirectToRoute('checkout_delivery');
        }

        if (!$merchantSession) {
            return $this->redirectToRoute('checkout_review');
        }

        $cardIdentifier = $request->query->get('cardIdentifier');

        $session->set('cardIdentifier', $cardIdentifier);

        $curl = curl_init();

        $str = $this->getParameter('sagepay_key') . ":" . $this->getParameter('sagepay_password');

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://pi-test.sagepay.com/api/v1/transactions",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => '{' .
                '"transactionType": "Payment",' .
                '"paymentMethod": {' .
                '    "card": {' .
                '        "merchantSessionKey": "' . $merchantSession . '",' .
                '        "cardIdentifier": "' . $cardIdentifier . '"' .
                '    }' .
                '},' .
                '"vendorTxCode": "demotransaction' . time() . '",' .
                '"amount": ' . ($basketDetails["deliveryAmount"] + $basketDetails["basketTotal"]) * 120 .',' .
                '"currency": "GBP",' .
                '"description": "Demo transaction",' .
                '"apply3DSecure": "UseMSPSetting",' .
                '"customerFirstName": "' . $invoiceDetails['invoiceAddress']["firstName"] . '",' .
                '"customerLastName": "' . $invoiceDetails['invoiceAddress']["lastName"] . '",' .
                '"billingAddress": {' .
                '    "address1": "' . $invoiceDetails['invoiceAddress']["address1"] . '",' .
                '    "city": "' . $invoiceDetails['invoiceAddress']["city"] . '",' .
                '    "postalCode": "' . $invoiceDetails['invoiceAddress']["postcode"] . '",' .
                '    "country": "GB"' .
                '},' .
                '"entryMethod": "Ecommerce"' .
                '}',
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic " . base64_encode($str),
                "Cache-Control: no-cache",
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            $this->addFlash('warning', 'There was a problem with your transaction, please call us.');
            return $this->redirectToRoute('checkout_review');
        }

        $order = new Order();
        $order->setCreatedAt(new \DateTime("now"));
        $order->setEmail($invoiceDetails["email"]);
        $order->setFirstName($invoiceDetails["invoiceAddress"]["firstName"]);
        $order->setLastName($invoiceDetails["invoiceAddress"]["lastName"]);
        $order->setComment($deliveryDetails["comment"]);
        $order->setInvoiceAddress(json_encode($invoiceDetails["invoiceAddress"]));
        $order->setDeliveryAddress(json_encode($deliveryDetails["deliveryAddress"]));
        $order->setOrderDescription(json_encode($basketDetails["basketProducts"]));
        $order->setOrderAmount($basketDetails["basketTotal"]);
        $order->setDeliveryAmount($basketDetails["deliveryAmount"]);
        $order->setCardIdentifier($cardIdentifier);

        $em = $this->getDoctrine()->getManager();

        /** @var Status $status */
        $status = $em->getRepository('AppBundle:Status')
            ->findOneBy(array(
                'id' => 1
            ));

        $order->setStatus($status);

        if ($invoiceDetails["purchaseOrderNumber"]) {
            $order->setPurchaseOrderNumber($invoiceDetails["purchaseOrderNumber"]);
        }

        if ($this->getUser()) {

            $user = $this->getUser();
            $order->setUser($user);

            $user->getDeliveryAddress();

            if ($invoiceDetails["invoiceAddress"] != $deliveryDetails["deliveryAddress"]) {

                for ($x = 0; $x < count($user->getDeliveryAddress()); $x++) {
                    if ($user->getDeliveryAddress()[$x] != $deliveryDetails["deliveryAddress"]) {

                        $newDeliveryAddress = $deliveryDetails["deliveryAddress"];
                        $oldDeliveryAddresses = $user->getDeliveryAddress();
                        $oldDeliveryAddresses[count($user->getDeliveryAddress())] = $newDeliveryAddress;
                        $user->setDeliveryAddress($oldDeliveryAddresses);

                        $em = $this->getDoctrine()->getManager();
                        $em->persist($user);
                        $em->flush();

                        break;
                    }
                }
            }
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($order);
        $em->flush();

         //Send email to customer, confirmation of new order
        $message = \Swift_Message::newInstance()
            ->setSubject('Auto-Confirmation of your order from Spill Kits Direct')
            ->setFrom($this->getParameter('mailer_user'))
            ->setTo($this->getParameter('mailer_user'))
            ->setBody(
                $this->renderView(
                    'email/newOrder.html.twig',
                    array(
                        'basketDetails' => $basketDetails,
                        'invoiceDetails' => $invoiceDetails,
                        'deliveryDetails' => $deliveryDetails
                    )
                ),
                'text/html'
            );
        $this->get('mailer')->send($message);

        // Get all dropshipper
        /** @var Dropshipper $dropshipper */
        $dropshipper = $em->getRepository('AppBundle:Dropshipper')
            ->findAll();

        // Loop over each dropshipper
        for ($y = 0; $y < count($dropshipper); $y++) {

            $dropshipperProducts = [];

            // Checking each product in basket to see who is the dropshipper
            for ($x = 0; $x < (count($basketDetails["basketProducts"])); $x++) {

                /** @var Product $product */
                $product = $em->getRepository('AppBundle:Product')
                    ->find($basketDetails["basketProducts"][$x]["id"]);

                // If the product is part of this dropshipper then add it
                if ($dropshipper[$y] == $product->getDropshipper()) {
                    $dropshipperProducts += $basketDetails["basketProducts"][$x];
                }
            }

            if ($dropshipperProducts) {

                /** @var Dropshipper $dropshipper */
                $dropshipper = $em->getRepository('AppBundle:Dropshipper')
                    ->find($dropshipper[$y]);

                // Send email to dropshipper with the products he/she needs to send
                $message = \Swift_Message::newInstance()
                    ->setSubject('New order from Spills Kits Direct - Drop ship')
                    ->setFrom($this->getParameter('mailer_user'))
                    ->setTo($this->getParameter('mailer_user'))
                    ->setBody(
                        $this->renderView(
                            'email/dropshipper.twig',
                            array(
                                'dropshipperProducts' => $dropshipperProducts,
                            )
                        ),
                        'text/html'
                    );
                $this->get('mailer')->send($message);
            }
        }

        return $this->render('frontend/order-placed.html.twig', array(
            'invoiceDetails' => $invoiceDetails
        ));
    }

    /**
     * @Route("/change-address/entered-new-address", name="change_address_entered_new_address")
     */
    public function changeAddressEnteredNewAddressAction(Request $request)
    {
        $session = $request->getSession();

        $invoiceDetails = $session->get('invoiceDetails');
        $deliveryDetails = $session->get('deliveryDetails');

        return $this->render(':frontend/checkout:_enteredNewAddress.html.twig', array(
            'invoiceDetails' => $invoiceDetails,
            'deliveryDetails' => $deliveryDetails
        ));
    }

    /**
     * @Route("/change-address/invoice-address", name="change_address_invoice_address")
     */
    public function changeAddressInvoiceAddressAction(Request $request)
    {
        $session = $request->getSession();

        $invoiceDetails = $session->get('invoiceDetails');

        return $this->render('frontend/checkout/_invoiceAddress.html.twig', array(
            'invoiceDetails' => $invoiceDetails
        ));
    }

    /**
     * @Route("/change-address/saved-address/{id}", name="change_address_saved_address")
     */
    public function changeAddressSavedAddressAction($id)
    {
        return $this->render('frontend/checkout/_savedAddress.html.twig', array(
            'id' => $id
        ));
    }

    /**
     * @Route("/generate-merchant-session", name="generate_merchant_session")
     */
    public function generateMerchantSession(Request $request)
    {
        $session = $request->getSession();

        $curl = curl_init();

        $str = $this->getParameter('sagepay_key') . ":" . $this->getParameter('sagepay_password');

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://pi-test.sagepay.com/api/v1/merchant-session-keys",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => '{ "vendorName":"' . $this->getParameter('sagepay_vendor') . '"}',
            CURLOPT_HTTPHEADER => array(
                "Authorization: Basic " . base64_encode($str),
                "Cache-Control: no-cache",
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        $merchantSession = json_decode($response, true);
        $merchantSession = $merchantSession['merchantSessionKey'];

        $session->set('merchantSession', $merchantSession);

        return $this->render('frontend/checkout/_merchantSession.html.twig', array(
            'merchantSession' => $merchantSession
        ));
    }
}