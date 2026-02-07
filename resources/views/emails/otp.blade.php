<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <h2>Email Verification</h2>
    <p>Hi {{ $user->name }},</p>
    <p>Thank you for registering with us! To complete your email verification, please use the following OTP:</p>
    
    <div style="background-color: #f5f5f5; padding: 20px; text-align: center; margin: 20px 0; border-radius: 5px;">
        <h1 style="margin: 0; letter-spacing: 5px; font-size: 32px; color: #333;">{{ $otp }}</h1>
    </div>
    
    <p>This OTP will expire in 5 minutes.</p>
    <p>If you did not request this code, please ignore this email.</p>
    
    <p>Best regards,<br>Eatelligent Team</p>
</div>
