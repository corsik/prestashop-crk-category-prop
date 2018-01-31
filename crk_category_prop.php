<?php
if (!defined('_PS_VERSION_'))
	exit;

class crk_category_prop extends Module
{

	protected $_errors = array();
	protected $_html = '';
        public $table_name =  'category_property';

	public function __construct()
	{
		$this->name = 'crk_category_prop';
		$this->tab = 'front_office_features';
		$this->version = '1.0';
		$this->author = 'corsik';
		$this->need_instance = 0;

		$this->bootstrap = true;

	 	parent::__construct();

		$this->displayName = $this->l('Show additional category property');
		$this->description = $this->l('Add an additional field to the category');
	}

	public function install()
	{
		if (!parent::install() ||
			!$this->installDB() ||
			!$this->registerHook('displayBackOfficeCategory') ||
			!$this->registerHook('categoryAddition') ||
			!$this->registerHook('categoryUpdate') ||
                        !$this->registerHook('filterCategoryContent')
			)
			return false;
		return true;
	}

	public function uninstall()
	{
		if (!parent::uninstall() ||
			!$this->uninstallDB() ||
                        !$this->unregisterHook('displayBackOfficeCategory') ||
			!$this->unregisterHook('categoryAddition') ||
			!$this->unregisterHook('categoryUpdate') ||
                        !$this->registerHook('filterCategoryContent')
                        )
			return false;
		return true;
	}

        public function installDB()
        {
            $res = Db::getInstance()->execute('
                    CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.$this->table_name.'` (
                    `id_category` INT UNSIGNED NOT NULL,
                    `property` TEXT DEFAULT NULL,
                    PRIMARY KEY (`id_category`)
                ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 ;'
            );
            if(!$res)
                return false;
            return true;
        }

        public function uninstallDB()
        {
            if(!Db::getInstance()->execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.$this->table_name.'`'))
		return false;
            return true;
        }

	public function getProperty($id_category)
	{
            return Db::getInstance()->getValue('SELECT property FROM '._DB_PREFIX_.$this->table_name.' WHERE id_category = '. (int)$id_category);
	}

	public function hookCategoryAddition($params)
	{
            if(boolval($this->getProperty($params['category']->id))){
                return Db::getInstance()->update($this->table_name, array('property' => pSQL(Tools::getValue('property'))), 'id_category = ' . $params['category']->id);
            }else{
                return Db::getInstance()->insert($this->table_name, ['id_category' => $params['category']->id, 'property' => pSQL(Tools::getValue('property'))]);
            }
	}

	public function hookCategoryUpdate($params)
	{
		$this->hookCategoryAddition($params);
	}

	public function hookDisplayBackOfficeCategory($params)
	{
		if(Tools::getValue('id_category'))
			$property = $this->getProperty(Tools::getValue('id_category'));
		else $property = '';

		$this->context->smarty->assign(['property'=> $property]);

		return $this->display(__FILE__, 'backoffice.tpl');
	}

        public function hookFilterCategoryContent(&$params)
	{
            $property = $this->getProperty($params['object']['id']);
            if(boolval($property))
                $params['object']['property'] = $property;
            return $params;
        }


}
