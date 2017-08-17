<?php
/**
 * Pimu Contact Module
 * @author Pimu <greg@pimu.eu>
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class Pimucontact extends Module implements WidgetInterface 
{

    protected static $googleVerifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
    protected $widgetData = [];

    public function __construct() 
    {
        $this->name = 'pimucontact';
        $this->author = 'Pimu';
        $this->tab = 'front_office_features';
        $this->version = '1.0';
        
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans( 'Pimu Product Contact Form', array(), 'Modules.Pimucontact.Admin' );
        $this->description = $this->trans( 'Adds a contact form to a product page or other place', array(), 'Modules.Pimucontact.Admin' );

        $this->ps_versions_compliancy = array( 'min' => '1.7.2.0', 'max' => _PS_VERSION_ );
        $this->_html = '';
    }
    
    public function install()
    {
        return ( parent::install() && $this->registerHook('header') );
    }

    /**
     * Hooks additional CSS & JS files to the header
    **/
    public function hookHeader() 
    {
        $this->context->controller->registerStylesheet('modules-homeslider', 'modules/'.$this->name.'/views/css/pimucontact.css', ['media' => 'all', 'priority' => 150]);
        $this->context->controller->registerJavascript('remote-recaptcha', 'https://www.google.com/recaptcha/api.js?hl=pl', ['server' => 'remote', 'position' => 'bottom', 'priority' => 20]);
    }    

    public function getWidgetVariables($hookName = null, array $configuration = []) 
    {
        return [];
    }

    /**
     * Renders the widget on the screen
    **/
    public function renderWidget( $hookName = null, array $configuration = [] ) 
    {

        $this->widgetData = $this->getPimuVariables();
        $this->checkIfSubmittedAndSendEmail();
        $this->smarty->assign( $this->widgetData );
        return $this->display( __FILE__, 'views/templates/widget/pimucontact.tpl' );
    }

    protected function checkIfSubmittedAndSendEmail() 
    {                
        
        if (Tools::isSubmit('isPimuSubmitted'))
        {
            
            $errorMessages = [];
            
            $userEmail = trim( Tools::getValue( 'pimu-message-email' ) );
            $userMessage = Tools::getValue( 'pimu-message-content' );
            $formResponse = Tools::getValue( 'g-recaptcha-response' );
            $remoteIP = $_SERVER['REMOTE_ADDR'];
            
            $isUserHuman = $this->askGoogle( $formResponse, $remoteIP );
            if ( !$userEmail || !Validate::isEmail( $userEmail ) ) {
                $errorMessages[] = 'Błędny adres e-mail.';
            }
            if ( !Validate::isCleanHtml( $userMessage )) {
                $errorMessages[] = 'Niedozwolone znaki w wiadomości.';
            }
            if ( !$isUserHuman['success'] ) {
                $errorMessages[] = $isUserHuman['message'];
            }
            
            if (!count($errorMessages)) 
            {
                $this->smarty->assign([
                    'pimuMessageStatus' => 'success',                    
                ]);
                                        
                $var_list = [
                    '{senderemail}' => $userEmail,
                    '{sendermessage}' => $userMessage,
                ];

                $id_product = (int)Tools::getValue('id_product');
                    
                if ( $id_product )
                {
                    $product = new Product((int)$id_product);
                    if ( Validate::isLoadedObject($product) && isset($product->name[Context::getContext()->language->id]) ) 
                    {
                        $var_list['{productname}'] = $product->name[Context::getContext()->language->id];
                        $var_list['{productlink}'] = $product->getLink(Context::getContext());
                    }
                }
                    
                Mail::Send(
                    $this->context->language->id,
                    'pimucontact_mail',
                    'Zapytanie o produkt',
                    $var_list,
                    Configuration::get('PIMUCONTACT_EMAIL_TO'),
                    null, // to name
                    Configuration::get('PIMUCONTACT_EMAIL_FROM'),
                    null, // from name
                    null,
                    null,
                    dirname(__FILE__).'/mails/',
                    false,
                    $this->context->shop->id,
                    null,
                    $userEmail,
                    null
                );                    
            
            } 
            else {
                $this->smarty->assign([
                    'pimuMessageStatus' => 'fail',
                    'pimuMessages' => $errorMessages,
                    'pimuRecoverEmail' => $userEmail,
                ]);
            }
        }          
    }

    /**
     * 
     * Returns Google's response about the captcha      
     * 
     * @param string $formResponse Response returned from our form
     * @param string $remoteIP IP of the user that sends the message
     * @return array Status and message(s)
     */    
    private function askGoogle( $formResponse, $remoteIP)
    {
        $ret = [
            'success' => false,
            'message' => 'Nieznany błąd weryfikacji'
        ];

        $data = [
            'secret' => Configuration::get('PIMUCONTACT_RECAPTCHA_SECRETKEY'),
            'response' => $formResponse,
            'remoteip' => $remoteIP
        ];

        $verify = curl_init();        
        curl_setopt( $verify, CURLOPT_URL, self::$googleVerifyUrl );
        curl_setopt( $verify, CURLOPT_POST, true );
        curl_setopt( $verify, CURLOPT_POSTFIELDS, http_build_query( $data ) );
        curl_setopt( $verify, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $verify, CURLOPT_RETURNTRANSFER, true );
        $response = curl_exec( $verify );

        if ( $response === false ) {
            $ret['message'] = curl_error( $verify );
        } elseif ( !empty($response) ) {
            $response = json_decode($response);
            if ( $response->success === true ) {
                $ret['success'] = true;                
            } else {                
                $ret['message'] = 'Nieudana weryfikacja antyspamowa.';
            }
        }
                
        return $ret;
    }
    
    public function getContent()
    {
        if (Tools::isSubmit('submitUpdate')) {

            Configuration::updateValue( 'PIMUCONTACT_RECAPTCHA_SITEKEY', Tools::getValue('PIMUCONTACT_RECAPTCHA_SITEKEY') );
            Configuration::updateValue( 'PIMUCONTACT_RECAPTCHA_SECRETKEY', Tools::getValue('PIMUCONTACT_RECAPTCHA_SECRETKEY') );
            Configuration::updateValue( 'PIMUCONTACT_EMAIL_TO', Tools::getValue('PIMUCONTACT_EMAIL_TO') );
            Configuration::updateValue( 'PIMUCONTACT_EMAIL_FROM', Tools::getValue('PIMUCONTACT_EMAIL_FROM') );
            
        } 

        $this->_html .= $this->renderForm();        
        return $this->_html;
    }

    public function renderForm()
    {
        
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->trans('Settings', array(), 'Admin.Global'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Recaptcha SITE KEY', array(), 'Modules.Pimucontact.Admin'),
                        'name' => 'PIMUCONTACT_RECAPTCHA_SITEKEY',                        
                        'desc' => $this->trans('Please enter your Recaptcha Site Key', array(), 'Modules.Pimucontact.Admin'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Recaptcha SECRET KEY', array(), 'Modules.Pimucontact.Admin'),
                        'name' => 'PIMUCONTACT_RECAPTCHA_SECRETKEY',                        
                        'desc' => $this->trans('Please enter your Recaptcha Secret Key', array(), 'Modules.Pimucontact.Admin'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Destination e-mail', array(), 'Modules.Pimucontact.Admin'),
                        'name' => 'PIMUCONTACT_EMAIL_TO',                        
                        'desc' => $this->trans('Where do you want the e-mails to be sent to?', array(), 'Modules.Pimucontact.Admin'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->trans('Sender e-mail', array(), 'Modules.Pimucontact.Admin'),
                        'name' => 'PIMUCONTACT_EMAIL_FROM',                        
                        'desc' => $this->trans('Enter the e-mail that will appear as the sender e-mail address.', array(), 'Modules.Pimucontact.Admin'),
                    ),                    
                ),
                'submit' => array(
                    'title' => $this->trans('Save', array(), 'Admin.Actions'),
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitUpdate';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($fields_form));
    }

    /**
     * Gets the variables that must be available for
     * the admin backend configuration form
     * @return array Admin configuration key/value pairs
    **/
    public function getConfigFieldsValues()
    {
        return [
            'PIMUCONTACT_RECAPTCHA_SITEKEY' => Tools::getValue( 'PIMUCONTACT_RECAPTCHA_SITEKEY', Configuration::get('PIMUCONTACT_RECAPTCHA_SITEKEY') ),
            'PIMUCONTACT_RECAPTCHA_SECRETKEY' => Tools::getValue( 'PIMUCONTACT_RECAPTCHA_SECRETKEY', Configuration::get('PIMUCONTACT_RECAPTCHA_SECRETKEY') ),
            'PIMUCONTACT_EMAIL_TO' => Tools::getValue( 'PIMUCONTACT_EMAIL_TO', Configuration::get('PIMUCONTACT_EMAIL_TO') ),
            'PIMUCONTACT_EMAIL_FROM' => Tools::getValue( 'PIMUCONTACT_EMAIL_FROM', Configuration::get('PIMUCONTACT_EMAIL_FROM') ),
        ];
    }

    /**
     * Get the variables that must be available while
     * rendering the widget template
     * @return array Widget configuration key/value pairs
    **/
    private function getPimuVariables() 
    {
        return [
            'PIMUCONTACT_RECAPTCHA_SITEKEY' => Configuration::get('PIMUCONTACT_RECAPTCHA_SITEKEY'),
        ];
    }

}