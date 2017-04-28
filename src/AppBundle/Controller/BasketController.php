<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Product;
use AppBundle\Service\Delivery;
use AppBundle\Service\Discount;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class BasketController extends Controller
{
    /**
     * @Route("/basket", name="basket")
     */
    public function basketAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        // Makes the products, products qty and the basket total available to the view if a basket exists
        $session = $request->getSession();

        $basketProducts = $session->get('basketProducts');
        $basketQty = $session->get('basketQty');
        $basketTotal = $session->get('basketTotal');
        $delivery = $session->get('delivery');

        $allRelatedProducts = [];

        if ($basketProducts) {
            foreach ($basketProducts as $basketProduct) {

                /** @var Product $product */
                $product = $em->getRepository('AppBundle:Product')
                    ->findOneBy(array(
                        'id' => $basketProduct['id']
                    ));

                $relatedProducts = $product->getRelatedProductsWithProduct();

                $allRelatedProducts[$basketProduct['id']] = $relatedProducts;
            }
        }

        return $this->render('frontend/basket.html.twig', array(
            'basketProducts' => $basketProducts,
            'basketQty' => $basketQty,
            'basketTotal' => $basketTotal,
            'delivery' => $delivery,
            'allRelatedProducts' => $allRelatedProducts
        ));

    }

//    /**
//     * @Route("basket/update/discount/{discountName}", name="basket_update_discount")
//     * @return RedirectResponse
//     */
//    public function basketUpdateDiscountAction($discountName, Request $request)
//    {
//
//        $discount = new Discount();
//        $discount->updateWithoutUser($discountName, $request);
//
//        if ($discount) {
//            $this->addFlash('success', 'Discount updated!');
//        }
//        else {
//            $this->addFlash('danger', 'Discount not available!');
//        }
//
//        return $this->redirectToRoute('basket');
//    }

    /**
     * @Route("/basket/delete-product/{id}", name="basket_delete_single_product")
     */
    public function basketDeleteProductAction($id, Request $request)
    {
        $session = $request->getSession();

        $basketProducts = $session->get('basketProducts');

        for ($x = 0; $x <= (count($basketProducts) - 1); $x++) {
            if ($basketProducts[$x]["id"] == $id) {
                unset($basketProducts[$x]);
            }
        }

        $basketProducts = array_values($basketProducts);

        $session->set('basketProducts', $basketProducts);

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

        return $this->redirectToRoute('basket');
    }

    /**
     * @Route("basket/update/product/delete-all", name="basket_empty")
     * @return RedirectResponse
     */
    public function basketUpdateProductDeleteAllAction(Request $request)
    {
        $session = $request->getSession();

        // Make basket empty by making the basket in session NULL
        $session->set('basket', Null);
        $session->set('basketProducts', Null);
        $session->set('basketQty', Null);
        $session->set('basketTotal', Null);
        $session->set('delivery', Null);

        $this->addFlash('success', 'All products have been deleted, your basket is empty!');

        // Redirect to index
        return $this->redirectToRoute('index');
    }
}


