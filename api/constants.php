<?php
require_once(__DIR__ . '/../ui/vendor/autoload.php');
require_once(__DIR__ . '/classes/AuthHelper.php');
require_once(__DIR__ . '/classes/ZeptoMailHelper.php');
require_once(__DIR__ . '/classes/Widget.php');

const WIDGET_URI = "https://scripts.squirrelcrmhub.com.au/zoho_scripts/marketplace/zepto-mail-extenstion";

const USER_MODULE = 'Users';
const SYSTEM_FIELDS = ['Created_By', 'Created_Time', 'Modified_By', 'Modified_Time', 'Owner', 'Tag', 'Last_Activity_Time', 'EntityId'];
const EMAIL_FIELD_MAPPING_DB_KEY = "email_field_mapping_";
const OPTOUT_FIELD_MAPPING_DB_KEY = "email_optout_field_mapping_";
const MODULE_PLURAL_LABEL_DB_KEY = "module_plural_label_";
const MODULE_URL_NAME_DB_KEY = "module_url_name_";
const ERROR_EMAIL_RECIPIENT = 'developer+burstextensionv2@squirrel.biz';
const DEFAULT_COUNTRY_CODE = "AU";
const DEFAULT_TIMEZONE = "Australia/Sydney";
const DEFAULT_SENDER = 'noreply@scripts.squirrelcrmhub.com.au';

const EXTENSION_NAMESPACE = 'zeptomailwidgetdemo__';

const EMAIL_HISTORY_FIELD_MAPPINGS = [
    'module_api_name' => EXTENSION_NAMESPACE . 'Email_Historys',
    'name_field' => 'Name',
    'owner_field' => 'Owner',
    'lead_field' => EXTENSION_NAMESPACE . 'Lead',
    'contact_field' => EXTENSION_NAMESPACE . 'Contact',
    'deal_field' => EXTENSION_NAMESPACE . 'Deal',
    'cm_name_field' => EXTENSION_NAMESPACE . 'Custom_Module_Name',
    'cm_id_field' => EXTENSION_NAMESPACE . 'Custom_Module_Record_ID',
    'cm_url_field' => EXTENSION_NAMESPACE . 'Custom_Module_Record_URL',
    'email_field' => EXTENSION_NAMESPACE . 'Recipient_Email',
    'email_template_field' => EXTENSION_NAMESPACE . 'Email_Template',
    'email_content_field' => EXTENSION_NAMESPACE . 'Email_Content',
    'scheduled_time_field' => EXTENSION_NAMESPACE . 'Scheduled_Time',
    'status_field' => EXTENSION_NAMESPACE . 'Status',
    'type_field' => EXTENSION_NAMESPACE . 'Type',
    'parent_email_field' => EXTENSION_NAMESPACE . 'Parent_Email',
    'zepto_email_id_field' => EXTENSION_NAMESPACE . 'Zepto_Email_ID',
    'opt_out_field' => EXTENSION_NAMESPACE . 'Email_Opt_Out1',
    'campaign_name_field' => EXTENSION_NAMESPACE . 'Campaign_Name'
];

const EMAIL_TEMPLATES_FIELD_MAPPINGS = [
    'module_api_name' => EXTENSION_NAMESPACE . 'EmailTemplates',
    'email_body_field' => EXTENSION_NAMESPACE . 'Email_Templates_Body'
];

const ZOHO_RECORD_URL = [
    'us' => 'https://crm.zoho.com/crm/org${org_id}/tab/${module_name}/${module_id}',
    'eu' => 'https://crm.zoho.eu/crm/org${org_id}/tab/${module_name}/${module_id}',
    'in' => 'https://crm.zoho.in/crm/org${org_id}/tab/${module_name}/${module_id}',
    'cn' => 'https://crm.zoho.com.cn/crm/org${org_id}/tab/${module_name}/${module_id}',
    'au' => 'https://crm.zoho.com.au/crm/org${org_id}/tab/${module_name}/${module_id}',
    'jp' => 'https://crm.zoho.jp/crm/org${org_id}/tab/${module_name}/${module_id}'
];

const BATCH_PROCESSING_STATUSES = [
    'queued' => 'Queued',
    'inprogress' => 'Inprogress',
    'sent' => 'Sent',
    'failed' => 'Failed',
    'retry' => 'Retry',
    'scheduled' => 'Scheduled',
    'withdraw' => 'Withdraw',
    'cancelled' => 'Cancelled',
    'completed' => 'Completed'
];

const EMAIL_HISTORY_STATUSES = [
    'sent' => 'Sent',
    'received' => 'Received',
    'scheduled' => 'Scheduled',
    'pending' => 'Pending',
    'bounced' => 'Bounced',
    'cancelled' => 'Cancelled'
];

const RECORD_NAME_FIELDS = [
    'Leads' => ['First_Name', 'Last_Name'],
    'Contacts' => ['First_Name', 'Last_Name'],
    'Deals' => ['Deal_Name'],
    'Custom_Modules' => ['Name']
];

const SQUIRREL_EXTENSION_PATH = 'https://scripts.squirrelcrmhub.com.au/zoho_scripts/marketplace/zepto-mail-extenstion/api/';
const EMAIL_REPLY = 'email_reply.php';
const EMAIL_DELIVERY = 'email_delivery.php';

const ALLOWED_ORIGINS = [
    "https://crm.zoho.com",
    "https://crm.zoho.com.au",
    "https://crm.zoho.eu",
    "https://crm.zoho.in",
    "https://crm.zoho.jp",
    "https://crm.zoho.com.cn",
    "https://scripts.squirrelcrmhub.com.au", // Squirrel
    "https://plugin-zeptomailwidgetdemo.zohosandbox.com",
    "https://127.0.0.1:5000", // For plugin testing
    "http://localhost", // For plugin testing
];

error_reporting(0);
