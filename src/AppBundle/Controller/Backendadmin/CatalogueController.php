<?php

namespace AppBundle\Controller\Backendadmin;

use AppBundle\Entity\CategoryMenu;
use AppBundle\Entity\Dropshipper;
use AppBundle\Entity\Product;
use AppBundle\Entity\Category;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Validator\Constraints\Image;

/**
 * @Route("/admin")
 * @Security("is_granted('ROLE_ADMIN')")
 */

class CatalogueController extends Controller
{
    /**
     * @Route("/products", name="products")
     */
    public function productsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $session = $request->getSession();

        $queryBuilder = $em->getRepository('AppBundle:Product')->createQueryBuilder('prod');

        $query = $queryBuilder->getQuery();

        $request->query->getAlnum('records');

        // Can be changed by the user in the view, if requested then changes records number
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

        $products = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', $limit),
            array('defaultSortFieldName' => 'prod.id', 'defaultSortDirection' => 'asc')
        );

        return $this->render('backendadmin/product.html.twig', array(
            'products' => $products,
            'page' => $limit
        ));
    }

    /**
     * @Route("/categories", name="categories")
     */
    public function categoriesAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $categories = $em->getRepository('AppBundle:Category')->findAll();

        // Find the ordering used for the menu
        $categoriesMenuRepository = $em->getRepository('AppBundle:CategoryMenu');
        $categoriesMenuRepository = $categoriesMenuRepository->findOneBy(
            array('name' => 'menu'));

        // If the ordering is saved from the view
        if ($request->query->get('categoriesMenu')) {

            $categoriesMenuRepository->setOrdering($request->query->get('categoriesMenu'));
            $em = $this->getDoctrine()->getManager();
            $em->persist($categoriesMenuRepository);
            $em->flush();

            $this->addFlash('success', 'Main menu updated');
        }

        // Create the array for ordering the categories in the view
        $categoriesMenuFinal = json_decode($categoriesMenuRepository->getOrdering(), true);

        return $this->render('backendadmin/category.html.twig', array(
            'categories' => $categories,
            'categoriesMenu' => $categoriesMenuFinal
        ));
    }

    /**
     * @Route("/products/view/{id}", name="products_view")
     */
    public function productsViewAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $session = $request->getSession();
        $session->clear();

        $categoriesMenuRepository = $em->getRepository('AppBundle:CategoryMenu');
        $productRepository = $em->getRepository('AppBundle:Product');

        $dropshippers = $em->getRepository('AppBundle:Dropshipper')->findAll();
        $categories = $em->getRepository('AppBundle:Category')->findAll();

        /**
         * @var $paginator \Knp\Component\Pager\Paginator
         */
        $paginator  = $this->get('knp_paginator');

        // Find the ordering used for the menu
        $categoriesMenuRepository = $categoriesMenuRepository->findOneBy(
            array('name' => 'menu'));

        // Create the array for ordering the categories in the view
        $categoriesMenuFinal = json_decode($categoriesMenuRepository->getOrdering(), true);

        // Get all products with parameters to search
        $queryBuilderProd = $em->getRepository('AppBundle:Product')->createQueryBuilder('prod');

        $queryProd = $queryBuilderProd->getQuery();

        $limit = 8;
        if ($request->query->getAlnum('searchValue')) {
            $limit = 200;
        }

        $products = $paginator->paginate(
            $queryProd,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', $limit),
            array('defaultSortFieldName' => 'prod.id', 'defaultSortDirection' => 'asc')
        );

        // Find the product requested
        /** @var Product $product */
        $product = $productRepository->findOneBy(
            array('id' => $id));

        // Get categories specific to the product
        $productCategories = $product->getCategories();

        // Check if a product after and before exits
        $nextProduct = 0;
        $previousProduct = 0;

        if ($productRepository->findOneBy(
            array('id' => ($id - 1))
        )) {
            $previousProduct = $id - 1;
        }

        if ($productRepository->findOneBy(
            array('id' => ($id + 1))
        )) {
            $nextProduct = $id + 1;
        }

        //Find related products to this product
        $relatedProductsSelected = [];
        $relatedProductsStored = [];
        if (null !== $request->query->get('checkboxes')) {
            $finalValues = explode(",", $request->query->get('checkboxes'));
            $relatedProductsSelected = [];
            for ($i = 0; $i < count($finalValues); $i++) {
                $relatedProduct = $em->getRepository('AppBundle:Product')
                    ->findOneBy(array(
                        'id' => $finalValues[$i]
                    ));
                $relatedProductsSelected[$i] = $relatedProduct;
            }
        } elseif ($product->getRelatedProductsWithProduct()) {
            $relatedProducts = $product->getRelatedProductsWithProduct();
            for ($i = 0; $i < count($relatedProducts); $i++) {
                $relatedProductsSelected[$i] = $product->getRelatedProductsWithProduct()[$i];
                $relatedProductsStored[$i] = $product->getRelatedProductsWithProduct()[$i]->getId();
            }
        }

        return $this->render('backendadmin/viewProduct.html.twig', array(
            'product' => $product,
            'products' => $products,
            'productCategories' => $productCategories,
            'categories' => $categories,
            'categoriesMenu' => $categoriesMenuFinal,
            'dropshippers' => $dropshippers,
            'previousProduct' => $previousProduct,
            'nextProduct' => $nextProduct,
            'relatedProductsSelected' => $relatedProductsSelected,
            'relatedProductsStored'=> $relatedProductsStored
        ));
    }

    /**
     * @Route("/categories/view/{id}", name="categories_view")
     */
    public function categoriesViewAction($id, Request $request)
    {
        // Find the category the admin wants to view
        $em = $this->getDoctrine()->getManager();

        $categoriesRepository = $em->getRepository('AppBundle:Category');

        /**
         * @var $paginator \Knp\Component\Pager\Paginator
         */
        $paginator  = $this->get('knp_paginator');

        // Get all products with parameters to search
        $queryBuilderProd = $em->getRepository('AppBundle:Product')->createQueryBuilder('prod');

        $queryProd = $queryBuilderProd->getQuery();

        $limit = 8;
        if ($request->query->getAlnum('searchValue')) {
            $limit = 200;
        }

        $products = $paginator->paginate(
            $queryProd,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', $limit),
            array('defaultSortFieldName' => 'prod.id', 'defaultSortDirection' => 'asc')
        );

        // Find requested category
        /** @var Category $category */
        $category = $categoriesRepository->findOneBy(
            array('id' => $id));

        // Check if a category after and before exits
        $nextCategory = 0;
        $previousCategory = 0;

        if ($categoriesRepository->findOneBy(
            array('id' => ($id - 1))
        )) {
            $previousCategory = $id - 1;
        }

        if ($categoriesRepository->findOneBy(
            array('id' => ($id + 1))
        )) {
            $nextCategory = $id + 1;
        }

        //Find allocated products to this category
        $allocatedProductsSelected = [];
        if (null !== $request->query->get('checkboxes')) {
            $finalValues = explode(",", $request->query->get('checkboxes'));
            $allocatedProductsSelected = [];
            for ($i = 0; $i < count($finalValues); $i++) {
                $relatedProduct = $em->getRepository('AppBundle:Product')
                    ->findOneBy(array(
                        'id' => $finalValues[$i]
                    ));
                $allocatedProductsSelected[$i] = $relatedProduct;
            }
        } elseif ($category->getCategoryProducts()) {
            $relatedProducts = $category->getCategoryProducts();
            for ($i = 0; $i < count($relatedProducts); $i++) {
                $relatedProduct = $em->getRepository('AppBundle:Product')
                    ->findOneBy(array(
                        'id' => $relatedProducts[$i]->getId()
                    ));
                $allocatedProductsSelected[$i] = $relatedProduct;
            }
        }

        return $this->render('backendadmin/viewCategory.html.twig', array(
            'category' => $category,
            'products' => $products,
            'previousCategory' => $previousCategory,
            'nextCategory' => $nextCategory,
            'allocatedProductsSelected' => $allocatedProductsSelected
        ));
    }

    /**
     * @Route("/products/new", name="products_new")
     */
    public function productsNewAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $dropshippers = $em->getRepository('AppBundle:Dropshipper')->findAll();
        $categories = $em->getRepository('AppBundle:Category')->findAll();

        $categoriesMenuRepository = $em->getRepository('AppBundle:CategoryMenu');

        // Find the ordering used for the menu
        $categoriesMenuRepository = $categoriesMenuRepository->findOneBy(
            array('name' => 'menu'));

        // Create the array for ordering the categories in the view
        $categoriesMenuFinal = json_decode($categoriesMenuRepository->getOrdering(), true);

        /**
         * @var $paginator \Knp\Component\Pager\Paginator
         */
        $paginator  = $this->get('knp_paginator');

        // Get all products with parameters to search
        $queryBuilderProd = $em->getRepository('AppBundle:Product')->createQueryBuilder('prod');

        $queryProd = $queryBuilderProd->getQuery();

        // Limit the table to 8 products
        $limit = 8;

        // If the user search for a specific term in the table, the limit becomes 100 products
        if ($request->query->getAlnum('searchValue')) {
            $limit = 200;
        }

        $products = $paginator->paginate(
            $queryProd,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', $limit),
            array('defaultSortFieldName' => 'prod.id', 'defaultSortDirection' => 'asc')
        );

        //Find related products to the product requested
        $relatedProductsSelected = [];
        if ($request->query->get('checkboxes')) {
            $finalValues = explode(",", $request->query->get('checkboxes'));
            $relatedProductsSelected = [];
            for ($i = 0; $i < count($finalValues); $i++) {
                $relatedProduct = $em->getRepository('AppBundle:Product')
                    ->findOneBy(array(
                        'id' => $finalValues[$i]
                    ));
                $relatedProductsSelected[$i] = $relatedProduct;
            }
        }

        // If the user filled in all the fields and click on create product
        if ($request->query->get('name')) {

            $product = new Product();

            $product->setName($request->query->get('name'));
            $product->setSku($request->query->get('sku'));
            $product->setPrice($request->query->get('price'));
            $product->setWeight($request->query->get('weight'));

            if ($request->query->get('url')) {
                $product->setSlug($request->query->get('url'));
            }

            if ($request->query->get('metaTitle')) {
                $product->setMetaTitle($request->query->get('metaTitle'));
            }

            if ($request->query->get('metaKeywords')) {
                $product->setMetaKeywords($request->query->get('metaKeywords'));
            }

            if ($request->query->get('metaDescription')) {
                $product->setMetaDescription($request->query->get('metaDescription'));
            }

            if ($request->query->get('shortDescription')) {
                $product->setShortDescription($request->query->get('shortDescription'));
            }

            if ($request->query->get('description')) {
                $product->setDescription($request->query->get('description'));
            }

            if ($request->query->get('capacityTable')) {
                $product->setDescription($request->query->get('capacityTable'));
            }

            if ($request->query->get('contentsTable')) {
                $product->setDescription($request->query->get('contentsTable'));
            }

            if ($request->query->get('productCodeTable')) {
                $product->setDescription($request->query->get('productCodeTable'));
            }

            if ($request->query->get('weightTable')) {
                $product->setDescription($request->query->get('weightTable'));
            }

            if ($request->query->get('dimensionTable')) {
                $product->setDescription($request->query->get('dimensionTable'));
            }

            if ($request->query->get('descriptionTable')) {
                $product->setDescription($request->query->get('descriptionTable'));
            }

            if ($request->query->get('packQuantityTable')) {
                $product->setDescription($request->query->get('packQuantityTable'));
            }

            $product->setDisplay(1);
            $product->setFeatured(0);
            $product->setImageName($request->query->get('imageName'));

            /** @var Dropshipper $dropshipper */
            $dropshipper = $em->getRepository('AppBundle:Dropshipper')
                ->findOneBy(array(
                    'id' => $request->query->get('dropshipper')
                ));

            // If product exists
            if (!$dropshipper) {
                throw $this->createNotFoundException('Dropshipper not found');
            }

            $product->setDropshipper($dropshipper);

            if ($request->query->get('checkboxes')) {
                $relatedProducts = explode(",", $request->query->get('checkboxes'));

                for ($i = 0; $i < count($relatedProducts); $i++) {
                    /** @var Product $product */
                    $relatedProduct = $em->getRepository('AppBundle:Product')
                        ->findOneBy(array(
                            'id' => $relatedProducts[$i]
                        ));
                    $product->addRelatedProductsWithProduct($relatedProduct);
                    $relatedProduct->addProductWithRelatedProduct($product);

                    $em->persist($relatedProduct);
                }
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($product);
            $em->flush();

            if ($request->query->get('categories')) {
                $categories = explode(",", $request->query->get('categories'));

                for ($j = 0; $j < count($categories); $j++) {

                    /** @var Category $category */
                    $category = $em->getRepository('AppBundle:Category')
                        ->findOneBy(array(
                            'id' => $categories[$j]
                        ));

                    $product->addCategoryProduct($category);
                    $category->addCategoryProduct($product);

                    $em->persist($category);
                    $em->persist($product);
                    $em->flush();

                }
            }

            return $this->redirectToRoute('products_view', array('id' => $product->getId()));
        }

    }

    /**
     * @Route("/categories/new", name="categories_new")
     */
    public function categoriesNewAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /**
         * @var $paginator \Knp\Component\Pager\Paginator
         */
        $paginator  = $this->get('knp_paginator');

        // Get all products with parameters to search
        $queryBuilderProd = $em->getRepository('AppBundle:Product')->createQueryBuilder('prod');

        $queryProd = $queryBuilderProd->getQuery();

        $limit = 8;
        if ($request->query->getAlnum('searchValue')) {
            $limit = 200;
        }

        $products = $paginator->paginate(
            $queryProd,
            $request->query->getInt('page', 1),
            $request->query->getInt('limit', $limit),
            array('defaultSortFieldName' => 'prod.id', 'defaultSortDirection' => 'asc')
        );

        //Find related products to this product
        $allocatedProductsSelected = [];
        if ($request->query->get('checkboxes')) {
            $finalValues = explode(",", $request->query->get('checkboxes'));
            $allocatedProductsSelected = [];
            for ($i = 0; $i < count($finalValues); $i++) {
                $relatedProduct = $em->getRepository('AppBundle:Product')
                    ->findOneBy(array(
                        'id' => $finalValues[$i]
                    ));
                $allocatedProductsSelected[$i] = $relatedProduct;
            }
        }

        // If the user filled in all the fields and click on create category
        if ($request->query->get('name')) {

            $category = new Category();

            $category->setName($request->query->get('name'));

            if ($request->query->get('url')) {
                $category->setSlug($request->query->get('url'));
            }

            if ($request->query->get('metaTitle')) {
                $category->setMetaTitle($request->query->get('metaTitle'));
            }

            if ($request->query->get('metaKeywords')) {
                $category->setMetaKeywords($request->query->get('metaKeywords'));
            }

            if ($request->query->get('metaDescription')) {
                $category->setMetaDescription($request->query->get('metaDescription'));
            }

            if ($request->query->get('description')) {
                $category->setDescription($request->query->get('description'));
            }

            $category->setDisplay(1);

            if ($request->query->get('imageName')) {
                $category->setImageName($request->query->get('imageName'));
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush();

            if ($request->query->get('products')) {
                $allProducts = $em->getRepository('AppBundle:Product')->findAll();
                $products = explode(",", $request->query->get('products'));

                foreach ($allProducts as $allProduct) {
                    $category->removeCategoryProduct($allProduct);
                    $allProduct->removeCategoryProduct($category);
                    $em->persist($allProduct);
                    $em->persist($category);
                    $em->flush();
                }

                for ($j = 0; $j < count($products); $j++) {

                    /** @var Product $product */
                    $product = $em->getRepository('AppBundle:Product')
                        ->findOneBy(array(
                            'id' => $products[$j]
                        ));

                    $category->addCategoryProduct($product);
                    $product->addCategoryProduct($category);

                    $em->persist($product);
                    $em->persist($category);
                    $em->flush();

                }
            }

            /** @var CategoryMenu $categoryMenu */
            $categoryMenu = $em->getRepository('AppBundle:CategoryMenu')
                ->findOneBy(array(
                    'name' => 'menu'
                ));
            $categoriesMenu = rtrim($categoryMenu->getOrdering(), ']');
            $categoriesMenu = $categoriesMenu . ',{"url":"' . $category->getSlug() . '"}]';

            $categoryMenu->setOrdering($categoriesMenu);
            $em->persist($categoryMenu);
            $em->flush();

            return $this->redirectToRoute('categories_view', array('id' => $category->getId()));
        }

        return $this->render('backendadmin/newCategory.html.twig', array(
            'products' => $products,
            'allocatedProductsSelected' => $allocatedProductsSelected
        ));
    }

    /**
     * @Route("/products/edit/{id}", name="products_edit")
     */
    public function productsEditAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var Product $product */
        $product = $em->getRepository('AppBundle:Product')
            ->findOneBy(array(
                'id' => $id
            ));

        if ($request->query->get('name')) {

            $product->setName($request->query->get('name'));
            $product->setSku($request->query->get('sku'));
            $product->setPrice($request->query->get('price'));
            $product->setImageName($request->query->get('imageName'));
            $product->setWeight($request->query->get('weight'));

            if ($request->query->get('url')) {
                $product->setSlug($request->query->get('url'));
            }

            if ($request->query->get('metaTitle')) {
                $product->setMetaTitle($request->query->get('metaTitle'));
            }

            if ($request->query->get('metaKeywords')) {
                $product->setMetaKeywords($request->query->get('metaKeywords'));
            }

            if ($request->query->get('metaDescription')) {
                $product->setMetaDescription($request->query->get('metaDescription'));
            }

            if ($request->query->get('shortDescription')) {
                $product->setShortDescription($request->query->get('shortDescription'));
            }

            if ($request->query->get('description')) {
                $product->setDescription($request->query->get('description'));
            }

            if ($request->query->get('capacityTable')) {
                $product->setDescription($request->query->get('capacityTable'));
            }

            if ($request->query->get('contentsTable')) {
                $product->setDescription($request->query->get('contentsTable'));
            }

            if ($request->query->get('productCodeTable')) {
                $product->setDescription($request->query->get('productCodeTable'));
            }

            if ($request->query->get('weightTable')) {
                $product->setDescription($request->query->get('weightTable'));
            }

            if ($request->query->get('dimensionTable')) {
                $product->setDescription($request->query->get('dimensionTable'));
            }

            if ($request->query->get('descriptionTable')) {
                $product->setDescription($request->query->get('descriptionTable'));
            }

            if ($request->query->get('packQuantityTable')) {
                $product->setDescription($request->query->get('packQuantityTable'));
            }

            /** @var Dropshipper $dropshipper */
            $dropshipper = $em->getRepository('AppBundle:Dropshipper')
                ->findOneBy(array(
                    'id' => $request->query->get('dropshipper')
                ));

            // If product exists
            if (!$dropshipper) {
                throw $this->createNotFoundException('Dropshipper not found');
            }

            $product->setDropshipper($dropshipper);

            if ($request->query->get('checkboxes')) {
                $allProducts = $em->getRepository('AppBundle:Product')->findAll();
                $relatedProducts = explode(",", $request->query->get('checkboxes'));

                foreach ($allProducts as $allProduct) {
                    $product->removeRelatedProductsWithProduct($allProduct);
                    $allProduct->removeProductWithRelatedProduct($product);
                    $em->persist($allProduct);
                    $em->persist($product);
                    $em->flush();
                }

                for ($i = 0; $i < count($relatedProducts); $i++) {
                    /** @var Product $product */
                    $relatedProduct = $em->getRepository('AppBundle:Product')
                        ->findOneBy(array(
                            'id' => $relatedProducts[$i]
                        ));
                    $product->addRelatedProductsWithProduct($relatedProduct);
                    $relatedProduct->addProductWithRelatedProduct($product);

                    $em->persist($relatedProduct);
                }
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($product);
            $em->flush();

            if ($request->query->get('categories')) {
                $allCategories = $em->getRepository('AppBundle:Category')->findAll();
                $categories = explode(",", $request->query->get('categories'));

                foreach ($allCategories as $allCategory) {
                    $product->removeCategoryProduct($allCategory);
                    $allCategory->removeCategoryProduct($product);
                    $em->persist($allCategory);
                    $em->persist($product);
                    $em->flush();
                }

                for ($j = 0; $j < count($categories); $j++) {

                    /** @var Category $category */
                    $category = $em->getRepository('AppBundle:Category')
                        ->findOneBy(array(
                            'id' => $categories[$j]
                        ));

                    $product->addCategoryProduct($category);
                    $category->addCategoryProduct($product);

                    $em->persist($category);
                    $em->persist($product);
                    $em->flush();

                }
            }

        }

        return $this->redirectToRoute('products_view', array('id' => $id));
    }

    /**
     * @Route("/categories/edit/{id}", name="categories_edit")
     */
    public function categoriesEditAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var Category $category */
        $category = $em->getRepository('AppBundle:Category')
            ->findOneBy(array(
                'id' => $id
            ));

        if ($request->query->get('name')) {

            $categoryOldUrl = $category->getSlug();

            $category->setName($request->query->get('name'));
            $category->setImageName($request->query->get('imageName'));

            if ($request->query->get('url')) {
                $category->setSlug($request->query->get('url'));
            }

            if ($request->query->get('metaTitle')) {
                $category->setMetaTitle($request->query->get('metaTitle'));
            }

            if ($request->query->get('metaKeywords')) {
                $category->setMetaKeywords($request->query->get('metaKeywords'));
            }

            if ($request->query->get('metaDescription')) {
                $category->setMetaDescription($request->query->get('metaDescription'));
            }

            if ($request->query->get('description')) {
                $category->setDescription($request->query->get('description'));
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($category);
            $em->flush();

            /** @var CategoryMenu $categoryMenu */
            $categoryMenu = $em->getRepository('AppBundle:CategoryMenu')
                ->findOneBy(array(
                    'name' => "menu"
                ));

            $categoryMenu->setOrdering(str_replace($categoryOldUrl,$category->getSlug(),$categoryMenu->getOrdering()));

            $em = $this->getDoctrine()->getManager();
            $em->persist($categoryMenu);
            $em->flush();

            if ($request->query->get('products')) {

                $allProducts = $em->getRepository('AppBundle:Product')->findAll();
                $products = explode(",", $request->query->get('products'));

                foreach ($allProducts as $allProduct) {
                    $category->removeCategoryProduct($allProduct);
                    $allProduct->removeCategoryProduct($category);
                    $em->persist($allProduct);
                    $em->persist($category);
                    $em->flush();
                }

                for ($j = 0; $j < count($products); $j++) {

                    /** @var Product $product */
                    $product = $em->getRepository('AppBundle:Product')
                        ->findOneBy(array(
                            'id' => $products[$j]
                        ));

                    $category->addCategoryProduct($product);
                    $product->addCategoryProduct($category);

                    $em->persist($product);
                    $em->persist($category);
                    $em->flush();

                }
            } else {
                $allProducts = $em->getRepository('AppBundle:Product')->findAll();

                foreach ($allProducts as $allProduct) {
                    $category->removeCategoryProduct($allProduct);
                    $allProduct->removeCategoryProduct($category);
                    $em->persist($allProduct);
                    $em->persist($category);
                    $em->flush();
                }
            }

        }

        return $this->redirectToRoute('categories_view', array('id' => $id));
    }

    /**
     * @Route("/products/delete/{id}", name="products_delete")
     */
    public function productsDeleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $product = $em->getRepository('AppBundle:Product')
            ->findOneBy(array(
                'id' => $id
            ));

        // If product exists
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $em->remove($product);
        $em->flush();

        return $this->redirectToRoute('products');
    }

    /**
     * @Route("/categories/delete/{id}", name="categories_delete")
     */
    public function categoriesDeleteAction($id)
    {
        // Find the category the admin wants to view
        $em = $this->getDoctrine()->getManager();

        $categoriesMenuRepository = $em->getRepository('AppBundle:CategoryMenu');
        $categoriesRepository = $em->getRepository('AppBundle:Category');

        // Find requested category
        /** @var Category $category */
        $category = $categoriesRepository->findOneBy(
            array('id' => $id));

        $categoriesMenuRep = $categoriesMenuRepository->findOneBy(
            array('name' => 'menu'));
        $categoriesMenu = json_decode($categoriesMenuRep->getOrdering(), true);

        $categoriesMenuFinal = [];

        // delete
        foreach ($categoriesMenu as $categoryMenu) {

            // array with children level 1
            if (count($categoryMenu) > 1 && is_array($categoryMenu["children"])) {
                if ($categoryMenu["url"] == $category->getSlug()) {
                    $testChild = ltrim(json_encode($categoryMenu["children"]), '[');
                    $testChild = rtrim($testChild, ']');
                    $categoriesMenuFinal = str_replace(json_encode($categoryMenu),$testChild,$categoriesMenuRep->getOrdering());
                }

                // array with children level 2
                foreach ($categoryMenu["children"] as $itemInChildren) {
                    if (count($itemInChildren) > 1 && is_array($itemInChildren["children"])) {
                        if ($itemInChildren["url"] == $category->getSlug()) {
                            $testChild = ltrim(json_encode($itemInChildren["children"]), '[');
                            $testChild = rtrim($testChild, ']');
                            $test1 = ',' . json_encode($itemInChildren);
                            $test2 = json_encode($itemInChildren) . ',';
                            if (strpos($categoriesMenuRep->getOrdering(), $test1) !== false) {
                                $categoriesMenuFinal = str_replace($test1,',' . $testChild,$categoriesMenuRep->getOrdering());
                            } else if (strpos($categoriesMenuRep->getOrdering(), $test2) !== false) {
                                $categoriesMenuFinal = str_replace($test2,$testChild . ',',$categoriesMenuRep->getOrdering());
                            } else {
                                $test3 = str_replace(json_encode($itemInChildren),'',$categoriesMenuRep->getOrdering());
                                $categoriesMenuFinal = str_replace(',"children":[]}','},' . $testChild,$test3);
                            }
                        }

                        // array with children level 3
                        foreach ($itemInChildren["children"] as $itemInChildren2) {
                            if (count($itemInChildren2) > 1 && is_array($itemInChildren2["children"])) {
                                if ($itemInChildren2["url"] == $category->getSlug()) {
                                    // Not possible as only 3 levels
                                }

                            } else {

                                // array without children level 3
                                if ($itemInChildren2["url"] == $category->getSlug()) {
                                    $test1 = ',' . json_encode($itemInChildren2);
                                    $test2 = json_encode($itemInChildren2) . ',';
                                    if (strpos($categoriesMenuRep->getOrdering(), $test1) !== false) {
                                        $categoriesMenuFinal = str_replace($test1,'',$categoriesMenuRep->getOrdering());
                                    } else if (strpos($categoriesMenuRep->getOrdering(), $test2) !== false) {
                                        $categoriesMenuFinal = str_replace($test2,'',$categoriesMenuRep->getOrdering());
                                    } else {
                                        $test3 = str_replace(json_encode($itemInChildren2),'',$categoriesMenuRep->getOrdering());
                                        $categoriesMenuFinal = str_replace(',"children":[]}','}',$test3);
                                    }
                                }
                            }
                        }

                    } else {

                        // array without children level 2
                        if ($itemInChildren["url"] == $category->getSlug()) {
                            $test1 = ',' . json_encode($itemInChildren);
                            $test2 = json_encode($itemInChildren) . ',';
                            if (strpos($categoriesMenuRep->getOrdering(), $test1) !== false) {
                                $categoriesMenuFinal = str_replace($test1,'',$categoriesMenuRep->getOrdering());
                            } else if (strpos($categoriesMenuRep->getOrdering(), $test2) !== false) {
                                $categoriesMenuFinal = str_replace($test2,'',$categoriesMenuRep->getOrdering());
                            } else {
                                $test3 = str_replace(json_encode($itemInChildren),'',$categoriesMenuRep->getOrdering());
                                $categoriesMenuFinal = str_replace(',"children":[]}','}',$test3);
                            }
                        }
                    }
                }

            } else {

                // array without children level 1
                if ($categoryMenu["url"] == $category->getSlug()) {
                    $test1 = ',' . json_encode($categoryMenu);
                    $test2 = json_encode($categoryMenu) . ',';
                    if (strpos($categoriesMenuRep->getOrdering(), $test1) !== false) {
                        $categoriesMenuFinal = str_replace($test1,'',$categoriesMenuRep->getOrdering());
                    } else if (strpos($categoriesMenuRep->getOrdering(), $test2) !== false) {
                        $categoriesMenuFinal = str_replace($test2,'',$categoriesMenuRep->getOrdering());
                    } else {
                        $categoriesMenuFinal = str_replace(json_encode($categoryMenu),'',$categoriesMenuRep->getOrdering());
                    }

                }
            }
        }

        $categoriesMenuRep->setOrdering($categoriesMenuFinal);
        $em->persist($categoriesMenuRep);
        $em->flush();

        $this->addFlash('success', 'Category ' . $category->getName() . ' has been successfully deleted, if the category deleted had sub-level(s) they are now available at a lower level. Please check below.');

        $em->remove($category);
        $em->flush();

        return $this->redirectToRoute('categories');
    }

    /**
     * @Route("/products/change", name="products_change_display_or_featured_product")
     */
    public function productsDisplayFeaturedAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var Product $product */
        $product = $em->getRepository('AppBundle:Product')
            ->findOneBy(array(
                'id' => $request->query->get('id')
            ));

        // If product exists
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        // If user wants to change the display status of the product
        if ($request->query->get('display')) {

            if ($request->query->get('display') == 2) {

                $product->setDisplay(0);
                $em->persist($product);
                $em->flush();
            } else {

                $product->setDisplay(1);
                $em->persist($product);
                $em->flush();
            }

            // If user wants to change the featured status of the product
        } elseif ($request->query->get('featured')) {

            if ($request->query->get('featured') == 2) {

                $product->setFeatured(0);
                $em->persist($product);
                $em->flush();
            } else {

                $product->setFeatured(1);
                $em->persist($product);
                $em->flush();
            }
        }

        return $this->redirectToRoute('products');
    }

    /**
     * @Route("/categories/change", name="categories_change_display")
     */
    public function categoriesDisplayAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var Product $product */
        $category = $em->getRepository('AppBundle:Category')
            ->findOneBy(array(
                'id' => $request->query->get('id')
            ));

        // If the category exists
        if (!$category) {
            throw $this->createNotFoundException('Category not found');
        }

        // If user wants to change the display status of the category
        if ($request->query->get('display')) {

            if ($request->query->get('display') == 2) {

                $category->setDisplay(0);
                $em->persist($category);
                $em->flush();
            } else {

                $category->setDisplay(1);
                $em->persist($category);
                $em->flush();
            }
        }

        return $this->redirectToRoute('categories');
    }

    /**
     * @Route("/products/changes", name="products_changes_display_product")
     */
    public function productsDisplaysAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        // Explode the products from ids separated by comma. ie: 1,2,3,4,5 to Array
        $ids = explode( ',', $request->query->get('productsId') );

        // Change each products to this new display status
        for ($x = 0; $x < count($ids); $x++) {
            /** @var Product $product */
            $product = $em->getRepository('AppBundle:Product')
                ->findOneBy(array(
                    'id' => $ids[$x]
                ));

            // If products exists
            if (!$product) {
                throw $this->createNotFoundException('product not found');
            }

            $product->setDisplay((int)($request->query->get('display') - 1));
            $em->persist($product);
            $em->flush();

        }

        return $this->redirectToRoute('products');
    }

    /**
     * @Route("/products/price", name="products_change_price")
     */
    public function productsPriceAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var Product $product */
        $product = $em->getRepository('AppBundle:Product')
            ->findOneBy(array(
                'id' => $request->query->get('id')
            ));

        // If product exists
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        // If user wants to change the price of the product
        $product->setPrice(($request->query->get('price')));
        $em->persist($product);
        $em->flush();

        return $this->redirectToRoute('products');
    }

    /**
     * @Route("/products/image-preview", name="products_image_preview")
     */
    public function productsImagePreviewAction(Request $request)
    {
        $pathImagePreview = 0;

        if($request->files->all()) {
            if($request->files->all()['form']['image']) {

                $uploadedFile = $request->files->all()['form']['image'];

                if (!$uploadedFile->isValid()) {
                    // will by one of the constants like UPLOAD_ERR_INI_SIZE or UPLOAD_ERR_FORM_SIZE
                    $error = $uploadedFile->getValue();

                }

                $fileName = md5(uniqid()) . '.' . $uploadedFile->guessExtension();

                $uploadedFile->move(
                    $this->getParameter('product_images_preview_directory'),
                    $fileName
                );

                $pathImagePreview = 'assets/images/products/tmp/' . $fileName;
            }
        }

        return $this->render('backendadmin/product/_previewImage.html.twig', array(
            'pathImagePreview' => $pathImagePreview
        ));
    }

    /**
     * @Route("/categories/image-preview", name="categories_image_preview")
     */
    public function categoriesImagePreviewAction(Request $request)
    {
        $pathImagePreview = 0;

        if($request->files->all()) {
            if($request->files->all()['form']['image']) {

                $uploadedFile = $request->files->all()['form']['image'];

                if (!$uploadedFile->isValid()) {
                    // will by one of the constants like UPLOAD_ERR_INI_SIZE or UPLOAD_ERR_FORM_SIZE
                    $error = $uploadedFile->getValue();

                }

                $fileName = md5(uniqid()) . '.' . $uploadedFile->guessExtension();

                $uploadedFile->move(
                    $this->getParameter('category_images_preview_directory'),
                    $fileName
                );

                $pathImagePreview = 'assets/images/categories/tmp/' . $fileName;
            }
        }

        return $this->render('backendadmin/category/_previewImage.html.twig', array(
            'pathImagePreview' => $pathImagePreview
        ));
    }

    /**
     * @Route("/products/image-save/{id}", name="products_image_save")
     */
    public function productsImageSaveAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var Product $product */
        $product = $em->getRepository('AppBundle:Product')
            ->findOneBy(array(
                'id' => $id
            ));

        // If product exists
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $fileName = $product->getImageName();

        if($request->files->all() && $request->files->all()['form']['image']) {

            $uploadedFile = $request->files->all()['form']['image'];

            if (!$uploadedFile->isValid()) {
                // will by one of the constants like UPLOAD_ERR_INI_SIZE or UPLOAD_ERR_FORM_SIZE
                $error = $uploadedFile->getValue();

            }

            $cacheManager = $this->get('liip_imagine.cache.manager');
            $cacheManager->remove('assets/images/products/' . $fileName);

            $fileName = $product->getSlug() . '.' . $uploadedFile->guessExtension();

            $uploadedFile->move(
                $this->getParameter('product_images_directory'),
                $fileName
            );
        }

        return $this->render('backendadmin/product/_saveImage.html.twig', array(
            'pathImageSave' => $fileName
        ));
    }

    /**
     * @Route("/categories/image-save/{id}", name="categories_image_save")
     */
    public function categoriesImageSaveAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var Category $category */
        $category = $em->getRepository('AppBundle:Category')
            ->findOneBy(array(
                'id' => $id
            ));

        // If product exists
        if (!$category) {
            throw $this->createNotFoundException('Product not found');
        }

        $fileName = $category->getImageName();

        if($request->files->all() && $request->files->all()['form']['image']) {

            $uploadedFile = $request->files->all()['form']['image'];

            if (!$uploadedFile->isValid()) {
                // will by one of the constants like UPLOAD_ERR_INI_SIZE or UPLOAD_ERR_FORM_SIZE
                $error = $uploadedFile->getValue();

            }

            $cacheManager = $this->get('liip_imagine.cache.manager');
            $cacheManager->remove('assets/images/categories/' . $fileName);

            $fileName = $category->getSlug() . '.' . $uploadedFile->guessExtension();

            $uploadedFile->move(
                $this->getParameter('category_images_directory'),
                $fileName
            );
        }

        return $this->render('backendadmin/category/_saveImage.html.twig', array(
            'pathImageSave' => $fileName
        ));
    }

    /**
     * @Route("/products/image-save-new", name="products_image_save_new")
     */
    public function productsImageSaveNewAction(Request $request)
    {
        // replace non letter or digits by -
        $name = preg_replace('~[^\pL\d]+~u', '-', $request->query->get('name'));

        // transliterate
        $name = iconv('utf-8', 'us-ascii//TRANSLIT', $name);

        // remove unwanted characters
        $name = preg_replace('~[^-\w]+~', '', $name);

        // trim
        $name = trim($name, '-');

        // remove duplicate -
        $name = preg_replace('~-+~', '-', $name);

        // lowercase
        $name = strtolower($name);

        if (empty($name)) {
            return;
        }

        if($request->files->all() && $request->files->all()['form']['image']) {

            $uploadedFile = $request->files->all()['form']['image'];

            if (!$uploadedFile->isValid()) {
                // will by one of the constants like UPLOAD_ERR_INI_SIZE or UPLOAD_ERR_FORM_SIZE
                $error = $uploadedFile->getValue();

            }

            $name = $name . '.' . $uploadedFile->guessExtension();

            $uploadedFile->move(
                $this->getParameter('product_images_directory'),
                $name
            );
        }

        return $this->render('backendadmin/product/_saveImage.html.twig', array(
            'pathImageSave' => $name
        ));
    }

    /**
     * @Route("/categories/image-save-new", name="categories_image_save_new")
     */
    public function categoriesImageSaveNewAction(Request $request)
    {
        $name = 0;

        if ($request->query->get('name')) {
            // replace non letter or digits by -
            $name = preg_replace('~[^\pL\d]+~u', '-', $request->query->get('name'));

            // transliterate
            $name = iconv('utf-8', 'us-ascii//TRANSLIT', $name);

            // remove unwanted characters
            $name = preg_replace('~[^-\w]+~', '', $name);

            // trim
            $name = trim($name, '-');

            // remove duplicate -
            $name = preg_replace('~-+~', '-', $name);

            // lowercase
            $name = strtolower($name);

            if (empty($name)) {
                return;
            }

            if ($request->files->all() && $request->files->all()['form']['image']) {

                $uploadedFile = $request->files->all()['form']['image'];

                if (!$uploadedFile->isValid()) {
                    // will by one of the constants like UPLOAD_ERR_INI_SIZE or UPLOAD_ERR_FORM_SIZE
                    $error = $uploadedFile->getValue();

                }

                $name = $name . '.' . $uploadedFile->guessExtension();

                $uploadedFile->move(
                    $this->getParameter('category_images_directory'),
                    $name
                );
            } else {
                $name = 0;
            }
        }

        return $this->render('backendadmin/category/_saveImage.html.twig', array(
            'pathImageSave' => $name
        ));
    }
}


