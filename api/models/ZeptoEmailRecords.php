<?php

namespace ZeptoMailExtension\Model;

use Carbon\Carbon;

/**
 * Class SMSRecords
 * @package BurstExtension\Model
 *
 *
 * @property int id
 * @property string client
 * @property string module
 * @property string record_id
 * @property string parameters
 * @property string response
 * @property string email_history_id
 * @property string zepto_mail_id
 * @property datetime schedule_at
 * @property string status
 * @property int reply_received
 * @property int parent_email_id
 * @property int batch_id
 * @property Carbon created_at
 * @property Carbon updated_at
 *
 */
class ZeptoEmailRecords extends \SquirrelModel
{

    /**
     * The database table used by the model.
     *
     * @var string
     */
    //protected $table = "sms_records_v2";
    protected $table = "zepto_email_records";
}
