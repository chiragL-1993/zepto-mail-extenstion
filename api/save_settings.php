<?php

/**
 * save_settings.php - Save the settings from Zepto Mail Setting Webtab/Widget from the Zepto Mail Extension 
 *
 *
 * @client Squirrel Market Place Extension
 * @author ali
 * @since Mon 20 Nov 2023 13:00:00 
 **/

require_once __DIR__ . '/constants.php';
$requestOrigin = $_SERVER['HTTP_ORIGIN'];

if (in_array($requestOrigin, ALLOWED_ORIGINS)) {
    header("Access-Control-Allow-Origin: $requestOrigin");
} else {
    exit;
}

use Squirrel\BurstSMS;

use ZeptoMailExtension\Model\Connections;
use ZeptoMailExtension\Model\Options;

use Squirrel\Marketplace\AuthHelper;

const CONTEXT = "Save Zepto Mail Settings";
// echo '<pre>';
// print_r($_POST);
// die;

require getenv('SQUIRREL_CLIENT_LIB_V2');

if (!isset($_POST['orgid']) || !isset($_POST["zgid"])) {
    // Should never occur as this needs to be set in the widget code
    $msg = "OrganisationID and/or ZGID not set, please try again!";
    $squirrelSc->log->critical($msg);
    $response = array(
        "success" => false,
        "message" => $msg
    );
    echo json_encode($response);
    exit;
}

$orgId = $_POST["orgid"];
$squirrelSc = new SquirrelClient("sqible_demo_crm", false, CONTEXT);
$authHelper = new AuthHelper($squirrelSc, CONTEXT);

$client = Connections::where(["org_id" => $orgId])->first();

if (empty($client)) {
    $msg = "No Connection database entry found for CRM with OrganisationID {$orgId}";
    $squirrelSc->log->critical($msg);
    $response = array(
        "success" => false,
        "message" => $msg
    );
    echo json_encode($response);
    exit;
}

// Parse sms modules array, for each module there should be phone and optout field mappings
// There should also be plural_label and url_name field for the module
$emailModulesArray = explode(',', $_POST["email-modules"]);
$fieldMappingsArray = [];
foreach ($emailModulesArray as $key => $emailModule) {
    if (!(array_key_exists("email-field-mapping-" . $emailModule, $_POST) && array_key_exists("email-optout-field-mapping-" . $emailModule, $_POST))) {
        $msg = "Field Mappings not set for " . $emailModule . ", please set the mappings & try again!";
        $squirrelSc->log->critical($msg);
        $response = array(
            "success" => false,
            "message" => $msg
        );
        echo json_encode($response);
        exit;
    }
    $fieldMappingsArray["email_field_mapping_" . $emailModule] = $_POST["email-field-mapping-" . $emailModule];
    $fieldMappingsArray["email_optout_field_mapping_" . $emailModule] = $_POST["email-optout-field-mapping-" . $emailModule];

    if (array_key_exists("module-plural-label-" . $emailModule, $_POST)) {
        $fieldMappingsArray["module_plural_label_" . $emailModule] = $_POST["module-plural-label-" . $emailModule];
    }
    if (array_key_exists("module-url-name-" . $emailModule, $_POST)) {
        $fieldMappingsArray["module_url_name_" . $emailModule] = $_POST["module-url-name-" . $emailModule];
    }
}


try {
    $client->zgid = $_POST["zgid"];
    $client->client_code = $_POST["client-code"];
    $client->error_email = $_POST["error-email"];
    $client->zepto_mail_api_key = $_POST["api-key"];
    $client->zepto_mail_sender_id = $_POST['sender'];
    $client->custom_module_name = $_POST["email-history-module"];
    $client->modules_for_zepto_mail = json_encode($emailModulesArray);
    $client->module_field_mappings = json_encode($fieldMappingsArray);
    $client->refreshed_data = date("Y-m-d H:i:s");
    $extraData = json_decode($client->extra_data);
    $extraData->timezone = $_POST["timezone"];
    //$extraData->two_way_email = $_POST["two-way-toggle"];
    $extraData->default_sender = $_POST["default_sender"];
    $client->extra_data = json_encode($extraData);
    /*echo '<pre>';
    print_r($client);
    die;*/
    $client->update();
    $response = array(
        "success" => true,
        "message" => "Settings updated succesfully!"
    );
    echo json_encode($response);
} catch (Exception $e) {
    $msg = "Error updating settings in database, please try again!";
    $squirrelSc->log->critical($msg . " - " . $response);
    $response = array(
        "success" => false,
        "message" => $msg
    );
    echo json_encode($response);
}
