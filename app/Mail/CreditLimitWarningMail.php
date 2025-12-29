<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Helpers\LeyscoHelpers;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class CreditLimitWarningMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Customer $customer)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Credit Limit Warning - ' . $this->customer->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
       $availableCredit = $this->customer->credit_limit - $this->customer->current_balance;
        $utilizationPercentage = ($this->customer->current_balance / $this->customer->credit_limit) * 100;
        
        return new Content(
            view: 'emails.credit-limit-warning',
            with: [
                'customerName' => $this->customer->name,
                'creditLimit' => LeyscoHelpers::formatCurrency($this->customer->credit_limit),
                'currentBalance' => LeyscoHelpers::formatCurrency($this->customer->current_balance),
                'availableCredit' => LeyscoHelpers::formatCurrency($availableCredit),
                'utilization' => round($utilizationPercentage, 2),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
