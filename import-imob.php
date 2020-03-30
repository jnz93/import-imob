<?php 
/**
 * Plugin name: Import Imob
 * Plugin URI: 
 * Description: Importe imóveis da plataforma Ingaia Imob via XML e transforme em publicações no seu portal.
 * Version: 1.5.1
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
        if ( ! wp_next_scheduled( 'cron_publish_properties' ) ) :
            wp_schedule_event( time(), 'custom_interval', 'cron_publish_properties' );
        endif;
        add_action('cron_publish_properties', array($this, 'publish_properties'));

        if (!wp_next_scheduled('cron_update_properties')) :
            wp_schedule_event(time(), 'update_gallery_interval', 'cron_update_properties');            
        endif;
        add_action('cron_update_properties', array($this, 'update_properties'));

        # ENQUEUE AWESOME FONTS
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts_admin'));

        # CREATE TAXONOMIES
        add_action('init', array($this, 'create_custom_taxonomies'));
    }

    # MENU WORDPRESS
    public function create_menu()
    {
        add_menu_page('Imob Import', 'ImobImport', 'administrator', 'imob-import', 'ImportImob::imob_admin_page', 'dashicons-admin-multisite', 65);
        add_submenu_page('imob-import', 'Configurações do plugin', 'Configurações', 10, 'setup-plugin', 'ImportImob::imob_settings');
    }

    # ENQUEUE SCRIPT IN ADMIN PANEL
    public function enqueue_scripts_admin()
    {
        wp_register_script('awesome-icons', 'https://kit.fontawesome.com/f18f521cf8.js', array());
        wp_enqueue_script('awesome-icons');
    }

    # REGISTER SETTINGS OPTIONS
    public function register_settings_options()
    {
        $option_group   = 'imob-import-settings';

        register_setting($option_group, PREFIX . '_timestamp');
        register_setting($option_group, PREFIX . '_url_load');
        register_setting($option_group, PREFIX . '_imgs_total');
        register_setting($option_group, PREFIX . 'ids_to_exlude_up_to_date');
        register_setting($option_group, PREFIX . '_last_time');
    }

    # FORM FOR SETTINGS INPUTS
    public function plugin_settings_options()
    {
        if (!current_user_can('administrator')) :
            wp_die(__('Você não tem permissão para acessar essa página.', 'TEXT_DOMAIN'));
        endif;

        $curr_timestamp     = get_option(PREFIX . '_timestamp');
        $curr_url_load      = get_option(PREFIX . '_url_load');
        $curr_imgs_total    = get_option(PREFIX . '_imgs_total');

        ?>
        <!-- JS -->
        <script type="text/javascript">
            jQuery('document').ready(function(){
                // Setar o selecionado
                var input = jQuery('#import_imob_timestamp');
                var spans = jQuery('.span-select');

                spans.each(function(index){
                    let currEl = jQuery(this);
                    let spanVal = currEl.attr('value');
                    // console.log(index + " : " + jQuery(this).attr('value') + ' : ' + input.val());
                    
                    if (spanVal == input.val())
                    {
                        currEl.css({
                            'background' : '#FBC02D'
                        });
                    }
                });
            });

            //  SETAR TEMPO PRÉ DEFINIDO
            function insertTime(el){
                var input = jQuery('#import_imob_timestamp');
                var value = el.attr('value');
                input.val(value);
                el.css({
                    // 'border' : '2px solid red'
                    'background' : '#FBC02D'
                });

                el.siblings().css({
                    // 'border' : 'none'
                    'background' : '#607D8B'
                });
            }

            // ABRIR TEXTO DE AJUDA
            function helpText(el)
            {
                el.siblings('.help-text').toggleClass('help-text-active');
            }
        </script>

        <!-- CSS -->
        <style>
            .regular-description{
                width: 100%; display: block; margin: 4px 0;
            }
            
            .span-select{
                background: #607D8B; color: #ffffff; margin: 0 4px; padding: 8px; border-radius: 4px; cursor: pointer;
            }

            .btn-help{
                font-size: 16px; color: #212121; margin: 0 4px; cursor: pointer;
            }

            .help-text{
                display: none; position: absolute; background: #FBC02D; font-size: 13px; color: #212121; padding: 8px; margin-top: 8px; border-radius: 4px;
            }
            .help-text-active{
                display: block;
            }
            tr{
                margin: 24px 0; display: block;
            }
        </style>

        <h3>Configurações Gerais</h3>
        <form method="post" action="options.php" class="row col-lg-8 settingsPage__form">
            <?php 
            settings_fields('imob-import-settings'); 
            do_settings_sections('imob-import-settings');
            ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="<?php echo PREFIX . '_timestamp' ?>">Intervalo de execução</label>
                            <i style="display: block">Em segundos</i>
                        </th>
                        <td>
                            <input type="text" id="<?php echo PREFIX . '_timestamp' ?>" name="<?php echo PREFIX . '_timestamp' ?>" class="regular-text" placeholder="" value="<?php echo ( empty($curr_timestamp) ? '' : $curr_timestamp ) ?>">
                            
                            <p class="regular-description">Selecione o tempo de execução pré-definido</p>
                            <div class="" style="display: flex; flex-wrap: wrap;">
                                <span class="span-select" onClick="insertTime(jQuery(this))" value="14400">14.400s (4 Horas)</span>
                                <span class="span-select" onClick="insertTime(jQuery(this))" value="28800">28.800s (8 Horas)</span>
                                <span class="span-select" onClick="insertTime(jQuery(this))" value="43200">43.200s (12 Horas)</span>
                                <span class="span-select" onClick="insertTime(jQuery(this))" value="57600">57.600s (16 Horas)</span>
                                <span class="span-select" onClick="insertTime(jQuery(this))" value="72000">72.000s (20 Horas)</span>
                                <span class="span-select" onClick="insertTime(jQuery(this))" value="86400">86.400s (24 Horas)</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="<?php echo PREFIX . '_url_load' ?>">URL de integração Imob</label>
                        </th>
                        <td>
                            <input type="url" id="<?php echo PREFIX . '_url_load' ?>" name="<?php echo PREFIX . '_url_load' ?>" class="regular-text" placeholder="" value="<?php echo ( empty($curr_url_load) ? '' : $curr_url_load ) ?>">
                            <i onClick="helpText(jQuery(this))" class="btn-help fas fa-question-circle" title="Clique para obter ajuda"></i>
                            <span class="help-text">Está com dúvidas? <a href="https://www.loom.com/share/bb42f79e918244609068ffb21b5e97e1" target="_blank">assista esse vídeo</a> e veja como configurar a exportação para o site e pegar a URL de integração.</span>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="<?php echo PREFIX . '_imgs_total' ?>">Fotos por Imóvel</label>
                        </th>
                        <td>
                            <input type="number" id="<?php echo PREFIX . '_imgs_total' ?>" name="<?php echo PREFIX . '_imgs_total' ?>" class="regular-text" placeholder="" value="<?php echo ( empty($curr_imgs_total) ? '' : $curr_imgs_total ) ?>">
                            <i onClick="helpText(jQuery(this))" class="btn-help fas fa-question-circle" title="Clique para obter ajuda"></i>
                            <span class="help-text">Quanto maior o número de imagens processadas maior o uso e gasto de recursos da hospedagem podendo chegar a exaustão e queda.</span>
                        </td>
                    </tr>
                </tbody>
            </table>
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

            $properties_to_publish[$index]['corretor_nome']     = utf8_decode((string) $property->corretor->nome);
            $properties_to_publish[$index]['corretor_email']    = utf8_decode((string) $property->corretor->email);
            $properties_to_publish[$index]['corretor_celular']  = utf8_decode((string) $property->corretor->celular);

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
        $properties         = ImportImob::xml_data_to_collection_of_properties();
        $properties_code    = ImportImob::check_properties_duplicity();

        # MAX 16 PROPERTIES POR LOOP
        $total_prop     = count($properties);
        if ($total_prop > 16) :
            $properties = array_chunk($properties, 16);
        endif;

        for ($i = 0; $i < count($properties); $i++)
        {
            foreach ($properties[$i] as $property) :
                $arr_of_realtors        = ImportImob::get_realtors();
                $property_code_id       = $property['codigo'];
                if (!in_array($property_code_id, $properties_code)) :
    
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
                    $property_full_address  = $property_street . ', '. $property_number . ', ' . $property_district . ', ' . $property_city . ' - ' . $property_uf;
    
                    $property_condon        = $property['nome_condominio'];
                    $property_building      = $property['nome_edificio'];
                    $property_construction  = $property['ano_construcao'];
                    $property_features      = $property['features'];
    
                    $property_video_url     = $property['video_url'];
                    $property_gallery       = $property['fotos'];
                    $property_code_id       = $property['codigo'];
                    
                    $property_realtor_name  = $property['corretor_nome'];
                    $property_realtor_email = $property['corretor_email'];
                    $property_realtor_phone = $property['corretor_celular'];
    
                    # TRATAMENTO FEATURES
                    $property_features_arr  = explode(',', $property_features);
    
                    # SETUP AND INSERT PROPERTY
                    $post_arr = array(
                        'post_type'     => 'property',
                        'post_title'    => $property_title,
                        'post_content'  => $property_description,
                        'post_author'   => get_current_user_id(),
                        'post_status'   => 'publish',
                    );
                    $post_id            = wp_insert_post($post_arr, $wp_error);

                    if (!is_wp_error($post_id)) :
    
                        # SAVE THE TAXONOMIES
                        wp_set_object_terms($post_id, array($property_type), 'property-type');
                        wp_set_object_terms($post_id, $property_features_arr, 'property-feature');
                        wp_set_object_terms($post_id, array($property_city), 'property-city');
                        wp_set_object_terms($post_id, array($property_district), 'property_neighborhood');

                        # CHECK SALE or LOCATION and set Term
                        if (empty($property_price_sec) && !empty($property_price)) :

                            wp_set_object_terms($post_id, array('Compra'), 'property-status');

                        else :

                            wp_set_object_terms($post_id, array('Aluguel'), 'property-status');

                        endif;
    
                        # SAVE METABOXES
                        update_post_meta($post_id, 'REAL_HOMES_property_price', $property_price);
                        update_post_meta($post_id, 'REAL_HOMES_property_size', $property_size);
                        update_post_meta($post_id, 'REAL_HOMES_property_size_postfix', $property_size_postfix);
                        update_post_meta($post_id, 'REAL_HOMES_property_bedrooms', $property_bedrooms);
                        update_post_meta($post_id, 'REAL_HOMES_property_bathrooms', $property_bathrooms);
                        update_post_meta($post_id, 'REAL_HOMES_property_garage', $property_garage);
                        update_post_meta($post_id, 'REAL_HOMES_property_year_built', $property_construction);
                        update_post_meta($post_id, 'property_custom_id', $property_code_id); # ID para checar duplicidade
                        update_post_meta($post_id, 'inspiry_ref._imóvel', $property_code_id); # ID para checar duplicidade
                        update_post_meta($post_id, 'REAL_HOMES_property_id', $property_code_id); # ID Oficial
                        update_post_meta($post_id, 'REAL_HOMES_property_address', $property_full_address);
                        update_post_meta($post_id, 'REAL_HOMES_property_images', $property_gallery);
                        update_post_meta($post_id, 'inspiry_bairro', $property_district);
    
                        #ImportImob::set_images_properties($property_gallery, $post_id);
                    else :
    
                        echo $post_id->get_error_message() . '<br>';
    
                    endif;

                    # Create agent
                    ImportImob::register_realtor($property_realtor_name, $property_realtor_phone, $property_realtor_email, $arr_of_realtors);
                endif;
            endforeach;
        }

        $time_execution = getdate();
        $data   = $time_execution['mday'] . '/' . $time_execution['mon'] . '/' . $time_execution['year'];
        $hora   = $time_execution['hours'] . ':' . $time_execution['minutes'] . ':' . $time_execution['seconds'];
        $time_execution = $data . ' - ' . $hora;
        update_option(PREFIX . '_last_time', $time_execution);
        
    }


    # UPDATE GALLERY OF PHOTOS
    public function update_properties()
    {
        $curr_ids_up_to_date    = get_option(PREFIX . 'ids_to_exlude_up_to_date');
        $arr_up_to_date         = explode(',', $curr_ids_up_to_date);
        $ids_to_exclude         = '';

        $args = array(
            'post_type'     => 'property',
            'numberposts'   => 10,
            'exclude'       => $arr_up_to_date,
        );
        $properties     = get_posts($args);

        if (empty($properties)) :
            exit;
        endif;

        foreach ($properties as $property) :
           setup_postdata($property);

            $prop_id    = $property->ID;
            $url_imgs   = get_post_meta($prop_id, 'REAL_HOMES_property_images', true);

            ImportImob::set_images_properties($url_imgs, $prop_id);           

            $ids_to_exclude .= ',' . $prop_id;
        endforeach;
        wp_reset_postdata();

        $ids_to_exclude .= $curr_ids_up_to_date;
        update_option(PREFIX . 'ids_to_exlude_up_to_date', $ids_to_exclude);
    }

    # SET IMAGES TO PROPERTIES BY ID
    public function set_images_properties($property_gallery, $post_id)
    {
        $max_images             = get_option(PREFIX . '_imgs_total');
        if (empty($max_images)) :
            $max_images = 2;
        endif;

        $arr_url_imgs           = explode(', ', $property_gallery); # STRING > ARRAY
        $arr_url_imgs           = array_slice($arr_url_imgs, 0, $max_images); # LIMITE DE IMAGENS
        $attachs_ids            = '';
        foreach ($arr_url_imgs as $url) :
            $attach_id          = ImportImob::upload_image(trim($url), $post_id);
            if (!is_wp_error($attach_id)) :
                $attachs_ids        .= $attach_id . ', ';
            endif;
        endforeach;
        update_post_meta($post_id, 'REAL_HOMES_property_images', $attachs_ids);

        # SET THUMBNAIL
        $attachs_arr = explode(', ', $attachs_ids);
        $thumbnail_id = $attachs_arr[0];        
        set_post_thumbnail($post_id, $thumbnail_id);

        // echo $post_id . ' Up-to-date <br>';
    }

    
    public function upload_image($url, $post_id) 
    {
        $image = "";
        $attachmentId = "";
        if($url != "") {
         
            $file = array();
            $file['name'] = $url;
            $file['tmp_name'] = download_url($url);
            
            # FIX EXTENSAO > JPG
            $pos = strpos($file['name'], '=w1024-h768');
            if ($pos != false) :
                $file['name'] = substr_replace($file['name'], '.jpg', $pos);
            endif;

            if (is_wp_error($file['tmp_name'])) {
                @unlink($file['tmp_name']);
                var_dump( $file['tmp_name']->get_error_messages( ) );
            } else {
                $attachmentId = media_handle_sideload($file, $post_id);
                 
                if ( is_wp_error($attachmentId) ) {
                    @unlink($file['tmp_name']);
                    var_dump( $attachmentId->get_error_messages( ) );
                }

                $image = wp_get_attachment_url( $attachmentId );
            }
        }
        return $attachmentId;
    }

    # SETUP CRONJOB INTERVAL AND EXECUTION
    public function setup_cronjob_interval($schedules)
    {
        $custom_interval    = get_option(PREFIX . '_timestamp');

        $schedules['custom_interval'] = array(
            'interval'  => $custom_interval,
            'display'   => esc_html__( 'Every ' . $custom_interval . ' seconds.' ),
        );

        $schedules['update_gallery_interval'] = array(
            'interval'  => 1800,
            'display'   => esc_html__('Every 30 Minutes')
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
                $property_id = get_post_meta($post_id, 'property_custom_id', true);

                $published_ids[] = $property_id;

            endwhile;            
        endif;

        # RETORNO IDS PUBLICADOS
        return $published_ids;    
    }

    /**
     * Function get_realtors()
     * @return $arr_of_realtors name's
     */
    public function get_realtors()
    {
        $arr_of_realtors    = array();
        $args = array(
            'post_type'     => 'agent',
            'post_status'   => 'publish',
            'numberposts'   => '-1'
        );
        $posts = get_posts($args);

        if ($posts) :
            foreach($posts as $agent) :
                setup_postdata($agent);
                $name_agent         = get_the_title($agent->ID);
                $arr_of_realtors[]  = $name_agent;
            endforeach;
            wp_reset_postdata();
        endif;

        return $arr_of_realtors;
    }

    /**
     * Function register_realtor()
     * 
     * @param $realtor_name = string name realtor
     * @param $realtor_phone = string phone number
     * @param $realtor_email = string realtor email
     * @param $arr_of_realtors = arr of realtors
     * 
     * @return $realtor_id;
     */
    public function register_realtor($realtor_name, $realtor_phone, $realtor_email, $arr_of_realtors)
    {        
        if (!in_array($realtor_name, $arr_of_realtors)) :
            # SETUP AND INSERT AGENT
            $post_agent = array(
                'post_type'     => 'agent',
                'post_title'    => $realtor_name,
                'post_author'   => get_current_user_id(),
                'post_status'   => 'publish',
            );
            $realtor_id = wp_insert_post($post_agent, $wp_error);
            
            if (!is_wp_error($realtor_id)) :
                // echo $realtor_id . ' - ' . $realtor_name . ': criado com sucesso! <br>';

                update_post_meta($realtor_id, 'REAL_HOMES_mobile_number', $realtor_phone);
                update_post_meta($realtor_id, 'REAL_HOMES_agent_email', $realtor_email);

                return $realtor_id;
            else :
                echo $realtor_id->get_error_message() . '<br>';
            endif;
        endif;
    }

    # LOGS DA CARGA
    public function log_load_properties()
    {
        $last_time              = get_option(PREFIX . '_last_time');
        $properties_arr         = ImportImob::xml_data_to_collection_of_properties();
        $prop_total             = count($properties_arr);

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
        ?>

        <h3>Dados da última importação: </h3>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="">Data: </label>
                    </th>
                    <td>
                        <span><?php echo $last_time ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="">Total de imóveis na carga:</label>
                    </th>
                    <td>
                        <span><?php echo $prop_total ?> imóvel(is)</span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="">Apartamentos:</label>
                    </th>
                    <td>
                        <span><?php echo count($apartments); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="">Casas:</label>
                    </th>
                    <td>
                        <span><?php echo count($houses); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="">Coberturas:</label>
                    </th>
                    <td>
                        <span><?php echo count($roofing); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="">Terrenos:</label>
                    </th>
                    <td>
                        <span><?php echo count($lands); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="">Pontos comerciais:</label>
                    </th>
                    <td>
                        <span><?php echo count($commercial_room); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="">Imóveis à venda:</label>
                    </th>
                    <td>
                        <span><?php echo count($properties_for_sale); ?></span>
                    </td>
                </tr> 
                <tr>
                    <th scope="row">
                        <label for="">Imóveis para locação:</label>
                    </th>
                    <td>
                        <span><?php echo count($properties_for_rent); ?></span>
                    </td>
                </tr> 
            </tbody>
        </table>

        <?php
    }

    # CREATE TAXONOMIES
    public function create_custom_taxonomies()
    {
        $singular_name  = "Bairro";
        $plural_name    = "Bairros";
        $taxonomy_name  = "property_neighborhood";
        $labels = array(
            'name'              => _x( $plural_name, 'taxonomy general name', 'textdomain' ),
            'singular_name'     => _x( $singular_name, 'taxonomy singular name', 'textdomain' ),
            'search_items'      => __( 'Search ' . $plural_name, 'textdomain' ),
            'all_items'         => __( 'All ' . $plural_name, 'textdomain' ),
            'parent_item'       => __( 'Parent ' . $singular_name, 'textdomain' ),
            'parent_item_colon' => __( 'Parent ' . $singular_name . ':', 'textdomain' ),
            'edit_item'         => __( 'Edit ' . $singular_name, 'textdomain' ),
            'update_item'       => __( 'Update ' . $singular_name, 'textdomain' ),
            'add_new_item'      => __( 'Add New ' . $singular_name, 'textdomain' ),
            'new_item_name'     => __( 'New '. $singular_name .' Name', 'textdomain' ),
            'menu_name'         => __( $plural_name, 'textdomain' ),
        );
     
        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'property-neighborhood' ),
        );
     
        register_taxonomy( $taxonomy_name, array( 'property' ), $args );
    }
}

ImportImob::getInstance();
