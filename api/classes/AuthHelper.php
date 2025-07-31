<?php

namespace Squirrel\Marketplace;

use Exception;
use ZeptoMailExtension\Model\Connections;
use ZeptoMailExtension\Model\Options;

require_once __DIR__ . '/../constants.php';

class AuthHelper
{
    private $squirrelSc;
    private $clientSc;
    private $clientScContext;

    public function __construct($squirrelSc, $context)
    {
        $this->clientScContext = $context;
        $this->squirrelSc = $squirrelSc;
    }

    public function findClientByZGID($zgid)
    {
        $clients = Connections::where(["zgid" => null])->get();

        foreach ($clients as $potentialClient) {
            $clientCode = $this->getClientCode($potentialClient);
            $clientSc = new \SquirrelClient($clientCode, false, $this->clientScContext);
            $clientSc->zoho->init();
            $org = $clientSc->zoho->getOrg();

            if ($org->getZgid() == $zgid) {
                $potentialClient->zgid = $zgid;
                $potentialClient->update();
                $this->clientSc = $clientSc;
                return $potentialClient;
            }
        }

        throw new Exception("Could not find match for ZGID.");
    }

    public function checkForExtensionVariables($client, $module)
    {
        $clientCode = $this->getClientCode($client);
        $this->clientSc = new \SquirrelClient($clientCode, false, $this->clientScContext);
        $this->clientSc->zoho->init();

        if (
            empty($client->client_code)
            || empty($client->zepto_mail_api_key)
            || empty($client->zepto_mail_sender_id)
            || empty($client->custom_module_name)
            || empty($client->modules_for_zepto_mail)
            || !in_array($module, json_decode($client->modules_for_zepto_mail))
            || empty($client->module_field_mappings)
            || !isset(json_decode($client->module_field_mappings)->{EMAIL_FIELD_MAPPING_DB_KEY . $module})
            || !isset(json_decode($client->module_field_mappings)->{OPTOUT_FIELD_MAPPING_DB_KEY . $module})
        ) {
            $msg = "Zepto Mail integration for Organisation {$client->org_id} cannot be initialised as necessary settings are not present in CRM.";
            throw new \Exception($msg);
        }
    }

    public function getClientCode($client)
    {
        if (
            empty($client->client_code)
            || empty($client->zepto_mail_api_key)
            || empty($client->zepto_mail_sender_id)
            || empty($client->custom_module_name)
        ) {
            $sbhconnectRecords = Options::where('value', 'like', $client->email)->get();
            foreach ($sbhconnectRecords as $opt) {
                if ($this->endsWith($opt->name, "_sbhconnect_email") && strpos($opt->name, "sandbox") === false) {
                    $clientCode = str_replace("_sbhconnect_email", "", $opt->name);
                }
            }

            if (empty($clientCode)) {
                $msg = "Account extension installed with email ({$client->email}) is not correctly linked to a client code. Please contact a Squirrel developer.";
                throw new \Exception($msg);
            }
        } else {
            $clientCode = $client->client_code;
        }

        return $clientCode;
    }

    public function getClientLocation($client)
    {
        $location = 'au';
        if (!empty($client) && !empty($client->client_code)) {
            $sbhconnectRecords = Options::where('name', '=', $client->client_code . '_sbhconnect_location')->get();
            foreach ($sbhconnectRecords as $opt) {
                $location = $opt->value;
            }
        }
        return $location;
    }

    private function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return !$length || (substr($haystack, -$length) === $needle);
    }

    public function getClientSc()
    {
        return $this->clientSc;
    }

    public function setClientSc($clientSc)
    {
        $this->clientSc = $clientSc;
    }
}
