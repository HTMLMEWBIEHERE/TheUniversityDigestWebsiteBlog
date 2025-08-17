<?php
/**
 * Email Helper Class
 * 
 * Provides utility functions for email-related operations
 * Integrates with the site_settings table to use configured email settings
 */

require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailHelper {
    /**
     * Configure PHPMailer with settings from database
     * 
     * @param PDO $conn Database connection
     * @param PHPMailer $mail PHPMailer instance to configure
     * @return array Email settings loaded from database
     */
    public static function configureMailer($conn, $mail) {
        // Get email settings from site_settings table
        $get_settings = $conn->prepare("SELECT * FROM `site_settings` WHERE setting_group = 'email'");
        $get_settings->execute();
        $email_settings = [];
        
        while($row = $get_settings->fetch(PDO::FETCH_ASSOC)) {
            $email_settings[$row['setting_name']] = $row['setting_value'];
        }
        
        // Configure PHPMailer with settings from database or fallback defaults
        $mail->isSMTP();
        $mail->Host       = $email_settings['email_smtp_host'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $email_settings['email_username'] ?? 'theuniversitydigestt@gmail.com';
        $mail->Password   = $email_settings['email_password'] ?? 'whza ylvo beqg naxs';
        $mail->SMTPSecure = $email_settings['email_encryption'] ?? 'tls';
        $mail->Port       = $email_settings['email_smtp_port'] ?? '587';
        
        // Return the settings for additional use if needed
        return $email_settings;
    }
    
    /**
     * Send verification email to user
     * 
     * @param PDO $conn Database connection
     * @param string $email Recipient email address
     * @param string $firstname Recipient first name
     * @param string $lastname Recipient last name
     * @param string $verification_code Verification code to include in email
     * @return bool True if email sent successfully, false otherwise
     * @throws Exception If email cannot be sent
     */
    public static function sendVerificationEmail($conn, $email, $firstname, $lastname, $verification_code) {
        $mail = new PHPMailer(true);
        
        try {
            // Configure mailer with settings from database
            $email_settings = self::configureMailer($conn, $mail);
            $sender_name = $email_settings['email_sender_name'] ?? 'The University Digest';
            
            $mail->setFrom($mail->Username, $sender_name);
            $mail->addAddress($email, $firstname . ' ' . $lastname);
            
            $mail->isHTML(true);
            $mail->Subject = 'Email Verification';
            $mail->Body    = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <h2 style='color: #444;'>Email Verification</h2>
                <p>Dear {$firstname} {$lastname},</p>
                
                <p>Thank you for registering with The University Digest. Please verify your email address by clicking the button below:</p>
                
                <p style='text-align: center;'>
                    <a href='http://the-university-digest.site/TheUnivDigest/user_content/verify.php?code=$verification_code' 
                       style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;'>
                       Verify Email
                    </a>
                </p>
                
                <p>If the button doesn't work, you can copy and paste the following link into your browser:</p>
                
                <p style='background-color: #f5f5f5; padding: 10px; border-radius: 4px;'>
                    <a href='http://the-university-digest.site/TheUnivDigest/user_content/verify.php?code=$verification_code'>
                        http://the-university-digest.site/TheUnivDigest/user_content/verify.php?code=$verification_code
                    </a>
                </p>
                
                <p>This link will expire in 24 hours.</p>
                
                <p>Best regards,<br>The University Digest Team</p>
            </body>
            </html>";
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }
} 