<?php

namespace Squirrel\Marketplace;

use Exception;
use DateTime;
use DateTimeZone;
use SquirrelClient;
use ZeptoMailExtension\Model\ZeptoEmailRecords;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\utility\APIConstants;
use zcrmsdk\crm\setup\users\ZCRMUser;
use zcrmsdk\crm\setup\users\ZCRMRole;
use zcrmsdk\crm\crud\ZCRMTag;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;

class ZeptoMailHelper
{
    private $sc;
    private $zeptoApiKey;

    public function __construct(SquirrelClient $sc, $zeptoApiKey = null)
    {
        $this->sc = $sc;
        $this->zeptoApiKey = $zeptoApiKey;
    }

    /*public function getEmailParameters(ZCRMRecord $record, $user, $configuration)
    {
        $email = $configuration['email'] ?: $record->getFieldValue($configuration['email_field']);
        if (empty($email)) {
            return ['error' => 'Missing recipient email address'];
        }

        $subject = $this->replaceMergeFields($configuration['subject'], $record, $user, $configuration);
        $body = $this->replaceMergeFields($configuration['email_body'], $record, $user, $configuration);

        if (empty($body)) {
            return ['error' => 'Email body is empty'];
        }

        return [
            'to' => $email,
            'subject' => $subject,
            'body' => $body,
            'from' => $configuration['email_from'] ?: DEFAULT_SENDER,
            //'bounce' => $configuration['bounce'] ?: null
        ];
    }*/

    public function getEmailParameters(ZCRMRecord $record, $user, $configuration)
    {
        $email = $configuration['email_address'] ?: $record->getFieldValue($configuration['email_field']);
        if (empty($email)) {
            return ['error' => 'Missing recipient email address'];
        }

        $subject = $this->replaceMergeFields($configuration['email_subject'], $record, $user, $configuration);
        $body = $this->replaceMergeFields($configuration['email_body'], $record, $user, $configuration);

        if (empty($body)) {
            return ['error' => 'Email body is empty'];
        }

        $fromAddress = $configuration['email_from'] ?: DEFAULT_SENDER;
        $replyAddress = $configuration['reply_to'] ?: DEFAULT_SENDER;

        return [
            'from' => [
                'address' => $fromAddress,
                //'name' => 'Zepto Mailer', 
            ],
            'to' => [[
                'email_address' => ['address' => $email]
            ]],
            'reply_to' => [[
                'address' => $replyAddress
            ]],
            'subject' => $subject,
            'htmlbody' => $body,
            "track_clicks" => true,
            "track_opens" => true,
            //'bounce_address' => $configuration['bounce'] ?? 'bounce@yourdomain.com' // Optional
        ];
    }

    /*public function getEmailBatchParameters(ZCRMRecord $record, $user, $configuration)
    {
        $email_address = $configuration['email_address'];
        $toList = [];
        if (!empty($email_address)) {
            foreach ($email_address as $email) {
                $toList[] = [
                    'email_address' => [
                        'address' => $email,
                    ]
                ];
            }
        }
        if (empty($toList)) {
            return ['error' => 'Missing recipient email address'];
        }
        $subject = $this->replaceMergeFields($configuration['email_subject'], $record, $user, $configuration);
        $body = $this->replaceMergeFields($configuration['email_body'], $record, $user, $configuration);

        if (empty($body)) {
            return ['error' => 'Email body is empty'];
        }

        $fromAddress = $configuration['email_from'] ?: DEFAULT_SENDER;
        $replyAddress = $configuration['reply_to'] ?: DEFAULT_SENDER;

        return [
            'from' => [
                'address' => $fromAddress,
                //'name' => 'Zepto Mailer', 
            ],
            'to' => $toList,
            'reply_to' => [[
                'address' => $replyAddress
            ]],
            'subject' => $subject,
            'htmlbody' => $body,
            'textbody' => strip_tags($body),
            "track_clicks" => true,
            "track_opens" => true,
            //'bounce_address' => $configuration['bounce'] ?? 'bounce@yourdomain.com' // Optional
        ];
    }*/
    public function replaceMergeFields($text, ZCRMRecord $record, $user, $configuration)
    {
        $pattern = '/\${(.*?)\.(.*?)}/';
        preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $module = $match[1];
            $field = $match[2];
            $value = '';

            if ($module === $configuration['module']) {
                $value = $this->resolveField($record->getFieldValue($field));
            } elseif ($module === USER_MODULE && $user) {
                $method = 'get' . str_replace('_', '', ucwords($field, '_'));
                $value = method_exists($user, $method) ? $this->resolveField($user->$method()) : '';
            }

            $text = str_replace($match[0], $value, $text);
        }

        return $text;
    }

    private function resolveField($value)
    {
        if ($value instanceof ZCRMRecord) {
            return $value->getLookupLabel();
        }
        if ($value instanceof ZCRMUser || $value instanceof ZCRMRole) {
            return $value->getName();
        }
        if (is_array($value)) {
            return implode(', ', array_map(function ($v) {
                return $v instanceof ZCRMTag ? $v->getName() : $v;
            }, $value));
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        return $this->getFormattedDate($value);
    }


    /*public function sendEmail(array $emailParams)
    {
        try {
            if ($this->zeptoApiKey) {
                return $this->sendViaZeptoApi($emailParams);
            } else {
                return $this->sc->sendTemplateEmail(
                    'general-wide',
                    $emailParams['to'],
                    $emailParams['subject'],
                    ['body' => $emailParams['email_body']]
                );
            }
        } catch (Exception $e) {
            $this->sc->log->critical("Failed to send email", [
                'exception' => $e->getMessage(),
                'params' => $emailParams
            ]);
            return false;
        }
    }*/

    public function sendEmail(array $params)
    {
        /*$payload = [
            //'bounce_address' => $params['bounce'] ?: 'bounce@yourdomain.com',
            'from' => [
                'address' => $params['from'],
                //'name' => 'Zepto Mailer'
            ],
            'to' => [[
                'email_address' => ['address' => $params['to']]
            ]],
            'subject' => $params['subject'],
            'htmlbody' => $params['body']
        ];*/

        $ch = curl_init('https://api.zeptomail.com/v1.1/email');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->zeptoApiKey,
                'Content-Type: application/json',
                "cache-control: no-cache",
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($params),
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        //$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            //throw new Exception("cURL Error: $error");
            return $error;
        }

        $result = json_decode($response, true);
        /*if ($httpCode >= 400) {
            throw new Exception("Zepto API Error ($httpCode): $response");
        }*/

        return $result;
    }

    /*public function sendBatchEmail(array $params)
    {
        $ch = curl_init('https://api.zeptomail.com/v1.1/email/batch');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $this->zeptoApiKey,
                'Content-Type: application/json',
                "cache-control: no-cache",
                'Accept: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($params),
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL Error: $error");
        }

        $result = json_decode($response, true);
       
        return $result;
    }*/


    public function logEmailRecord($recordId, $emailParams, $status = 'sent')
    {
        ZeptoEmailRecords::create([
            'crm_record_id' => $recordId,
            'email_to' => $emailParams['to'],
            'email_subject' => $emailParams['email_subject'],
            'email_body' => $emailParams['email_body'],
            'status' => $status,
            'sent_at' => (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s')
        ]);
    }

    public function sendErrorEmail($recipient, $errors)
    {
        $body = "<strong>Email sending failures:</strong><br><br><ul>";
        foreach ($errors as $error) {
            $body .= "<li><strong>{$error['name']}</strong> ({$error['to']}): {$error['message']}</li>";
        }
        $body .= "</ul>";

        if ($this->zeptoApiKey) {
            $this->sendViaZeptoApi([
                'to' => $recipient,
                'subject' => 'Bulk Email Sending Failures',
                'body' => $body,
                'from' => DEFAULT_SENDER
            ]);
        } else {
            $this->sc->sendTemplateEmail(
                'general-wide',
                $recipient,
                'Bulk Email Sending Failures',
                ['body' => $body]
            );
        }
    }

    /**
     * Get CurrentUsers data from CRM
     *
     * @param $loginUserId
     * @return ZCRMUser|bool $loggedInUser
     */
    public function getLoggedInUser($loginUserId)
    {
        $loggedInUser = false;
        try {
            $userRecords = $this->sc->zoho->getAllUsers();
            foreach ($userRecords as $userInstance) {
                if ($userInstance->getId() == $loginUserId) {
                    $loggedInUser = $userInstance;
                }
            }
            return $loggedInUser;
        } catch (Exception $e) {
            $msg = "Error retrieving user from CRM : Code- " . $e->getCode() . " Message- " . $e->getMessage();
            throw new Exception($msg);
        }
    }

    /**
     * Get All Users data from CRM
     *
     * @return ZCRMUser array
     */
    public function getAllUsers()
    {
        $allUsers = false;
        try {
            $userRecords = $this->sc->zoho->getAllUsers();
            return $userRecords;
        } catch (Exception $e) {
            $msg = "Error retrieving users from CRM : Code- " . $e->getCode() . " Message- " . $e->getMessage();
            throw new Exception($msg);
        }
    }

    /**
     * Search User from given CRM Users
     *
     * @param ZCRMUser array
     * @param $recordOwnerId
     * @return ZCRMUser|bool $recordOwner
     */
    public function getRecordOwner($users, $recordOwnerId)
    {
        $recordOwner = false;
        foreach ($users as $userInstance) {
            if ($userInstance->getId() == $recordOwnerId) {
                $recordOwner = $userInstance;
            }
        }
        return $recordOwner;
    }
    /**
     * Small helper function to generate standard error array while sending/scheduling Email 
     *
     * @param $module
     * @param ZCRMRecord $record
     * @param ZCRMUser|bool $user
     * @param $configuration
     * @param $errorMessage
     * @return array
     */
    public function getSendEmailErrorArray($module, ZCRMRecord $record, $user, $configuration, $errorMessage)
    {
        $url = ZOHO_RECORD_URL[$configuration['client_location']];
        $url = str_replace(['${org_id}', '${module_name}', '${module_id}'], [$configuration['zgid'], $configuration['module_url_name'], $configuration['module_id']], $url);
        return [
            'module' => $module,
            'record_id' => $record->getEntityId(),
            'email' => $configuration['email_address'],
            'email_text' => $this->replaceEmailVariables($record, $user, $configuration),
            'message' => $errorMessage,
            'name' => $this->getRecordName($module, $record),
            'record_url' => $url
        ];
    }

    /**
     * Get the record name as a concatenated list of fields.
     * Mainly used to get Contacts First and Last Name. Other modules will likely just use 1 field.
     *
     * @param ZCRMRecord $record
     * @param $nameFields string[]
     * @return string
     */
    public function getRecordName($module, ZCRMRecord $record)
    {

        $nameValues = [];

        if (array_key_exists($module, RECORD_NAME_FIELDS)) {
            $nameFields = RECORD_NAME_FIELDS[$module];
        } else {
            $nameFields = RECORD_NAME_FIELDS['Custom_Modules'];
        }

        foreach ($nameFields as $nameField) {
            $nameValues[] = trim($record->getFieldValue($nameField));
        }

        $nameValues = array_filter($nameValues);

        return implode(" ", $nameValues);
    }
    /**
     *
     * Replaces any variables present in the email message. Variables are Module API Name & API Field Names separated with dot within curly braces.
     * E.g. Hello ${Leads.First_Name}
     *
     * @param ZCRMRecord $record
     * @param ZCRMUser|bool $user
     * @param $configuration
     * @return string
     */
    public function replaceEmailVariables(ZCRMRecord $record, $user, $configuration)
    {

        $emailText = $configuration['email_body'];
        $pattern = '/\${(.*?)\.(.*?)}/';
        preg_match_all($pattern, $emailText, $matches, PREG_SET_ORDER);

        $moduleMergeFields = [];
        $userMergeFields = [];

        foreach ($matches as $match) {
            $prefix = $match[1]; // e.g., Leads or Users
            $variable = $match[2]; // e.g., First_Name, Last_Name, first_name

            if ($prefix === $configuration['module']) {
                $moduleMergeFields[] = $variable;
            } elseif ($prefix === USER_MODULE) {
                $userMergeFields[] = $variable;
            }
        }

        $emailText = $this->replaceModuleMergeFields($record, $moduleMergeFields, $emailText, $configuration['module']);
        $emailText = $this->replaceUserMergeFields($user, $userMergeFields, $emailText, USER_MODULE);

        return $emailText;
    }
    /**
     *
     * Fetch field values from the given Module
     *
     * @param ZCRMRecord $record
     * @param $mergeFields
     * @param $emailText
     * @param $module
     * @return array
     */
    public function replaceModuleMergeFields(ZCRMRecord $record, $mergeFields, $emailText, $module)
    {

        foreach ($mergeFields as $mergeField) {

            // Dynamically create getFieldAPIName function call with exception for Tags if System Keys
            // Otherwise call the getFieldValue function
            if (in_array($mergeField, SYSTEM_FIELDS)) {
                $functionCall = ($mergeField === SYSTEM_FIELDS[5])
                    ? 'getTags'
                    : 'get' . str_replace('_', '', ucwords($mergeField, '_'));
                $mergeValue = $record->$functionCall();
            } else {
                $mergeValue = $record->getFieldValue($mergeField);
            }

            // If it's a lookup to a module
            if ($mergeValue instanceof ZCRMRecord) {
                $mergeValue = $mergeValue->getLookupLabel();
            }
            // If it's a lookup to a user
            if ($mergeValue instanceof ZCRMUser) {
                $mergeValue = $mergeValue->getName();
            }
            // If it's an array as in the case of Tags
            if (is_array($mergeValue)) {
                $stringValue = '';
                foreach ($mergeValue as $arrValue) {
                    $arrValue = ($arrValue instanceof ZCRMTag) ? $arrValue->getName() : $arrValue;
                    $stringValue .= $stringValue ? ',' . $arrValue : $arrValue;
                }
                $mergeValue = $stringValue;
            }
            // If it's a boolean value
            if (gettype($mergeValue) === 'boolean') {
                $mergeValue = $mergeValue ? 'Yes' : 'No';
            }
            // Format if it's a date or datetime field
            $mergeValue = $this->getFormattedDate($mergeValue);

            $emailText = str_replace('${' . $module . '.' . $mergeField . '}', $mergeValue, $emailText);
        }
        return $emailText;
    }

    /**
     *
     * Fetch field values from the User Module
     *
     * @param ZCRMUser|bool $user
     * @param $mergeFields
     * @param $emailText
     * @param $module
     * @return array
     */
    public function replaceUserMergeFields($user, $mergeFields, $emailText, $module)
    {

        foreach ($mergeFields as $mergeField) {
            if (!$user) {
                $mergeValue = '';
            } else {
                // Dynamically create getFieldAPIName function call
                $functionCall = 'get' . str_replace('_', '', ucwords($mergeField, '_'));
                // Handle the exception for 'Isonline'
                if ($mergeField === 'Isonline') {
                    $functionCall = 'getIsOnline';
                }
                $mergeValue = $user->$functionCall();

                // If it's a User or Role Instance
                if ($mergeValue instanceof ZCRMUser || $mergeValue instanceof ZCRMRole) {
                    $mergeValue = $mergeValue->getName();
                }
                // If it's an array as in the case of Created By
                if (is_array($mergeValue) && array_key_exists('name', $mergeValue)) {
                    $mergeValue = $mergeValue['name'];
                }
                // Format if it's a date or datetime field
                $mergeValue = $this->getFormattedDate($mergeValue);
            }
            $emailText = str_replace('${' . $module . '.' . $mergeField . '}', $mergeValue, $emailText);
        }
        return $emailText;
    }

    /**
     *
     * Format Date or DateTime fields e.g. Thu 25th Feb 2023 12:00pm
     *
     * @param $record
     * @return string
     */
    public function getFormattedDate($value)
    {
        // Convert DateTime field
        $dt = DateTime::createFromFormat(DateTime::ISO8601, $value);
        if ($dt) {
            $value = $dt->format("D jS M Y g:ia");
        }
        // Convert Date field
        $dt = DateTime::createFromFormat("Y-m-d", $value);
        if ($dt) {
            $value = $dt->format("D jS M Y ");
        }
        return $value;
    }
    /**
     *
     * Format Date or DateTime fields in given Timezone
     *
     * @param $value
     * @param $timezone
     * @return string
     */
    public function getTZFormattedDate($value, $timezone, $fromFormat, $toFormat)
    {
        $value = DateTime::createFromFormat($fromFormat, $value, new DateTimeZone($timezone));
        $value = $value->format($toFormat);
        return $value;
    }


    /**
     *
     * Format Date or DateTime fields in UTC time from given Timezone
     *
     * @param $value
     * @return string
     */
    public function convertTZDateToUTC($value, $timezone, $fromFormat, $toFormat)
    {
        $value = DateTime::createFromFormat($fromFormat, $value, new DateTimeZone($timezone));
        $value->setTimezone(new DateTimeZone('UTC'));
        $value = $value->format($toFormat);
        return $value;
    }

    /**
     * Returns array of Email Record to create in CRM
     *
     * @param $configuration
     * @param $emailParameters
     * @param $emailResult
     * @param $singleEmail
     * @return array
     */
    public function getEmailRecordArray($configuration, $emailParameters, $emailResult, $dbEmailRecordId, $singleEmail = true)
    {
        $scheduleDateTime = trim($configuration['schedule_at']);
        if (!empty($scheduleDateTime)) {
            $scheduleDateTime1 = $this->getTZFormattedDate($scheduleDateTime, $configuration['timezone'], 'd/m/Y h:i A', 'Y-m-d h:i A');
            $scheduleDateTime2 = $this->convertTZDateToUTC($scheduleDateTime, $configuration['timezone'], 'd/m/Y h:i A', 'c');
        }
        $emailHistoryName = $configuration['campaign_name'] ? $configuration['campaign_name'] . ' on ' : ($singleEmail ? 'Single Email on ' : 'Bulk Email on ');
        $emailHistoryName .= $scheduleDateTime ? $scheduleDateTime1 . ' ' : (new DateTime('now', new DateTimeZone($configuration['timezone'])))->format('Y-m-d h:i A') . ' ';
        //$emailHistoryName .= implode(' ', array_slice(preg_split('/\s+/', trim($emailParameters['body'])), 0, 5)) . '...';
        if (strlen($emailHistoryName) > 120) {
            $emailHistoryName = substr($emailHistoryName, 0, 117) . '...';
        }
        $cmURL = ZOHO_RECORD_URL[$configuration['client_location']];
        $cmURL = str_replace(['${org_id}', '${module_name}', '${module_id}'], [$configuration['zgid'], $configuration['module_url_name'], $configuration['module_id']], $cmURL);
        $emailRecordData = [
            EMAIL_HISTORY_FIELD_MAPPINGS['name_field'] => $emailHistoryName,
            EMAIL_HISTORY_FIELD_MAPPINGS['owner_field'] => $configuration['login_userid'],
            EMAIL_HISTORY_FIELD_MAPPINGS['cm_name_field'] => $configuration['module'],
            EMAIL_HISTORY_FIELD_MAPPINGS['cm_id_field'] => $configuration['module_id'],
            EMAIL_HISTORY_FIELD_MAPPINGS['cm_url_field'] => $cmURL,
            EMAIL_HISTORY_FIELD_MAPPINGS['email_field'] => $emailParameters['to'][0]['email_address']['address'],
            EMAIL_HISTORY_FIELD_MAPPINGS['email_template_field'] => $configuration['email_template_id'],
            EMAIL_HISTORY_FIELD_MAPPINGS['email_subject_field'] => $emailParameters['subject'],
            EMAIL_HISTORY_FIELD_MAPPINGS['email_content_field'] => $emailParameters['htmlbody'],
            EMAIL_HISTORY_FIELD_MAPPINGS['scheduled_time_field'] => $scheduleDateTime ? $scheduleDateTime2 : '',
            EMAIL_HISTORY_FIELD_MAPPINGS['status_field'] => $scheduleDateTime ? EMAIL_HISTORY_STATUSES['scheduled'] : EMAIL_HISTORY_STATUSES['sent'],
            EMAIL_HISTORY_FIELD_MAPPINGS['type_field'] => 'Outbound',
            EMAIL_HISTORY_FIELD_MAPPINGS['zepto_email_id_field'] => (string) $emailResult['request_id'],
            EMAIL_HISTORY_FIELD_MAPPINGS['campaign_name_field'] => $configuration['campaign_name'] ? $configuration['campaign_name'] : '',
            '__email_db_record_id' => $dbEmailRecordId
        ];
        if ($configuration['module'] == 'Leads') {
            $emailRecordData[EMAIL_HISTORY_FIELD_MAPPINGS['lead_field']] = $configuration['module_id'];
        }
        if ($configuration['module'] == 'Contacts') {
            $emailRecordData[EMAIL_HISTORY_FIELD_MAPPINGS['contact_field']] = $configuration['module_id'];
        }
        if ($configuration['module'] == 'Deals') {
            $emailRecordData[EMAIL_HISTORY_FIELD_MAPPINGS["deal_field"]] = $configuration['module_id'];
        }
        return $emailRecordData;
    }

    public function getRecordsForView($module, $view_id, $page = 1)
    {
        $records = [];
        try {
            $instance = ZCRMRestClient::getInstance()->getModuleInstance($module);
            $view = $instance->getCustomView($view_id);
            if (!empty($view)) {
                //Get the criteria of the view
                $records = $view->getData()->getRecords(['page' => $page]);
                return $records;
            }
        } catch (Exception $e) {
            $msg = "Error retrieving records from CRM : Code is- " . $e->getCode() . " Message- " . $e->getMessage();
            throw new \Exception($msg);
        }
        return $records;
    }

    /**
     *
     * Mass create CRM Email History Records and update the database records with the Email History Record CRM ID
     *
     * @param $module
     * @param $records
     */
    public function createEmailRecords($module, $records)
    {
        if (empty($records)) {
            return;
        }

        $chunks = array_chunk($records, 99);
        foreach ($chunks as $chunk) {
            try {
                $results = $this->sc->zoho->createRecords($module, $chunk);
                foreach ($results as $index => $result) {
                    if ($result->getCode() == APIConstants::CODE_SUCCESS) {
                        $resultDetails = $result->getDetails();
                        (new ZeptoEmailRecords)->where('id', $chunk[$index]['__email_db_record_id'])
                            ->update(['email_history_id' => $resultDetails['id']]);
                    } else {
                        $this->sc->log->critical("Failure occurred while creating Email records in CRM for {$module}", [
                            'chunk' => $chunk,
                            'result_details' => $result->getDetails(),
                            'result' => $result,
                        ]);
                    }
                }
            } catch (Exception $e) {
                $this->sc->log->critical("Exception occurred while creating Email records in CRM for {$module} - {$e->getMessage()}", [
                    'chunk' => $chunk,
                    'exception' => (array) $e,
                ]);
            }
        }
    }

    /**
     *
     * Mass update CRM Email History Records and update the corresponding database records
     *
     * @param $module
     * @param $records
     * @param $updateConfiguration
     */
    public function updateEmailRecords($module, $records, $updateConfiguration)
    {
        if (empty($records)) {
            return;
        }

        $chunks = array_chunk($records, 99);
        foreach ($chunks as $chunk) {
            try {
                $results = $this->sc->zoho->updateRecords($module, $chunk);
                foreach ($results as $index => $result) {
                    if ($result->getCode() == APIConstants::CODE_SUCCESS) {
                        (new ZeptoEmailRecords)->where('id', $chunk[$index]['__email_db_record_id'])
                            ->update($updateConfiguration);
                    } else {
                        $this->sc->log->critical("Failure occurred while updating Email records in CRM for {$module}");
                    }
                }
            } catch (Exception $e) {
                $this->sc->log->critical("Exception occurred while updating Email records in CRM for {$module} - {$e->getMessage()}", [
                    'chunk' => $chunk,
                    'exception' => (array) $e,
                ]);
            }
        }
    }

    /**
     *
     * Format Date or DateTime fields in given TZ from UTC
     *
     * @param $value
     * @return string
     */
    public function convertUTCDateToTZ($value, $timezone, $fromFormat, $toFormat)
    {
        $value = DateTime::createFromFormat($fromFormat, $value, new DateTimeZone('UTC'));
        $value->setTimezone(new DateTimeZone($timezone));
        $value = $value->format($toFormat);
        return $value;
    }
}
