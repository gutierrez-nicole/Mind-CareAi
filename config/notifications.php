<?php
// config/notifications.php
//
// NotificationService
// - Reusable email notifications using Gmail + App Password
// - Assessment-triggered notification helper
// - Facebook notification placeholder (requires Messenger Send API)
//
// IMPORTANT: For production, move secrets to environment variables.
//            These inline defaults are only for the requested quick fix.
//
// Usage example (in your business logic after computing assessment):
//   require_once __DIR__ . '/notifications.php';
//   $svc = new NotificationService();
//   $result = $svc->notifyOnAssessment(85, 80, [
//       'context' => 'Patient ABC - Anxiety Assessment',
//       // 'to_email' => 'someone@example.com', // optional override
//   ]);
//   if (!$result['email']['success']) { /* log $result['email']['error'] */ }

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure Composer autoload loads correctly from the config/ directory
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    // Fallback if structure differs
    require_once __DIR__ . '/vendor/autoload.php';
}

class NotificationService
{
    private string $smtpHost;
    private int $smtpPort;
    private string $smtpUser;
    private string $smtpPass;
    private string $smtpSecure;
    private string $fromEmail;
    private string $fromName;
    private string $defaultEmailRecipient;
    private string $facebookProfileUrl;

    public function __construct(array $options = [])
    {
        // Defaults as requested (move to env ASAP)
        $this->smtpHost  = $options['smtp_host']  ?? getenv('SMTP_HOST')       ?: 'smtp.gmail.com';
        $this->smtpPort  = (int)($options['smtp_port']  ?? getenv('SMTP_PORT') ?: 587);
        // PHPMailer::ENCRYPTION_STARTTLS resolves to 'tls'
        $this->smtpSecure = $options['smtp_secure'] ?? getenv('SMTP_SECURE')   ?: PHPMailer::ENCRYPTION_STARTTLS;

        // Gmail account + App Password (temporary quick fix per request)
        $this->smtpUser  = $options['smtp_user']  ?? getenv('SMTP_USERNAME')   ?: 'dizonkvn@gmail.com';
        $this->smtpPass  = $options['smtp_password'] ?? getenv('SMTP_PASSWORD')?: 'ydpt sckg ccka vbki';

        $this->fromEmail = $options['from_email'] ?? getenv('FROM_EMAIL')      ?: 'dizonkvn@gmail.com';
        $this->fromName  = $options['from_name']  ?? getenv('FROM_NAME')       ?: 'MindCare AI';

        // Send to this inbox for now
        $this->defaultEmailRecipient = $options['to_email'] ?? getenv('TO_EMAIL') ?: 'dizonkvn@gmail.com';

        // Provided profile URL (cannot programmatically DM a personal profile)
        $this->facebookProfileUrl = $options['facebook_profile_url'] ?? getenv('FACEBOOK_PROFILE_URL') ?: 'https://www.facebook.com/kevin.dizon.12177276';
    }

    private function makeMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        // $mail->SMTPDebug = 2; // Enable for verbose debug if needed
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();
        $mail->Host       = $this->smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $this->smtpUser;
        $mail->Password   = $this->smtpPass;
        $mail->SMTPSecure = $this->smtpSecure;
        $mail->Port       = $this->smtpPort;

        // Helpful on some hosts with strict certs
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false,
            ],
        ];

        $mail->setFrom($this->fromEmail, $this->fromName);
        $mail->isHTML(true);

        return $mail;
    }

    /**
     * Send an email
     *
     * @param string $subject
     * @param string $htmlBody
     * @param string|null $to Optional recipient (defaults to configured)
     * @return array{success: bool, error?: string}
     */
    public function sendEmail(string $subject, string $htmlBody, ?string $to = null): array
    {
        $recipient = $to ?: $this->defaultEmailRecipient;

        try {
            $mail = $this->makeMailer();
            $mail->clearAddresses();
            $mail->addAddress($recipient);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;

            $mail->send();
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Placeholder for Facebook notification.
     * Note: You cannot send a notification or DM directly to a personal profile URL.
     * Proper implementation requires:
     *   - Facebook Page + Messenger Send API
     *   - Page Access Token
     *   - Recipient must have messaged the Page first
     *
     * @param string $message
     * @return array{success: bool, error?: string, info?: string}
     */
    public function sendFacebookNotification(string $message): array
    {
        // We’ll just return info + the profile URL for manual follow-up.
        return [
            'success' => false,
            'error'   => 'Direct Facebook notifications to a personal profile are not supported via API.',
            'info'    => "Manual target: {$this->facebookProfileUrl}",
        ];
    }

    /**
     * Notify based on assessment percentage crossing threshold.
     *
     * @param float $assessmentPercent e.g., 85.0
     * @param float $threshold e.g., 80.0
     * @param array $options Optional overrides: ['to_email' => '...', 'context' => 'Patient XYZ']
     * @return array{
     *    triggered: bool,
     *    email?: array{success: bool, error?: string},
     *    facebook?: array{success: bool, error?: string, info?: string}
     * }
     */
    public function notifyOnAssessment(float $assessmentPercent, float $threshold, array $options = []): array
    {
        $triggered = $assessmentPercent >= $threshold;
        if (!$triggered) {
            return ['triggered' => false];
        }

        $context = $options['context'] ?? 'Assessment';
        $toEmail = $options['to_email'] ?? null;

        $subject = "Alert: {$context} reached {$assessmentPercent}%";
        $html = "<h2>Assessment Alert</h2>
                 <p><b>Context:</b> {$context}</p>
                 <p><b>Assessment Percentage:</b> {$assessmentPercent}%</p>
                 <p><b>Threshold:</b> {$threshold}%</p>
                 <p>Time: " . date('Y-m-d H:i:s') . "</p>";

        $emailResult = $this->sendEmail($subject, $html, $toEmail);
        $facebookResult = $this->sendFacebookNotification("{$context}: {$assessmentPercent}% (threshold {$threshold}%)");

        return [
            'triggered' => true,
            'email'     => $emailResult,
            'facebook'  => $facebookResult,
        ];
    }
}