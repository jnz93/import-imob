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


    # READER XML TEXT TO OBJECT
    public function reader_xml()
    {
        # XML TEMPORÁRIO EM PRODUÇÃO
        $temp_xml = require_once(plugin_dir_path(__FILE__) . 'imoveis.xml');

        return $temp_xml;
    }
}

ImportImob::getInstance();
