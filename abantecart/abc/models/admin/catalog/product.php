<?php
/*------------------------------------------------------------------------------
  $Id$

  AbanteCart, Ideal OpenSource Ecommerce Solution
  http://www.AbanteCart.com

  Copyright © 2011-2018 Belavier Commerce LLC

  This source file is subject to Open Software License (OSL 3.0)
  License details is bundled with this package in the file LICENSE.txt.
  It is also available at this URL:
  <http://www.opensource.org/licenses/OSL-3.0>

 UPGRADE NOTE:
   Do not edit or add to this file if you wish to upgrade AbanteCart to newer
   versions in the future. If you wish to customize AbanteCart for your
   needs please refer to http://www.AbanteCart.com for more information.
------------------------------------------------------------------------------*/

namespace abc\models\admin;

use abc\core\ABC;
use abc\core\lib\ALanguageManager;
use abc\core\engine\HtmlElementFactory;
use abc\core\engine\Model;
use abc\core\lib\ADB;
use abc\core\lib\ALayoutManager;
use abc\core\lib\AResourceManager;
use abc\core\lib\contracts\AttributeManagerInterface;
use abc\models\catalog\Category;
use abc\models\catalog\ProductOption;
use abc\models\catalog\ProductOptionValueDescription;
use abc\modules\events\ABaseEvent;
use H;

/**
 * @property \abc\models\admin\ModelCatalogDownload $model_catalog_download
 * @property ALanguageManager $language
 * @property ADB $db
 */
class ModelCatalogProduct extends Model
{

    public $data = [];

    /* public function addProduct($data)
     {
         $language_id = (int)$this->language->getContentLanguageID();

         $fields = $this->getProductColumns();
         $this->data['preformat_fields'] = [
         "shipping_price",
         "price",
         "cost",
         "weight",
         "length",
         "width",
         "height",
     ];
         $this->data['perform_json'] = [];
         $this->data['perform_serialize'] = [];
         $this->data['timestamps'] = ['date_available'];

         $this->data['nullable'] = ['sku'];

         $affected_tables = [];

         $this->extensions->hk_InitData($this, __FUNCTION__);

         $insert = [];
           foreach ($fields as $f) {
               if (isset($data[$f])) {
                   if (in_array($f, $this->data['preformat_fields'])) {
                       $data[$f] = H::preformatFloat($data[$f], $this->language->get('decimal_point'));
                       $insert[] = $f." = '".$this->db->escape($data[$f])."'";
                   }elseif (in_array($f, $this->data['nullable'])) {
                       $insert[] = $f." = ".($data[$f] ? "'".$this->db->escape($data[$f])."'" : "NULL");
                   }elseif (in_array($f, $this->data['timestamps'])) {
                       $insert[] = $f." = ".($data[$f] ? "'".$this->db->escape($data[$f])."'" : "NOW()");
                   } elseif (in_array($f, $this->data['perform_json'])) {
                       $insert[] = $f." = '".json_encode($data[$f])."'";
                   } elseif (in_array($f, $this->data['perform_serialize'])) {
                       $insert[] = $f." = '".serialize($data[$f])."'";
                   } else {
                       $insert[] = $f." = '".$this->db->escape($data[$f])."'";
                   }

               }
           }

         if (!empty($insert)) {
             $this->db->query(
                 "INSERT INTO `".$this->db->table_name("products")."`
                 SET ".implode(',', $insert)
             );
             $affected_tables[] = 'products';
         }
         $product_id = $this->db->getLastId();


         // if new product
         if (!is_int(key($data['product_description']))) {
             $update = [];
             foreach ($data['product_description'] as $field => $value) {
                 $update[$language_id][$field] = $value;
             }
             $this->language->replaceDescriptions(
                 'product_descriptions',
                 ['product_id' => (int)$product_id],
                 $update
             );
             $affected_tables[] = 'product_descriptions';
         } else { // if cloning
             foreach ($data['product_description'] as $language_id => $value) {
                 $this->db->query(
                     "INSERT INTO ".$this->db->table_name("product_descriptions")."
                     SET product_id = '".(int)$product_id."',
                         language_id = '".(int)$language_id."',
                         name = '".$this->db->escape($value['name'])."',
                         meta_keywords = '".$this->db->escape($value['meta_keywords'])."',
                         meta_description = '".$this->db->escape($value['meta_description'])."',
                         description = '".$this->db->escape($value['description'])."',
                         blurb = '".$this->db->escape($value['blurb'])."'"
                 );
             }
             reset($data['product_description']);
         }

         $seo_keys = [];
         if ($data['keyword']) {
             if (is_string($data['keyword'])) {
                 $seo_keys = [
                     $language_id => [
                         'keyword' => H::SEOEncode($data['keyword'], 'product_id', $product_id),
                     ],
                 ];
             } //when cloning
             else {
                 if (is_array($data['keyword'])) {
                     $all_languages = $this->language->getAvailableLanguages();
                     $all_ids = [];
                     foreach ($all_languages as $l) {
                         $all_ids[] = $l['language_id'];
                     }
                     foreach ($data['keyword'] as $lang_id => $seo_key) {
                         if (!in_array($lang_id, $all_ids)) {
                             continue;
                         }
                         $seo_keys[(int)$lang_id] = [
                             'keyword' => H::SEOEncode($seo_key, 'product_id', $product_id),
                         ];
                     }
                 }
             }
         } else {
             //Default behavior to save SEO URL keyword from product name in default language
             // when new product
             if (!is_int(key($data['product_description']))) {
                 $seo_keys = [
                     $language_id => [
                         'keyword' => H::SEOEncode($data['product_description']['name'], 'product_id', $product_id),
                     ],
                 ];
             } else { // when clones
                 $product_seo_keys = $this->getProductSEOKeywords($product_id);

                 $all_languages = $this->language->getAvailableLanguages();
                 $all_ids = [];
                 foreach ($all_languages as $l) {
                     $all_ids[] = $l['language_id'];
                 }
                 foreach ($product_seo_keys as $lang_id => $seo_key) {
                     if (!in_array($lang_id, $all_ids)) {
                         continue;
                     }
                     $seo_keys[(int)$lang_id] = [
                         'keyword' => H::SEOEncode($seo_key, 'product_id', $product_id),
                     ];
                 }
             }
         }
         if ($seo_keys) {
             foreach ($seo_keys as $lang_id => $seo_key) {
                 $this->language->replaceDescriptions('url_aliases',
                     [
                         'query'       => "product_id=".(int)$product_id,
                         'language_id' => $lang_id,
                     ],
                     [$lang_id => $seo_key]
                 );
             }
         } else {
             $this->db->query(
                 "DELETE
                 FROM ".$this->db->table_name("url_aliases")."
                 WHERE query = 'product_id=".(int)$product_id."'
                     AND language_id = '".(int)$language_id."'");
         }
         $affected_tables[] = 'url_aliases';
         if ($data['product_tags']) {
             if (is_string($data['product_tags'])) {
                 $tags = (array)explode(',', $data['product_tags']);
                 $tags = [$language_id => $tags];
             } elseif (is_array($data['product_tags'])) {
                 $tags = $data['product_tags'];
                 foreach ($tags as &$taglist) {
                     $taglist = (array)explode(',', $taglist);
                 }
                 unset($taglist);
             } else {
                 $tags = (array)$data['product_tags'];
             }

             array_walk_recursive($tags, 'trim');

             foreach ($tags as $lang_id => $taglist) {
                 $taglist = array_unique($taglist);

                 foreach ($taglist as $tag) {
                     $tag = trim($tag);
                     if (!$tag) {
                         continue;
                     }
                     $sql = "INSERT INTO ".$this->db->table_name('product_tags')."
                             (product_id, language_id, tag)
                             VALUES
                             (".(int)$product_id.", ".(int)$lang_id.", '".$this->db->escape($tag)."');";
                     $this->db->query($sql);
                 }
             }
             $affected_tables[] = 'product_tags';
         }
         $this->cache->flush('product');
         $data['operation_type'] = 'insert';
         $this->updateEvent($product_id, $data, $affected_tables);
         return $product_id;
     }
 */

    /*  public function addProductDiscount($product_id, $data)
      {
          $data['price'] = str_replace(" ", "", $data['price']);
          if (!empty($data['date_start']) && !$data['iso_date']) {
              $data['date_start'] = H::dateDisplay2ISO($data['date_start'], $this->language->get('date_format_short'));
          }
          if (!empty($data['date_end']) && !$data['iso_date']) {
              $data['date_end'] = H::dateDisplay2ISO($data['date_end'], $this->language->get('date_format_short'));
          }
          $this->db->query(
              "INSERT INTO ".$this->db->table_name("product_discounts")."
                  SET product_id = '".(int)$product_id."',
                      customer_group_id = '".(int)$data['customer_group_id']."',
                      quantity = '".H::preformatInteger($data['quantity'])."',
                      priority = '".(int)$data['priority']."',
                      price = '".H::preformatFloat($data['price'])."',
                      date_start = '".$this->db->escape($data['date_start'])."',
                      date_end = '".$this->db->escape($data['date_end'])."'"
          );
          $id = $this->db->getLastId();
          $this->touchProduct($product_id);
          $this->updateEvent($product_id, $data, ['product_discounts']);

          return $id;
      }*/

    /*
        public function addProductSpecial($product_id, $data)
        {
            $data['price'] = str_replace(" ", "", $data['price']);
            if (!empty($data['date_start']) && !$data['iso_date']) {
                $data['date_start'] = H::dateDisplay2ISO(
                    $data['date_start'],
                    $this->language->get('date_format_short')
                );
            }
            if (!empty($data['date_end']) && !$data['iso_date']) {
                $data['date_end'] = H::dateDisplay2ISO(
                    $data['date_end'],
                    $this->language->get('date_format_short')
                );
            }

            $this->db->query(
                "INSERT INTO ".$this->db->table_name("product_specials")."
                SET product_id = '".(int)$product_id."',
                    customer_group_id = '".(int)$data['customer_group_id']."',
                    priority = '".(int)$data['priority']."',
                    price = '".H::preformatFloat($data['price'], $this->language->get('decimal_point'))."',
                    date_start = '".$this->db->escape($data['date_start'])."',
                    date_end = '".$this->db->escape($data['date_end'])."'");
            $id = $this->db->getLastId();
            $this->touchProduct($product_id);
            $this->updateEvent($product_id, $data, ['product_specials']);

            return $id;
        }
    */

    /*   public function updateProduct($product_id, $data)
       {
           $language_id = (int)$this->language->getContentLanguageID();

           $fields = $this->getProductColumns();
           $this->data['preformat_fields'] = [
               "shipping_price",
               "price",
               "cost",
               "weight",
               "length",
               "width",
               "height",
           ];
           $this->data['perform_json'] = [];
           $this->data['perform_serialize'] = [];
           $this->data['timestamps'] = ['date_available'];

           $this->data['nullable'] = ['sku'];

           $affected_tables = [];

           $this->extensions->hk_InitData($this, __FUNCTION__);

           $update = [];
           foreach ($fields as $f) {
               if (isset($data[$f])) {
                   if (in_array($f, $this->data['preformat_fields'])) {
                       $data[$f] = H::preformatFloat($data[$f], $this->language->get('decimal_point'));
                       $update[] = $f." = '".$this->db->escape($data[$f])."'";
                   }elseif (in_array($f, $this->data['nullable'])) {
                       $update[] = $f." = ".($data[$f] ? "'".$this->db->escape($data[$f])."'" : "NULL");
                   }elseif (in_array($f, $this->data['timestamps'])) {
                       $update[] = $f." = ".($data[$f] ? "'".$this->db->escape($data[$f])."'" : "NOW()");
                   } elseif (in_array($f, $this->data['perform_json'])) {
                       $update[] = $f." = '".json_encode($data[$f])."'";
                   } elseif (in_array($f, $this->data['perform_serialize'])) {
                       $update[] = $f." = '".serialize($data[$f])."'";
                   } else {
                       $update[] = $f." = '".$this->db->escape($data[$f])."'";
                   }

               }
           }

           if (!empty($update)) {
               $this->db->query(
                   "UPDATE `".$this->db->table_name("products`")."
                   SET ".implode(',', $update)."
                   WHERE product_id = '".(int)$product_id."'"
               );
               $affected_tables[] = 'products';
           }

           if (!empty($data['product_description'])) {

               foreach ($data['product_description'] as $field => $value) {

                   $fields = ['name', 'description', 'meta_keywords', 'meta_description', 'blurb'];
                   $update = [];
                   foreach ($fields as $f) {
                       if ($f == $field) {
                           $update[$f] = $value;
                       }
                   }

                   if (!empty($update)) {
                       $this->language->replaceDescriptions('product_descriptions',
                           ['product_id' => (int)$product_id],
                           [$language_id => $update]);
                   }
               }
               $affected_tables[] = 'product_descriptions';
           }

           if (isset($data['keyword'])) {
               $data['keyword'] = H::SEOEncode($data['keyword'], 'product_id', $product_id);
               if ($data['keyword']) {
                   $this->language->replaceDescriptions('url_aliases',
                       ['query' => "product_id=".(int)$product_id],
                       [$language_id => ['keyword' => $data['keyword']]]);
               } else {
                   $this->db->query("DELETE
                                   FROM ".$this->db->table_name("url_aliases")."
                                   WHERE query = 'product_id=".(int)$product_id."'
                                       AND language_id = '".$language_id."'");
               }
               $affected_tables[] = 'url_aliases';
           }

           if (isset($data['product_tags'])) {
               $tags = explode(',', $data['product_tags']);

               foreach ($tags as &$tag) {
                   $tag = $this->db->escape(trim($tag));
               }

               $this->language->replaceMultipleDescriptions('product_tags',
                   ['product_id' => (int)$product_id],
                   [$language_id => ['tag' => array_unique($tags)]]);
               $affected_tables[] = 'product_tags';
           }

           if (!in_array('products', $affected_tables)) {
               $this->touchProduct($product_id);
           }
           $data['operation_type'] = 'update';
           $this->updateEvent($product_id, $data, $affected_tables);
       }
   */

    /* public function updateProductDiscount($product_discount_id, $data)
     {
         $fields = ["customer_group_id", "quantity", "priority", "price", "date_start", "date_end",];
         if (isset($data['price'])) {
             $data['price'] = H::preformatFloat($data['price'], $this->language->get('decimal_point'));
         }
         if (!empty($data['date_start'])) {
             $data['date_start'] = H::dateDisplay2ISO($data['date_start'], $this->language->get('date_format_short'));
         }
         if (!empty($data['date_end'])) {
             $data['date_end'] = H::dateDisplay2ISO($data['date_end'], $this->language->get('date_format_short'));
         }
         $update = [];
         foreach ($fields as $f) {
             if (isset($data[$f])) {
                 $update[] = $f." = '".$this->db->escape($data[$f])."'";
             }
         }
         if (!empty($update)) {
             $this->db->query("UPDATE ".$this->db->table_name("product_discounts")."
                                 SET ".implode(',', $update)."
                                 WHERE product_discount_id = '".(int)$product_discount_id."'");
         }
         $this->cache->flush('product');
     }*/

    /*
        public function updateProductSpecial($product_special_id, $data)
        {
            $fields = ["customer_group_id", "priority", "price", "date_start", "date_end",];
            if (isset($data['price'])) {
                $data['price'] = H::preformatFloat($data['price'], $this->language->get('decimal_point'));
            }
            if (!empty($data['date_start'])) {
                $data['date_start'] = H::dateDisplay2ISO($data['date_start'], $this->language->get('date_format_short'));
            }
            if (!empty($data['date_end'])) {
                $data['date_end'] = H::dateDisplay2ISO($data['date_end'], $this->language->get('date_format_short'));
            }

            $update = [];
            foreach ($fields as $f) {
                if (isset($data[$f])) {
                    $update[] = $f." = '".$this->db->escape($data[$f])."'";
                }
            }
            if (!empty($update)) {
                $this->db->query(
                    "UPDATE `".$this->db->table_name("product_specials`")."
                    SET ".implode(',', $update)."
                    WHERE product_special_id = '".(int)$product_special_id."'"
                );
            }
            $this->cache->flush('product');
        }
    */

    /*
    public function updateProductLinks($product_id, $data)
    {
        if (!(int)$product_id || !$data) {
            return false;
        }
        $affected_tables = [];
        if (isset($data['product_store'])) {
            $this->db->query(
                "DELETE FROM ".$this->db->table_name("products_to_stores")." 
                WHERE product_id = '".(int)$product_id."'"
            );
            foreach ($data['product_store'] as $store_id) {
                $this->db->query(
                    "INSERT INTO ".$this->db->table_name("products_to_stores")." 
                    SET product_id = '".(int)$product_id."', store_id = '".(int)$store_id."'"
                );
            }
            $affected_tables[] = 'products_to_stores';
        }

        if (isset($data['product_download'])) {
            $this->db->query(
                "DELETE FROM ".$this->db->table_name("products_to_downloads")." 
                WHERE product_id = '".(int)$product_id."'"
            );
            foreach ($data['product_download'] as $download_id) {
                if ((int)$download_id) {
                    $this->db->query(
                        "INSERT INTO ".$this->db->table_name("products_to_downloads")." 
                        SET product_id = '".(int)$product_id."', download_id = '".(int)$download_id."'"
                    );
                }
            }
            $affected_tables[] = 'products_to_downloads';
        }

        if (isset($data['product_category'])) {
            $this->db->query(
                "DELETE FROM ".$this->db->table_name("products_to_categories")." 
                WHERE product_id = '".(int)$product_id."'"
            );
            foreach ($data['product_category'] as $category_id) {
                if ((int)$category_id) {
                    $this->db->query(
                        "INSERT INTO ".$this->db->table_name("products_to_categories")." 
                        SET product_id = '".(int)$product_id."', category_id = '".(int)$category_id."'"
                    );
                }
            }
            $affected_tables[] = 'products_to_categories';
        }

        if (isset($data['product_related'])) {
            $this->db->query(
                "DELETE FROM ".$this->db->table_name("products_related")." 
                WHERE product_id = '".(int)$product_id."'"
            );
            foreach ($data['product_related'] as $related_id) {
                if ((int)$related_id) {
                    $this->db->query(
                        "INSERT INTO ".$this->db->table_name("products_related")." 
                        SET product_id = '".(int)$product_id."', related_id = '".(int)$related_id."'"
                    );
                    $this->db->query(
                        "DELETE FROM ".$this->db->table_name("products_related")." 
                        WHERE product_id = '".(int)$related_id."' AND related_id = '".(int)$product_id."'"
                    );
                    $this->db->query(
                        "INSERT INTO ".$this->db->table_name("products_related")." 
                        SET product_id = '".(int)$related_id."', related_id = '".(int)$product_id."'"
                    );
                }
            }
            $affected_tables[] = 'products_related';
        }
        if ($affected_tables) {
            $this->touchProduct($product_id);
        }
        $this->updateEvent($product_id, $data, $affected_tables);

        return true;
    }
*/

    /*   public function relateProducts($product_ids = [])
       {
           if (!$product_ids || !is_array($product_ids)) {
               return false;
           }
           foreach ($product_ids as $product_id) {
               if ((int)$product_id) {
                   foreach ($product_ids as $related_id) {
                       if ((int)$related_id && $related_id != $product_id) {
                           $this->db->query(
                               "DELETE FROM ".$this->db->table_name("products_related")."
                               WHERE product_id = '".(int)$related_id."' AND related_id = '".(int)$product_id."'");
                           $this->db->query(
                               "INSERT INTO ".$this->db->table_name("products_related")."
                               SET product_id = '".(int)$related_id."', related_id = '".(int)$product_id."'"
                           );
                       }
                       $this->updateEvent($product_id, ['related_id' => $related_id], ['products_related']);
                   }
               }
           }

           return true;
       }
   */

//    public function addProductOption($product_id, $data)
//       {
//
//
//           $am = ABC::getObjectByAlias('AttributeManager');
//           $attribute = $am->getAttribute($data['attribute_id']);
//
//           if ($attribute) {
//               $data['element_type'] = $attribute['element_type'];
//               $data['required'] = $attribute['required'];
//               $data['regexp_pattern'] = $attribute['regexp_pattern'];
//               $data['placeholder'] = $attribute['placeholder'];
//               $data['sort_order'] = $attribute['sort_order'];
//               $data['settings'] = $attribute['settings'];
//           } else {
//               $data['placeholder'] = $data['option_placeholder'];
//           }
//
//           $this->db->query(
//               "INSERT INTO ".$this->db->table_name("product_options")."
//                   (product_id,
//                    attribute_id,
//                    element_type,
//                    required,
//                    sort_order,
//                    group_id,
//                    status,
//                    regexp_pattern,
//                    settings)
//               VALUES ('".(int)$product_id."',
//                   '".(int)$data['attribute_id']."',
//                   '".$this->db->escape($data['element_type'])."',
//                   '".(int)$data['required']."',
//                   '".(int)$data['sort_order']."',
//                   '".(int)$data['group_id']."',
//                   '".(int)$data['status']."',
//                   '".$this->db->escape($data['regexp_pattern'])."',
//                   '".$this->db->escape($data['settings'])."'
//                   )"
//           );
//           $product_option_id = $this->db->getLastId();
//
//           if (!empty($data['option_name'])) {
//               $attributeDescriptions = [
//                   $this->language->getContentLanguageID() => [
//                       'name'        => $data['option_name'],
//                       'error_text'  => $data['error_text'],
//                       'placeholder' => $data['placeholder'],
//                   ],
//               ];
//           } else {
//               $attributeDescriptions = $am->getAttributeDescriptions($data['attribute_id']);
//           }
//
//           foreach ($attributeDescriptions as $language_id => $descr) {
//               $this->language->replaceDescriptions('product_option_descriptions',
//                   [
//                       'product_option_id' => (int)$product_option_id,
//                       'product_id'        => (int)$product_id,
//                   ],
//                   [
//                       $language_id => [
//                           'name'               => $descr['name'],
//                           'error_text'         => $descr['error_text'],
//                           'option_placeholder' => $data['placeholder'],
//                       ],
//                   ]);
//           }
//
//           //add empty option value for single value attributes
//           $elements_with_options = HtmlElementFactory::getElementsWithOptions();
//           if (!in_array($data['element_type'], $elements_with_options)) {
//               $this->insertProductOptionValue($product_id, $product_option_id, '', '', []);
//           }
//
//           $this->touchProduct($product_id);
//
//           return $product_option_id;
//       }

    /* public function deleteProductOption($product_id, $product_option_id)
     {

         $am = ABC::getObjectByAlias('AttributeManager');
         $attribute = $am->getAttributeByProductOptionId($product_option_id);
         $group_attribute = $am->getAttributes(['limit' => null], 0, $attribute['attribute_id']);
         if (count($group_attribute)) {
             //delete children options/values
             $children = $this->db->query(
                 "SELECT product_option_id
                 FROM ".$this->db->table_name("product_options")."
                 WHERE product_id = '".(int)$product_id."'
                     AND group_id = '".(int)$product_option_id."'"
             );
             foreach ($children->rows as $g_attribute) {
                 $this->_deleteProductOption($product_id, $g_attribute['product_option_id']);
             }
         }

         $this->_deleteProductOption($product_id, $product_option_id);

         $this->touchProduct($product_id);
     }*/

    //Add new product option value and value descriptions for all global attributes languages or current language

//    public function addProductOptionValueAndDescription($product_id, $option_id, $data)
//    {
//        if (empty($product_id) || empty($option_id) || empty($data)) {
//            return null;
//        }
//
//        $attribute_value_id = $data['attribute_value_id'];
//        if (is_array($data['attribute_value_id'])) {
//            $attribute_value_id = '';
//        }
//
//        /**
//         * @var AttributeManagerInterface $am
//         */
//        $am = ABC::getObjectByAlias('AttributeManager');
//        //build grouped attributes if this is a parent attribute
//        if (is_array($data['attribute_value_id'])) {
//            //add children option values from global attributes
//            $groupData = [];
//            foreach ($data['attribute_value_id'] as $child_option_id => $attribute_value_id) {
//                #special serialized data for grouped options
//                $groupData[] = [
//                    'attr_id' => $child_option_id,
//                    'attr_v_id' => $attribute_value_id
//                ];
//            }
//            $data['grouped_attribute_data'] = serialize($groupData);
//        }
//
//        $pd_opt_val_id = $this->insertProductOptionValue($product_id, $option_id, $attribute_value_id, '', $data);
//
//        //Build options value descriptions
//        if (is_array($data['attribute_value_id'])) {
//            //add children option values description from global attributes
//            $group_description = [];
//            $descr_names = [];
//            foreach ($data['attribute_value_id'] as $child_option_id => $attribute_value_id) {
//                #special insert for grouped options
//                foreach ($am->getAttributeValueDescriptions($attribute_value_id) as $language_id => $name) {
//                    $group_description[$language_id][] = [
//                        'attr_v_id' => $attribute_value_id,
//                        'name' => $name
//                    ];
//                    $descr_names[$language_id][] = $name;
//                }
//            }
//
//            // Insert generic merged name
//            $grouped_names = null;
//            foreach ($descr_names as $language_id => $name) {
//                if (count($group_description[$language_id])) {
//                    $grouped_names = serialize($group_description[$language_id]);
//                }
//                $this->insertProductOptionValueDescriptions(
//                    $product_id,
//                    $pd_opt_val_id,
//                    implode(' / ', $name),
//                    $language_id,
//                    $grouped_names
//                );
//            }
//
//        } else {
//            if (!$data['attribute_value_id']) {
//                //We save custom option value for current language
//                $valueDescriptions = [$this->language->getContentLanguageID() => $data['name']];
//            } else {
//                //We have global attributes, copy option value text from there.
//                $valueDescriptions = $am->getAttributeValueDescriptions((int)$data['attribute_value_id']);
//            }
//            foreach ($valueDescriptions as $language_id => $name) {
//                $this->insertProductOptionValueDescriptions($product_id, $pd_opt_val_id, $name, $language_id);
//            }
//        }
//
//
//
//        $this->touchProduct($product_id);
//
//        return $pd_opt_val_id;
//    }

    /**
     * @param int $product_id
     * @param int $pd_opt_val_id
     * @param int $language_id
     *
     * @return null|\stdClass
     * @throws \Exception
     */
//    public function getProductOptionValueDescriptions($product_id, $pd_opt_val_id, $language_id)
//    {
//        if (empty($product_id) || empty($pd_opt_val_id) || empty($language_id)) {
//            return null;
//        }
//
//        return $this->db->query(
//            "SELECT *
//            FROM ".$this->db->table_name("product_option_value_descriptions")."
//            WHERE product_option_value_id = '".(int)$pd_opt_val_id."'
//                AND product_id = '".(int)$product_id."'
//                AND language_id = '".(int)$language_id."' "
//        );
//    }

    /**
     * @param int $product_id
     * @param int $pd_opt_val_id
     * @param string $name
     * @param int $language_id
     * @param null|string $grp_attr_names
     *
     * @return int|null
     * @throws \abc\core\lib\AException
     */
//    public function insertProductOptionValueDescriptions(
//        $product_id,
//        $pd_opt_val_id,
//        $name,
//        $language_id,
//        $grp_attr_names = null
//    ) {
//        if (empty($product_id) || empty($pd_opt_val_id) || empty($language_id)) {
//            return null;
//        }
//
//        $this->language->replaceDescriptions('product_option_value_descriptions',
//            [
//                'product_option_value_id' => (int)$pd_opt_val_id,
//                'product_id'              => (int)$product_id,
//            ],
//            [
//                $language_id => [
//                    'name'                    => $name,
//                    'grouped_attribute_names' => $grp_attr_names,
//                ],
//            ]);
//
//        return $this->db->getLastId();
//    }

    /**
     * @param int $product_id
     * @param int $pd_opt_val_id
     * @param string $name
     * @param int $language_id
     * @param null|string $grp_attr_names
     *
     * @return null|int
     * @throws \abc\core\lib\AException
     */
//    public function updateProductOptionValueDescriptions(
//        $product_id,
//        $pd_opt_val_id,
//        $name,
//        $language_id,
//        $grp_attr_names = null
//    ) {
//        if (empty($product_id) || empty($pd_opt_val_id) || empty($language_id)) {
//            return null;
//        }
//        $this->language->replaceDescriptions('product_option_value_descriptions',
//            [
//                'product_option_value_id' => (int)$pd_opt_val_id,
//                'product_id'              => (int)$product_id,
//            ],
//            [
//                $language_id => [
//                    'name'                    => $name,
//                    'grouped_attribute_names' => $grp_attr_names,
//                ],
//            ]);
//
//        return $pd_opt_val_id;
//    }

    /**
     * @param int $product_id
     * @param int $option_id
     * @param int $attribute_value_id
     * @param int $pd_opt_val_id
     * @param array $data
     *
     * @return int|false
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
//    public function insertProductOptionValue($product_id, $option_id, $attribute_value_id, $pd_opt_val_id, $data)
//    {
//        if (empty($product_id) || empty($option_id)) {
//            return false;
//        }
//        $this->db->query(
//            "INSERT INTO ".$this->db->table_name("product_option_values")."
//            SET product_option_id = '".(int)$option_id."',
//                product_id = '".(int)$product_id."',
//                group_id = '".(int)$pd_opt_val_id."',
//                sku = ".($data['sku'] ? "'".$this->db->escape($data['sku'])."'" : "NULL").",
//                quantity = '".$this->db->escape($data['quantity'])."',
//                subtract = '".$this->db->escape($data['subtract'])."',
//                price = '".H::preformatFloat($data['price'], $this->language->get('decimal_point'))."',
//                prefix = '".$this->db->escape($data['prefix'])."',
//                weight = '".H::preformatFloat($data['weight'], $this->language->get('decimal_point'))."',
//                weight_type = '".$this->db->escape($data['weight_type'])."',
//                attribute_value_id = '".$this->db->escape($attribute_value_id)."',
//                grouped_attribute_data = '".$this->db->escape($data['grouped_attribute_data'])."',
//                sort_order = '".(int)$data['sort_order']."',
//                `default` = '".(int)$data['default']."'");
//
//        return $this->db->getLastId();
//    }

    /**
     *  Update singe product option value
     *
     * @param int $pd_opt_val_id
     * @param int $attribute_value_id
     * @param array $data
     *
     * @return null|int
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
//    public function updateProductOptionValue($pd_opt_val_id, $attribute_value_id, $data)
//    {
//        if (empty($pd_opt_val_id) || empty($data)) {
//            return null;
//        }
//        //If se have grouped (parent/child) options save no main attribute id
//        if (is_array($attribute_value_id)) {
//            $attribute_value_id = '';
//        }
//
//        $this->db->query(
//            "UPDATE ".$this->db->table_name("product_option_values")."
//            SET sku = ".($data['sku'] ? "'".$this->db->escape($data['sku'])."'" : "NULL").",
//                quantity = '".$this->db->escape($data['quantity'])."',
//                subtract = '".$this->db->escape($data['subtract'])."',
//                price = '".$this->db->escape($data['price'])."',
//                prefix = '".$this->db->escape($data['prefix'])."',
//                weight = '".H::preformatFloat($data['weight'], $this->language->get('decimal_point'))."',
//                weight_type = '".$this->db->escape($data['weight_type'])."',
//                attribute_value_id = ".($attribute_value_id ?  "'".$this->db->escape($attribute_value_id)."'": "NULL").",
//                grouped_attribute_data = '".$this->db->escape($data['grouped_attribute_data'])."',
//                sort_order = '".(int)$data['sort_order']."',
//                `default` = '".(int)$data['default']."'
//            WHERE product_option_value_id = '".(int)$pd_opt_val_id."'  ");
//
//        return $pd_opt_val_id;
//    }

    /**
     *    Update product option value and value descriptions for set language
     *
     * @param int $product_id
     * @param int $pd_opt_val_id
     * @param array $data
     * @param int $language_id
     *
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
//    public function updateProductOptionValueAndDescription($product_id, $pd_opt_val_id, $data, $language_id)
//    {
//        $attribute_value_id = $data['attribute_value_id'];
//        if (is_array($data['attribute_value_id'])) {
//            $attribute_value_id = '';
//        }
//
//        /**
//         * @var AttributeManagerInterface $am
//         */
//        $am = ABC::getObjectByAlias('AttributeManager');
//        //build grouped attributes if this is a parent attribute
//        if (is_array($data['attribute_value_id'])) {
//            //update children option values from global attributes
//            $groupData = [];
//            foreach ($data['attribute_value_id'] as $child_option_id => $attr_val_id) {
//                #special serialized data for grouped options
//                $groupData[] = [
//                    'attr_id'   => $child_option_id,
//                    'attr_v_id' => $attr_val_id,
//                ];
//            }
//            $data['grouped_attribute_data'] = serialize($groupData);
//        }
//
//        $this->updateProductOptionValue($pd_opt_val_id, $attribute_value_id, $data);
//
//        if (is_array($data['attribute_value_id'])) {
//            //update children option values description from global attributes
//            $group_description = [];
//            $descr_names = [];
//            foreach ($data['attribute_value_id'] as $child_option_id => $attr_val_id) {
//                #special insert for grouped options
//                foreach ($am->getAttributeValueDescriptions($attr_val_id) as $lang_id => $name) {
//                    if ($language_id == $lang_id) {
//                        $group_description[$language_id][] = [
//                            'attr_v_id' => $attr_val_id,
//                            'name'      => $name,
//                        ];
//                        $descr_names[$language_id][] = $name;
//                    }
//                }
//            }
//            // Insert generic merged name
//            foreach ($descr_names as $lang_id => $name) {
//                if ($language_id == $lang_id && count($group_description[$language_id])) {
//                    $group_description[$language_id][] = $name;
//                    $grouped_names = serialize($group_description[$language_id]);
//                    $this->updateProductOptionValueDescriptions(
//                        $product_id,
//                        $pd_opt_val_id,
//                        implode(' / ', $name),
//                        $language_id,
//                        $grouped_names
//                    );
//                }
//            }
//        } else {
//            if (!$data['attribute_value_id']) {
//                $exist = ProductOptionValueDescription::where(
//                    [
//                        'product_id'              => $product_id,
//                        'product_option_value_id' => $pd_opt_val_id,
//                        'language_id'             => $language_id,
//                    ]
//                )->first();
//                if ($exist) {
//                    $exist->update(['name' => $data['name']]);
//                } else {
//                    ProductOptionValueDescription::create(
//                        [
//                            'product_id'              => $product_id,
//                            'product_option_value_id' => $pd_opt_val_id,
//                            'name'                    => $data['name'],
//                            'language_id'             => $language_id,
//                        ]
//                    );
//                }
//            } else {
//                $valueDescriptions = $am->getAttributeValueDescriptions((int)$data['attribute_value_id']);
//                foreach ($valueDescriptions as $lang_id => $name) {
//                    if ($language_id == $lang_id) {
//                        //Update only language that we currently work with
//                        $this->updateProductOptionValueDescriptions($product_id, $pd_opt_val_id, $name, $language_id);
//                    }
//                }
//            }
//        }
//        $this->touchProduct($product_id);
//    }

    /**
     * @param int $product_id
     *
     * @return bool|array
     * @throws \abc\core\lib\AException
     * @throws \ReflectionException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function copyProduct($product_id)
    {
        if (empty($product_id)) {
            return false;
        }

        $sql = "SELECT DISTINCT *, p.product_id
                FROM ".$this->db->table_name("products")." p
                LEFT JOIN ".$this->db->table_name("product_descriptions")." pd
                    ON (p.product_id = pd.product_id 
                        AND pd.language_id = '".(int)$this->config->get('storefront_language_id')."')
                WHERE p.product_id = '".(int)$product_id."'";
        $query = $this->db->query($sql);

        if (!$query->num_rows) {
            return false;
        }

        $data = $query->row;
        $data = array_merge($data, ['product_description' => $this->getProductDescriptions($product_id)]);
        foreach ($data['product_description'] as $lang => $desc) {
            $data['product_description'][$lang]['name'] .= ' ( Copy )';
        }
        $data = array_merge($data, ['product_option' => $this->getProductOptions($product_id)]);
        foreach ($data['product_option'] as &$option) {
            foreach ($option['product_option_value'] as &$val) {
                if ($val['sku']) {
                    $val['sku'] .= '(copy)';
                }
            }
        }

        $data['keyword'] = '';
        if ($data['sku']) {
            $data['sku'] .= '(copy)';
        }

        // $data = array_merge($data, ['product_discount' => $this->getProductDiscounts($product_id)]);
        $data = array_merge($data, ['product_special' => $this->getProductSpecials($product_id)]);
        $data = array_merge($data, ['product_download' => $this->getProductDownloads($product_id)]);
        $data = array_merge($data, ['product_category' => $this->getProductCategories($product_id)]);
        $data = array_merge($data, ['product_store' => $this->getProductStores($product_id)]);
        $data = array_merge($data, ['product_related' => $this->getProductRelated($product_id)]);
        $data = array_merge($data, ['product_tags' => $this->getProductTags($product_id)]);
        $data = array_merge($data, ['keyword' => $this->getProductSEOKeywords($product_id)]);

        //set status to off for cloned product
        $data['status'] = 0;

        //get product resources
        $rm = new AResourceManager();
        $resources = $rm->getResourcesList(
            [
                'object_name' => 'products',
                'object_id'   => $product_id,
                'sort'        => 'sort_order',
            ]);

        $new_product_id = $this->addProduct($data);

        foreach ($data['product_discount'] as $item) {
            //sign to prevent converting date from display format to iso
            $item['iso_date'] = true;
            $this->addProductDiscount($new_product_id, $item);
        }
        foreach ($data['product_special'] as $item) {
            $item['iso_date'] = true;
            $this->addProductSpecial($new_product_id, $item);
        }

        $this->updateProductLinks($new_product_id, $data);
        $this->_clone_product_options($new_product_id, $data);

        foreach ($resources as $r) {
            $rm->mapResource(
                'products',
                $new_product_id,
                $r['resource_id']
            );
        }
        $this->cache->flush('product');

        //clone layout for the product if present
        $layout_clone_result = $this->_clone_product_layout($product_id, $new_product_id);

        return [
            'name'         => $data['name'],
            'id'           => $new_product_id,
            'layout_clone' => $layout_clone_result,
        ];
    }

    /**
     * @param int $product_id
     * @param array $data
     *
     * @throws \ReflectionException
     * @throws \abc\core\lib\AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function _clone_product_options($product_id, $data)
    {
        //Do not use before close review.
        //Note: This is done only after product cloning. This is not to be used on existing product.
        $this->db->query(
            "DELETE 
            FROM ".$this->db->table_name("product_options")." 
            WHERE product_id = '".(int)$product_id."'"
        );
        $this->db->query(
            "DELETE FROM ".$this->db->table_name("product_option_descriptions")." 
            WHERE product_id = '".(int)$product_id."'"
        );
        $this->db->query(
            "DELETE 
            FROM ".$this->db->table_name("product_option_values")." 
            WHERE product_id = '".(int)$product_id."'"
        );
        $this->db->query(
            "DELETE 
            FROM ".$this->db->table_name("product_option_value_descriptions")
            ." WHERE product_id = '".(int)$product_id."'"
        );

        if (isset($data['product_option'])) {
            foreach ($data['product_option'] as $product_option) {
                $sql = "INSERT INTO ".$this->db->table_name("product_options")." 
                        SET product_id = '".(int)$product_id."',
                            sort_order = '".(int)$product_option['sort_order']."'";
                if ($product_option['attribute_id']) {
                    $sql .= ", attribute_id = '".(int)$product_option['attribute_id']."'";
                }
                if ($product_option['group_id']) {
                    $sql .= ", group_id = '".(int)$product_option['group_id']."'";
                }
                if ($product_option['element_type']) {
                    $sql .= ", element_type = '".$this->db->escape($product_option['element_type'])."'";
                }
                if ($product_option['required']) {
                    $sql .= ", required = '".(int)$product_option['required']."'";
                }
                if ($product_option['regexp_pattern']) {
                    $sql .= ", regexp_pattern = '".$this->db->escape($product_option['regexp_pattern'])."'";
                }
                $this->db->query($sql);
                $product_option_id = $this->db->getLastId();

                foreach ($product_option['language'] as $language_id => $language) {
                    $this->language->replaceDescriptions('product_option_descriptions',
                        [
                            'product_option_id' => (int)$product_option_id,
                            'product_id'        => (int)$product_id,
                        ],
                        [
                            $language_id => [
                                'name'               => $language['name'],
                                'error_text'         => $language['error_text'],
                                'option_placeholder' => $language['placeholder'],
                            ],
                        ]);
                }

                if (isset($product_option['product_option_value'])) {
                    //get product resources
                    $rm = new AResourceManager();
                    foreach ($product_option['product_option_value'] as $pd_opt_vals) {
                        $pd_opt_vals['price'] = str_replace(" ", "", $pd_opt_vals['price']);

                        $this->db->query(
                            "INSERT INTO ".$this->db->table_name("product_option_values")." 
                            SET product_option_id = '".(int)$product_option_id."',
                                product_id = '".(int)$product_id."',
                                sku = ".($pd_opt_vals['sku'] ? "'".$this->db->escape($pd_opt_vals['sku'])."'" : "NULL").",
                                quantity = '".(int)$pd_opt_vals['quantity']."',
                                subtract = '".(int)$pd_opt_vals['subtract']."',
                                price = '".H::preformatFloat($pd_opt_vals['price'],$this->language->get('decimal_point'))."',
                                weight = '".(float)$pd_opt_vals['weight']."',
                                weight_type = '".$this->db->escape($pd_opt_vals['weight_type'])."',
                                prefix = '".$this->db->escape($pd_opt_vals['prefix'])."',
                                attribute_value_id = '".$this->db->escape($pd_opt_vals['attribute_value_id'])."',
                                grouped_attribute_data = '".$this->db->escape($pd_opt_vals['grouped_attribute_data'])."',
                                group_id = '".$this->db->escape($pd_opt_vals['group_id'])."',
                                sort_order = '".(int)$pd_opt_vals['sort_order']."',
                                `default` = '".(int)$pd_opt_vals['default']."'"
                        );

                        $pd_opt_val_id = $this->db->getLastId();
                        // clone resources of option value
                        if ($pd_opt_vals['product_option_value_id']) {
                            $resources = $rm->getResourcesList([
                                'object_name' => 'product_option_value',
                                'object_id'   => $pd_opt_vals['product_option_value_id'],
                            ]);
                            foreach ($resources as $r) {
                                $rm->mapResource(
                                    'product_option_value',
                                    $pd_opt_val_id,
                                    $r['resource_id']
                                );
                            }
                        }

                        foreach ($pd_opt_vals['language'] as $language_id => $lang_data) {
                            $grouped_attribute_names = serialize($lang_data['children_options_names']);

                            $this->language->replaceDescriptions(
                                'product_option_value_descriptions',
                                [
                                    'product_option_value_id' => (int)$pd_opt_val_id,
                                    'product_id'              => (int)$product_id,
                                ],
                                [
                                    $language_id => [
                                        'name'                    => $lang_data['name'],
                                        'grouped_attribute_names' => $grouped_attribute_names,
                                    ],
                                ]);
                        }
                    }
                }
            }
        }
        $this->cache->flush('product');
    }

    /**
     * @param int $product_id
     * @param int $new_product_id
     *
     * @return null
     * @throws \abc\core\lib\AException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function _clone_product_layout($product_id, $new_product_id)
    {
        if (!H::has_value($product_id) && !H::has_value($new_product_id)) {
            return false;
        }

        //clone layout for the product if present
        $lm = new ALayoutManager();
        $pages = $lm->getPages('pages/product/product', 'product_id', (int)$product_id);
        if (count($pages) && H::has_value($pages[0]['page_id'])) {
            $tmpl_id = $this->config->get('config_storefront_template');
            $src_layout_id = $pages[0]['layout_id'];
            $src_page_id = $pages[0]['page_id'];
            //create instance for source layout
            $lm = new ALayoutManager($tmpl_id, $src_page_id, $src_layout_id);
            //create new page
            $page_info = [
                'controller' => 'pages/product/product',
                'key_param'  => 'product_id',
                'key_value'  => $new_product_id,
            ];

            $product_info = $this->getProductDescriptions($new_product_id);
            if ($product_info) {
                foreach ($product_info as $language_id => $description) {
                    if (!H::has_value($language_id)) {
                        continue;
                    }
                    $page_info['page_descriptions'][$language_id] = $description;
                }
            }
            //save new page
            $new_page_id = $lm->savePage($page_info);

            $default_language_id = $this->language->getDefaultLanguageID();
            $layout_name = 'Product: '.$product_info[$default_language_id]['name'];
            //create instance for new layout
            $lm = new ALayoutManager($tmpl_id, $new_page_id, '');

            return $lm->clonePageLayout($src_layout_id, '', $layout_name);
        }

        return false;
    }

    /**
     * @param int $product_id
     *
     * @return bool
     * @throws \abc\core\lib\AException
     * @throws \ReflectionException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteProduct($product_id)
    {
        if (!(int)$product_id) {
            return false;
        }
        $rm = new AResourceManager();
        $this->db->query(
            "DELETE 
            FROM ".$this->db->table_name("product_descriptions")." 
            WHERE product_id = '".(int)$product_id."'"
        );

        $resources = $rm->getResourcesList(
            [
                'object_name' => 'products',
                'object_id'   => (int)$product_id,
            ]
        );
        foreach ($resources as $r) {
            $rm->unmapResource(
                'products',
                $product_id,
                $r['resource_id']
            );
            //if resource become orphan - delete it
            if (!$rm->isMapped($r['resource_id'])) {
                $rm->deleteResource($r['resource_id']);
            }
        }

        //delete product's downloads
        $this->load->model('catalog/download');
        $downloads = $this->model_catalog_download->getProductDownloadsDetails(
            $product_id,
            ['subsql_filter' => 'AND d.shared<>1']
        );
        foreach ($downloads as $d) {
            $this->model_catalog_download->deleteDownload($d['download_id']);
        }

        //cascade removing product options with values and their images
        $options = $this->getProductOptions($product_id);
        foreach ($options as $option) {
            $this->_deleteProductOption($product_id, $option['product_option_id']);
        }

        $this->db->query(
            "DELETE 
            FROM ".$this->db->table_name("product_discounts")." 
            WHERE product_id = '".(int)$product_id."'"
        );
        $this->db->query(
            "DELETE FROM ".$this->db->table_name("products_related")." 
            WHERE product_id = '".(int)$product_id."'"
        );
        $this->db->query(
            "DELETE FROM ".$this->db->table_name("products_to_downloads")." 
            WHERE product_id = '".(int)$product_id."'"
        );
        $this->db->query(
            "DELETE FROM ".$this->db->table_name("products_to_categories")." 
            WHERE product_id = '".(int)$product_id."'"
        );
        $this->db->query(
            "DELETE FROM ".$this->db->table_name("reviews")." 
            WHERE product_id = '".(int)$product_id."'"
        );
        $this->db->query(
            "DELETE FROM ".$this->db->table_name("products_to_stores")." 
            WHERE product_id = '".(int)$product_id."'"
        );
        $this->db->query(
            "DELETE FROM ".$this->db->table_name("url_aliases")." 
            WHERE query = 'product_id=".(int)$product_id."'"
        );
        $this->db->query(
            "DELETE FROM ".$this->db->table_name("product_tags")." 
            WHERE product_id='".(int)$product_id."'"
        );

        $this->db->query(
            "DELETE FROM ".$this->db->table_name("product_specials")." 
            WHERE product_id='".(int)$product_id."'");

        $lm = new ALayoutManager();
        $lm->deletePageLayout('pages/product/product', 'product_id', (int)$product_id);

        $this->db->query("DELETE FROM ".$this->db->table_name("products")." WHERE product_id = '".(int)$product_id."'");

        $this->cache->flush('product');

        return true;
    }

    /**
     * @param int $product_discount_id
     *
     * @throws \Exception
     */
    public function deleteProductDiscount($product_discount_id)
    {
        $this->db->query(
            "DELETE FROM ".$this->db->table_name("product_discounts")." 
            WHERE product_discount_id = '".(int)$product_discount_id."'"
        );
        $this->cache->flush('product');
    }

    /**
     * @param int $product_special_id
     *
     * @throws \Exception
     */
    public function deleteProductSpecial($product_special_id)
    {
        $this->db->query(
            "DELETE FROM ".$this->db->table_name("product_specials")." 
            WHERE product_special_id='".(int)$product_special_id."'"
        );

        $this->cache->flush('product');
    }

    /**
     * @param int $product_id
     *
     * @return array
     * @throws \Exception
     */
    public function getProduct($product_id)
    {
        $query = $this->db->query(
            "SELECT DISTINCT *, p.product_id, 
                (SELECT keyword
                 FROM ".$this->db->table_name("url_aliases")." 
                 WHERE query = 'product_id=".(int)$product_id."'
                    AND language_id='".(int)$this->language->getContentLanguageID()."' ) AS keyword
            FROM ".$this->db->table_name("products")." p
            LEFT JOIN ".$this->db->table_name("product_descriptions")." pd
                    ON (p.product_id = pd.product_id
                            AND pd.language_id = '".(int)$this->config->get('storefront_language_id')."')
            WHERE p.product_id = '".(int)$product_id."'");

        return $query->row;
    }

    /**
     * @param int $product_discount_id
     *
     * @return array
     * @throws \Exception
     */
    public function getProductDiscount($product_discount_id)
    {
        $query = $this->db->query(
            "SELECT *
            FROM ".$this->db->table_name("product_discounts")." 
            WHERE product_discount_id = '".(int)$product_discount_id."'");

        return $query->row;
    }

    /**
     * @param int $product_special_id
     *
     * @return array
     * @throws \Exception
     */
    public function getProductSpecial($product_special_id)
    {
        $query = $this->db->query(
            "SELECT *
            FROM ".$this->db->table_name("product_specials")." 
            WHERE product_special_id = '".(int)$product_special_id."'");

        return $query->row;
    }


    /**
     * @param string $keyword
     *
     * @return array
     * @throws \Exception
     */
    public function getProductsByKeyword($keyword)
    {
        if ($keyword) {
            $query = $this->db->query(
                "SELECT *, p.product_id
                FROM ".$this->db->table_name("products")." p
                LEFT JOIN ".$this->db->table_name("product_descriptions")." pd
                        ON (p.product_id = pd.product_id 
                            AND pd.language_id = '".(int)$this->config->get('storefront_language_id')."')
                WHERE (LCASE(pd.name) LIKE '%".$this->db->escape(mb_strtolower($keyword))."%'
                        OR LCASE(p.model) LIKE '%".$this->db->escape(mb_strtolower($keyword))."%')"
            );

            return $query->rows;
        } else {
            return [];
        }
    }

    /**
     * @param        $category_id
     * @param string $mode
     *
     * @return array|int
     * @throws \Exception
     */
    public function getProductsByCategoryId($category_id, $mode = 'default')
    {
        $query = $this->db->query(
            "SELECT *, p.product_id
                FROM ".$this->db->table_name("products")." p
                LEFT JOIN ".$this->db->table_name("product_descriptions")." pd
                    ON (p.product_id = pd.product_id 
                        AND pd.language_id = '".(int)$this->config->get('storefront_language_id')."' )
                LEFT JOIN ".$this->db->table_name("products_to_categories")." p2c 
                    ON (p.product_id = p2c.product_id)
                WHERE p2c.category_id = '".(int)$category_id."'
                ORDER BY pd.name ASC"
        );
        if ($mode == 'total_only') {
            return $query->num_rows;
        }

        return $query->rows;
    }

    /**
     * @param int $product_id
     * @param int $language_id
     *
     * @return array
     * @throws \Exception
     */
    public function getProductDescriptions($product_id, $language_id = 0)
    {
        $product_description_data = [];
        $language_id = (int)$language_id;

        $query = $this->db->query(
            "SELECT *
            FROM ".$this->db->table_name("product_descriptions")." 
            WHERE product_id = '".(int)$product_id."'"
        );

        foreach ($query->rows as $result) {
            $product_description_data[$result['language_id']] = [
                'name'             => $result['name'],
                'meta_keywords'    => $result['meta_keywords'],
                'meta_description' => $result['meta_description'],
                'description'      => $result['description'],
                'blurb'            => $result['blurb'],
            ];
        }

        return $language_id ? $product_description_data[$language_id] : $product_description_data;
    }

//    /**
//     * @param int $attribute_id
//     * @param int $group_id
//     *
//     * @return int
//     * @throws \Exception
//     */
//    public function getProductOptionByAttributeId($attribute_id, $group_id)
//    {
//        $inc = (int)$group_id ? "group_id = '".(int)$group_id."'" : "group_id IS NULL";
//        $product_option = $this->db->query(
//            "SELECT product_option_id
//                FROM ".$this->db->table_name("product_options")."
//                WHERE attribute_id = '".(int)$attribute_id."'
//                    AND ".$inc."
//                ORDER BY sort_order");
//
//        return $product_option->row['product_option_id'];
//    }

    /**
     *    Get single option data
     *
     * @param int $product_id
     * @param int $option_id
     *
     * @return array|null
     * @throws \Exception
     */
    public function getProductOption($product_id, $option_id = 0)
    {
        $product_option = $this->db->query(
            "SELECT *
                FROM ".$this->db->table_name("product_options")." 
                WHERE product_id = '".(int)$product_id."'
                    AND product_option_id = '".(int)$option_id."'
                ORDER BY sort_order"
        );

        $product_option_description = $this->db->query(
            "SELECT *
            FROM ".$this->db->table_name("product_option_descriptions")."
            WHERE product_option_id = '".(int)$option_id."'"
        );
        $product_option_description_data = [];
        foreach ($product_option_description->rows as $result) {
            $product_option_description_data[$result['language_id']] = [
                'name'               => $result['name'],
                'option_placeholder' => $result['option_placeholder'],
                'error_text'         => $result['error_text'],
            ];
        }

        if ($product_option->num_rows) {
            $row = $product_option->row;
            $row['language'] = $product_option_description_data;

            return $row;
        } else {
            return null;
        }
    }

//    /**
//     * @param int $product_option_id
//     * @param array $data
//     *
//     * @throws \Exception
//     */
//    public function updateProductOption($product_option_id, $data)
//    {
//        $fields = [
//            "sort_order",
//            "status",
//            "required",
//            "regexp_pattern",
//            "settings",
//        ];
//        $update = [];
//        foreach ($fields as $f) {
//            if (isset($data[$f])) {
//                if ($f == 'settings') {
//                    $data[$f] = serialize($data[$f]);
//                }
//                $update[] = $f." = '".$this->db->escape($data[$f])."'";
//            }
//        }
//        if (!empty($update)) {
//            $this->db->query(
//                "UPDATE ".$this->db->table_name("product_options")."
//                SET ".implode(',', $update)."
//                WHERE product_option_id = '".(int)$product_option_id."'"
//            );
//        }
//
//        if (!empty($data['name'])) {
//
//            $language_id = $this->language->getContentLanguageID();
//
//            $this->language->replaceDescriptions('product_option_descriptions',
//                ['product_option_id' => (int)$product_option_id],
//                [
//                    (int)$language_id => [
//                        'name'               => $data['name'],
//                        'error_text'         => $data['error_text'],
//                        'option_placeholder' => $data['option_placeholder'],
//                    ],
//                ]);
//        }
//
//        $this->cache->flush('product');
//    }

    /**
     * Main method to get complete options data for product
     *
     * @param int $product_id
     * @param int $group_id
     *
     * @return array
     * @throws \Exception
     */
//    public function getProductOptions($product_id, $group_id = 0)
//    {
//        $product_option_data = [];
//        $group_select = '';
//        if ((int)$group_id) {
//            $group_select = "AND group_id = '".(int)$group_id."'";
//        }
//        $product_option = $this->db->query(
//            "SELECT *
//             FROM ".$this->db->table_name("product_options")."
//             WHERE product_id = '".(int)$product_id."' "
//            .$group_select.
//            " ORDER BY sort_order");
//
//        foreach ($product_option->rows as $product_option) {
//            $option_data = $this->getProductOption($product_id, $product_option['product_option_id']);
//            $option_data['product_option_value'] =
//                $this->getProductOptionValues($product_id, $product_option['product_option_id']);
//            $product_option_data[] = $option_data;
//        }
//
//        return $product_option_data;
//    }

    /**
     *    Main function to be called to update option values.
     *
     * @param int $product_id
     * @param int $option_id
     * @param array $data
     *
     * @return null
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
//    public function updateProductOptionValues($product_id, $option_id, $data)
//    {
//        if (!is_array($data['product_option_value_id']) || !$option_id || !$product_id) {
//            return false;
//        }
//        $language_id = $this->language->getContentLanguageID();
//
//        foreach ($data['product_option_value_id'] as $valueId => $status) {
//            $option_value_data = [
//                'product_id'             => $product_id,
//                'product_option_id'      => $option_id,
//                'attribute_value_id'     => $data['attribute_value_id'][$valueId],
//                'grouped_attribute_data' => $data['grouped_attribute_data'][$valueId],
//                'name'                   => $data['name'][$valueId],
//                'sku'                    => $data['sku'][$valueId],
//                'quantity'               => $data['quantity'][$valueId],
//                'subtract'               => $data['subtract'][$valueId],
//                'price'                  => H::preformatFloat(
//                                                    $data['price'][$valueId],
//                                                    $this->language->get('decimal_point')
//                                            ),
//                'prefix'                 => $data['prefix'][$valueId],
//                'sort_order'             => $data['sort_order'][$valueId],
//                'weight'                 => $data['weight'][$valueId],
//                'weight_type'            => $data['weight_type'][$valueId],
//                'default'                => ($data['default_value'] == $valueId ? 1 : 0),
//            ];
//
//            //Check if new, delete or update
//            if ($status == 'delete' && strpos($valueId, 'new') === false) {
//                //delete this option value for all languages
//                $this->deleteProductOptionValue($product_id, $valueId);
//            } else {
//                if ($status == 'new') {
//                    // Need to create new option value
//                    $data = $option_value_data;
//                    ProductOption::addProductOptionValueAndDescription($option_value_data);
//                } else {
//                    //Existing need to update
//                    $this->updateProductOptionValueAndDescription($product_id, $valueId, $option_value_data,
//                        $language_id);
//                }
//            }
//        }
//        $this->touchProduct($product_id);
//
//        return true;
//    }

    /**
     * @param int $product_id
     * @param int $option_value_id
     *
     * @return array
     * @throws \Exception
     */
//    public function getProductOptionValue($product_id, $option_value_id)
//    {
//
//        $product_option_value = $this->db->query(
//            "SELECT *
//            FROM ".$this->db->table_name("product_option_values")."
//            WHERE product_id = '".(int)$product_id."'
//                AND product_option_value_id = '".(int)$option_value_id."'
//                AND group_id = 0
//            ORDER BY sort_order"
//        );
//
//        $option_value = $product_option_value->row;
//        $value_description_data = [];
//        $value_description = $this->db->query(
//            "SELECT *
//            FROM ".$this->db->table_name("product_option_value_descriptions")."
//            WHERE product_option_value_id = '".(int)$option_value['product_option_value_id']."'");
//
//        foreach ($value_description->rows as $description) {
//            //regular option value name
//            $value_description_data[$description['language_id']]['name'] = $description['name'];
//            //get children (grouped options) individual names array
//            if ($description['grouped_attribute_names']) {
//                $value_description_data[$description['language_id']]['children_options_names'] =
//                    unserialize($description['grouped_attribute_names']);
//            }
//        }
//
//        $result = [
//            'product_option_value_id' => $option_value['product_option_value_id'],
//            'language'                => $value_description_data,
//            'sku'                     => $option_value['sku'],
//            'quantity'                => $option_value['quantity'],
//            'subtract'                => $option_value['subtract'],
//            'price'                   => $option_value['price'],
//            'prefix'                  => $option_value['prefix'],
//            'weight'                  => $option_value['weight'],
//            'weight_type'             => $option_value['weight_type'],
//            'attribute_value_id'      => $option_value['attribute_value_id'],
//            'grouped_attribute_data'  => $option_value['grouped_attribute_data'],
//            'sort_order'              => $option_value['sort_order'],
//            'default'                 => $option_value['default'],
//        ];
//
//        //get children (grouped options) data
//        $child_option_values = unserialize($result['grouped_attribute_data']);
//        if (is_array($child_option_values) && sizeof($child_option_values)) {
//            $result['children_options'] = [];
//            foreach ($child_option_values as $child_value) {
//                $result['children_options'][$child_value['attr_id']] = (int)$child_value['attr_v_id'];
//            }
//        }
//
//        return $result;
//    }

    /**
     * @param int $product_id
     * @param int $option_id
     *
     * @return array
     * @throws \Exception
     */
    public function getProductOptionValues($product_id, $option_id)
    {

        $result = [];

        $product_option_value = $this->db->query(
            "SELECT product_option_value_id FROM ".$this->db->table_name("product_option_values")."
            WHERE product_id = '".(int)$product_id."'
                AND product_option_id = '".(int)$option_id."'
            ORDER BY sort_order"
        );

        foreach ($product_option_value->rows as $option_value) {
            $result[] = $this->getProductOptionValue($product_id, $option_value['product_option_value_id']);
        }

        return $result;
    }

    /**
     * @param int $product_id
     *
     * @return array
     * @throws \Exception
     */
    public function getProductDiscounts($product_id)
    {
        $query = $this->db->query(
            "SELECT *
            FROM ".$this->db->table_name("product_discounts")." 
            WHERE product_id = '".(int)$product_id."'
            ORDER BY quantity, priority, price"
        );

        return $query->rows;
    }

    /**
     * @param int $product_id
     *
     * @return int mixed
     * @throws \Exception
     */
    public function getProductSpecials($product_id)
    {
        $query = $this->db->query(
            "SELECT *
            FROM ".$this->db->table_name("product_specials")." 
            WHERE product_id = '".(int)$product_id."'
            ORDER BY priority, price"
        );

        return $query->rows;
    }

    /**
     * @param int $product_id
     *
     * @return array
     * @throws \Exception
     */
    public function getProductDownloads($product_id)
    {
        $product_download_data = [];

        $query = $this->db->query(
            "SELECT *
            FROM ".$this->db->table_name("products_to_downloads")." 
            WHERE product_id = '".(int)$product_id."'"
        );

        foreach ($query->rows as $result) {
            $product_download_data[] = $result['download_id'];
        }

        return $product_download_data;
    }

    /**
     * @param int $product_id
     *
     * @return array
     * @throws \Exception
     */
    public function getProductStores($product_id)
    {
        $product_store_data = [];
        $rows = $this->getProductStoresInfo($product_id);

        foreach ($rows as $result) {
            $product_store_data[] = $result['store_id'];
        }

        return $product_store_data;
    }

    /**
     * @param int $product_id
     *
     * @return array
     * @throws \Exception
     */
    public function getProductStoresInfo($product_id)
    {

        $query = $this->db->query(
            "SELECT p2s.*,
                s.name AS store_name,
                ss.`value` AS store_url,
                sss.`value` AS store_ssl_url
            FROM ".$this->db->table_name("products_to_stores")." p2s
            LEFT JOIN ".$this->db->table_name("stores")." s ON s.store_id = p2s.store_id
            LEFT JOIN ".$this->db->table_name("settings")." ss
                ON (ss.store_id = p2s.store_id AND ss.`key`='config_url')
            LEFT JOIN ".$this->db->table_name("settings")." sss
                ON (sss.store_id = p2s.store_id AND sss.`key`='config_ssl_url')
            WHERE p2s.product_id = '".(int)$product_id."'"
        );

        return $query->rows;
    }

    /**
     * @param int $product_id
     *
     * @return array
     * @throws \Exception
     */
    public function getProductCategories($product_id)
    {
        $product_category_data = [];

        $query = $this->db->query("SELECT *
                                    FROM ".$this->db->table_name("products_to_categories")." 
                                    WHERE product_id = '".(int)$product_id."'");

        foreach ($query->rows as $result) {
            $product_category_data[] = $result['category_id'];
        }

        return $product_category_data;
    }

    /**
     * @param int $product_id
     *
     * @return array
     * @throws \Exception
     */
    public function getProductRelated($product_id)
    {
        $product_related_data = [];

        $query = $this->db->query("SELECT *
                                    FROM ".$this->db->table_name("products_related")." 
                                    WHERE product_id = '".(int)$product_id."'");

        foreach ($query->rows as $result) {
            $product_related_data[] = $result['related_id'];
        }

        return $product_related_data;
    }

    /**
     * @param int $product_id
     * @param int $language_id
     *
     * @return array
     * @throws \Exception
     */
    public function getProductTags($product_id, $language_id = 0)
    {
        $language_id = (int)$language_id;
        $product_tag_data = [];

        $query = $this->db->query("SELECT *
                                   FROM ".$this->db->table_name("product_tags")." 
                                   WHERE product_id = '".(int)$product_id."'");

        $tag_data = [];

        foreach ($query->rows as $result) {
            $tag_data[$result['language_id']][] = $result['tag'];
        }

        foreach ($tag_data as $language => $tags) {
            $product_tag_data[$language] = implode(',', $tags);
        }

        if ($language_id) {
            return $product_tag_data[$language_id];
        } else {
            return $product_tag_data;
        }
    }

    /**
     * @param int $product_id
     * @param int $language_id
     *
     * @return array
     * @throws \Exception
     */
    public function getProductSEOKeywords($product_id, $language_id = 0)
    {
        $language_id = (int)$language_id;
        $product_seo_keys = [];

        $query = $this->db->query("SELECT *
                                   FROM ".$this->db->table_name("url_aliases")."
                                   WHERE `query` = 'product_id=".(int)$product_id."'");

        foreach ($query->rows as $result) {
            $product_seo_keys[$result['language_id']] = $result['keyword'];
        }

        if ($language_id) {
            return $product_seo_keys[$language_id];
        } else {
            return $product_seo_keys;
        }
    }

    /**
     * @param array $data
     *
     * @return array|int
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getProducts($data = [])
    {

        if (!empty($data['content_language_id'])) {
            $language_id = (int)$data['content_language_id'];
        } else {
            $language_id = (int)$this->config->get('storefront_language_id');
        }

        if ($data['store_id']) {
            $store_id = (int)$data['store_id'];
        } else {
            $store_id = (int)$this->config->get('config_store_id');
        }

        if ($data) {
            $match = '';
            $filter = (isset($data['filter']) ? $data['filter'] : []);

            $sql = "SELECT ".$this->db->raw_sql_row_count()." *, p.product_id";
            $sql .= ", (SELECT 
                            CASE WHEN SUM(COALESCE(ppov.subtract,0))>0
                             THEN SUM(COALESCE(ppov.quantity,0))
                            ELSE pp.quantity END as quantity
                        FROM ".$this->db->table_name("products")." pp
                        LEFT JOIN ".$this->db->table_name("product_options")." ppo
                            ON ppo.product_id = pp.product_id
                        LEFT JOIN  ".$this->db->table_name("product_option_values")." ppov
                            ON (ppo.product_option_id = ppov.product_option_id)
                        WHERE pp.product_id = p.product_id
                        GROUP BY pp.product_id) as quantity ";

            $sql .= " FROM ".$this->db->table_name("products")." p
                        LEFT JOIN ".$this->db->table_name("product_descriptions")." pd
                            ON (p.product_id = pd.product_id AND pd.language_id = '".$language_id."')
                        INNER JOIN ".$this->db->table_name('products_to_stores')." ps
                            ON (p.product_id = ps.product_id AND ps.store_id = '".$store_id."') ";

            if (isset($filter['category']) && $filter['category'] > 0) {
                $sql .= " LEFT JOIN ".$this->db->table_name("products_to_categories")
                    ." p2c ON (p.product_id = p2c.product_id) ";
            }

            $sql .= ' WHERE 1=1 ';

            if (!empty($data['subsql_filter'])) {
                $sql .= " AND ".$data['subsql_filter'];
            }

            if (isset($filter['match']) && !is_null($filter['match'])) {
                $match = $filter['match'];
            }

            if (isset($filter['exclude']['product_id'])) {
                $exclude = $filter['exclude']['product_id'];
                $excludes = [];
                if (is_array($exclude)) {
                    foreach ($exclude as $ex) {
                        $excludes[] = (int)$ex;
                    };
                } elseif ((int)$exclude) {
                    $excludes = [(int)$exclude];
                }

                if ($excludes) {
                    $sql .= " AND p.product_id NOT IN (".implode(',', $excludes).") ";
                }
            }

            if (isset($filter['keyword']) && !is_null($filter['keyword'])) {
                $keywords = explode(' ', $filter['keyword']);

                if ($match == 'any') {
                    $sql .= " AND (";
                    foreach ($keywords as $k => $keyword) {
                        $sql .= $k > 0 ? " OR" : "";
                        $sql .= " (LCASE(pd.name) LIKE '%".$this->db->escape(mb_strtolower($keyword), true)."%'";
                        $sql .= " OR LCASE(p.model) LIKE '%".$this->db->escape(mb_strtolower($keyword), true)."%'";
                        $sql .= " OR LCASE(p.sku) LIKE '%".$this->db->escape(mb_strtolower($keyword), true)."%')";
                    }
                    $sql .= " )";
                } else {
                    if ($match == 'all') {
                        $sql .= " AND (";
                        foreach ($keywords as $k => $keyword) {
                            $sql .= $k > 0 ? " AND" : "";
                            $sql .= " (LCASE(pd.name) LIKE '%".$this->db->escape(mb_strtolower($keyword), true)."%'";
                            $sql .= " OR LCASE(p.model) LIKE '%".$this->db->escape(mb_strtolower($keyword), true)."%'";
                            $sql .= " OR LCASE(p.sku) LIKE '%".$this->db->escape(mb_strtolower($keyword), true)."%')";
                        }
                        $sql .= " )";
                    } else {
                        if ($match == 'exact') {
                            $sql .= " AND (LCASE(pd.name) LIKE '%".$this->db->escape(mb_strtolower($filter['keyword']),
                                    true)."%'";
                            $sql .= " OR LCASE(p.model) LIKE '%".$this->db->escape(mb_strtolower($filter['keyword']),
                                    true)."%'";
                            $sql .= " OR LCASE(p.sku) LIKE '%".$this->db->escape(mb_strtolower($filter['keyword']),
                                    true)."%')";
                        } else {
                            if ($match == 'begin') {
                                $sql .= " AND (LCASE(pd.name) LIKE '"
                                    .$this->db->escape(mb_strtolower($filter['keyword']), true)."%'";
                                $sql .= " OR LCASE(p.model) LIKE '".$this->db->escape(mb_strtolower($filter['keyword']),
                                        true)."%'";
                                $sql .= " OR LCASE(p.sku) LIKE '".$this->db->escape(mb_strtolower($filter['keyword']),
                                        true)."%')";
                            }
                        }
                    }
                }
            }

            if (isset($filter['pfrom']) && !is_null($filter['pfrom'])) {
                $sql .= " AND p.price >= '".(float)$filter['pfrom']."'";
            }
            if (isset($filter['pto']) && !is_null($filter['pto'])) {
                $sql .= " AND p.price <= '".(float)$filter['pto']."'";
            }

            if ($filter['category']) {
                $childrenIds = Category::getChildrenIDs((int)$filter['category']);
                $childrenIds[] = (int)$filter['category'];
                $sql .= " AND p2c.category_id IN (".implode(',', $childrenIds).")";
            }
            if (isset($filter['status']) && !is_null($filter['status'])) {
                $sql .= " AND p.status = '".(int)$filter['status']."'";
            }

            $sort_data = [
                'product_id'    => 'p.product_id',
                'name'          => 'pd.name',
                'model'         => 'p.model',
                'sku'           => 'p.sku',
                'quantity'      => 'quantity',
                'price'         => 'p.price',
                'status'        => 'p.status',
                'sort_order'    => 'p.sort_order',
                'date_modified' => 'p.date_modified',
            ];

            if (isset($data['sort']) && array_key_exists($data['sort'], $sort_data)) {
                $sql .= " ORDER BY ".$sort_data[$data['sort']];
            } else {
                //for faster SQL default to ID based order
                $sql .= " ORDER BY p.product_id";
            }

            if (isset($data['order']) && ($data['order'] == 'DESC')) {
                $sql .= " DESC";
            } else {
                $sql .= " ASC";
            }

            if (isset($data['start']) || isset($data['limit'])) {
                if ($data['start'] < 0) {
                    $data['start'] = 0;
                }

                if ($data['limit'] < 1) {
                    $data['limit'] = 20;
                }
                $sql .= " LIMIT ".(int)$data['start'].",".(int)$data['limit'];
            }
            $query = $this->db->query($sql);
            return $query->rows;
        } else {
            $cache_key = 'product.lang_'.$language_id;
            $product_data = $this->cache->get($cache_key);
            if ($product_data === null) {
                $query = $this->db->query("SELECT ".$this->db->raw_sql_row_count()." *, p.product_id
                                            FROM ".$this->db->table_name("products")." p
                                            LEFT JOIN ".$this->db->table_name("product_descriptions")." pd
                                                ON (p.product_id = pd.product_id AND pd.language_id = '".$language_id."')
                                            ORDER BY pd.name ASC");
                $product_data = $query->rows;
                $this->cache->put($cache_key, $product_data);
            }

            return $product_data;
        }
    }

    /**
     * @param int $stock_status_id
     *
     * @return int
     * @throws \Exception
     */
    public function getTotalProductsByStockStatusId($stock_status_id)
    {
        $query = $this->db->query("SELECT COUNT(*) AS total
                                    FROM ".$this->db->table_name("products")."
                                    WHERE stock_status_id = '".(int)$stock_status_id."'");

        return (int)$query->row['total'];
    }

    /**
     * @param int $tax_class_id
     *
     * @return int
     * @throws \Exception
     */
    public function getTotalProductsByTaxClassId($tax_class_id)
    {
        $query = $this->db->query("SELECT COUNT(*) AS total
                                    FROM ".$this->db->table_name("products")."
                                    WHERE tax_class_id = '".(int)$tax_class_id."'");

        return (int)$query->row['total'];
    }

    /**
     * @param int $weight_class_id
     *
     * @return int
     * @throws \Exception
     */
    public function getTotalProductsByWeightClassId($weight_class_id)
    {
        $query = $this->db->query("SELECT COUNT(*) AS total
                                    FROM ".$this->db->table_name("products")."
                                    WHERE weight_class_id = '".(int)$weight_class_id."'");

        return $query->row['total'];
    }

    /**
     * @param int $length_class_id
     *
     * @return int
     * @throws \Exception
     */
    public function getTotalProductsByLengthClassId($length_class_id)
    {
        $query = $this->db->query("SELECT COUNT(*) AS total
                                    FROM ".$this->db->table_name("products")."
                                    WHERE length_class_id = '".(int)$length_class_id."'");

        return $query->row['total'];
    }

    /**
     * @param int $option_id
     *
     * @return int
     * @throws \Exception
     */
    public function getTotalProductsByOptionId($option_id)
    {
        $query = $this->db->query("SELECT COUNT(*) AS total
                                    FROM ".$this->db->table_name("product_to_option")."
                                    WHERE option_id = '".(int)$option_id."'");

        return $query->row['total'];
    }

    /**
     * @param int $download_id
     *
     * @return int
     * @throws \Exception
     */
    public function getTotalProductsByDownloadId($download_id)
    {
        $query = $this->db->query("SELECT COUNT(*) AS total
                                    FROM ".$this->db->table_name("products_to_downloads")."
                                    WHERE download_id = '".(int)$download_id."'");

        return $query->row['total'];
    }

    /**
     * @param int $manufacturer_id
     *
     * @return int mixed
     * @throws \Exception
     */
    public function getTotalProductsByManufacturerId($manufacturer_id)
    {
        $query = $this->db->query("SELECT COUNT(*) AS total
                                    FROM ".$this->db->table_name("products")."
                                    WHERE manufacturer_id = '".(int)$manufacturer_id."'");

        return $query->row['total'];
    }

    /**
     * function checks if product will be displayed on storefront and returns array with messages about causes
     *
     * @param $product_id
     *
     * @return array
     * @throws \Exception
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getProductCondition($product_id)
    {
        $product_id = (int)$product_id;
        if (!$product_id) {
            return [];
        }

        $language_id = (int)$this->language->getContentLanguageID();

        $sql = "SELECT p.*,
                       p.quantity AS base_quantity,
                       p.subtract AS base_subtract,
                       pov.*,
                       pod.name AS option_name,
                       povd.name AS option_value_name
                FROM ".$this->db->table_name('products')." p
                LEFT JOIN ".$this->db->table_name('product_option_values')." pov
                    ON pov.product_id = p.product_id
                LEFT JOIN ".$this->db->table_name('product_option_value_descriptions')." povd
                    ON (povd.product_option_value_id = pov.product_option_value_id 
                        AND povd.language_id = ".$language_id.")
                LEFT JOIN ".$this->db->table_name('product_option_descriptions')." pod
                    ON (pod.product_option_id = pov.product_option_id AND pod.language_id = ".$language_id.")
                WHERE p.product_id = ".$product_id;
        $result = $this->db->query($sql);

        // id product disabled do not run other checks
        if (!$result->row['status']) {
            return [$this->language->get('text_product_disabled')];
        }
        $hasTrackOptions = $this->hasTrackOptions($product_id);
        $output = [];
        // check is product available
        if (H::dateISO2Int($result->row['date_available']) > time()) {
            $output[] = $this->language->get('text_product_unavailable');
        }

        //check is stock track for whole product(not options) enabled and product quantity more than 0
        if ($result->row['base_subtract'] && $result->row['base_quantity'] <= 0 && !$hasTrackOptions) {
            $output[] = $this->language->get('text_product_out_of_stock');
        }
        $out_of_stock = false;
        $error_txt = [];
        if($hasTrackOptions) {
            foreach ($result->rows as $k => $row) {
                if ($row['subtract'] && $row['quantity'] <= 0) {
                    $error_txt[] = $row['option_name'].' => '.$row['option_value_name'];
                    $out_of_stock = true;
                }
            }
        }

        if ($out_of_stock && $hasTrackOptions) {
            $output[] = $this->language->get('text_product_option_out_of_stock');
            $output = array_merge($output, $error_txt);
        }

        return $output;
    }

    public function getOrderProductOptions($product_id)
    {
        if (!(int)$product_id) {
            return [];
        }
        $language_id = $this->language->getContentLanguageID();
        $elements = HtmlElementFactory::getAvailableElements();

        $product_option_data = [];
        $product_option_query = $this->db->query(
            "SELECT po.*, pod.option_placeholder, pod.error_text
            FROM ".$this->db->table_name("product_options")." po
            LEFT JOIN ".$this->db->table_name("product_option_descriptions")." pod
                ON pod.product_option_id = po.product_option_id AND pod.language_id =  '".$language_id."'
            WHERE po.product_id = '".(int)$product_id."'
                AND po.group_id = 0
                AND po.status = 1
            ORDER BY po.sort_order"
        );
        if ($product_option_query) {
            foreach ($product_option_query->rows as $product_option) {

                $attribute_values = [];
                $product_option_value_data = [];
                $product_option_value_query = $this->db->query(
                    "SELECT *
                    FROM ".$this->db->table_name("product_option_values")."
                    WHERE product_option_id = '".(int)$product_option['product_option_id']."'
                    ORDER BY sort_order"
                );
                if ($product_option_value_query) {
                    foreach ($product_option_value_query->rows as $product_option_value) {
                        if ($product_option_value['attribute_value_id']) {
                            //skip duplicate attributes values if it is not grouped parent/child
                            if (in_array($product_option_value['attribute_value_id'], $attribute_values)) {
                                continue;
                            }
                            $attribute_values[] = $product_option_value['attribute_value_id'];
                        }
                        $pd_opt_val_description_qr = $this->db->query(
                            "SELECT *
                            FROM ".$this->db->table_name("product_option_value_descriptions")."
                            WHERE product_option_value_id = '".(int)$product_option_value['product_option_value_id']."'
                                AND language_id = '".(int)$language_id."'"
                        );

                        $product_option_value_data[$product_option_value['product_option_value_id']] = [
                            'product_option_value_id' => $product_option_value['product_option_value_id'],
                            'attribute_value_id'      => $product_option_value['attribute_value_id'],
                            'grouped_attribute_data'  => $product_option_value['grouped_attribute_data'],
                            'group_id'                => $product_option_value['group_id'],
                            'name'                    => $pd_opt_val_description_qr->row['name'],
                            'option_placeholder'      => $product_option['option_placeholder'],
                            'regexp_pattern'          => $product_option['regexp_pattern'],
                            'error_text'              => $product_option['error_text'],
                            'settings'                => $product_option['settings'],
                            'children_options_names'  => $pd_opt_val_description_qr->row['children_options_names'],
                            'sku'                     => $product_option_value['sku'],
                            'price'                   => $product_option_value['price'],
                            'prefix'                  => $product_option_value['prefix'],
                            'weight'                  => $product_option_value['weight'],
                            'weight_type'             => $product_option_value['weight_type'],
                            'quantity'                => $product_option_value['quantity'],
                            'subtract'                => $product_option_value['subtract'],
                            'default'                 => $product_option_value['default'],
                        ];

                    }
                }
                $prd_opt_description_qr = $this->db->query(
                    "SELECT *
                    FROM ".$this->db->table_name("product_option_descriptions")."
                    WHERE product_option_id = '".(int)$product_option['product_option_id']."'
                        AND language_id = '".(int)$language_id."'"
                );

                $product_option_data[$product_option['product_option_id']] = [
                    'product_option_id'  => $product_option['product_option_id'],
                    'attribute_id'       => $product_option['attribute_id'],
                    'group_id'           => $product_option['group_id'],
                    'name'               => $prd_opt_description_qr->row['name'],
                    'option_placeholder' => $product_option['option_placeholder'],
                    'option_value'       => $product_option_value_data,
                    'sort_order'         => $product_option['sort_order'],
                    'element_type'       => $product_option['element_type'],
                    'html_type'          => $elements[$product_option['element_type']]['type'],
                    'required'           => $product_option['required'],
                    'regexp_pattern'     => $product_option['regexp_pattern'],
                    'error_text'         => $product_option['error_text'],
                    'settings'           => $product_option['settings'],
                ];
            }
        }

        return $product_option_data;
    }

    public function touchProduct($product_id)
    {
        $product_id = (int)$product_id;
        if (!$product_id) {
            return false;
        }
        $this->db->query(
            "UPDATE ".$this->db->table_name('products')." 
            SET date_modified = NOW() 
            WHERE product_id ='".$product_id."'"
        );
        $this->cache->flush('product');

        return true;
    }

    /**
     * @param int $product_id
     * @param array $product_data
     * @param array $tables
     *
     * @throws \abc\core\lib\AException
     */
    protected function updateEvent(int $product_id, array $product_data, array $tables)
    {
        $product_info = $this->getProduct($product_id);

        H::event('abc\models\admin\product\update', [new ABaseEvent($product_info, $tables)]);
    }

    /**
     * @param int $product_id
     *
     * @return bool
     * @throws \Exception
     */
    public function hasTrackOptions($product_id)
    {
        $sql = "SELECT *
                FROM ".$this->db->table_name('product_option_values')." pov
                INNER JOIN ".$this->db->table_name('product_options')." po
                    ON (pov.product_option_id = po.product_option_id AND po.status = 1) 
                WHERE pov.product_id=".(int)$product_id." AND pov.subtract = 1";
        $result = $this->db->query($sql);

        return ($result->num_rows ? true : false);
    }

    /*  public function hasAnyStock($product_id)
      {
          if (!(int)$product_id) {
              return 0;
          }
          $total_quantity = 0;
          //check product option values
          $query = $this->db->query("SELECT pov.quantity AS quantity, pov.subtract
                                      FROM ".$this->db->table_name("product_options")." po
                                      LEFT JOIN ".$this->db->table_name("product_option_values")." pov
                                          ON (po.product_option_id = pov.product_option_id)
                                      WHERE po.product_id = '".(int)$product_id."' AND po.status = 1");
          if ($query->num_rows) {
              $notrack_qnt = 0;
              foreach ($query->rows as $row) {
                  //if tracking of stock disabled - set quantity as big
                  if (!$row['subtract']) {
                      $notrack_qnt += 10000000;
                      continue;
                  }
                  $total_quantity += $row['quantity'] < 0 ? 0 : $row['quantity'];
              }
              //if some of option value have subtract NO - think product is available
              if ($total_quantity == 0 && $notrack_qnt) {
                  $total_quantity = true;
              }
          } else {
              //get product quantity without options
              $query = $this->db->query("SELECT quantity
                                          FROM ".$this->db->table_name("products")." p
                                          WHERE p.product_id = '".(int)$product_id."'");
              $total_quantity = (int)$query->row['quantity'];
          }
          return $total_quantity;
      }*/

    public function getProductColumns()
    {
        $sql = "SELECT COLUMN_NAME
                FROM information_schema.COLUMNS c
                WHERE c.`TABLE_SCHEMA` = '".$this->db->getDatabaseName()."'
                    AND c.`TABLE_NAME` = '".$this->db->table_name('products')."'";
        $result = $this->db->query($sql);

        return array_column($result->rows, 'COLUMN_NAME');
    }
}
