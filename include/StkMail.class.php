<?php
/**
 * Copyright 2009      Lucas Baudin <xapantu@gmail.com>
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
 * This file is part of stk-addons.
 *
 * stk-addons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stk-addons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stk-addons. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Class StkMail
 */
use PHPMailer\PHPMailer\PHPMailer;
class StkMail
{
    /**
     * Hold the mail instance
     *
     * @var PHPMailer
     */
    private $mail;

    /**
     * The base url for links in emails
     * @var string
     */
    private $base_url;

    /**
     * PHPMailer send method wrapper, that handles errors and exceptions
     *
     * @throws StkMailException
     */
    private function send()
    {
        try
        {
            if (!$this->mail->send())
            {
                throw new StkMailException($this->mail->ErrorInfo);
            }
        }
        catch (PHPMailer\PHPMailer\Exception $e)
        {
            throw new StkMailException($e->getMessage());
        }
    }

    /**
     * PHPMailer addAddress method wrapper.
     *
     * @param string $address
     * @param string $name
     *
     * @throws StkMailException
     */
    private function addAddress($address, $name = "")
    {
        try
        {
            if (!$this->mail->addAddress($address, $name))
            {
                throw new StkMailException(sprintf("Address '%s' is already used.", h($address)));
            }
        }
        catch (PHPMailer\PHPMailer\Exception $e)
        {
            throw new StkMailException($e->getMessage());
        }
    }

    /**
     * Build the body of an action email
     *
     * @param array $tpl_data the template variables
     */
    private function buildBodyActionEmail(array $tpl_data)
    {
        $tpl = StkTemplate::get("email/action.tpl");
        $tpl->assign("email", $tpl_data);
        $this->mail->Body = $tpl->toString();

        $username = $tpl_data["username"];
        $message = $tpl_data["message"];
        $url_href = $tpl_data["url_href"];
        $warning = $tpl_data["warning"];
        $this->mail->AltBody = <<<EMAIL
Hello $username,

$message
$url_href

$warning

Best regards,
Your SuperTuxKart Team

EMAIL;
    }

    /**
     * The constructor
     *
     * @throws StkMailException
     */
    public function __construct()
    {
        $this->base_url = ROOT_LOCATION;
        if (!IS_SSL_CERTIFICATE_VALID) // use normal http
        {
            $this->base_url = str_replace("https", "http", $this->base_url);
        }

        $this->mail = new PHPMailer(true);
        if (IS_SMTP)
        {
            $this->mail->isSMTP();
            $this->mail->SMTPAuth = SMTP_AUTH;
            $this->mail->SMTPSecure = SMTP_PREFIX;

            $this->mail->Host = SMTP_HOST;
            $this->mail->Port = SMTP_PORT;
            $this->mail->Username = SMTP_USER;
            $this->mail->Password = SMTP_PASS;
        }
        else // use sendmail
        {
            $this->mail->isSendmail();

            if (SENDMAIL_PATH)
            {
                $this->mail->Sendmail = SENDMAIL_PATH;
            }
        }

        try
        {
            $admin_email = Config::get(Config::EMAIL_ADMIN);
            if (!$admin_email)
            {
                throw new StkMailException("The admin email is not set. Could not send email.");
            }
            if (!Util::isEmail($admin_email))
            {
                throw new StkMailException(sprintf("The admin email = %s is NOT an email!!!!. Could not send email.", $admin_email));
            }

            $this->mail->setFrom($admin_email, "STK-Addons Administrator");
        }
        catch (PHPMailer\PHPMailer\Exception $e)
        {
            throw new StkMailException($e->getMessage());
        }

        $this->mail->isHTML(true);
        $this->mail->CharSet = "UTF-8";
        $this->mail->Encoding = "8bit";
    }

    /**
     * Set the Reply-To field in the email
     *
     * @param string $email
     * @param string $name
     *
     * @return $this
     */
    public function setReplyTo($email, $name = "STK-Addons Administrator")
    {
        $this->mail->addReplyTo($email, $name);

        return $this;
    }

    /**
     * Send new register email
     *
     * @param string $email
     * @param int    $user_id
     * @param string $username
     * @param string $ver_code
     * @param string $ver_page
     *
     * @throws StkMailException
     */
    public function newAccountNotification($email, $user_id, $username, $ver_code, $ver_page)
    {
        $subject = "New SuperTuxKart Account";

        $days_str = sprintf("%d %s", CRON_DAILY_VERIFICATION_DAYS, CRON_DAILY_VERIFICATION_DAYS == 1 ? "day" : "days");
        $tpl_data = [
            "username"  => $username,
            "url_href"  => $this->base_url . "$ver_page?action=valid&num=$ver_code&user=$user_id",
            "url_label" => "Confirm email address",
            "warning"   => sprintf("The activation link will expire in %s.\nIf you did not request an account for SuperTuxKart, please just ignore this email.", $days_str),
            "subject"   => $subject,
            "message"   => "Thank you for registering an account on the SuperTuxKart server. Please click on the button/link below to confirm your email.",
        ];

        $this->addAddress($email);
        $this->mail->Subject = $subject;
        $this->buildBodyActionEmail($tpl_data);

        $this->send();
    }

    /**
     * Send new password reset mail
     *
     * @param string $email
     * @param int    $user_id
     * @param string $username
     * @param string $ver_code
     * @param string $ver_page
     *
     * @throws StkMailException
     */
    public function passwordResetNotification($email, $user_id, $username, $ver_code, $ver_page)
    {
        $subject = "Reset Password for SuperTuxKart Account";
        $tpl_data = [
            "username"  => $username,
            "url_href"  => $this->base_url . "$ver_page?action=valid&num=$ver_code&user=$user_id",
            "url_label" => "Reset your password",
            "warning"   => "If you did not request a password reset, please just ignore this email.",
            "subject"   => $subject,
            "message"   => "You have requested to reset your password on the SuperTuxKart server. Please click on the button/link below to reset your password.",
        ];

        $this->addAddress($email);
        $this->mail->Subject = $subject;
        $this->buildBodyActionEmail($tpl_data);

        $this->send();
    }

    /**
     * Send email when a new addon note is created/updated
     *
     * @param string $email
     * @param string $addon_name
     * @param string $notes
     *
     * @throws StkMailException
     */
    public function addonNoteNotification($email, $addon_name, $notes)
    {
        $message =
            "A moderator has left a note concerning your add-on, '$addon_name.' The notes saved for each revision of this add-on are shown below.\n\n";
        $message .= $notes;

        $this->addAddress($email);
        $this->mail->Subject = "New message for add-on '$addon_name'";
        $this->mail->Body = $this->mail->AltBody = $message;

        $this->send();
    }

    /**
     * Send email when a new addon bug report is created/updated
     *
     * @param string $email
     * @param string $addon_name
     * @param string $notes
     *
     * @throws StkMailException
     */
    public function addonBugNotification($email, $user_id, $addon_name, $content, $comment)
    {
        if ($comment)
        {
            $subject = "New bug report for add-on '$addon_name'";
            $message =
                "$user_id has made a bug report concerning your add-on, '$addon_name.' The report is shown below.\n\n";
        } else {
            $subject = "New reply to bug report for add-on '$addon_name'";
            $message =
                "$user_id has commented on a bug report concerning your add-on, '$addon_name.' The reply is shown below.\n\n";
        }
        $message .= $content;

        $this->addAddress($email);
        $this->mail->Subject = $subject;
        $this->mail->Body = $this->mail->AltBody = $message;

        $this->send();
    }

    /**
     * Send a new mail to the moderator list mail
     *
     * @param string $subject
     * @param string $message_html
     *
     * @throws StkMailException
     */
    public function moderatorNotification($subject, $message_html)
    {
        $mail_address = Config::get(Config::EMAIL_LIST);
        if (!$mail_address)
        {
            throw new StkMailException('No moderator mailing-list email is set.');
        }

        $this->addAddress($mail_address);
        $this->mail->Subject = $subject;
        $this->mail->Body = $this->mail->AltBody = $message_html;

        $this->send();
    }

    /**
     * Factory method
     *
     * @return static
     */
    public static function get()
    {
        return new static();
    }
}
