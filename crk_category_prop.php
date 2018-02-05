<?php
if (!defined ('_PS_VERSION_'))
    exit;

class crk_category_prop extends Module
{

    protected $_errors = array();
    protected $_html = '';
    public $table_name = 'category_property';
    public $properties = ['color', 'link'];
    public function __construct ()
    {
        $this->name = 'crk_category_prop';
        $this->tab = 'front_office_features';
        $this->version = '1.0';
        $this->author = 'corsik';
        $this->need_instance = 0;

        $this->bootstrap = true;

        parent::__construct ();

        $this->displayName = $this->l ('Show additional category property');
        $this->description = $this->l ('Displays an additional field');

        $this->ps_versions_compliancy = array('min' => '1.7.2.0', 'max' => _PS_VERSION_);
    }

    public function install ()
    {
        if (!parent::install () ||
            !$this->installDB () ||
            !$this->registerHook ('displayBackOfficeCategory') ||
            !$this->registerHook ('categoryAddition') ||
            !$this->registerHook ('categoryUpdate') ||
            !$this->registerHook ('filterCategoryContent') ||
            !$this->registerHook ('filterProductContent')

        )
            return false;
        return true;
    }

    public function uninstall ()
    {
        if (!parent::uninstall () ||
            !$this->uninstallDB () ||
            !$this->unregisterHook ('displayBackOfficeCategory') ||
            !$this->unregisterHook ('categoryAddition') ||
            !$this->unregisterHook ('categoryUpdate') ||
            !$this->unregisterHook ('filterCategoryContent') ||
            !$this->unregisterHook ('filterProductContent')
        )
            return false;
        return true;
    }

    public function installDB ()
    {
        $res = Db::getInstance ()->execute ('
                    CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . $this->table_name . '` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_category` INT NOT NULL,
                    `property_name` VARCHAR(255) NOT NULL,
                    `property_value` TEXT DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 ;'
        );
        if (!$res)
            return false;
        return true;
    }

    public function uninstallDB ()
    {
        if (!Db::getInstance ()->execute ('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $this->table_name . '`'))
            return false;
        return true;
    }

    public function getProperty ($id_category, $prop)
    {
        return Db::getInstance()->getRow ('SELECT * FROM `' . _DB_PREFIX_ . $this->table_name . '` WHERE `id_category` = "' . (int)$id_category . '" AND `property_name` = "'.$prop.'"');
    }

    public function getArrayProperty($id_category)
    {
        $arrProperty = [];
        foreach ($this->properties as $prop) {
            if ($id_category > 0){
                $property = $this->getProperty($id_category, $prop);
                $arrProperty[$prop] = $property['property_value'];
            }else{
                $arrProperty[$prop] = '';
            }
        }
        return $arrProperty;
    }

    public function hookCategoryAddition ($params)
    {
        foreach ($this->properties as $prop) {
            $result = $this->getProperty ($params['category']->id, $prop);
            if (boolval ($result))
            {
                Db::getInstance ()->update (
                    $this->table_name,
                    [
                        'id_category' => (int)$params['category']->id,
                        'property_name' => pSQL ($prop),
                        'property_value' => pSQL (Tools::getValue ('property_'.$prop))
                    ],
                    'id = ' . $result['id']);
            }
            else
            {
                Db::getInstance()->insert(
                    $this->table_name,
                    [
                        'id_category' => (int)$params['category']->id,
                        'property_name' => pSQL ($prop),
                        'property_value' => pSQL (Tools::getValue ('property_'.$prop))
                    ]
                );
            }
        }
    }

    public function hookCategoryUpdate ($params)
    {
        $this->hookCategoryAddition ($params);
    }

    public function hookDisplayBackOfficeCategory ($params)
    {
        $arrProperty = $this->getArrayProperty (Tools::getValue ('id_category'));

        $this->context->smarty->assign (['arrProperty' => $arrProperty]);

        return $this->display (__FILE__, 'backoffice.tpl');
    }

    public function hookFilterCategoryContent (&$params)
    {
        $arrProperty = $this->getArrayProperty ($params['object']['id']);
        if (boolval ($arrProperty))
            $params['object']['property'] = $arrProperty;

        return $params;
    }

    public function hookFilterProductContent (&$params)
    {
        $arrProperty = $this->getArrayProperty ($params['object']['id_category_default']);
        if (boolval ($arrProperty))
            $params['object']['category_property'] = $arrProperty;

        return $params;
    }
}