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
        add_action('admin_init', array($this, 'register_settings_options'));

        # CRON JOB
        add_filter('cron_schedules', array($this, 'setup_cronjob_interval'));
        add_action('cron_publish_properties', array($this, 'publish_properties'));
        #add_action('admin_enqueue_scripts', array($this, 'register_and_enqueue_scripts'));
    }

    # MENU WORDPRESS
    public function create_menu()
    {
        add_menu_page('Imob Import', 'ImobImport', 'administrator', 'imob-import', 'ImportImob::imob_admin_page', 'dashicons-admin-multisite', 65);
        add_submenu_page('imob-import', 'Configurações do plugin', 'Configurações', 10, 'setup-plugin', 'ImportImob::imob_settings');
    }

    # REGISTER SETTINGS OPTIONS
    public function register_settings_options()
    {
        $option_group   = 'imob-import-settings';

        register_setting($option_group, PREFIX . '_timestamp');
        register_setting($option_group, PREFIX . '_url_load');
    }

    # FORM FOR SETTINGS INPUTS
    public function plugin_settings_options()
    {
        if (!current_user_can('administrator')) :
            wp_die(__('Você não tem permissão para acessar essa página.', 'TEXT_DOMAIN'));
        endif;

        $curr_timestamp     = get_option(PREFIX . '_timestamp');
        $curr_url_load      = get_option(PREFIX . '_url_load');

        ?>
        <form method="post" action="options.php" class="row col-lg-8 settingsPage__form">
            <?php 
            settings_fields('imob-import-settings'); 
            do_settings_sections('imob-import-settings');
            ?>

            <div class="col-lg-4 settingsPage__wrapInput">
                <label for="<?php echo PREFIX . '_timestamp' ?>" class="">Intervalo de execução</label>
                <input type="text" id="<?php echo PREFIX . '_timestamp' ?>" name="<?php echo PREFIX . '_timestamp' ?>" class="" placeholder="" value="<?php echo ( empty($curr_timestamp) ? '' : $curr_timestamp ) ?>">
            </div>

            <div class="col-lg-4 settingsPage__wrapInput">
                <label for="<?php echo PREFIX . '_url_load' ?>" class="">URL de Carga Imob Ingaia</label>
                <input type="text" id="<?php echo PREFIX . '_url_load' ?>" name="<?php echo PREFIX . '_url_load' ?>" class="" placeholder="" value="<?php echo ( empty($curr_url_load) ? '' : $curr_url_load ) ?>">
            </div>
            <?php submit_button(); ?>
        </form>

        <?php
    }

    # VIEW HOME PAGE
    public function imob_admin_page()
    {
        require_once(plugin_dir_path(__FILE__) . '/views/home-plugin.php');
    }

    # VIEW SETTINGS PAGE
    public function imob_settings()
    {
        require_once(plugin_dir_path(__FILE__) . '/views/setup-plugin.php');
    }

    # EXTRACT DATA FOR XML OBJECT
    public function xml_to_arr()
    {
        # LOAD XML
        $imob_xml_url       = get_option(PREFIX . '_url_load');
        $load_xml_by_url    = $imob_xml_url;
        
        if (!empty($load_xml_by_url)) :
            $xml_to_obj         = simplexml_load_file($load_xml_by_url);
            $arr_imoveis        = $xml_to_obj->Imoveis->Imovel;
        else :
            wp_die('URL de download do XML não configurada.');
        endif;

        # RETURN ARRAY OF PROPERTIES
        return $arr_imoveis;

        // echo '<pre>';
        // print_r($arr_imoveis);
        // echo '<pre>';
        // echo utf8_decode($arr_imoveis->TituloImovel) . '<br>';
        // echo utf8_decode($arr_imoveis->CategoriaImovel) . '<br>';
        // echo utf8_decode($arr_imoveis->Cidade) . '<br>';
    }

    # READ XML DATA AND RETURN COLLECTION OF PROPERTIES
    public function xml_data_to_collection_of_properties()
    {
        # GET XML DATA
        $arr_properties = ImportImob::xml_to_arr();

        $properties_to_publish  = array();
        $properties_features    = array(
            'PortaoEletronico',
            'FrenteMar',
            'BeiraMar',
            'PeDireitoDuplo',
            'Deposito',
            'Mezanino',
            'Terraco',
            'JardimInverno',
            'ServicoCozinha',
            'DormitorioEmpregada',
            'Zelador',
            'Adega',
            'Solarium',
            'Sacada',
            'Lavabo',
            'DormitorioReversivel',
            'ArmarioCorredor',
            'ArmarioCloset',
            'ArmarioDormitorio',
            'ArmarioBanheiro',
            'ArmarioSala',
            'ArmarioEscritorio',
            'ArmarioHomeTheater',
            'ArmarioDormitorioEmpregada',
            'ArmarioAreaServico',
            'PisoAquecido',
            'PisoArdosia',
            'PisoBloquete',
            'Carpete',
            'CarpeteAcrilico',
            'CarpeteMadeira',
            'CarpeteNylon',
            'PisoCeramica',
            'CimentoQueimado',
            'ContraPiso',
            'PisoEmborrachado',
            'PisoGranito',
            'PisoLaminado',
            'PisoMarmore',
            'PisoPorcelanato',
            'PisoTacoMadeira',
            'Agua',
            'ArCondicionado',
            'ArmarioCozinha',
            'Churrasqueira',
            'Copa',
            'EntradaCaminhoes',
            'Escritorio',
            'Esgoto',
            'Piscina',
            'PisoElevado',
            'QuadraPoliEsportiva',
            'Quintal',
            'RuaAsfaltada',
            'Sauna',
            'TVCabo',
            'Varanda',
            'Vestiario',
            'WCEmpregada',
            'Hidromassagem',
            'AreaServico',
            'CampoFutebol',
            'Caseiro',
            'Despensa',
            'EnergiaEletrica',
            'Doca',
            'Mobiliado',
            'Lareira',
            'Interfone',
        );
        $index = 0;
        foreach ($arr_properties as $property) :
            
            $properties_to_publish[$index]['titulo']            = utf8_decode((string) $property->TituloImovel);
            $properties_to_publish[$index]['descricao']         = utf8_decode((string) $property->Observacao);
            
            $properties_to_publish[$index]['quartos']           = (string) $property->QtdDormitorios;
            $properties_to_publish[$index]['suites']            = (string) $property->QtdSuites;
            $properties_to_publish[$index]['salas']             = (string) $property->QtdSalas;
            $properties_to_publish[$index]['banheiros']         = (string) $property->QtdBanheiros;
            $properties_to_publish[$index]['vagas']             = (string) $property->QtdVagas;
            
            $properties_to_publish[$index]['area_util']         = (string) $property->AreaUtil;
            $properties_to_publish[$index]['area_total']        = (string) $property->AreaTotal;
            $properties_to_publish[$index]['area_privativa']    = (string) $property->AreaPrivativa;
            $properties_to_publish[$index]['und_metrica']       = (string) $property->UnidadeMetrica;
            
            $properties_to_publish[$index]['tipo_imovel']       = utf8_decode((string) $property->TipoImovel);
            $properties_to_publish[$index]['subtipo_imovel']    = utf8_decode((string) $property->SubTipoImovel);
            $properties_to_publish[$index]['finalidade']        = utf8_decode((string) $property->Finalidade);
            $properties_to_publish[$index]['categoria']         = utf8_decode((string) $property->CategoriaImovel);
            $properties_to_publish[$index]['acao']              = utf8_decode((string) $property->PublicaValores);
            
            $properties_to_publish[$index]['preco_venda']       = (string) $property->PrecoVenda;
            $properties_to_publish[$index]['preco_locacao']     = (string) $property->PrecoLocacao;
            $properties_to_publish[$index]['preco_locacao_temp']= (string) $property->PrecoLocacaoTemporada;
            $properties_to_publish[$index]['preco_iptu']        = (string) $property->PrecoIptu;
            $properties_to_publish[$index]['preco_condominio']  = (string) $property->PrecoCondominio;

            $properties_to_publish[$index]['pais']              = utf8_decode((string) $property->Pais);
            $properties_to_publish[$index]['estado']            = utf8_decode((string) $property->Estado);
            $properties_to_publish[$index]['cidade']            = utf8_decode((string) $property->Cidade);
            $properties_to_publish[$index]['bairro']            = utf8_decode((string) $property->Bairro);
            $properties_to_publish[$index]['regiao']            = utf8_decode((string) $property->Regiao);
            $properties_to_publish[$index]['rua']               = utf8_decode((string) $property->Endereco);
            $properties_to_publish[$index]['numero']            = (string) $property->Numero;
            $properties_to_publish[$index]['cep']               = (string) $property->CEP;
            $properties_to_publish[$index]['complemento']       = utf8_decode((string) $property->ComplementoEndereco);
            $properties_to_publish[$index]['nome_condominio']   = utf8_decode((string) $property->NomeCondominio);
            $properties_to_publish[$index]['nome_edificio']     = utf8_decode((string) $property->NomeEdificio);
            $properties_to_publish[$index]['latitude']          = (string) $property->latitude;
            $properties_to_publish[$index]['longitude']         = (string) $property->longitude;
            
            $properties_to_publish[$index]['ano_construcao']    = (string) $property->AnoConstrucao;
            $properties_to_publish[$index]['video_url']         = (string) $property->LinkVideo;
            $properties_to_publish[$index]['codigo']            = (string) $property->CodigoImovel;

            # FEATURES
            $properties_to_publish[$index]['features']          = '';
            foreach ($properties_features as $feature) :
                # 0 = false / 1 = true
                if ((string) $property->$feature == '1') :

                    $properties_to_publish[$index]['features'] .= utf8_decode($feature) . ', ';

                endif;

            endforeach;

            # GALLERY
            $properties_to_publish[$index]['fotos']             = '';
            foreach ($property->Fotos as $arquivo) :
                
                $index2 = 0;
                foreach ($arquivo as $foto) :

                    if ($foto->URLArquivo) :
                        $properties_to_publish[$index]['fotos'] .= $foto->URLArquivo . ', ';
                    endif;
                    
                    $index2++;
                endforeach;

            endforeach;

            # INCREMENT CONT
            $index++;
        endforeach;

        # RETURN COLECTION OF PROPERTIES
        return $properties_to_publish;
    }

    # READ PROPERTIES AND CREATE POSTS
    public function publish_properties()
    {
        $properties     = ImportImob::xml_data_to_collection_of_properties();
        $published_ids  = ImportImob::check_properties_duplicity();

        foreach ($properties as $property) :

            # SETUP PROPERTY
            $property_title         = $property['titulo'];
            $property_description   = $property['descricao'];

            $property_bedrooms      = $property['quartos'];
            $property_bathrooms     = $property['banheiros'];
            $property_garage        = $property['vagas'];
            $property_size          = $property['area_util'];
            $property_size_total    = $property['area_total'];
            $property_size_postfix  = $property['und_metrica'];

            $property_type          = $property['tipo_imovel'];
            $property_subtype       = $property['subtipo_imovel'];
            $property_function      = $property['finalidade'];
            $property_category      = $property['categoria'];

            $property_price         = $property['preco_venda'];
            $property_price_sec     = $property['preco_locacao'];
            $property_price_iptu    = $property['preco_iptu'];
            $property_price_condon  = $property['preco_condominio'];
            
            $property_country       = $property['pais'];
            $property_uf            = $property['estado'];
            $property_city          = $property['cidade'];
            $property_district      = $property['bairro'];
            $property_zone          = $property['regiao'];
            $property_street        = $property['rua'];
            $property_number        = $property['numero'];
            $property_cep           = $property['cep'];
            $property_complement    = $property['complemento'];

            $property_condon        = $property['nome_condominio'];
            $property_building      = $property['nome_edificio'];
            $property_construction  = $property['ano_construcao'];
            $property_features      = $property['features'];

            $property_video_url     = $property['video_url'];
            $property_gallery       = $property['fotos'];
            $property_code_id       = $property['codigo'];

            # TRATAMENTO FEATURES
            $property_features_arr  = explode(',', $property_features);

            if (!in_array($property_code_id, $published_ids)) :

                # SETUP POST
                $post_arr = array(
                    'post_type'     => 'property',
                    'post_title'    => $property_title,
                    'post_content'  => $property_description,
                    'post_author'   => get_current_user_id(),
                    'post_status'   => 'publish',
                );

                $post_id = wp_insert_post($post_arr, $wp_error);
                

                // $taxonomies = array(
                //     'property_type', 
                //     'property_status', 
                //     'property_feature', 
                //     'propert_label', 
                //     'property_estate', 
                //     'property_city', 
                //     'property_area'
                // );
                // foreach ($taxonomies as $tax) :

                //     wp_set_post_terms($post_id, $tags, $tax);
                    
                // endforeach;

                # SAVE THE TAXONOMIES
                wp_set_object_terms($post_id, array($property_type), 'property_type');
                wp_set_object_terms($post_id, $property_features_arr, 'property_feature');
                wp_set_object_terms($post_id, array($property_uf), 'property_estate');
                wp_set_object_terms($post_id, array($property_city), 'property_city');
                wp_set_object_terms($post_id, array($property_district), 'property_area');

                # SAVE METABOXES
                update_post_meta($post_id, 'fave_property_price', $property_price);
                update_post_meta($post_id, 'fave_property_sec_price', $property_price_sec);
                update_post_meta($post_id, 'fave_property_size', $property_size);
                update_post_meta($post_id, 'fave_property_size_prefix', $property_size_postfix);
                update_post_meta($post_id, 'fave_property_land', $property_size_total);
                update_post_meta($post_id, 'fave_property_bedrooms', $property_bedrooms);
                update_post_meta($post_id, 'fave_property_bathrooms', $property_bathrooms);
                update_post_meta($post_id, 'fave_property_garage', $property_garage);
                update_post_meta($post_id, 'fave_property_year', $property_construction);
                update_post_meta($post_id, 'fave_property_id', $property_code_id);
                update_post_meta($post_id, 'fave_property_map_address', $property_street);
                update_post_meta($post_id, 'fave_property_street', $property_street);
                update_post_meta($post_id, 'fave_property_zip', $property_cep);
                update_post_meta($post_id, 'fave_property_country', $property_country);
                update_post_meta($post_id, 'fave_property_images', $property_gallery);
                update_post_meta($post_id, 'fave_video_url', $property_video_url);


                if (!is_wp_error($post_id)) :
                    echo 'Imóvel publicado com sucesso! ' . $post_id . '<br>';
                else :
                    echo $post_id->get_error_message() . '<br>';
                endif;

            endif;

        endforeach;
    }

    # SETUP CRONJOB INTERVAL AND EXECUTION
    public function setup_cronjob_interval($schedules)
    {
        $custom_interval    = get_option(PREFIX . '_timestamp');

        $schedules['custom_interval'] = array(
            'interval'  => $custom_interval,
            'display'   => esc_html__( 'Every ' . $custom_interval . ' seconds.' ),
        );
        
        return $schedules;
    }

    # CHECK DUPLICITY
    public function check_properties_duplicity()
    {
        # QUERY DAS PUBLICAÇÕES
        $args = array(
            'post_type'         => 'property',
            'posts_per_page'    => -1,
            'post_status'       => 'publish'
        );
        $properties_published = new WP_Query($args);

        # COLETA DOS IDS PUBLICADOS
        $published_ids = array();
        if ($properties_published->have_posts()) :
            while ($properties_published->have_posts()) :
                $properties_published->the_post();
                $post_id = get_the_ID();
                $property_id = get_post_meta($post_id, 'fave_property_id', true);

                $published_ids[] = $property_id;

            endwhile;            
        endif;

        # RETORNO IDS PUBLICADOS
        return $published_ids;    
    }

    # LOGS DA CARGA
    public function log_load_properties()
    {
        $properties_arr     = ImportImob::xml_data_to_collection_of_properties();
        $prop_total         = count($properties_arr);

        $apartments             = array(); # Apartamentos
        $lands                  = array(); # Terrenos
        $houses                 = array(); # Casas
        $roofing                = array(); # Coberturas
        $commercial_room        = array(); # Salas comerciais
        $properties_for_sale    = array(); # Propriedades a venda
        $properties_for_rent    = array(); # Propriedades para alugar

        if (!empty($properties_arr)) :
            foreach($properties_arr as $property) :
                # VERIFICAÇÃO DE TIPOS
                if ($property['tipo_imovel'] == "Apartamento") :
                    # APARTAMENTOS
                    $apartments[] = $property['codigo'];
                
                elseif ($property['tipo_imovel'] == 'Casa') :
                    # CASAS
                    $houses[] = $property['codigo'];

                elseif ($property['tipo_imovel'] == 'Cobertura') :
                    # COBERTURAS
                    $roofing[] = $property['codigo'];

                elseif ($property['tipo_imovel'] == 'Terreno') :
                    # TERRENOS
                    $lands[] = $property['codigo'];

                elseif ($property['tipo_imovel'] == 'Ponto' || $property['tipo_imovel'] == 'Prédio' || $property['tipo_imovel'] == 'Sala' || $property['tipo_imovel'] == 'Salão' || $property['tipo_imovel'] == 'Andar Corporativo' || $property['tipo_imovel'] == 'Área' || $property['tipo_imovel'] == 'Loja') :
                    # COMERCIAIS
                    $commercial_room[] = $property['codigo'];

                endif;

                // 1 - Publicar venda e locação
                // 2 - Publicar somente venda
                // 3 - Publicar somente locação
                // 4 - Não publicar valores

                # VERIFICAÇÃO DE AÇÃO
                if ($property['acao'] == 1) :
                    # VENDA E LOCAOÇÃO
                    $properties_for_sale[] = $property['codigo'];
                    $properties_for_rent[] = $property['codigo'];
                
                elseif ($property['acao'] == 2) :
                    # SOMENTE VENDA
                    $properties_for_sale[] = $property['codigo'];
                
                elseif ($property['acao'] == 3) :
                    # SOMENTE LOCAÇÃO
                    $properties_for_rent[] = $property['codigo'];

                endif;
            endforeach;
        endif;

        $card_total_properties = '';

        // echo $prop_total;
        echo 'DADOS DA ÚLTIMA IMPORTAÇÃO: <br>';
        echo "TOTAL DE APARTAMENTOS: " . count($apartments) . '<br>';
        echo "TOTAL DE CASAS: " . count($houses) . '<br>';
        echo "TOTAL DE COBERTURAS: " . count($roofing) . '<br>';
        echo "TOTAL DE TERRENOS: " . count($lands) . '<br>';
        echo "TOTAL DE PONTOS COMERCIAIS: " . count($commercial_room) . '<br>';
        echo "TOTAL DE IMÓVEIS Á VENDA: " . count($properties_for_sale) . '<br>';
        echo "TOTAL DE IMÓVEIS PARA LOCAÇÃO: " . count($properties_for_rent) . '<br>';
    }
}

ImportImob::getInstance();
