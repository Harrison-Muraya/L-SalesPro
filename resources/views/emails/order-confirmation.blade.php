<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px;">
        <h2 style="color: #2c3e50; margin-bottom: 20px;">Order Confirmation</h2>
        
        <p>Hello,</p>
        
        <p>Your order has been confirmed successfully.</p>
        
        <div style="background-color: white; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p><strong>Order Number:</strong> {{ $orderNumber }}</p>
            <p><strong>Order Date:</strong> {{ $orderDate }}</p>
            <p><strong>Total Amount:</strong> {{ $totalAmount }}</p>
        </div>
        
        <p>Thank you for your business!</p>
        
        <p style="margin-top: 30px; font-size: 12px; color: #666;">
            This is an automated email from L-SalesPro. Please do not reply to this email.
        </p>
    </div>
</body>
</html>