<?php

/**
 * process_schedule_single_email.php - Processes Email to send single Email (immediate and scheduled)
 *
 *
 * @client Squirrel Market Place Extension
 * @author ali
 * @since Mon 11 Dec 2023 20:00:00
 **/

use Carbon\Carbon;
use ZeptoMailExtension\Model\ZeptoEmailRecords;
use ZeptoMailExtension\Model\Connections;
use ZeptoMailExtension\Model\Options;

use Squirrel\Marketplace\ZeptoMailHelper;
use Squirrel\Marketplace\AuthHelper;

const CONTEXT = "Process Scheduled Single Email";

require getenv('SQUIRREL_CLIENT_LIB_V2');
$squirrelSc = new SquirrelClient("sqible_demo_crm", false, CONTEXT);

$authHelper = new AuthHelper($squirrelSc, CONTEXT);

$currentMinuteStart = Carbon::now('UTC')->startOfMinute();
$currentMinuteEnd = Carbon::now('UTC')->endOfMinute();

$scheduledRecords = ZeptoEmailRecords::where(function ($query) {
    $query->where('status', BATCH_PROCESSING_STATUSES['scheduled'])
        ->orWhere('status', BATCH_PROCESSING_STATUSES['retry']);
})
    ->whereBetween('schedule_at', [$currentMinuteStart, $currentMinuteEnd])
    ->get();

/*$scheduledRecords = ZeptoEmailRecords::where(function ($query) {
    $query->where('status', BATCH_PROCESSING_STATUSES['scheduled'])
        ->orWhere('status', BATCH_PROCESSING_STATUSES['retry']);
})
    ->whereBetween('schedule_at', [$currentMinuteStart, $currentMinuteEnd])
    ->get();*/
echo $currentMinuteStart;
echo '<br>';
echo $currentMinuteEnd;

//echo '<br>';
//echo '<pre>';
//print_r($scheduledRecords);


$squirrelSc->log->info('Scheduled Email Info: ' . json_encode($scheduledRecords));
$emailHistoryRecords = [];
$errors = [];

foreach ($scheduledRecords as $record) {
    //echo '<pre>';
    //print_r($record);
    $retryRecord = false;
    // Verify to see if it's been picked up by another instance of the Job
    $refresheRecord = ZeptoEmailRecords::find($record->id);
    if ($refresheRecord->status != BATCH_PROCESSING_STATUSES['scheduled']) {
        continue;
    }

    // Update the status to Inprogress and increment number of attempts
    $record->update(['status' => BATCH_PROCESSING_STATUSES['inprogress']]);

    // Get DB data
    $emailParameters = json_decode($record->parameters);
    $moduleRecordID = $record->record_id;
    $clientCode = $record->client;
    $moduleName = $record->module;
    $scheduleAt = $record->schedule_at;

    $emailModuleConfiguration = [];

    $client = Connections::where(["client_code" => $clientCode])->first();

    if (empty($client)) {
        $msg = "No Connection database entry for Client {$clientCode}. Should not occur unless the extension is uninstalled";
        $squirrelSc->log->critical($msg);
        $record->update(['status' => BATCH_PROCESSING_STATUSES['failed']]); //Update the status to failed
        continue;
    }

    try {
        $authHelper->checkForExtensionVariables($client, $moduleName);
    } catch (Exception $e) {
        $msg = "Error encountered while doing initial setup for Client {$clientCode}. " . $e->getMessage();
        $squirrelSc->log->error($msg);
        $record->update(['status' => BATCH_PROCESSING_STATUSES['retry']]); //Update the status to retry
        continue;
    }

    $clientSc = $authHelper->getClientSc();
    $clientLocation = $authHelper->getClientLocation($client);

    $emailSenderId = $client->zepto_mail_sender_id ? $client->zepto_mail_sender_id : "";
    $zeptoMailApiKey = $client->zepto_mail_api_key;
    // Send errors to defined email by client - otherwise, send to default address
    $emailNotification = !empty($client->error_email) ? $client->error_email : ERROR_EMAIL_RECIPIENT;

    $zeptoMailHelper = new ZeptoMailHelper($clientSc, $zeptoMailApiKey);

    try {
        $users = $zeptoMailHelper->getAllUsers();
        if (!$users) {
            throw new \Exception('Error retrieving users from CRM');
        }
    } catch (Exception $e) {
        $msg = "Error retrieving users from CRM : Code- " . $e->getCode() . " Message- " . $e->getMessage();
        $clientSc->log->error($msg);
    }

    $emailResult = $zeptoMailHelper->sendEmail((array)$emailParameters);

    $clientSc->log->info("Send Email Result: " . json_encode($emailResult));

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

        continue;
    }

    $recordUpdateStatus = $retryRecord
        ? BATCH_PROCESSING_STATUSES['retry']
        : BATCH_PROCESSING_STATUSES['sent'];
    $record->update(['status' => $recordUpdateStatus]);
}
