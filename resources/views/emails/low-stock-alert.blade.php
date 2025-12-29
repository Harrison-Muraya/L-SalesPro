<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Low Stock Alert</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #fff3cd; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107;">
        <h2 style="color: #856404; margin-bottom: 20px;">⚠️ Low Stock Alert</h2>
        
        <p>Hello,</p>
        
        <p>This is an alert that the following product is running low on stock:</p>
        
        <div style="background-color: white; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p><strong>Product:</strong> {{ $productName }}</p>
            <p><strong>SKU:</strong> {{ $sku }}</p>
            <p><strong>Current Stock:</strong> {{ $currentStock }} {{ $unit }}</p>
            <p><strong>Reorder Level:</strong> {{ $reorderLevel }} {{ $unit }}</p>
        </div>
        
        <p style="color: #856404;"><strong>Action Required:</strong> Please reorder this product to avoid stockouts.</p>
        
        <p style="margin-top: 30px; font-size: 12px; color: #666;">
            This is an automated email from L-SalesPro. Please do not reply to this email.
        </p>
    </div>
</body>
</html>