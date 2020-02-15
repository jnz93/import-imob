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
            
            $properties_to_publish[$index]['titulo']            = (string) $property->TituloImovel;
            $properties_to_publish[$index]['descricao']         = (string) $property->Observacao;
            
            $properties_to_publish[$index]['quartos']           = (string) $property->QtdDormitorios;
            $properties_to_publish[$index]['suites']            = (string) $property->QtdSuites;
            $properties_to_publish[$index]['salas']             = (string) $property->QtdSalas;
            $properties_to_publish[$index]['banheiros']         = (string) $property->QtdBanheiros;
            $properties_to_publish[$index]['vagas']             = (string) $property->QtdVagas;
            
            $properties_to_publish[$index]['area_util']         = (string) $property->AreaUtil;
            $properties_to_publish[$index]['area_total']        = (string) $property->AreaTotal;
            $properties_to_publish[$index]['area_privativa']    = (string) $property->AreaPrivativa;
            $properties_to_publish[$index]['und_metrica']       = (string) $property->UnidadeMetrica;
            
            $properties_to_publish[$index]['tipo_imovel']       = (string) $property->TipoImovel;
            $properties_to_publish[$index]['subtipo_imovel']    = (string) $property->SubTipoImovel;
            $properties_to_publish[$index]['finalidade']        = (string) $property->Finalidade;
            $properties_to_publish[$index]['categoria']         = (string) $property->CategoriaImovel;
            
            $properties_to_publish[$index]['preco_venda']       = (string) $property->PrecoVenda;
            $properties_to_publish[$index]['preco_locacao']     = (string) $property->PrecoLocacao;
            $properties_to_publish[$index]['preco_locacao_temp']= (string) $property->PrecoLocacaoTemporada;
            $properties_to_publish[$index]['preco_iptu']        = (string) $property->PrecoIptu;
            $properties_to_publish[$index]['preco_condominio']  = (string) $property->PrecoCondominio;

            $properties_to_publish[$index]['pais']              = (string) $property->Pais;
            $properties_to_publish[$index]['estado']            = (string) $property->Estado;
            $properties_to_publish[$index]['cidade']            = (string) $property->Cidade;
            $properties_to_publish[$index]['bairro']            = (string) $property->Bairro;
            $properties_to_publish[$index]['regiao']            = (string) $property->Regiao;
            $properties_to_publish[$index]['rua']               = (string) $property->Endereco;
            $properties_to_publish[$index]['numero']            = (string) $property->Numero;
            $properties_to_publish[$index]['cep']               = (string) $property->CEP;
            $properties_to_publish[$index]['complemento']       = (string) $property->ComplementoEndereco;
            $properties_to_publish[$index]['nome_condominio']   = (string) $property->NomeCondominio;
            $properties_to_publish[$index]['nome_edificio']     = (string) $property->NomeEdificio;
            $properties_to_publish[$index]['latitude']          = (string) $property->latitude;
            $properties_to_publish[$index]['longitude']         = (string) $property->longitude;
            
            $properties_to_publish[$index]['ano_construcao']    = (string) $property->AnoConstrucao;
            $properties_to_publish[$index]['video_url']         = (string) $property->LinkVideo;

            # FEATURES
            $properties_to_publish[$index]['features']          = '';
            foreach ($properties_features as $feature) :
                # 0 = false / 1 = true
                if ((string) $property->$feature == '1') :

                    $properties_to_publish[$index]['features'] .= $feature . ', ';

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
}

ImportImob::getInstance();
