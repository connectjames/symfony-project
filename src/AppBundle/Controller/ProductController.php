<?php

namespace AppBundle\Controller;
use AppBundle\Entity\CategoryMenu;
use AppBundle\Entity\Product;
use AppBundle\Entity\Category;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ProductController extends Controller
{
//    /**
//     * @Route("/{categoryLevel1}/{categoryLevel2}/{categoryLevel3}/{categoryName}.html", name="sub_sub_sub_category", requirements={"^(?!admin|my-account|checkout|basket|index|home|login|logout).+"})
//     */
//    public function subSubSubCategoryAction($categoryLevel1, $categoryLevel2, $categoryLevel3, $categoryName)
//    {
//        $response = $this->forward('AppBundle:Product:productWithCatThreeLevel', array(
//            'categoryLevel1' => $categoryLevel1,
//            'categoryLevel2' => $categoryLevel2,
//            'categoryName' => $categoryLevel3,
//            'product' => $categoryName,
//        ));
//        return $response;
//    }

    /**
     * @Route("/{categoryLevel1}/{categoryLevel2}/{categoryName}.html", name="sub_sub_category", requirements={"^(?!admin|my-account|checkout|basket|index|home|login|logout).+"})
     */
    public function subSubCategoryAction($categoryLevel1, $categoryLevel2, $categoryName, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var CategoryMenu $categoryMenu */
        $categoryMenu = $em->getRepository('AppBundle:CategoryMenu')
            ->findOneBy(array(
                'name' => "menu"
            ));

        foreach (json_decode($categoryMenu->getOrdering(), true) as $key => $value) {
            if ($value['url'] == $categoryLevel1 && isset($value['children'])) {
                foreach ($value['children'] as $keySub => $valueSub) {
                    if ($valueSub['url'] == $categoryLevel2) {
                        foreach ($valueSub['children'] as $keySubSub => $valueSubSub) {
                            if ($valueSubSub['url'] == $categoryName) {

                                /** @var Product $featuredProducts */
                                $featuredProducts = $em->getRepository('AppBundle:Product')
                                    ->findBy(array(
                                        'featured' => 1
                                    ));
                                $productRepository = $this->getDoctrine()
                                    ->getManager()
                                    ->getRepository('AppBundle:Product');
                                $categories = $productRepository->sortProductsFromCategory($categoryName);

                                /**
                                 * @var $paginator \Knp\Component\Pager\Paginator
                                 */
                                $paginator = $this->get('knp_paginator');
                                $categoryProducts = $paginator->paginate(
                                    $categories,
                                    $request->query->getInt('page', 1),
                                    $request->query->getInt('limit', 16)
                                );

                                /** @var Category $categoryLevel1 */
                                $categoryLevel1 = $em->getRepository('AppBundle:Category')
                                    ->findOneBy(array(
                                        'slug' => $categoryLevel1
                                    ));
                                /** @var Category $categoryLevel2 */
                                $categoryLevel2 = $em->getRepository('AppBundle:Category')
                                    ->findOneBy(array(
                                        'slug' => $categoryLevel2
                                    ));
                                /** @var Category $category */
                                $category = $em->getRepository('AppBundle:Category')
                                    ->findOneBy(array(
                                        'slug' => $categoryName
                                    ));

                                // Makes the products, products qty and the basket total available to the view if a basket exists
                                $session = $request->getSession();
                                $basketProducts = $session->get('basketProducts');
                                $basketQty = $session->get('basketQty');
                                $basketTotal = $session->get('basketTotal');

                                return $this->render('frontend/categorySubSub.html.twig', array(
                                    'categoryLevel1' => $categoryLevel1,
                                    'categoryLevel2' => $categoryLevel2,
                                    'category' => $category,
                                    'categoryProducts' => $categoryProducts,
                                    'featuredProducts' => $featuredProducts,
                                    'basketProducts' => $basketProducts,
                                    'basketQty' => $basketQty,
                                    'basketTotal' => $basketTotal
                                ));

                            }
                        }

                    }
                }
            }
        }

        $response = $this->forward('AppBundle:Product:productWithCatTwoLevel', array(
            'categoryLevel1' => $categoryLevel1,
            'categoryName' => $categoryLevel2,
            'product' => $categoryName,
        ));
        return $response;
    }

    /**
     * @Route("/{categoryLevel1}/{categoryName}.html", name="sub_category", requirements={"categoryLevel1": "^(?!admin|index|my-account|retrieve-password).+"})
     */
    public function subCategoryAction($categoryLevel1, $categoryName, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var CategoryMenu $categoryMenu */
        $categoryMenu = $em->getRepository('AppBundle:CategoryMenu')
            ->findOneBy(array(
                'name' => "menu"
            ));

        foreach (json_decode($categoryMenu->getOrdering(), true) as $key => $value) {
            if ($value['url'] == $categoryLevel1 && isset($value['children'])) {
                foreach ($value['children'] as $keySub => $valueSub) {
                    if ($valueSub['url'] == $categoryName) {

                        /** @var Product $featuredProducts */
                        $featuredProducts = $em->getRepository('AppBundle:Product')
                            ->findBy(array(
                                'featured' => 1
                            ));
                        $productRepository = $this->getDoctrine()
                            ->getManager()
                            ->getRepository('AppBundle:Product');
                        $categories = $productRepository->sortProductsFromCategory($categoryName);

                        /**
                         * @var $paginator \Knp\Component\Pager\Paginator
                         */
                        $paginator = $this->get('knp_paginator');
                        $categoryProducts = $paginator->paginate(
                            $categories,
                            $request->query->getInt('page', 1),
                            $request->query->getInt('limit', 16)
                        );

                        $categorySubs = [];
                        if (!$categories) {
                            for ($x = 0; $x < count($value['children']); $x++) {

                                /** @var Category $categorySub */
                                $categorySub = $em->getRepository('AppBundle:Category')
                                    ->findOneBy(array(
                                        'slug' => $value['children'][$x]["url"]
                                    ));

                                $categorySubs[$x] = $categorySub;
                            }
                        }

                        /** @var Category $categoryLevel1 */
                        $categoryLevel1 = $em->getRepository('AppBundle:Category')
                            ->findOneBy(array(
                                'slug' => $categoryLevel1
                            ));
                        /** @var Category $category */
                        $category = $em->getRepository('AppBundle:Category')
                            ->findOneBy(array(
                                'slug' => $categoryName
                            ));

                        // Makes the products, products qty and the basket total available to the view if a basket exists
                        $session = $request->getSession();
                        $basketProducts = $session->get('basketProducts');
                        $basketQty = $session->get('basketQty');
                        $basketTotal = $session->get('basketTotal');

                        return $this->render('frontend/categorySub.html.twig', array(
                            'categoryLevel1' => $categoryLevel1,
                            'category' => $category,
                            'categoryProducts' => $categoryProducts,
                            'categorySubs' => $categorySubs,
                            'featuredProducts' => $featuredProducts,
                            'basketProducts' => $basketProducts,
                            'basketQty' => $basketQty,
                            'basketTotal' => $basketTotal
                        ));

                    }
                }
            }
        }

        $response = $this->forward('AppBundle:Product:productWithCat', array(
            'categoryName' => $categoryLevel1,
            'product' => $categoryName,
        ));
        return $response;
    }

    /**
     * @Route("/{categoryName}.html", name="category", requirements={"^(?!admin|my-account|checkout|basket|index|home|login|logout).+"})
     */
    public function categoryAction($categoryName, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var CategoryMenu $categoryMenu */
        $categoryMenu = $em->getRepository('AppBundle:CategoryMenu')
            ->findOneBy(array(
                'name' => "menu"
            ));

        foreach (json_decode($categoryMenu->getOrdering(), true) as $key => $value) {
            if ($value['url'] == $categoryName) {

                /** @var Product $featuredProducts */
                $featuredProducts = $em->getRepository('AppBundle:Product')
                    ->findBy(array(
                        'featured' => 1
                    ));
                $productRepository = $this->getDoctrine()
                    ->getManager()
                    ->getRepository('AppBundle:Product');
                $categories = $productRepository->sortProductsFromCategory($categoryName);

                /**
                 * @var $paginator \Knp\Component\Pager\Paginator
                 */
                $paginator = $this->get('knp_paginator');
                $categoryProducts = $paginator->paginate(
                    $categories,
                    $request->query->getInt('page', 1),
                    $request->query->getInt('limit', 16)
                );

                $categorySubs = [];
                if (!$categories) {
                    for ($x = 0; $x < count($value['children']); $x++) {

                        /** @var Category $categorySub */
                        $categorySub = $em->getRepository('AppBundle:Category')
                            ->findOneBy(array(
                                'slug' => $value['children'][$x]["url"]
                            ));

                        $categorySubs[$x] = $categorySub;
                    }
                }

                /** @var Category $category */
                $category = $em->getRepository('AppBundle:Category')
                    ->findOneBy(array(
                        'slug' => $categoryName
                    ));

                // Makes the products, products qty and the basket total available to the view if a basket exists
                $session = $request->getSession();
                $basketProducts = $session->get('basketProducts');
                $basketQty = $session->get('basketQty');
                $basketTotal = $session->get('basketTotal');

                return $this->render('frontend/category.html.twig', array(
                    'category' => $category,
                    'categoryProducts' => $categoryProducts,
                    'categorySubs' => $categorySubs,
                    'featuredProducts' => $featuredProducts,
                    'basketProducts' => $basketProducts,
                    'basketQty' => $basketQty,
                    'basketTotal' => $basketTotal
                ));
            }
        }

        $response = $this->forward('AppBundle:Product:productOnly', array(
            'product' => $categoryName,
        ));
        return $response;
    }

    /**
     * @Route("/{categoryLevel1}/{categoryLevel2}/{categoryName}/{product}.html", name="product_with_three_level_category", requirements={"categoryLevel1": "^(?!admin|index|my-account|retrieve-password).+"})
     */
    public function productWithCatThreeLevelAction($categoryLevel1, $categoryLevel2, $categoryName, $product, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var CategoryMenu $categoryMenu */
        $categoryMenu = $em->getRepository('AppBundle:CategoryMenu')
            ->findOneBy(array(
                'name' => "menu"
            ));

        foreach (json_decode($categoryMenu->getOrdering(), true) as $key => $value) {
            if ($value['url'] == $categoryLevel1) {
                foreach ($value['children'] as $keySub => $valueSub) {
                    if ($valueSub['url'] == $categoryLevel2) {
                        foreach ($valueSub['children'] as $keySubSub => $valueSubSub) {
                            if ($valueSubSub['url'] == $categoryName) {

                                $categoriesOneLevelOne = [];
                                foreach (json_decode($categoryMenu->getOrdering(), true) as $k1 => $v1) {
                                    foreach ($v1 as $k2 => $v2) {
                                        if ($k2 == "url") {
                                            /** @var Category $categoryLevel */
                                            $categoryLevel = $em->getRepository('AppBundle:Category')->findOneBy(
                                                array('slug' => $v2));
                                            $categoriesLevelOne[] = $categoryLevel;
                                        }
                                    }
                                }

                                /** @var Product $featuredProducts */
                                $featuredProducts = $em->getRepository('AppBundle:Product')
                                    ->findBy(array(
                                        'featured' => 1
                                    ));

                                /** @var Product $product */
                                $product = $em->getRepository('AppBundle:Product')
                                    ->findOneBy(array(
                                        'slug' => $product
                                    ));

                                if (!$product) {
                                    throw $this->createNotFoundException('The product does not exist');
                                }

                                $relatedProducts = $product->getRelatedProductsWithProduct();

                                /** @var Category $categoryLevel1 */
                                $categoryLevel1 = $em->getRepository('AppBundle:Category')
                                    ->findOneBy(array(
                                        'slug' => $categoryLevel1
                                    ));
                                /** @var Category $categoryLevel2 */
                                $categoryLevel2 = $em->getRepository('AppBundle:Category')
                                    ->findOneBy(array(
                                        'slug' => $categoryLevel2
                                    ));
                                /** @var Category $category */
                                $category = $em->getRepository('AppBundle:Category')
                                    ->findOneBy(array(
                                        'slug' => $categoryName
                                    ));

                                // Makes the products, products qty and the basket total available to the view if a basket exists
                                $session = $request->getSession();
                                $basketProducts = $session->get('basketProducts');
                                $basketQty = $session->get('basketQty');
                                $basketTotal = $session->get('basketTotal');

                                return $this->render('frontend/productWithCatSubSub.html.twig', array(
                                    'categoryLevel1' => $categoryLevel1,
                                    'categoryLevel2' => $categoryLevel2,
                                    'category' => $category,
                                    'product' => $product,
                                    'categories' => $categoriesOneLevelOne,
                                    'featuredProducts' => $featuredProducts,
                                    'basketProducts' => $basketProducts,
                                    'basketQty' => $basketQty,
                                    'basketTotal' => $basketTotal,
                                    'relatedProducts' => $relatedProducts
                                ));

                            }
                        }
                    }
                }
            }
        }

        throw $this->createNotFoundException('The product does not exist');
    }

    /**
     * @Route("/{categoryLevel1}/{categoryName}/{product}.html", name="product_with_two_level_category", requirements={"categoryLevel1": "^(?!admin|index|my-account|retrieve-password).+"})
     */
    public function productWithCatTwoLevelAction($categoryLevel1, $categoryName, $product, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var CategoryMenu $categoryMenu */
        $categoryMenu = $em->getRepository('AppBundle:CategoryMenu')
            ->findOneBy(array(
                'name' => "menu"
            ));

        foreach (json_decode($categoryMenu->getOrdering(), true) as $key => $value) {
            if ($value['url'] == $categoryLevel1) {
                foreach ($value['children'] as $keySub => $valueSub) {
                    if ($valueSub['url'] == $categoryName) {

                        $categoriesOneLevelOne = [];
                        foreach (json_decode($categoryMenu->getOrdering(), true) as $k1 => $v1) {
                            foreach ($v1 as $k2 => $v2) {
                                if ($k2 == "url") {
                                    /** @var Category $categoryLevel */
                                    $categoryLevel = $em->getRepository('AppBundle:Category')->findOneBy(
                                        array('slug' => $v2));
                                    $categoriesLevelOne[] = $categoryLevel;
                                }
                            }
                        }

                        /** @var Product $featuredProducts */
                        $featuredProducts = $em->getRepository('AppBundle:Product')
                            ->findBy(array(
                                'featured' => 1
                            ));

                        /** @var Product $product */
                        $product = $em->getRepository('AppBundle:Product')
                            ->findOneBy(array(
                                'slug' => $product
                            ));

                        if (!$product) {
                            throw $this->createNotFoundException('The product does not exist');
                        }

                        $relatedProducts = $product->getRelatedProductsWithProduct();

                        /** @var Category $categoryLevel1 */
                        $categoryLevel1 = $em->getRepository('AppBundle:Category')
                            ->findOneBy(array(
                                'slug' => $categoryLevel1
                            ));
                        /** @var Category $category */
                        $category = $em->getRepository('AppBundle:Category')
                            ->findOneBy(array(
                                'slug' => $categoryName
                            ));

                        // Makes the products, products qty and the basket total available to the view if a basket exists
                        $session = $request->getSession();
                        $basketProducts = $session->get('basketProducts');
                        $basketQty = $session->get('basketQty');
                        $basketTotal = $session->get('basketTotal');

                        return $this->render('frontend/productWithCatSub.html.twig', array(
                            'categoryLevel1' => $categoryLevel1,
                            'category' => $category,
                            'product' => $product,
                            'categories' => $categoriesOneLevelOne,
                            'featuredProducts' => $featuredProducts,
                            'basketProducts' => $basketProducts,
                            'basketQty' => $basketQty,
                            'basketTotal' => $basketTotal,
                            'relatedProducts' => $relatedProducts
                        ));

                    }
                }
            }
        }

        throw $this->createNotFoundException('The product does not exist');
    }

    /**
     * @Route("/{categoryName}/{product}.html", name="product_with_category", requirements={"category": "^(?!admin|index|my-account|retrieve-password).+"})
     */
    public function productWithCatAction($categoryName, $product, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var CategoryMenu $categoryMenu */
        $categoryMenu = $em->getRepository('AppBundle:CategoryMenu')
            ->findOneBy(array(
                'name' => "menu"
            ));

        foreach (json_decode($categoryMenu->getOrdering(), true) as $key => $value) {
            if ($value['url'] == $categoryName) {

                $categoriesOneLevelOne = [];
                foreach (json_decode($categoryMenu->getOrdering(), true) as $k1 => $v1) {
                    foreach ($v1 as $k2 => $v2) {
                        if ($k2 == "url") {
                            /** @var Category $categoryLevel */
                            $categoryLevel = $em->getRepository('AppBundle:Category')->findOneBy(
                                array('slug' => $v2));
                            $categoriesLevelOne[] = $categoryLevel;
                        }
                    }
                }

                /** @var Product $featuredProducts */
                $featuredProducts = $em->getRepository('AppBundle:Product')
                    ->findBy(array(
                        'featured' => 1
                    ));

                /** @var Product $product */
                $product = $em->getRepository('AppBundle:Product')
                    ->findOneBy(array(
                        'slug' => $product
                    ));

                if (!$product) {
                    throw $this->createNotFoundException('The product does not exist');
                }

                $relatedProducts = $product->getRelatedProductsWithProduct();

                /** @var Category $category */
                $category = $em->getRepository('AppBundle:Category')
                    ->findOneBy(array(
                        'slug' => $categoryName
                    ));

                // Makes the products, products qty and the basket total available to the view if a basket exists
                $session = $request->getSession();
                $basketProducts = $session->get('basketProducts');
                $basketQty = $session->get('basketQty');
                $basketTotal = $session->get('basketTotal');

                return $this->render('frontend/productWithCat.html.twig', array(
                    'category' => $category,
                    'product' => $product,
                    'categories' => $categoriesOneLevelOne,
                    'featuredProducts' => $featuredProducts,
                    'basketProducts' => $basketProducts,
                    'basketQty' => $basketQty,
                    'basketTotal' => $basketTotal,
                    'relatedProducts' => $relatedProducts
                ));
            }
        }

        throw $this->createNotFoundException('The product does not exist');
    }

    /**
     * @Route("/{product}.html", name="product_only")
     */
    public function productOnlyAction($product, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var Product $product */
        $product = $em->getRepository('AppBundle:Product')
            ->findOneBy(array(
                'slug' => $product
            ));
        // If product exists
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }
        $relatedProducts = $product->getRelatedProductsWithProduct();
        // Makes the products, products qty and the basket total available to the view if a basket exists
        $session = $request->getSession();
        $basketProducts = $session->get('basketProducts');
        $basketQty = $session->get('basketQty');
        $basketTotal = $session->get('basketTotal');
        return $this->render('frontend/productOnly.html.twig', array(
            'product' => $product,
            'basketProducts' => $basketProducts,
            'basketQty' => $basketQty,
            'basketTotal' => $basketTotal,
            'relatedProducts' => $relatedProducts
        ));
    }
}
