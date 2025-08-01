<?php

/**
 * process_email_batches.php - Processes Email batches to send bulk Email (immediate and scheduled)
 *
 *
 * @client Squirrel Market Place Extension
 * @author ali
 * @since Mon 11 Dec 2023 20:00:00
 **/

use Stiphle\Throttle\LeakyBucket;

use ZeptoMailExtension\Model\ZeptoEmailRecords;
use ZeptoMailExtension\Model\ZeptoEmailBatches;
use ZeptoMailExtension\Model\Connections;
use ZeptoMailExtension\Model\Options;

use Squirrel\Marketplace\ZeptoMailHelper;
use Squirrel\Marketplace\AuthHelper;

const CONTEXT = "Process Email Batches (V2)";

require getenv('SQUIRREL_CLIENT_LIB_V2');
$squirrelSc = new SquirrelClient("sqible_demo_crm", false, CONTEXT);

$authHelper = new AuthHelper($squirrelSc, CONTEXT);

/*
if (!$squirrelSc->lockScript()){
    $squirrelSc->log->debug("Process Email Batches still in progress.");
    exit;
}
*/

$batches = ZeptoEmailBatches::where(function ($query) {
    $query->where('status', '=', BATCH_PROCESSING_STATUSES['queued'])
        ->orWhere('status', '=', BATCH_PROCESSING_STATUSES['retry']);
})
    ->where('attempts', '<', 3)
    ->get();

foreach ($batches as $batch) {

    // Verify to see if it's been picked up by another instance of the Job
    $refreshedBatch = ZeptoEmailBatches::find($batch->id);
    if (!($refreshedBatch->status == BATCH_PROCESSING_STATUSES['queued'] || $refreshedBatch->status == BATCH_PROCESSING_STATUSES['retry'])) {
        continue;
    }

    // Update the status to Inprogress and increment number of attempts
    $batch->update(['status' => BATCH_PROCESSING_STATUSES['inprogress'], 'attempts' => ($batch->attempts + 1)]);

    // Get DB data
    $emailModuleConfiguration = (array)json_decode($batch->zepto_mail_configurations);
    $moduleRecords = json_decode($batch->module_records);
    $clientCode = $batch->client;
    $moduleName = $batch->module;
    $scheduleAt = $batch->schedule_at;

    if (empty($scheduleAt)) {
        $client = Connections::where(["client_code" => $clientCode])->first();
        if (empty($client)) {
            $msg = "No Connection database entry for Client {$clientCode}. Should not occur unless the extension is uninstalled";
            $squirrelSc->log->critical($msg);
            $batch->update(['status' => BATCH_PROCESSING_STATUSES['failed']]); //Update the status to failed
            continue;
        }

        try {
            $authHelper->checkForExtensionVariables($client, $moduleName);
        } catch (Exception $e) {
            $msg = "Error encountered while doing initial setup for Client {$clientCode}. " . $e->getMessage();
            $squirrelSc->log->error($msg);
            $batch->update(['status' => BATCH_PROCESSING_STATUSES['retry']]); //Update the status to retry
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

        $throttle = new LeakyBucket();

        $emailHistoryRecords = [];
        $errors = [];

        // Get all keys as an array
        $chunks = array_chunk(array_keys((array)$moduleRecords), 100);
        $retryBatch = true;
        foreach ($chunks as $chunk) {
            try {
                $records = $clientSc->zoho->getRecords($moduleName, null, null, ["ids" => implode(",", $chunk)]);
                $retryBatch = false; //Retry only if all the chunks failed to be retreived
            } catch (Exception $e) {
                $clientSc->log->error("Error while retrieving records from " . $moduleName . ": " . $e->getMessage(), ['emailBatchData' => $batch]);
                continue;
            }

            foreach ($records as $record) {

                // Retreive User (if login user id is set give precedence to it)
                $recordOwnerId = $emailModuleConfiguration['login_userid'] != "" ? $emailModuleConfiguration['login_userid'] : $record->getOwner()->getId();
                $emailModuleConfiguration['login_userid'] = $recordOwnerId;
                $user = $users ? $zeptoMailHelper->getRecordOwner($users, $recordOwnerId) : false;

                // Get message parameters
                $moduleId = $record->getEntityId();
                $emailModuleConfiguration['module_id'] = $moduleId;
                $emailModuleConfiguration['email_address'] = $moduleRecords->{$moduleId};
                $emailModuleConfiguration['reply_to'] = $user->getEmail();
                $emailParameters = $zeptoMailHelper->getEmailParameters($record, $user, $emailModuleConfiguration);

                if (isset($emailParameters['error'])) {
                    $errors[] = $zeptoMailHelper->getSendEmailErrorArray($moduleName, $record, $user, $emailModuleConfiguration, $emailParameters['error']);
                    continue;
                }

                $emailResult = $zeptoMailHelper->sendEmail($emailParameters);

                // Limit sending to 10 Email per second
                $throttle->throttle("send_email", 10, 1000);

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

                // Handle Success
                // Add to Email Records database
                $dbEmailRecord = (new ZeptoEmailRecords)->create([
                    'client' => $clientCode,
                    'module' => $moduleName,
                    'record_id' => $moduleId,
                    'parameters' => json_encode($emailParameters),
                    'response' => json_encode($emailResult),
                    'zepto_mail_id' => (string) $emailResult['request_id'],
                    'batch_id' => $batch->id,
                    'status' => $scheduleAt ? EMAIL_HISTORY_STATUSES['scheduled'] : EMAIL_HISTORY_STATUSES['sent']
                ]);

                $emailHistoryRecords[] = $zeptoMailHelper->getEmailRecordArray($emailModuleConfiguration, $emailParameters, $emailResult, $dbEmailRecord->id, false);
            }
        }

        // Add Email Records to CRM
        if (!empty($emailHistoryRecords)) {
            $zeptoMailHelper->createEmailRecords(EMAIL_HISTORY_FIELD_MAPPINGS['module_api_name'], $emailHistoryRecords);
        } else {
            $retryBatch = true; //Retry if all the records failed
        }

        // Send error report via email if necessary
        if (!empty($errors)) {
            $zeptoMailHelper->sendErrorEmail($emailNotification, $errors, $emailModuleConfiguration['schedule_at']);
        }
    }

    $batchUpdateStatus = $retryBatch
        ? BATCH_PROCESSING_STATUSES['retry']
        : ($scheduleAt ? BATCH_PROCESSING_STATUSES['scheduled'] : BATCH_PROCESSING_STATUSES['sent']);
    $batch->update(['status' => $batchUpdateStatus]);
}
