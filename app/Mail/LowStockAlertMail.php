<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class LowStockAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Product $product)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Low Stock Alert - ' . $this->product->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
       $totalStock = $this->product->inventory->sum('quantity');
        
        return new Content(
            view: 'emails.low-stock-alert',
            with: [
                'productName' => $this->product->name,
                'sku' => $this->product->sku,
                'currentStock' => $totalStock,
                'reorderLevel' => $this->product->reorder_level,
                'unit' => $this->product->unit,
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
