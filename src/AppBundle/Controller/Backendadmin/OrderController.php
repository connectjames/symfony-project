<?php

namespace AppBundle\Controller\Backendadmin;

use AppBundle\Entity\Order;
use AppBundle\Entity\Status;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/admin")
 * @Security("is_granted('ROLE_ADMIN')")
 */

class OrderController extends Controller
{
    /**
     * @Route("/orders", name="orders")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();

        $queryBuilder = $em->getRepository('AppBundle:Order')->createQueryBuilder('ord');

        $query = $queryBuilder->getQuery();

        $request->query->getAlnum('records');

        if ($request->query->getAlnum('records')) {
            $limit = $request->query->getAlnum('records');
            $session->set('limit', $limit);
        } elseif ($session->get('limit')) {
            $limit = $session->get('limit');
        } else {
            $limit = 15;
        }

        /**
         * @var $paginator \Knp\Component\Pager\Paginator
         */
        $paginator  = $this->get('knp_paginator');

        $orders = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', $limit),
            array('defaultSortFieldName' => 'ord.createdAt', 'defaultSortDirection' => 'desc')
        );

        return $this->render('backendadmin/order.html.twig', array(
            'orders' => $orders,
            'page' => $limit
        ));
    }

    /**
     * @Route("/orders/change-status", name="orders_change_status_order")
     */
    public function changeStatusOrderAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var Order $order */
        $order = $em->getRepository('AppBundle:Order')
            ->findOneBy(array(
                'id' => $request->query->get('id')
            ));

        // Check if order exists
        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        // Switches the order to the right status
        switch ($request->query->get('statusId')) {
            case 1:
                echo "Release payment and send message that the order is on the way to the customer " . $request->query->get('date');
                $order->setDispatchedAt(new \DateTime($request->query->get('date')));
                break;
            case 2:
                echo "Release payment";
                $order->setDispatchedAt(new \DateTime($request->query->get('date')));
                break;
            case 3:
                echo "Pending, nothing happens";
                break;
            case 4:
                echo "Block payment and send message about the transaction being blocked";
                break;
            case 5:
                echo "Abort payment and send message about the transaction being cancelled";
                break;
            default:
                $this->addFlash('warning', 'Status not found!');
        }

        /** @var Status $status */
        $status = $em->getRepository('AppBundle:Status')
            ->findOneBy(array(
                'id' => $request->query->get('statusId')
            ));

        $order->setStatus($status);
        $em->persist($order);
        $em->flush();

        return $this->redirectToRoute('orders');

    }

    /**
     * @Route("/orders/change-statuses", name="orders_change_statuses_order")
     */
    public function changeStatusesOrderAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var Status $status */
        $status = $em->getRepository('AppBundle:Status')
            ->findOneBy(array(
                'id' => $request->query->get('statusId')
            ));

        // Explode the orders from ids separated by comma. ie: 1,2,3,4,5 to Array
        $ids = explode( ',', $request->query->get('ordersId') );

        // Finds all the orders needing a status change
        for ($x = 0; $x < count($ids); $x++) {
            /** @var Order $order */
            $order = $em->getRepository('AppBundle:Order')
                ->findOneBy(array(
                    'id' => $ids[$x]
                ));

            // Check if order exists
            if (!$order) {
                throw $this->createNotFoundException('order not found');
            }

            // Switches the order to the right status
            switch ($request->query->get('statusId')) {
                case 1:
                    echo "Release payment and send message that the order is on the way to the customer";
                    $order->setDispatchedAt(new \DateTime($request->query->get('date')));
                    break;
                case 2:
                    echo "Release payment";
                    $order->setDispatchedAt(new \DateTime($request->query->get('date')));
                    break;
                case 3:
                    echo "Pending, nothing happens";
                    break;
                case 4:
                    echo "Block payment and send message about the transaction being blocked";
                    break;
                case 5:
                    echo "Abort payment and send message about the transaction being cancelled";
                    break;
                default:
                    $this->addFlash('warning', 'Status not found!');
            }

            $order->setStatus($status);
            $em->persist($order);
            $em->flush();
        }

        return $this->redirectToRoute('orders');

    }

    /**
     * @Route("/orders/view/{id}", name="orders_view")
     */
    public function viewAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $orderRepository = $em->getRepository('AppBundle:Order');

        /** @var Order $order */
        $order = $em->getRepository('AppBundle:Order')
            ->findOneBy(array(
                'id' => $id
            ));

        // Check if an order after and before exists and make their Id available to the view
        $nextOrder = 0;
        $previousOrder = 0;

        if ($orderRepository->findOneBy(
            array('id' => ($id - 1))
        )) {
            $previousOrder = $id - 1;
        }

        if ($orderRepository->findOneBy(
            array('id' => ($id + 1))
        )) {
            $nextOrder = $id + 1;
        }

        return $this->render('backendadmin/viewOrder.html.twig', array(
            'order' => $order,
            'previousOrder' => $previousOrder,
            'nextOrder' => $nextOrder,
        ));
    }

    /**
     * @Route("/orders/edit/{id}", name="orders_edit")
     */
    public function editAction($id, Request $request)
    {
        // Find the order the user wants to view
        $em = $this->getDoctrine()->getManager();

        /** @var Order $order */
        $order = $em->getRepository('AppBundle:Order')
            ->findOneBy(array(
                'id' => $id
            ));

        // If user edits the invoice address
        if ($request->query->get('invoiceAddress')) {

            $order->setEmail($request->query->get('email'));

            $order->setInvoiceAddress($request->query->get('invoiceAddress'));

            $em = $this->getDoctrine()->getManager();
            $em->persist($order);
            $em->flush();

            // If user edits the delivery address
        } else if ($request->query->get('deliveryAddress')) {

            $order->setDeliveryAddress($request->query->get('deliveryAddress'));

            $em = $this->getDoctrine()->getManager();
            $em->persist($order);
            $em->flush();
        }

        return $this->redirectToRoute('orders_view', array('id' => $id));
    }

    /**
     * @Route("/orders/delete/{id}", name="orders_delete")
     */
    public function deleteAction($id)
    {
        // Find the order the user wants to view
        $em = $this->getDoctrine()->getManager();

        /** @var Order $order */
        $order = $em->getRepository('AppBundle:Order')
            ->findOneBy(array(
                'id' => $id
            ));

        $em->remove($order);
        $em->flush();

        return $this->redirectToRoute('orders');
    }

    /**
     * @Route("/orders/resend/{id}", name="orders_resend_email")
     */
    public function resendAction($id)
    {
        // Find the order the user wants to view
        $em = $this->getDoctrine()->getManager();

        /** @var Order $order */
        $order = $em->getRepository('AppBundle:Order')
            ->findOneBy(array(
                'id' => $id
            ));

        // Resend email to customer, confirmation of order
        $message = \Swift_Message::newInstance()
            ->setSubject('')
            ->setFrom($this->getParameter('mailer_user'))
            ->setTo($this->getParameter('mailer_user'))
            ->setBody(
                $this->renderView(
                    'email/newOrder.html.twig',
                    array(
                        'order' => $order,
                    )
                ),
                'text/html'
            );
        $this->get('mailer')->send($message);

        return $this->redirectToRoute('orders_view', array('id' => $id));
    }

    /**
     * @Route("/orders/view/print/{id}", name="orders_view_print")
     */
    public function printInViewAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var Order $order */
        $order = $em->getRepository('AppBundle:Order')
            ->find($id);

        // Creates a PDF file with the order in (https://github.com/KnpLabs/snappy)
        $snappy = $this->get('knp_snappy.pdf');
        $html = $this->renderView('printOrder.html.twig', array(
            'order' => $order
        ));
        return new Response(
            $snappy->getOutputFromHtml($html),
            200,
            array(
                'Content-Type'          => 'application/pdf',
                'Content-Disposition'   => 'inline; filename="' . $order->getId() . "-something" . '.pdf"'
            )
        );
    }

    /**
     * @Route("/orders/print/invoices", name="orders_print_orders_invoices")
     */
    public function printInvoicesOrdersAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $orders = [];

        // Explode the orders from ids separated by comma. ie: 1,2,3,4,5 to Array
        $ids = explode( ',', $request->query->get('ordersId') );

        // Finds all the orders invoices to print
        for ($x = 0; $x < count($ids); $x++) {
            /** @var Order $orders[$ids[$x]] */
            $orders[$ids[$x]] = $em->getRepository('AppBundle:Order')
                ->findOneBy(array(
                    'id' => $ids[$x]
                ));
        }

        // Creates the name which will be used for the PDF
        $ids = implode('-', $ids);

        // Creates a PDF file with the orders in (https://github.com/KnpLabs/snappy)
        $snappy = $this->get('knp_snappy.pdf');
        $html = $this->renderView('backendadmin/printInvoicesOrders.html.twig', array(
            'orders' => $orders
        ));
        return new Response(
            $snappy->getOutputFromHtml($html),
            200,
            array(
                'Content-Type'          => 'application/pdf',
                'Content-Disposition'   => 'inline; filename="'. $ids . '-something-invoices.pdf"'
            )
        );
    }

    /**
     * @Route("/orders/print/dispatch-notes", name="orders_print_orders_dispatch_notes")
     */
    public function printDispatchNotesOrdersAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $orders = [];

        // Explode the orders from ids separated by comma. ie: 1,2,3,4,5 to Array
        $ids = explode( ',', $request->query->get('ordersId') );

        // Finds all the orders delivery notes to print
        for ($x = 0; $x < count($ids); $x++) {
            /** @var Order $orders[$ids[$x]] */
            $orders[$ids[$x]] = $em->getRepository('AppBundle:Order')
                ->findOneBy(array(
                    'id' => $ids[$x]
                ));
        }

        // Creates the name which will be used for the PDF
        $ids = implode('-', $ids);

        // Creates a PDF file with the orders in (https://github.com/KnpLabs/snappy)
        $snappy = $this->get('knp_snappy.pdf');
        $html = $this->renderView('backendadmin/printDispatchNotesOrders.html.twig', array(
            'orders' => $orders
        ));
        return new Response(
            $snappy->getOutputFromHtml($html),
            200,
            array(
                'Content-Type'          => 'application/pdf',
                'Content-Disposition'   => 'inline; filename="'. $ids . '-something-dispatch-notes.pdf"'
            )
        );
    }

    /**
     * @Route("/orders/print/invoices-dispatch-notes", name="orders_print_orders_invoices_dispatch_notes")
     */
    public function printInvoicesDispatchNotesOrdersAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $orders = [];

        // Explode the orders from ids separated by comma. ie: 1,2,3,4,5 to Array
        $ids = explode( ',', $request->query->get('ordersId') );

        // Finds all the orders invoices and delivery notes to print
        for ($x = 0; $x < count($ids); $x++) {
            /** @var Order $orders[$ids[$x]] */
            $orders[$ids[$x]] = $em->getRepository('AppBundle:Order')
                ->findOneBy(array(
                    'id' => $ids[$x]
                ));
        }

        // Creates the name which will be used for the PDF
        $ids = implode('-', $ids);

        // Creates a PDF file with the orders in (https://github.com/KnpLabs/snappy)
        $snappy = $this->get('knp_snappy.pdf');
        $html = $this->renderView('backendadmin/printInvoicesAndDispatchNotesOrders.html.twig', array(
            'orders' => $orders
        ));
        return new Response(
            $snappy->getOutputFromHtml($html),
            200,
            array(
                'Content-Type'          => 'application/pdf',
                'Content-Disposition'   => 'inline; filename="'. $ids . '-something-invoices-dispatch-notes.pdf"'
            )
        );
    }
}


