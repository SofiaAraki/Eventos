<?php

class SSOAcademico extends TPage
{
    public function __construct()
    {
        parent::__construct();
    }

    public static function autenticar($param)
    {
        try
        {
            $token = $param['token'] ?? null;
            // Chave secreta que AMBOS os sistemas devem conhecer
            $secretKey = 'SuaChaveSuperSecretaEUnica123!';

            if (empty($token) || strpos($token, '.') === false) {
                throw new Exception("Token inválido.");
            }

            list($payload, $hash) = explode('.', $token);

            // 1. Valida se a assinatura é autêntica
            $expectedHash = hash_hmac('sha256', $payload, $secretKey);
            if (!hash_equals($expectedHash, $hash)) {
                throw new Exception("Token adulterado ou inválido.");
            }

            // 2. Decodifica os dados
            $dados = json_decode(base64_decode($payload), true);
            
            $cpf     = $dados['cpf'] ?? null;
            $expires = $dados['expires'] ?? 0;

            // 3. Verifica expiração
            if (time() > $expires) {
                throw new Exception("Token expirado.");
            }

            // 4. Busca o usuário e loga
            TTransaction::open('teste');

            $user = SystemUser::where('login', '=', $cpf)->first();

            if (!$user) {
                throw new Exception("CPF: $cpf não encontrado no sistema de Eventos.");
            }

            TSession::regenerate();
            TSession::setValue('logged', TRUE);
            TSession::setValue('login', $user->login);
            TSession::setValue('userid', $user->id);
            TSession::setValue('username', $user->name);
            TSession::setValue('userunitid', 1);

            if (class_exists('SystemPermissionService')) {
                SystemPermissionService::reloadPermissions();
            }

            TTransaction::close();

            session_write_close();
            TScript::create("top.window.location.href = 'index.php';");

        }
        catch (Exception $e)
        {
            if (TTransaction::getDatabase()) {
                TTransaction::rollback();
            }
            new TMessage('error', 'Falha no Login SSO: ' . $e->getMessage());
        }
    }
}