<?php

/**
 * send_bulk_email.php - Create an Email batch in Squirrel DB to be processed later when submitted through Zepto Email Widget from the Squirrel Zepto Email Extension V2
 *
 *
 * @client Squirrel Market Place Extension
 * @author ali
 * @since Mon 11 Dec 2023 17:00:00 
 **/

require_once __DIR__ . '/constants.php';
$requestOrigin = $_SERVER['HTTP_ORIGIN'];
if (in_array($requestOrigin, ALLOWED_ORIGINS)) {
    header("Access-Control-Allow-Origin: $requestOrigin");
} else {
    exit;
}


use ZeptoMailExtension\Model\ZeptoEmailBatches;
use ZeptoMailExtension\Model\Connections;
use ZeptoMailExtension\Model\Options;

use Squirrel\Marketplace\AuthHelper;
use Squirrel\Marketplace\ZeptoMailHelper;

const CONTEXT = "Send Bulk Email"; //Add Module Name from received param

require_once 'constants.php';
require getenv('SQUIRREL_CLIENT_LIB_V2');
$squirrelSc = new SquirrelClient("sqible_demo_crm", false, CONTEXT);

if (
    !isSetAndNotEmpty($_POST['zgid'])
    || !isSetAndNotEmpty($_POST["module_name"])
    || !isSetAndNotEmpty($_POST["module_data"])
    || !isSetAndNotEmpty($_POST["email_body"])
) {
    // Should never occur as these needs to be set in the Email widget
    $msg = "One or more of ZGID, Module Name, Module Data, Email Body or Loggedin User not set, please check the extension and try again.";
    $squirrelSc->log->critical($msg, ['postParameters' => $_POST]);
    sendResponse(false, $msg);
}

$squirrelSc->log->info('Processing Bulk Email Request for Zepto Mail Extension', ['postParameters' => $_POST]);

$zgid = $_POST["zgid"];
$moduleName = $_POST['module_name'];
$moduleURLName = $_POST['module_url_name'] ? $_POST['module_url_name'] : $_POST['module_name'];
$moduleData = $_POST['module_data'];
$emailBody = $_POST['email_body'];
$loginUserId = $_POST['login_userid'];
$scheduleAt = isset($_POST['schedule_at']) ? $_POST['schedule_at'] : '';
$emailTemplateId = isset($_POST['email_template_id']) ? $_POST['email_template_id'] : '';
$campaignName = isset($_POST['campaign_name']) ? $_POST['campaign_name'] : '';
$timezone = isset($_POST['timezone']) ? $_POST['timezone'] : DEFAULT_TIMEZONE;
$countryCode = isset($_POST['country']) ? $_POST['country'] : DEFAULT_COUNTRY_CODE;
$emailSenderId = isset($_POST['sender_id']) ? $_POST['sender_id'] : DEFAULT_SENDER;

$authHelper = new AuthHelper($squirrelSc, CONTEXT);

$client = Connections::where(["zgid" => $zgid])->first();
if (empty($client)) {
    try {
        $client = $authHelper->findClientByZGID($zgid);
    } catch (Exception $e) {
        $msg = "No Connection database entry for CRM with organisation ZGID {$zgid}. Should not occur. " . $e->getMessage();
        $squirrelSc->log->critical($msg, ['postParameters' => $_POST]);
        sendResponse(false, $msg);
    }
}

try {
    $authHelper->checkForExtensionVariables($client, $moduleName);
} catch (Exception $e) {
    $msg = "Error encountered while doing initial setup for Organisation {$zgid}. " . $e->getMessage();
    $squirrelSc->log->error($msg, ['postParameters' => $_POST]);
    sendResponse(false, $msg);
}

$clientSc = $authHelper->getClientSc();
$clientLocation = $authHelper->getClientLocation($client);

$clientCode = $client->client_code;
$clientCode = $client->client_code;
$zeptoMailApiKey = $client->zepto_mail_api_key;
$moduleFieldMappings = json_decode($client->module_field_mappings);
$emailMappingKey = EMAIL_FIELD_MAPPING_DB_KEY . '' . $moduleName;
$optoutMappingKey = OPTOUT_FIELD_MAPPING_DB_KEY . '' . $moduleName;
$moduleEmailFieldMapping = $moduleFieldMappings->$emailMappingKey;
$moduleOptoutFieldMapping = $moduleFieldMappings->$optoutMappingKey;

$zeptoMailHelper = new ZeptoMailHelper($clientSc, $zeptoMailApiKey);

$emailModuleConfiguration = [
    'zgid' => $zgid,
    'module' => $moduleName,
    'module_url_name' => $moduleURLName,
    'module_id' => $moduleId,
    'email_field' => $moduleEmailFieldMapping,
    'optout_field' => $moduleOptoutFieldMapping,
    'email_body' => $emailBody,
    'email_from' => $emailSenderId,
    'email_address' => $emailAddress,
    'login_userid' => $loginUserId,
    'schedule_at' => $scheduleAt,
    'email_template_id' => $emailTemplateId,
    'campaign_name' => $campaignName,
    'client_location' => $clientLocation,
    'timezone' => $timezone,
    'country' => $countryCode,
    'subject' => 'Zepto Mail '
];

// Check if requires scheduling
if (!empty($scheduleAt)) {
    $scheduleAt = $zeptoMailHelper->convertTZDateToUTC($scheduleAt, $timezone, 'd/m/Y h:i A', 'Y-m-d H:i:s');
}

if (isset($_POST['action']) && $_POST['action'] == "get_view_records") {
    //We need to send get the view, get all contact IDs for the view, and then send the Contact IDs via COQL to validate the numbers
    $squirrelSc->log->info('Getting records for Bulk Email Request for Burst Zepto Mail Extension V2', ['postParameters' => $_POST]);
    $zgid = $_POST["zgid"];
    $moduleName = $_POST['module_name'];
    //Get view records
    try {
        $records_array = [];
        $response = $zeptoMailHelper->getRecordsForView($moduleName, $_POST['view_id']);
        if ($response->getInfo()->getMoreRecords()) {
            while ($response->getInfo()->getMoreRecords()) {
                $next_page = $response->getInfo()->getPageNo() + 1;
                $squirrelSc->log->debug("Getting records for page {$next_page} for view {$_POST['view_id']} for Organisation {$zgid} (total records so far: " . count($records_array) . ")");
                foreach ($response->getData() as $record) {
                    $records_array[] = $record->getEntityId();
                }
                $response = $zeptoMailHelper->getRecordsForView($moduleName, $_POST['view_id'], $response->getInfo()->getPageNo() + 1);
                if (!$response->getInfo()->getMoreRecords()) {
                    foreach ($response->getData() as $record) {
                        $records_array[] = $record->getEntityId();
                    }
                }
            }
        } else {
            foreach ($response->getData() as $record) {
                $records_array[] = $record->getEntityId();
            }
        }

        sendResponse(true, [
            "records" => $records_array
        ]);
    } catch (Exception $e) {
        $msg = "Error encountered while fetching records for the view for Organisation {$zgid}. " . $e->getMessage();
        $squirrelSc->log->error($msg, ['postParameters' => $_POST]);
        sendResponse(false, $msg);
    }
}

try {
    // Add to Batch Records database
    $dbBatchRecord = (new ZeptoEmailBatches)->create([
        'client' => $clientCode,
        'module' => $moduleName,
        'module_records' => json_encode(json_decode($moduleData)),
        'zepto_mail_configurations' => json_encode($emailModuleConfiguration),
        'schedule_at' => $scheduleAt ? $scheduleAt : NULL,
        'status' => BATCH_PROCESSING_STATUSES['queued'],
        'attempts' => 0,
    ]);
    $msg = $scheduleAt ? 'Email will be scheduled shortly!' : 'Email will be dispatched shortly!';
    sendResponse(true, $msg);
} catch (Exception $e) {
    $msg = "Error encountered while creating the batch for sending Email " . $e->getMessage();
    $squirrelSc->log->error($msg, ['postParameters' => $_POST]);
    sendResponse(false, $msg);
}

function sendResponse($success, $message)
{
    $response = array(
        "success" => $success,
        "message" => $message
    );
    echo json_encode($response);
    exit;
}

function isSetAndNotEmpty($variable)
{
    return isset($variable) && !empty($variable);
}
