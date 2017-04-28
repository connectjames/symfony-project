<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ProductRepository")
 * @ORM\Table(name="product")
 */
class Product
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $sku;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $name;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $metaTitle;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $metaDescription;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $metaKeywords;

    /**
     * @ORM\ManyToMany(targetEntity="Category", mappedBy="categoryProducts")
     * @ORM\OrderBy({"name" = "ASC"})
     */
    private $categories;

    /**
     * @ORM\ManyToOne(targetEntity="Dropshipper", inversedBy="products")
     * @ORM\JoinColumn(name="dropshipper_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $dropshipper;

    /**
     * @ORM\Column(type="decimal", scale=2)
     */
    private $price;

    /**
     * @ORM\Column(type="string", unique=true)
     * @Gedmo\Slug(fields={"name"})
     */
    private $slug;

    /**
     * @ORM\Column(type="integer", options={"default":1})
     */
    private $display;

    /**
     * @Assert\Image
     */
    protected $image;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $ImageName;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     */
    private $shortDescription;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $dimension;

    /**
     * @ORM\Column(type="integer")
     */
    private $weight;

    /**
     * @ORM\Column(type="integer", options={"default":0})
     */
    private $featured;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     */
    private $capacityTable;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     */
    private $contentsTable;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     */
    private $productCodeTable;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     */
    private $weightTable;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     */
    private $dimensionTable;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     */
    private $descriptionTable;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     */
    private $packQuantityTable;

    /**
     * @ORM\ManyToMany(targetEntity="Product", mappedBy="relatedProductsWithProduct")
     */
    private $productWithRelatedProducts;

    /**
     * @ORM\ManyToMany(targetEntity="Product", inversedBy="productWithRelatedProducts")
     * @ORM\JoinTable(name="related_products",
     *      joinColumns={@ORM\JoinColumn(name="product_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="related_product_id", referencedColumnName="id")}
     *      )
     */
    private $relatedProductsWithProduct;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->productWithRelatedProducts = new ArrayCollection();
        $this->relatedProductsWithProduct = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setSku($sku)
    {
        $this->sku = $sku;

        return $this;
    }

    public function getSku()
    {
        return $this->sku;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getMetaTitle()
    {
        return $this->metaTitle;
    }

    public function setMetaTitle($metaTitle)
    {
        $this->metaTitle = $metaTitle;
    }

    public function setMetaDescription($metaDescription)
    {
        $this->metadescription = $metaDescription;

        return $this;
    }

    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

    public function setMetaKeywords($metaKeywords)
    {
        $this->metaKeywords = $metaKeywords;

        return $this;
    }

    public function getMetaKeywords()
    {
        return $this->metaKeywords;
    }

    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    public function setDisplay($display)
    {
        $this->display = $display;

        return $this;
    }

    public function getDisplay()
    {
        return $this->display;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function getImageName()
    {
        return $this->ImageName;
    }

    public function setImageName($ImageName)
    {
        $this->ImageName = $ImageName;
    }

    public function setShortDescription($shortDescription)
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getShortDescription()
    {
        return $this->shortDescription;
    }

    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDimension($dimension)
    {
        $this->dimension = $dimension;

        return $this;
    }

    public function getDimension()
    {
        return $this->dimension;
    }

    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    public function getWeight()
    {
        return $this->weight;
    }

    public function getFeatured()
    {
        return $this->featured;
    }

    public function setFeatured($featured)
    {
        $this->featured = $featured;
    }

    public function getCapacityTable()
    {
        return $this->capacityTable;
    }

    public function setCapacityTable($capacityTable)
    {
        $this->capacityTable = $capacityTable;
    }

    public function getContentsTable()
    {
        return $this->contentsTable;
    }

    public function setContentsTable($contentsTable)
    {
        $this->contentsTable = $contentsTable;
    }

    public function getProductCodeTable()
    {
        return $this->productCodeTable;
    }

    public function setProductCodeTable($productCodeTable)
    {
        $this->productCodeTable = $productCodeTable;
    }

    public function getWeightTable()
    {
        return $this->weightTable;
    }

    public function setWeightTable($weightTable)
    {
        $this->weightTable = $weightTable;
    }

    public function getDimensionTable()
    {
        return $this->dimensionTable;
    }

    public function setDimensionTable($dimensionTable)
    {
        $this->dimensionTable = $dimensionTable;
    }

    public function getDescriptionTable()
    {
        return $this->descriptionTable;
    }

    public function setDescriptionTable($descriptionTable)
    {
        $this->descriptionTable = $descriptionTable;
    }

    public function getPackQuantityTable()
    {
        return $this->packQuantityTable;
    }

    public function setPackQuantityTable($packQuantityTable)
    {
        $this->packQuantityTable = $packQuantityTable;
    }

    /**
     * Set dropshipper
     *
     * @param \AppBundle\Entity\Dropshipper $dropshipper
     *
     * @return Product
     */
    public function setDropshipper(Dropshipper $dropshipper = null)
    {
        $this->dropshipper = $dropshipper;

        return $this;
    }

    /**
     * Get dropshipper
     *
     * @return \AppBundle\Entity\Dropshipper
     */
    public function getDropshipper()
    {
        return $this->dropshipper;
    }

    /**
     * @return ArrayCollection|Category[]
     */
    public function getCategories()
    {
        return $this->categories;
    }

    public function addCategoryProduct(Category $category)
    {
        if ($this->categories->contains($category)) {
            return;
        }

        $this->categories[] = $category;
    }

    public function removeCategoryProduct(Category $category)
    {
        if ($this->categories->contains($category)) {
            $this->categories->removeElement($category);
        }

        return;
    }

    public function addProductWithRelatedProduct(Product $productWithRelatedProduct)
    {
        if ($this->productWithRelatedProducts->contains($productWithRelatedProduct)) {
            return;
        }

        $this->productWithRelatedProducts[] = $productWithRelatedProduct;
    }

    public function removeProductWithRelatedProduct(Product $productWithRelatedProduct)
    {
        if ($this->productWithRelatedProducts->contains($productWithRelatedProduct)) {
            $this->productWithRelatedProducts->removeElement($productWithRelatedProduct);
        }

        return;
    }

    /**
     * @return ArrayCollection|Product[]
     */
    public function getProductWithRelatedProducts()
    {
        return $this->productWithRelatedProducts;
    }

    public function addRelatedProductsWithProduct(Product $relatedProductWithProduct)
    {
        if ($this->relatedProductsWithProduct->contains($relatedProductWithProduct)) {
            return;
        }

        $this->relatedProductsWithProduct[] = $relatedProductWithProduct;
    }

    public function removeRelatedProductsWithProduct(Product $relatedProductWithProduct)
    {
        if ($this->relatedProductsWithProduct->contains($relatedProductWithProduct)) {
            $this->relatedProductsWithProduct->removeElement($relatedProductWithProduct);
        }

        return;
    }

    /**
     * @return ArrayCollection|Product[]
     */
    public function getRelatedProductsWithProduct()
    {
        return $this->relatedProductsWithProduct;
    }
}
