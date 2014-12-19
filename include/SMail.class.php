<?php
/**
 * Copyright 2009 Lucas Baudin <xapantu@gmail.com>
 *           2014 Daniel Butum <danibutum at gmail dot com>
 * This file is part of stkaddons
 *
 * stkaddons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stkaddons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stkaddons.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Class SMail
 */
class SMail
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
     * @throws SMailException
     */
    private function send()
    {
        try
        {
            if (!$this->mail->send())
            {
                throw new SMailException($this->mail->ErrorInfo);
            }
        }
        catch(phpmailerException $e)
        {
            throw new SMailException($e->getMessage());
        }
    }

    /**
     * PHPMailer addAddress method wrapper.
     *
     * @param string $address
     * @param string $name
     *
     * @throws SMailException
     */
    private function addAddress($address, $name = "")
    {
        try
        {
            if (!$this->mail->addAddress($address, $name))
            {
                throw new SMailException(sprintf("Address '%s' is already used.", h($address)));
            }
        }
        catch(phpmailerException $e)
        {
            throw new SMailException($e->getMessage());
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
     * @throws SMailException
     */
    public function __construct()
    {
        if (IS_SSL_CERTIFICATE_VALID)
        {
            $this->base_url = ROOT_LOCATION;
        }
        else // use normal http
        {
            $this->base_url = ROOT_LOCATION;
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
            $this->mail->Password = SMTP_PASS;;
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
                throw new SMailException("The admin email is not set. Could not send email.");
            }

            $this->mail->setFrom($admin_email, "STK-Addons Administrator");
        }
        catch(phpmailerException $e)
        {
            throw new SMailException($e->getMessage());
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
     * @param int    $userid
     * @param string $username
     * @param string $ver_code
     * @param string $ver_page
     *
     * @throws SMailException
     */
    public function newAccountNotification($email, $userid, $username, $ver_code, $ver_page)
    {
        $subject = "New SuperTuxKart Account";
        $tpl_data = [
            "username"  => $username,
            "url_href"  => $this->base_url . "$ver_page?action=valid&num=$ver_code&user=$userid",
            "url_label" => "Confirm email address",
            "warning"   => "If you did not request an account for SuperTuxKart, please just ignore this email.",
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
     * @param int    $userid
     * @param string $username
     * @param string $ver_code
     * @param string $ver_page
     *
     * @throws SMailException
     */
    public function passwordResetNotification($email, $userid, $username, $ver_code, $ver_page)
    {
        $message = "You have requested to reset your password on the SuperTuxKart Add-Ons Manager.\n" .
            "Please go to " . $this->base_url . "$ver_page?action=valid&num=$ver_code&user=$userid to reset your password.\n\n" .
            "Username: $username";

        $subject = "Reset Password for SuperTuxKart Account";
        $tpl_data = [
            "username"  => $username,
            "url_href"  => $this->base_url . "$ver_page?action=valid&num=$ver_code&user=$userid",
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
     * @param string $addon_id
     * @param string $notes
     *
     * @throws SMailException
     */
    public function addonNoteNotification($email, $addon_id, $notes)
    {
        $addon_name = Addon::getNameByID($addon_id);
        $message =
            "A moderator has left a note concerning your add-on, '$addon_name.' The notes saved for each revision of this add-on are shown below.\n\n";
        $message .= $notes;

        $this->addAddress($email);
        $this->mail->Subject = "New message for add-on '$addon_name'";
        $this->mail->Body = $this->mail->AltBody = $message;

        $this->send();
    }

    /**
     * Send a new mail to the moderator list mail
     *
     * @param string $subject
     * @param string $message_html
     *
     * @throws SMailException
     */
    public function moderatorNotification($subject, $message_html)
    {
        $mail_address = Config::get(Config::EMAIL_LIST);
        if (!$mail_address)
        {
            throw new SMailException('No moderator mailing-list email is set.');
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
