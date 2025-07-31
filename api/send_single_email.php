<?php

/**
 * send_single_email.php - Send a single email when submitted through  Mail Widget from the Squirrel Zepto Mail Extension
 *
 *
 * @client Squirrel Market Place Extension
 * @author ali
 * @since Fri 01 Dec 2023 17:00:00
 **/

require_once __DIR__ . '/constants.php';
$requestOrigin = $_SERVER['HTTP_ORIGIN'];
if (in_array($requestOrigin, ALLOWED_ORIGINS)) {
    header("Access-Control-Allow-Origin: $requestOrigin");
} else {
    exit;
}


use ZeptoMailExtension\Model\ZeptoEmailRecords;
use ZeptoMailExtension\Model\Connections;
use ZeptoMailExtension\Model\Options;

use Squirrel\Marketplace\AuthHelper;
use Squirrel\Marketplace\ZeptoMailHelper;


const CONTEXT = "Send Single Email"; //Add Module Name from received param

require getenv('SQUIRREL_CLIENT_LIB_V2');
$squirrelSc = new SquirrelClient("sqible_demo_crm", false, CONTEXT);

if (
    !isSetAndNotEmpty($_POST['zgid'])
    || !isSetAndNotEmpty($_POST["module_name"])
    || !isSetAndNotEmpty($_POST["module_id"])
    || !isSetAndNotEmpty($_POST["email_body"])
) {
    // Should never occur as these needs to be set in the Email widget
    $msg = "One or more of ZGID, Module Name, Module ID, Email Body or Loggedin User not set, please check the extension and try again.";
    $squirrelSc->log->critical($msg, ['postParameters' => $_POST]);
    sendResponse(false, $msg);
}

$squirrelSc->log->info('Processing Single Email Request for Zepto Mail Extension ', ['postParameters' => $_POST]);

$zgid = $_POST["zgid"];
$moduleName = $_POST['module_name'];
$moduleURLName = $_POST['module_url_name'] ? $_POST['module_url_name'] : $_POST['module_name'];
$moduleId = $_POST['module_id'];
$emailBody = $_POST['email_body'];
$loginUserId = $_POST['login_userid'];
$emailAddress = isset($_POST['email_address']) ? $_POST['email_address'] : '';
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
$zeptoMailApiKey = $client->zepto_mail_api_key;
$moduleFieldMappings = json_decode($client->module_field_mappings);
$emailMappingKey = EMAIL_FIELD_MAPPING_DB_KEY . '' . $moduleName;
$optoutMappingKey = OPTOUT_FIELD_MAPPING_DB_KEY . '' . $moduleName;
$moduleEmailFieldMapping = $moduleFieldMappings->$emailMappingKey;
$moduleOptoutFieldMapping = $moduleFieldMappings->$optoutMappingKey;

// Send errors to defined email by client - otherwise, send to default address
$emailNotification = !empty($client->error_email) ? $client->error_email : ERROR_EMAIL_RECIPIENT;

$zeptoMailHelper = new ZeptoMailHelper($clientSc, $zeptoMailApiKey);

try {
    $record = $clientSc->zoho->getRecord($moduleName, $moduleId);
    if (!is_object($record)) {
        throw new \Exception('Not able to retreive the record from CRM');
    }
} catch (Exception $e) {
    $msg = "Error retrieving $moduleName record with ID $moduleId from CRM : Code- " . $e->getCode() . " Message- " . $e->getMessage();
    $clientSc->log->error($msg, ['postParameters' => $_POST]);
    sendResponse(false, $msg);
}

try {
    $loginUserId = $loginUserId != "" ? $loginUserId : $record->getOwner()->getId(); // Take record's owner instead of the one who is actioning
    $user = $zeptoMailHelper->getLoggedInUser($loginUserId);
    if (!$user) {
        throw new \Exception('No user found with given Id');
    }
} catch (Exception $e) {
    $msg = "Error retrieving loggedin user data with ID $loginUserId from CRM : Code- " . $e->getCode() . " Message- " . $e->getMessage();
    $clientSc->log->error($msg, ['postParameters' => $_POST]);
}

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

$emailParameters = $zeptoMailHelper->getEmailParameters($record, $user, $emailModuleConfiguration);
if (isset($emailParameters['error'])) {
    $clientSc->log->error($emailParameters['error'], ['postParameters' => $_POST]);
    sendResponse(false, $emailParameters['error']);
}

// Send message
$emailResult = $zeptoMailHelper->sendEmail($emailParameters);

// Handle Errors from Zepto API
if ($emailResult['message'] != "OK") {
    $msg = $emailResult['error']['details'] && $emailResult['error']['details'][0]['message']
        ? "Error from Zepto Mail - " . $emailResult['error']['details'][0]['message']
        : "Unknown failure response from Zepto Mail";
    if (
        !empty($emailResult['error']) &&
        !empty($emailResult['error']['code']) &&
        in_array($emailResult['error']['code'], ZEPTOMAIL_API_ERROR_CODE)
    ) {
        $clientSc->log->critical($msg, [
            'emailResult' => $emailResult,
            'emailParameters' => $emailParameters,
            'emailConfiguration' => $emailModuleConfiguration
        ]);
        $errors[] = $zeptoMailHelper->getSendEmailErrorArray(
            $moduleName,
            $record,
            $user,
            $emailModuleConfiguration,
            $msg
        );
    } else {
        $clientSc->log->critical($msg, ['emailResult' => $emailResult, 'emailParameters' => $emailParameters, 'emailConfiguration' => $emailModuleConfiguration]);
        $errors[] = $zeptoMailHelper->getSendEmailErrorArray($moduleName, $record, $user, $emailModuleConfiguration, $msg);
    }

    sendResponse(false, $msg);
}

// Handle Success
// Add to Email Records database
$dbEmailRecord = (new ZeptoEmailRecords)->create([
    'client' => $clientCode,
    'module' => $moduleName,
    'record_id' => $moduleId,
    'parameters' => json_encode($emailParameters),
    'response' => json_encode($emailResult),
    'zepto_mail_id' => (string) $emailResult->request_id,
    'status' => $scheduleAt ? EMAIL_HISTORY_STATUSES['scheduled'] : EMAIL_HISTORY_STATUSES['sent']
]);

$emailHistoryData = $zeptoMailHelper->getEmailRecordArray($emailModuleConfiguration, $emailParameters, $emailResult, $dbEmailRecord->id);
try {
    $result = $clientSc->zoho->createRecord(EMAIL_HISTORY_FIELD_MAPPINGS['module_api_name'], $emailHistoryData);
    if (isset($result["id"])) {
        // Record response in database
        $dbEmailRecord->setAttribute('email_history_id', $result["id"]);
        $dbEmailRecord->save();
        $msg = $scheduleAt ? 'Email has been scheduled!' : 'Email has been sent!';
        sendResponse(true, $msg);
    } else {
        $msg = "Exception occurred while creating Email History record in CRM";
        $clientSc->log->error($msg, ['postParameters' => $_POST, 'emailHistoryData' => $emailHistoryData]);
        sendResponse(false, $msg);
    }
} catch (Exception $e) {
    $msg = "Exception occurred while creating Email History record in CRM - Code- " . $e->getCode() . " Message- " . $e->getMessage() . " Details- " . print_r($e->getExceptionDetails(), true);
    $clientSc->log->error($msg, ['postParameters' => $_POST, 'emailHistoryData' => $emailHistoryData, 'exception' => (array) $e]);
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
