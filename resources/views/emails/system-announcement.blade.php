<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Announcement</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #d1ecf1; padding: 20px; border-radius: 8px; border-left: 4px solid #17a2b8;">
        <h2 style="color: #0c5460; margin-bottom: 20px;">📢 System Announcement</h2>
        
        <p>Hello,</p>
        
        <h3 style="color: #0c5460;">{{ $title }}</h3>
        
        <div style="background-color: white; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <p>{{ $message }}</p>
        </div>
        
        @if($actionUrl && $actionText)
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $actionUrl }}" 
               style="display: inline-block; padding: 12px 30px; background-color: #17a2b8; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">
                {{ $actionText }}
            </a>
        </div>
        @endif
        
        <p>Thank you for using L-SalesPro!</p>
        
        <p style="margin-top: 30px; font-size: 12px; color: #666;">
            This is an automated email from L-SalesPro. Please do not reply to this email.
        </p>
    </div>
</body>
</html>