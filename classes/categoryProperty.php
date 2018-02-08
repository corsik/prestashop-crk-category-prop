<?php

class categoryProperty extends ObjectModel {

    public $id;
    public $id_category;
    public $property_type;
    public $property_lang;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'category_lang_property',
        'primary' => 'id_property',
        'multilang' => false,
        'fields' => array(
            'id_category' => array('type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true),
            'property_type' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
            'property_lang' => array('type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true),
        )
    );
    public function copyFromPost()
    {
        /* Classical fields */
        foreach ($_POST as $key => $value) {
            if (array_key_exists ($key, $this) and $key != 'id_' . $this->table) {
                $this->{$key} = $value;
            }
        }
    }

}
