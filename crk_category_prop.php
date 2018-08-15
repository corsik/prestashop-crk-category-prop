<?php

if (!defined('_PS_VERSION_'))
    exit;

require_once _PS_MODULE_DIR_ . 'crk_category_prop/classes/categoryProperty.php';

class crk_category_prop extends Module {

    private $templateFile;
    protected $_errors = array();
    protected $_html = '';
    public $table_properties = 'category_property';
    public $table_lang_properties = 'category_lang_property';
    public $properties = [];
    public $selected_categories = [];

    public function __construct() {
        $this->name = 'crk_category_prop';
        $this->tab = 'front_office_features';
        $this->version = '1.2';
        $this->author = 'corsik';
        $this->need_instance = 0;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Show additional category property');
        $this->description = $this->l('Displays an additional field');

        $this->ps_versions_compliancy = array('min' => '1.7.2.0', 'max' => _PS_VERSION_);

        $this->templateFile = 'module:crk_category_prop/views/templates/hook/backoffice.tpl';
    }

    public function install() {
        return parent::install() &&
                $this->installDB() &&
                $this->registerHook('displayBackOfficeCategory') &&
                $this->registerHook('categoryAddition') &&
                $this->registerHook('categoryUpdate') &&
                $this->registerHook('filterCategoryContent') &&
                $this->registerHook('filterProductContent');
    }

    public function uninstall() {
        return parent::uninstall() &&
                $this->uninstallDB() &&
                $this->unregisterHook('displayBackOfficeCategory') &&
                $this->unregisterHook('categoryAddition') &&
                $this->unregisterHook('categoryUpdate') &&
                $this->unregisterHook('filterCategoryContent') &&
                $this->unregisterHook('filterProductContent');
    }

    public function installDB() {
        $return = true;
        $return &= Db::getInstance()->execute('
                    CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . $this->table_properties . '` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_category` INT NOT NULL,
                    `id_property` INT(10) NOT NULL,
                    `property_value` TEXT DEFAULT NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 ;'
        );

        $return &= Db::getInstance()->execute('
                    CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . $this->table_lang_properties . '` (
                    `id_property` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_category` INT NOT NULL,
                    `property_type` VARCHAR(255) NOT NULL,
                    `property_code` VARCHAR(255) DEFAULT NULL,
                    `property_lang` VARCHAR(255) NOT NULL,
                    `property_subcategory` INT(1) NOT NULL,
                    PRIMARY KEY (`id_property`)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 ;'
        );
        return $return;
    }

    public function uninstallDB() {
        return Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $this->table_properties . '`') && Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $this->table_lang_properties . '`');
    }

    public function getContent() {
        $id_property = (int) Tools::getValue('id_property');
        $html = '';

        if (Tools::isSubmit('saveProperty')) {
            if ($id_property > 0) {
                $property = new categoryProperty((int) $id_property);
            } else {
                $property = new categoryProperty();
            }

            $property->copyFromPost();

            if ($property->validateFields(false)) {
                $property->save();
                $this->_clearCache('*');
            } else {
                $html .= '<div class="conf error">' . $this->trans('An error occurred while attempting to save.', array(), 'Admin.Notifications.Error') . '</div>';
            }
        }


        if (Tools::isSubmit('update' . $this->name) || Tools::isSubmit('add' . $this->name)) {
            $helper = $this->initForm();

            if (isset($id_property) && $id_property > 0) {
                $objProperty = new categoryProperty((int) $id_property);
                $this->fields_form[0]['form']['input'][] = array('type' => 'hidden', 'name' => 'id_property');
            }

            $helper->fields_value['id_property'] = (isset($id_property) && $id_property > 0) ? $id_property : "";
            $helper->fields_value['property_lang'] = (isset($id_property) && $id_property > 0) ? $objProperty->property_lang : "";
            $helper->fields_value['property_code'] = (isset($id_property) && $id_property > 0) ? $objProperty->property_code : "";
            $helper->fields_value['property_type'] = (isset($id_property) && $id_property > 0) ? $objProperty->property_type : "";
            $helper->fields_value['property_subcategory'] = (isset($id_property) && $id_property > 0) ? $objProperty->property_subcategory : "";

            return $html . $helper->generateForm($this->fields_form);
        } elseif (Tools::isSubmit('delete' . $this->name)) {
            $objProperty = new categoryProperty((int) $id_property);
            $objProperty->delete();
            $this->deleteProperty('id_property = ' . $id_property);
            $this->_clearCache('*');
            Tools::redirectAdmin(AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'));
        } else {
            $content = $this->getLangProperties();
            $helper = $this->initList();
            $helper->listTotal = count($content);

            return $html . $helper->generateList($content, $this->fields_list);
        }
    }

    protected function initForm() {

        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $objProperty = new categoryProperty((int) Tools::getValue('id_property'));
        $selected_categories = [$objProperty->id_category];

        $this->fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Category properties'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->trans('Text', array(), 'Admin.Global'),
                    'name' => 'property_lang',
                    'cols' => 40,
                    'rows' => 10
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Property code'),
                    'name' => 'property_code',
                    'cols' => 40,
                    'rows' => 10
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Type field'),
                    'name' => 'property_type',
                    'multiple' => false,
                    'options' => array(
                        'query' => array(
                            array('key' => 'input', 'name' => 'input'),
                            array('key' => 'textarea', 'name' => 'textarea'),
                            array('key' => 'html', 'name' => 'html'),
                        ),
                        'id' => 'key',
                        'name' => 'name'
                    ),
                ),
                array(
                    'type' => 'categories',
                    'label' => $this->l('Category'),
                    'name' => 'id_category',
                    'tree' => array(
                        'id' => 'categories-tree',
                        'selected_categories' => $selected_categories,
//                      'disabled_categories' =>
                        'root_category' => Context::getContext()->shop->getCategory()
                    )
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Subcategory property'),
                    'name' => 'property_subcategory',
                    'required' => false,
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        )
                    ),
                ),
            ),
            'submit' => array(
                'title' => $this->trans('Save', array(), 'Admin.Actions'),
            )
        );

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        foreach (Language::getLanguages(false) as $lang) {
            $helper->languages[] = array(
                'id_lang' => $lang['id_lang'],
                'iso_code' => $lang['iso_code'],
                'name' => $lang['name'],
                'is_default' => ($default_lang == $lang['id_lang'] ? 1 : 0)
            );
        }

        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'saveProperty';
        $helper->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->trans('Save', array(), 'Admin.Actions'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' =>
            array(
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->trans('Back to list', array(), 'Admin.Actions'),
            )
        );
        return $helper;
    }

    protected function initList() {
        $this->fields_list = array(
            'id_property' => array(
                'title' => $this->trans('ID', array(), 'Admin.Global'),
                'width' => 120,
                'type' => 'text',
                'search' => false,
                'orderby' => false
            ),
            'id_category' => array(
                'title' => $this->l('Category ID'),
                'width' => 120,
                'type' => 'text',
                'search' => false,
                'orderby' => false
            ),
            'property_type' => array(
                'title' => $this->l('Type field'),
                'width' => 140,
                'type' => 'text',
                'search' => false,
                'orderby' => false
            ),
            'property_code' => array(
                'title' => $this->l('Property code'),
                'width' => 140,
                'type' => 'text',
                'search' => false,
                'orderby' => false
            ),
            'property_lang' => array(
                'title' => $this->trans('Text', array(), 'Admin.Global'),
                'width' => 140,
                'type' => 'text',
                'search' => false,
                'orderby' => false
            ),
        );

        if (Shop::isFeatureActive()) {
            $this->fields_list['id_shop'] = array(
                'title' => 'Название свойства',
                'align' => 'center',
                'width' => 25,
                'type' => 'int'
            );
        }
        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = false;
        $helper->identifier = 'id_property';
        $helper->actions = array('edit', 'delete');
        $helper->show_toolbar = true;
        $helper->imageType = 'jpg';
        $helper->toolbar_btn['new'] = array(
            'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&add' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'),
            'desc' => $this->trans('Add new', array(), 'Admin.Actions')
        );

        $helper->title = $this->displayName;
        $helper->table = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        return $helper;
    }

    protected function _clearCache($template, $cacheId = null, $compileId = null) {
        parent::_clearCache($this->templateFile);
    }

//    public function setProperty() {
//        $arrValues = Tools::getAllValues();
//        $arrSet = [
//            'id_category' => (int) $arrValues['id_category'],
//            'property_type' => pSQL($arrValues['property_type']),
//            'property_lang' => pSQL($arrValues['property_lang'])
//        ];
//
//        if (isset($arrValues) && $arrValues['id_property'] > 0) {
//            Db::getInstance()->update($this->table_properties, $arrSet, 'id = ' . $arrValues['id']);
//        } else {
//            Db::getInstance()->insert($this->table_lang_properties, $arrSet);
//        }
//    }

    public function deleteProperty($where) {
        return Db::getInstance()->delete($this->table_properties, $where);
    }

    public function getLangProperties() {
        return Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . $this->table_lang_properties . '`');
    }

    public function getLangProperty($where = array(), $value = false) {
        $sql = 'SELECT ' . _DB_PREFIX_ . $this->table_lang_properties . '.*, ' . _DB_PREFIX_ . $this->table_properties . '.property_value'
                . ' FROM `' . _DB_PREFIX_ . $this->table_lang_properties . '`';
        if ($value) {
            $sql .= ' LEFT JOIN `' . _DB_PREFIX_ . $this->table_properties . '` ON '
                    . _DB_PREFIX_ . $this->table_lang_properties . '.id_property = ' . _DB_PREFIX_ . $this->table_properties . '.id_property';
        }
        $sql .= ' WHERE ' . _DB_PREFIX_ . $this->table_properties . '.' . key($where) . ' = "' . (int) $where[key($where)] . '"';

        return Db::getInstance()->executeS($sql);
    }

    public function getPropertiesCategory($id_category, $id_property) {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . $this->table_properties . '` WHERE `id_category` = "' . (int) $id_category . '"';
        if (isset($id_property) && strlen($id_property) > 1)
            $sql .= ' AND `id_property` = "' . $id_property . '"';
        return Db::getInstance()->executeS($sql);
    }

    public function getBackOfficeProperty() {
        $arrProperty = [];
        $id_category = Tools::getValue('id_category');
        $id_parent = categoryProperty::getParentCategory($id_category);
        $property_parent = categoryProperty::getParentProperty($id_parent['id_parent']);
        $property_value = $this->getLangProperty(['id_category' => $id_category], true);

        if (!empty($property_parent)) {
            foreach ($property_parent as $key => $property) {
                $arrProperty[$property['id_property']] = $property;
            }
        }

        if (!empty($property_value)) {
            foreach ($property_value as $key => $value) {
                if ($arrProperty[$value['id_property']]) {
                    $arrProperty[$value['id_property']]['property_value'] = $value['property_value'];
                }
                $arrProperty[$value['id_property']] = $value;
            }
        }

        $this->properties = $arrProperty;
    }

    public function hookCategoryAddition($params) {
//        $this->properties = $this->getLangProperty (['id_category' => Tools::getValue ('id_category')], true);
        $this->getBackOfficeProperty();

        foreach ($this->properties as $prop) {
            $id_category = (int) $params['category']->id;
            $arrSet = [
                'id_category' => $id_category,
                'id_property' => (int) $prop['id_property'],
                'property_value' => Tools::getValue('property_' . $prop['id_property'])
            ];

            if (strlen($prop['property_value']) > 0) {
                Db::getInstance()->update($this->table_properties, $arrSet, 'id_property = ' . $prop['id_property'] . ' AND `id_category` = "' . $id_category . '"');
            } else {
                Db::getInstance()->insert($this->table_properties, $arrSet);
            }
        }
    }

    public function hookCategoryUpdate($params) {
        $this->hookCategoryAddition($params);
    }

    public function hookDisplayBackOfficeCategory($params) {
        $this->getBackOfficeProperty();

        $this->context->smarty->assign(['arrProperty' => $this->properties]);

        return $this->display(__FILE__, 'backoffice.tpl');
    }

    public function hookFilterCategoryContent(&$params) {
        $arrProperty = $this->getLangProperty(['id_category' => $params['object']['id']], true);
        if(!empty($arrProperty)){
            foreach ($arrProperty as $value) {
                $params['object']['property'][$value['property_code']] = $value['property_value'];
            }
        }

        return $params;
    }

    public function hookFilterProductContent(&$params) {
        $arrProperty = $this->getLangProperty(['id_category' => $params['object']['id_category_default']], true);
        if(!empty($arrProperty)){
            foreach ($arrProperty as $value) {
                $params['object']['category_property'][$value['property_code']] = $value['property_value'];
            }
        }
        return $params;
    }

}
