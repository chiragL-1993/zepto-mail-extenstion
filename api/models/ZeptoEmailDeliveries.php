<?php

namespace ZeptoMailExtension\Model;

use Carbon\Carbon;

/**
 * Class SMSDeliveries
 * @package BurstExtension\Model
 *
 *
 * @property int id
 * @property string zepto_email_id
 * @property string recipient_email_id
 * @property Carbon sent_at
 * @property string email_status
 * @property string queue_status
 * @property int attempts
 * @property Carbon created_at
 * @property Carbon updated_at
 *
 */
class ZeptoEmailDeliveries extends \SquirrelModel
{

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = "zepto_email_deliveries";

}
