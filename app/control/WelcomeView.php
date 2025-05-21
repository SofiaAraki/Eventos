<?php
/**
 * WelcomeView
 *
 * @version    8.1
 * @package    control
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    https://adiantiframework.com.br/license-template
 */
class WelcomeView extends TPage
{
    function __construct()
    {
        parent::__construct();
        
        // create the HTML Renderer
        $this->html = new THtmlRenderer('app/resources/jumbotron.html');
        
        $ini = AdiantiApplicationConfig::get();
        
        $replaces = ['title' => _t('Welcome'),
                     'content' => $ini['general']['welcome_message'] ?? ''];
        
        // replace the main section variables
        $this->html->enableSection('main', $replaces);
        
        parent::add( $this->html );
    }
}


    // public function delete($id = NULL)
    // {
    //     $id = $id ?? $this->id_inscricao;

    //     // Exclui todos os pagamentos relacionados
    //     $pagamentos = Pagamentos::where('id_inscricao', '=', $id)->load();

    //     foreach ($pagamentos as $pagamento) {
    //         $pagamento->delete();
    //     }

    //     // Agora exclui a inscrição normalmente
    //     parent::delete($id);
    // }