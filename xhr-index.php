<?php
require_once 'init.php';

use Adianti\Core\AdiantiCoreApplication;

new TSession;
ApplicationAuthenticationService::checkMultiSession();
ApplicationTranslator::setLanguage( TSession::getValue('user_language'), true );

// Executa a aplicação no modo AJAX
AdiantiCoreApplication::run();
