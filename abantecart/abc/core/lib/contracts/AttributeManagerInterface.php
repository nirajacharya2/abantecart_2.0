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

namespace abc\core\lib\contracts;

use abc\core\engine\contracts\AttributeInterface;
use abc\core\lib\AException;
use abc\core\lib\ASession;

/**
 * Class to handle access to global attributes
 *
 * @property ASession $session
 */
interface AttributeManagerInterface extends AttributeInterface
{
    /**
     * Get details about given group for attributes
     *
     * @param $group_id
     * @param int $language_id
     *
     * @return array
     * @throws \Exception
     */
    public function getActiveAttributeGroup($group_id, $language_id = 0);

    /**
     * Get array of all core attribute types controllers (for recognizing of core attribute types)
     *
     * @return array
     */
    public function getCoreAttributeTypesControllers();

    /**
     * @param string $type
     *
     * @return null | int
     * Get attribute type id based on attribute type_key
     */
    public function getAttributeTypeID($type);

    /**
     * @param string $type
     *
     * @return array
     * Get attribute type data based on attribute type_key
     */
    public function getAttributeTypeInfo($type);

    /**
     * @param int $type_id
     *
     * @return array
     * Get attribute type data based on attribute type id
     */
    public function getAttributeTypeInfoById($type_id);

    /**
     * load all the attributes for specified type
     *
     * @param $attribute_type
     * @param int $language_id - Language id. default 0 (english)
     * @param int $attribute_parent_id - Parent attribute ID if any. Default 0 (parent)
     *
     * @return array
     * @throws \Exception
     */
    public function getAttributesByType($attribute_type, $language_id = 0, $attribute_parent_id = 0);

    /**
     * get attribute connected to option
     *
     * @param $option_id
     *
     * @return null
     * @throws \Exception
     */
    public function getAttributeByProductOptionId($option_id);

    /**
     * method for validation of data based on global attributes requirements
     *
     * @param array $data - usually it's a $_POST
     *
     * @return array - array with error text for each of invalid field data
     */
    public function validateAttributeData($data = []);

    public function clearCache();

    /**
     * @param int $attribute_id
     *
     * @throws \Exception
     */
    public function deleteAttribute($attribute_id);

    /**
     * @param array $data
     *
     * @return bool|int
     * @throws AException
     */
    public function addAttribute($data);

    /**
     * @param int $attribute_id
     * @param array $data
     *
     * @throws AException
     */
    public function updateAttribute($attribute_id, $data);

    /**
     * @param int $attribute_id
     * @param int $sort_order
     *
     * @return bool|int
     * @throws \Exception
     */
    public function addAttributeValue($attribute_id, $sort_order);

    /**
     * @param int $attribute_value_id
     *
     * @return bool
     * @throws \Exception
     */
    public function deleteAttributeValues($attribute_value_id);

    /**
     * @param int $attribute_value_id
     * @param int $sort_order
     *
     * @return bool
     * @throws \Exception
     */
    public function updateAttributeValue($attribute_value_id, $sort_order);

    /**
     * @param int $attribute_id
     * @param int $attribute_value_id
     * @param int $language_id
     * @param string $value
     *
     * @return bool
     * @throws AException
     */
    public function addAttributeValueDescription($attribute_id, $attribute_value_id, $language_id, $value);

    /**
     * @param int $attribute_id
     * @param int $attribute_value_id
     * @param int $language_id
     * @param string $value
     *
     * @return bool
     * @throws AException
     */
    public function updateAttributeValueDescription($attribute_id, $attribute_value_id, $language_id, $value);

    /**
     * @param int $attribute_value_id
     *
     * @return bool
     * @throws \Exception
     */
    public function deleteAllAttributeValueDescriptions($attribute_value_id);

    /**
     * @param int $attribute_value_id
     * @param int $language_id
     *
     * @return bool
     * @throws \Exception
     */
    public function deleteAttributeValueDescription($attribute_value_id, $language_id);

    /**
     * @param int $group_id
     *
     * @void
     * @throws \Exception
     */
    public function deleteAttributeGroup($group_id);

    /**
     * @param array $data
     *
     * @return int
     * @throws AException
     */
    public function addAttributeGroup($data);

    /**
     * @param int $group_id
     * @param array $data
     *
     * @throws AException
     */
    public function updateAttributeGroup($group_id, $data);

    /**
     * Get details about given group for attributes
     *
     * @param int $group_id
     * @param int $language_id
     *
     * @return array
     * @throws \Exception
     */
    public function getAttributeGroup($group_id, $language_id = 0);

    /**
     * @param array $data
     *
     * @return array
     * @throws \Exception
     */
    public function getAttributeGroups($data = []);

    /**
     * @param array $data
     *
     * @return array
     * @throws \Exception
     */
    public function getTotalAttributeGroups($data = []);

    /**
     * @param int $attribute_id
     * @param int $language_id
     *
     * @return array|null
     * @throws \Exception
     */
    public function getAttribute($attribute_id, $language_id = 0);

    /**
     * @param int $attribute_id
     *
     * @return array
     * @throws \Exception
     */
    public function getAttributeDescriptions($attribute_id);

    /**
     * @param int $attribute_id
     * @param int $language_id
     *
     * @return array
     * @throws \Exception
     */
    public function getAttributeValues($attribute_id, $language_id = 0);

    /**
     * @param int $attribute_value_id
     *
     * @return array
     * @throws \Exception
     */
    public function getAttributeValueDescriptions($attribute_value_id);

    /**
     * @param array $data
     * @param int $language_id
     * @param null|int $attribute_parent_id
     * @param string $mode
     *
     * @return array|int
     * @throws \Exception
     */
    public function getAttributes($data = [], $language_id = 0, $attribute_parent_id = null, $mode = 'default');

    /**
     * @param array $data
     * @param int $language_id
     * @param null $attribute_parent_id
     *
     * @return int
     * @throws \Exception
     */
    public function getTotalAttributes($data = [], $language_id = 0, $attribute_parent_id = null);

    /**
     * @return array
     * @throws \Exception
     */
    public function getLeafAttributes();

    /**
     * common method for external validation of attribute
     *
     * @param array $data
     *
     * @return array
     * @throws AException
     * @throws \ReflectionException
     */
    public function validateAttributeCommonData($data = []);
}