<?php

namespace App\Services\Midtrans;

use App\Models\Payment;
use App\Services\Midtrans\Midtrans;
use Midtrans\Notification;

class CallbackService extends Midtrans
{
    protected $notification;
    protected $payment;
    protected $serverKey;

    public function __construct()
    {
        parent::__construct();

        $this->serverKey = config('midtrans.server_key');
        $this->_handleNotification();
    }

    public function isSignatureKeyVerified()
    {
        return ($this->_createLocalSignatureKey() == $this->notification->signature_key);
    }

    public function isSuccess()
    {
        $statusCode = $this->notification->status_code;
        $transactionStatus = $this->notification->transaction_status;
        $fraudStatus = !empty($this->notification->fraud_status) ? ($this->notification->fraud_status == 'accept') : true;

        return ($statusCode == 200 && $fraudStatus && ($transactionStatus == 'capture' || $transactionStatus == 'settlement'));
    }

    public function isExpire()
    {
        return ($this->notification->transaction_status == 'expire');
    }

    public function isCancelled()
    {
        return ($this->notification->transaction_status == 'cancel');
    }

    public function getNotification()
    {
        return $this->notification;
    }

    public function getOrder()
    {
        return $this->payment;
    }

    protected function _createLocalSignatureKey()
    {
        return hash('sha512',
            $this->notification->order_id . $this->notification->status_code .
            $this->notification->gross_amount . $this->serverKey);
    }

    protected function _handleNotification()
    {
        $notification = new Notification();

        $orderNumber = $notification->order_id;
        $payment = Payment::where('order_id', $orderNumber)->first();

        $this->notification = $notification;
        $this->payment = $payment;
    }
}
