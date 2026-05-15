<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderPaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public $orderId;
    public $amount;

    public function __construct($orderId, $amount)
    {
        $this->orderId = $orderId;
        $this->amount = $amount;
    }

    public function build()
    {
        return $this->subject('Order Payment Confirmation')
                    ->view('emails.order_paid')
                    ->with([
                        'orderId' => $this->orderId,
                        'amount' => $this->amount,
                    ]);
    }
}
