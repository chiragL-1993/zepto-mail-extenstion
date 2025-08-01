<?php

/**
 * email_webhook.php - Send a single email when called as an webhook as part of the Zepto Mail Extension
 *
 *
 * @client Squirrel Market Place Extension
 * @author ali
 * @since Fri 19 Jan 2024 15:00:00 
 **/


use ZeptoMailExtension\Model\ZeptoEmailRecords;
use ZeptoMailExtension\Model\Connections;
use ZeptoMailExtension\Model\Options;

use Squirrel\Marketplace\AuthHelper;
use Squirrel\Marketplace\ZeptoMailHelper;


const CONTEXT = "Zepto Mail Webhook";

require_once __DIR__ . '/constants.php';
require getenv('SQUIRREL_CLIENT_LIB_V2');
$squirrelSc = new SquirrelClient("sqible_demo_crm", false, CONTEXT);

$inputJSON = file_get_contents('php://input');
$inputData = json_decode($inputJSON, true);

$squirrelSc->log->info('Processing Email Webhook Request for Zepto Mail Extension', ['postParameters' => $inputData]);

if (
    !isSetAndNotEmpty($inputData['org_id'])
    || !isSetAndNotEmpty($inputData["module_name"])
    || !isSetAndNotEmpty($inputData["module_id"])
    || !isSetAndNotEmpty($inputData["login_userid"])
    || !(isSetAndNotEmpty($inputData["email_template_id"]) || isSetAndNotEmpty($inputData["email_body"]) || isSetAndNotEmpty($inputData["email_subject"]))
) {

    // Should never occur as these needs to be set in the Email widget
    $msg = "Missing one or more of Organisation Id, User Id, Module API Name, Record ID or Email Template ID/Email Body/Email Subject.";
    $squirrelSc->log->critical($msg, ['postParameters' => $inputData]);
    sendResponse(false, $msg);
}

$orgId = $inputData["org_id"];
$moduleName = $inputData['module_name'];
$moduleId = $inputData['module_id'];
$loginUserId = $inputData['login_userid'];
$emailBody = isset($inputData['email_body']) ? $inputData['email_body'] : '';
$emailSubject = isset($inputData['email_subject']) ? $inputData['email_subject'] : '';
$emailTemplateId = isset($inputData['email_template_id']) ? $inputData['email_template_id'] : '';
$scheduleAt = isset($inputData['schedule_at']) ? $inputData['schedule_at'] : '';
$emailAddress = isset($inputData['email_address']) ? $inputData['email_address'] : '';
$campaignName = isset($inputData['campaign_name']) ? $inputData['campaign_name'] : '';
$timezone = isset($inputData['timezone']) ? $inputData['timezone'] : DEFAULT_TIMEZONE;
$countryCode = isset($inputData['country']) ? $inputData['country'] : DEFAULT_COUNTRY_CODE;
$emailSenderId = isset($inputData['sender_id']) ? $inputData['sender_id'] : DEFAULT_SENDER;

$authHelper = new AuthHelper($squirrelSc, CONTEXT);

$client = Connections::where(["zgid" => $orgId])->first();
if (empty($client)) {
    $msg = "No Connection database entry for CRM with organisation Id {$orgId}.";
    $squirrelSc->log->critical($msg, ['postParameters' => $inputData]);
    sendResponse(false, $msg);
}

try {
    $authHelper->checkForExtensionVariables($client, $moduleName);
} catch (Exception $e) {
    $msg = "Error encountered while doing initial setup for Organisation {$orgId}. " . $e->getMessage();
    $squirrelSc->log->error($msg, ['postParameters' => $inputData]);
    sendResponse(false, $msg);
}

$clientSc = $authHelper->getClientSc();
$clientLocation = $authHelper->getClientLocation($client);

$zgid = $client->zgid;
$clientCode = $client->client_code;
$zeptoMailApiKey = $client->zepto_mail_api_key;
$moduleFieldMappings = json_decode($client->module_field_mappings);
$emailMappingKey = EMAIL_FIELD_MAPPING_DB_KEY . '' . $moduleName;
$optoutMappingKey = OPTOUT_FIELD_MAPPING_DB_KEY . '' . $moduleName;
$moduleEmailFieldMapping = $moduleFieldMappings->$emailMappingKey;
$moduleOptoutFieldMapping = $moduleFieldMappings->$optoutMappingKey;
$urlNameMappingKey = MODULE_URL_NAME_DB_KEY . '' . $moduleName;
$moduleURLName = $moduleFieldMappings->$urlNameMappingKey;

$extraData = json_decode($client->extra_data);
$timezone = property_exists($extraData, 'timezone') ? $extraData->timezone : DEFAULT_TIMEZONE;
$countryCode = property_exists($extraData, 'country') ? $extraData->country : DEFAULT_COUNTRY_CODE;
$emailSenderId = $client->zepto_mail_sender_id ? $client->zepto_mail_sender_id : DEFAULT_SENDER;
// Send errors to defined email by client - otherwise, send to default address
$emailNotification = !empty($client->error_email) ? $client->error_email : ERROR_EMAIL_RECIPIENT;

$zeptoMailHelper = new ZeptoMailHelper($clientSc, $zeptoMailApiKey);

// Check if requires scheduling
if (!empty($scheduleAt)) {
    $dateObject = DateTime::createFromFormat('Y-m-d H:i:s', $scheduleAt);
    if (!$dateObject) {
        $msg = "The scheduled at date-time field doesn't contain a valid date time value - " . $scheduleAt;
        $clientSc->log->error($msg, ['postParameters' => $inputData]);
        sendResponse(false, $msg);
    }
    $scheduleAt = $zeptoMailHelper->getTZFormattedDate($scheduleAt, $timezone, 'Y-m-d H:i:s', 'd/m/Y h:i A');
}

try {
    $record = $clientSc->zoho->getRecord($moduleName, $moduleId);
    if (!is_object($record)) {
        throw new \Exception('Not able to retreive the record from CRM');
    }
} catch (Exception $e) {
    $msg = "Error retrieving $moduleName record with ID $moduleId from CRM : Code- " . $e->getCode() . " Message- " . $e->getMessage();
    $clientSc->log->error($msg, ['postParameters' => $inputData]);
    sendResponse(false, $msg);
}

try {
    $user = $zeptoMailHelper->getLoggedInUser($loginUserId);
    $recordOWnerEmail = $user->getEmail();
    if (!$user) {
        throw new \Exception('No user found with given Id');
    }
} catch (Exception $e) {
    $msg = "Error retrieving loggedin user data with ID $loginUserId from CRM : Code- " . $e->getCode() . " Message- " . $e->getMessage();
    $clientSc->log->error($msg, ['postParameters' => $inputData]);
}

if ($emailBody == '') {
    try {
        $template = $clientSc->zoho->getRecord(EMAIL_TEMPLATES_FIELD_MAPPINGS['module_api_name'], $emailTemplateId);
        if (!is_object($template)) {
            throw new \Exception('Not able to retreive the record from CRM');
        }
        $emailBody = $template->getFieldValue(EMAIL_TEMPLATES_FIELD_MAPPINGS['email_body_field']);
    } catch (Exception $e) {
        $msg = "Error retrieving EMAIL Template record with ID $emailTemplateId from CRM : Code- " . $e->getCode() . " Message- " . $e->getMessage();
        $clientSc->log->error($msg, ['postParameters' => $inputData]);
        sendResponse(false, $msg);
    }
}

$emailModuleConfiguration = [
    'zgid' => $zgid,
    'module' => $moduleName,
    'module_url_name' => $moduleURLName,
    'module_id' => $moduleId,
    'email_field' => $moduleEmailFieldMapping,
    'optout_field' => $moduleOptoutFieldMapping,
    'email_subject' => $emailSubject,
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
    'reply_to' => $recordOWnerEmail
];

$emailParameters = $zeptoMailHelper->getEmailParameters($record, $user, $emailModuleConfiguration);
if (isset($emailParameters['error'])) {
    $clientSc->log->error($emailParameters['error'], ['postParameters' => $inputData]);
    sendResponse(false, $emailParameters['error']);
}
$emailResult = [];
if (empty($scheduleAt)) {
    // Send message
    $emailResult = $zeptoMailHelper->sendEmail($emailParameters);

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
}

// Handle Success
// Add to Email Records database
$dbEmailRecord = (new ZeptoEmailRecords)->create([
    'client' => $clientCode,
    'module' => $moduleName,
    'record_id' => $moduleId,
    'parameters' => json_encode($emailParameters),
    'response' => json_encode($emailResult),
    'zepto_mail_id' => (string) $emailResult['request_id'],
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
        $clientSc->log->error($msg, ['postParameters' => $inputData, 'emailHistoryData' => $emailHistoryData]);
        sendResponse(false, $msg);
    }
} catch (Exception $e) {
    $msg = "Exception occurred while creating Email History record in CRM - Code- " . $e->getCode() . " Message- " . $e->getMessage() . " Details- " . print_r($e->getExceptionDetails(), true);
    $clientSc->log->error($msg, ['postParameters' => $inputData, 'emailHistoryData' => $emailHistoryData, 'exception' => (array) $e]);
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
