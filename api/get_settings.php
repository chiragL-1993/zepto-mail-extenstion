<?php

/**
 * get_settings.php - Get the settings for the SMS Setting Webtab/Widget from the Squirrel Burst SMS Extension V2
 *
 *
 * @client Squirrel Market Place Extension
 * @author ali
 * @since Tue 21 Nov 2023 10:30:00 
 **/

require_once __DIR__ . '/constants.php';
/*$requestOrigin = !empty($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : null;

if (in_array($requestOrigin, ALLOWED_ORIGINS)) {
    header("Access-Control-Allow-Origin: $requestOrigin");
} else {
    echo json_encode(array("success" => false, "message" => "Request Origin not allowed", "origin" => $requestOrigin));
    exit;
}*/

//use Squirrel\BurstSMS;

use ZeptoMailExtension\Model\Connections;
use ZeptoMailExtension\Model\Options;

//use Squirrel\Marketplace\AuthHelper;
use Squirrel\Marketplace\SMSHelper;

const CONTEXT = "Get Burst SMS Settings";

require getenv('SQUIRREL_CLIENT_LIB_V2');

if (!isset($_GET['orgid'])) {
    $msg = "OrganisationID not set, can't proceed further!";
    echo $msg;
    exit;
}

$orgId = $_GET["orgid"];
$squirrelSc = new SquirrelClient("sqible_demo_crm", false, CONTEXT);
//$authHelper = new AuthHelper($squirrelSc, CONTEXT);

$client = Connections::where(["org_id" => $orgId])->first();
if (empty($client)) {
    $msg = "No Connection database entry found for CRM with OrganisationID {$orgId}";
    $response = array(
        "success" => false,
        "message" => $msg
    );
    echo json_encode($response);
    exit;
}

// Return DB row if present
$client->zepto_mail_sender_id = $client->zepto_mail_sender_id;
$client->modules_for_zepto_mail = json_decode($client->modules_for_zepto_mail);
$client->module_field_mappings = json_decode($client->module_field_mappings);
$client->extra_data = json_decode($client->extra_data);
$response = array(
    "success" => true,
    "data" => $client
);
echo json_encode($response);
