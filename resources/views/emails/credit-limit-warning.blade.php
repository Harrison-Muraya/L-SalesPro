<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Limit Warning</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8d7da; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3545;">
        <h2 style="color: #721c24; margin-bottom: 20px;">⚠️ Credit Limit Warning</h2>
        
        <p>Hello,</p>
        
        <p>This is a warning that customer <strong>{{ $customerName }}</strong> is approaching their credit limit.</p>
        
        <div style="background-color: white; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p><strong>Customer:</strong> {{ $customerName }}</p>
            <p><strong>Credit Limit:</strong> {{ $creditLimit }}</p>
            <p><strong>Current Balance:</strong> {{ $currentBalance }}</p>
            <p><strong>Available Credit:</strong> {{ $availableCredit }}</p>
            <p><strong>Utilization:</strong> {{ $utilization }}%</p>
        </div>
        
        <p style="color: #721c24;"><strong>Action Required:</strong> Please review the customer's account.</p>
        
        <p style="margin-top: 30px; font-size: 12px; color: #666;">
            This is an automated email from L-SalesPro. Please do not reply to this email.
        </p>
    </div>
</body>
</html>