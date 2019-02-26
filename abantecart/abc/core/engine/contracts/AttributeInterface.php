<?php


namespace abc\core\engine\contracts;

interface AttributeInterface
{
    public function getAttributes();

    public function getActiveAttributeGroup($group_id, $language_id = 0);

    public function getAttributeTypes();

    public function getCoreAttributeTypesControllers();

    public function getAttributeTypeID($type);

    public function getAttributeTypeInfo($type);

    public function getAttributeTypeInfoById($type_id);

    public function totalChildren($attribute_id);

    public function getAttributesByType($attribute_type, $language_id = 0, $attribute_parent_id = 0);

    public function getAttributeByProductOptionId($option_id);

    public function getAttribute($attribute_id);

    public function getAttributeValues($attribute_id, $language_id = 0);

    public function validateAttributeData($data = []);
}