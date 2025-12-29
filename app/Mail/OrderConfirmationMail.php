<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Helpers\LeyscoHelpers;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Order $order)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Confirmation - ' . $this->order->order_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
       return new Content(
            view: 'emails.order-confirmation',
            with: [
                'orderNumber' => $this->order->order_number,
                'totalAmount' => LeyscoHelpers::formatCurrency($this->order->total_amount),
                'orderDate' => $this->order->created_at->format('F d, Y'),
                'items' => $this->order->items,
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
