<?php

/**
 * Class GetCategoryModel.
 *
 * @OA\Schema (
 *     schema="GetCategoryModel"
 * )
 */
class GetCategoryModel
{
    /**
     * @OA\Property(
     *     description="Category Id",
     * )
     *
     * @var int
     */
    private $category_id;

    /**
     * @OA\Property(
     *     description="Category UUID",
     * )
     *
     * @var string
     */
    private $uuid;

    /**
     * @OA\Property(
     *     description="Parent Category Id",
     * )
     *
     * @var int
     */
    private $parent_id;

    /**
     * @OA\Property(
     *     description="Category Path",
     * )
     * @var string
     */
    private $path;

    /**
     * @OA\Property(
     *     description="Total products count",
     * )
     * @var int
     */
    private $total_products_count;

    /**
     * @OA\Property(
     *     description="Active products count",
     * )
     * @var int
     */
    private $active_products_count;

    /**
     * @OA\Property(
     *     description="Child categories count",
     * )
     * @var int
     */
    private $children_count;

    /**
     * @OA\Property(
     *     description="Sort Order",
     * )
     * @var int
     */
    private $sort_order;

    /**
     * @OA\Property(
     *     description="Status",
     * )
     * @var int
     */
    private $status;

    /**
     * @OA\Property(
     *     description="Date added",
     * )
     * @var string
     */
    private $date_added;

    /**
     * @OA\Property(
     *     description="Date modiefied",
     * )
     * @var string
     */
    private $date_modified;

    /**
     * @OA\Property(
     *     description="Date deleted",
     * )
     * @var string
     */
    private $date_deleted;

    /**
     * @OA\Property(
     *     description="Stage Id",
     * )
     * @var string
     */
    private $stage_id;

    /**
     * @OA\Property(
     *     description="Id",
     * )
     * @var int
     */
    private $id;

    /**
     * @OA\Property(
     *     description="Language Id",
     * )
     * @var int
     */
    private $language_id;

    /**
     * @OA\Property(
     *     description="Category Name",
     * )
     * @var string
     */
    private $name;

    /**
     * @OA\Property(
     *     description="Meta Keywords",
     * )
     * @var string
     */
    private $meta_keywords;

    /**
     * @OA\Property(
     *     description="Meta Description",
     * )
     * @var string
     */
    private $meta_description;

    /**
     * @OA\Property(
     *     description="Description",
     * )
     * @var string
     */
    private $description;

    /**
     * @OA\Property(
     *     description="Store Id",
     * )
     * @var int
     */
    private $store_id;

    /**
     * @OA\Property(
     *     description="Count of products in top level of category",
     * )
     * @var int
     */
    private $products_count;

    /**
     * @OA\Property(
     *     description="Category thumbnail url",
     * )
     * @var string
     */
    private $thumbnail;

    /**
     * @OA\Property(
     *     description="Total products",
     * )
     * @var int
     */
    private $total_products;

    /**
     * @OA\Property(
     *     description="Total subcategories count",
     * )
     * @var int
     */
    private $total_subcategories;

    /**
     * @OA\Property(
     *     description="Subcategories list",
     *     @OA\Items(
     *     ref="#/components/schemas/SubcategoryModel"
     *  )
     * )
     *
     * @var array
     */
    private $subcategories;

}

/**
 * Class SubcategoryModel.
 *
 * @OA\Schema (
 *     schema="SubcategoryModel"
 * )
 */
class SubcategoryModel
{
    /**
     * @OA\Property(
     *     description="Category Id",
     * )
     * @var int
     */
    private $category_id;

    /**
     * @OA\Property(
     *     description="Category Name",
     * )
     * @var string
     */
    private $name;

    /**
     * @OA\Property(
     *     description="Sort Order",
     * )
     * @var int
     */
    private $sort_order;

    /**
     * @OA\Property(
     *     description="Category Thumb url",
     * )
     * @var string
     */
    private $thumb;

    /**
     * @OA\Property(
     *     description="Count of subcategories",
     * )
     * @var int
     */
    private $total_subcategories;
}
