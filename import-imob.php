<?php 
/**
 * Plugin name: Import Imob
 * Plugin URI: 
 * Description: Importe imóveis da plataforma Ingaia Imob via XML e transforme em publicações no seu portal.
 * Version: 1.0.0
 * Author: JA93
 * Author URI: http://unitycode.tech
 * Text domain: import-imob
 * License: GPL2
*/
if (!defined('ABSPATH')) :
    exit;
endif;

# CONSTANTS
define('TEXT_DOMAIN', 'import_imob');
define('PREFIX', 'import_imob');

class ImportImob {

    private static $instance;

    public static function getInstance()
    {
        if (self::$instance == NULL) :

            self::$instance = new self();

        endif;

        return self::$instance;
    }

    # CONSTRUTOR
    private function __construct()
    {
        add_action('admin_menu', array($this, 'create_menu'));
        #add_action('admin_enqueue_scripts', array($this, 'register_and_enqueue_scripts'));
    }

    # MENU WORDPRESS
    public function create_menu()
    {
        add_menu_page('Imob Import', 'ImobImport', 'administrator', 'imob-import', 'ImportImob::imob_admin_page', 'dashicons-admin-multisite', 65);
    }

    # VIEW RENDER PAGE
    public function imob_admin_page()
    {
        require_once(plugin_dir_path(__FILE__) . '/views/settings-page.php');
    }


    # EXTRACT DATA FOR XML OBJECT
    public function xml_to_arr()
    {
        # URL TO GET XML
        $configs_json       = file_get_contents(plugin_dir_path(__FILE__) . 'configs.json');
        $json_obj           = json_decode($configs_json);
        $load_xml_by_url    = $json_obj->url_xml; 
        
        # READER XML
        $xml_to_obj         = simplexml_load_file($load_xml_by_url);
        $arr_imoveis        = $xml_to_obj->Imoveis->Imovel;

        # RETURN ARRAY OF PROPERTIES
        return $arr_imoveis;
    }

}

ImportImob::getInstance();
