<?php

/**
 * cancel_email_batches.php - Cancel the scheduled Email batches 
 *
 *
 * @client Squirrel Market Place Extension
 * @author ali
 * @since Mon 13 Dec 2023 16:00:00 
 **/


use Stiphle\Throttle\LeakyBucket;

use ZeptoMailExtension\Model\ZeptoEmailRecords;
use ZeptoMailExtension\Model\ZeptoEmailBatches;
use ZeptoMailExtension\Model\Connections;
use ZeptoMailExtension\Model\Options;

use Squirrel\Marketplace\ZeptoMailHelper;
use Squirrel\Marketplace\AuthHelper;

const CONTEXT = "Cancel Email Batches";

require getenv('SQUIRREL_CLIENT_LIB_V2');
$squirrelSc = new SquirrelClient("sqible_demo_crm", false, CONTEXT);

$authHelper = new AuthHelper($squirrelSc, CONTEXT);

if (!$squirrelSc->lockScript()) {
    $squirrelSc->log->debug("Cancel Email Batches still in progress.");
    exit;
}

$batches = ZeptoEmailBatches::where('status', '=', BATCH_PROCESSING_STATUSES['withdraw'])->get();

foreach ($batches as $batch) {

    // Get DB data
    $emailModuleConfiguration = (array)json_decode($batch->zepto_mail_configurations);
    $clientCode = $batch->client;
    $moduleName = $batch->module;
    $scheduleAt = $batch->schedule_at;

    $client = Connections::where(["client_code" => $clientCode])->first();
    if (empty($client)) {
        $msg = "No Connection database entry for Client {$clientCode}. Should not occur unless the extension is uninstalled";
        $squirrelSc->log->critical($msg);
        continue;
    }

    try {
        $authHelper->checkForExtensionVariables($client, $moduleName);
    } catch (Exception $e) {
        $msg = "Error encountered while doing initial setup for Client {$clientCode}. " . $e->getMessage();
        $squirrelSc->log->error($msg);
        continue;
    }

    $clientSc = $authHelper->getClientSc();
    $clientLocation = $authHelper->getClientLocation($client);

    $emailSenderId = $client->zepto_mail_sender_id ? $client->zepto_mail_sender_id : "";
    $zeptoMailApiKey = $client->zepto_mail_api_key;
    $emailHistoryModuleURLName = $client->custom_module_name;

    // Send errors to defined email by client - otherwise, send to default address
    $emailNotification = !empty($client->error_email) ? $client->error_email : ERROR_EMAIL_RECIPIENT;

    $zeptoMailHelper = new ZeptoMailHelper($clientSc, $zeptoMailApiKey);


    $throttle = new LeakyBucket();

    $updateEmailRecords = [];
    $emailRecords = ZeptoEmailRecords::where('batch_id', '=', $batch->id)->get();
    foreach ($emailRecords as $emailRecord) {
        $updateEmailRecords[] = [
            '__ID__' => $emailRecords->email_history_id,
            EMAIL_HISTORY_FIELD_MAPPINGS['status_field'] => EMAIL_HISTORY_STATUSES['cancelled'],
            "__email_db_record_id" => $emailRecords->id,
        ];
    }

    // Update Email Records in CRM
    if (!empty($updateEmailRecords)) {
        $updateConfiguration = ['status' => EMAIL_HISTORY_STATUSES['cancelled']];
        $zeptoMailHelper->updateEmailRecords(EMAIL_HISTORY_FIELD_MAPPINGS['module_api_name'], $updateEmailRecords, $updateConfiguration);
    }

    // Send error report via email if necessary
    if (!empty($errors)) {
        $zeptoMailHelper->sendErrorEmail($emailNotification, $errors, $emailModuleConfiguration['schedule_at'], true);
    }

    $batch->update(['status' => BATCH_PROCESSING_STATUSES['cancelled']]);
}
