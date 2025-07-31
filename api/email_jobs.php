<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
// Make sure request is coming from Zoho Widget
/*$listValidReferrers = [
    "https://one.zoho.com/",
    "https://crm.zoho.com/",
    "https://crm.zoho.com.au/",
    "https://crm.zoho.eu/",
    "https://crm.zoho.in/",
    "https://crm.zoho.jp/",
    "https://crm.zoho.com.cn/",
    "https://plugin-zeptomailwidgetdemo.zohosandbox.com/", // For plugin testing
    "http://localhost/", // For plugin testing
];
print_r($_SERVER['HTTP_REFERER']);

$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (!in_array($referer, $listValidReferrers)) {
    exit('Invalid Referer: ' . $referer);
}
if (!isset($_SERVER['HTTP_REFERER']) || !in_array($_SERVER['HTTP_REFERER'], $listValidReferrers)) {
    exit;
}*/

//require_once __DIR__ . "/config.php";
require_once __DIR__ . "/constants.php";
$widget = new ZCRMWidget();
$cssArray = [
    WIDGET_URI  . "/ui/css/common.css?v='" . time() . "'",
];
$jsArray = [
    WIDGET_URI  . "/ui/js/common.js?v='" . time() . "'",
    WIDGET_URI  . "/ui/js/email_jobs.js?v='" . time() . "'",
];

$widget->render(
    "Scheduled Email Jobs",
    $cssArray,
    $jsArray,
    file_get_contents(__DIR__ . "/../ui/html/email_jobs.php")
);
