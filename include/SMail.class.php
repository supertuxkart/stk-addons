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
    protected $mail;

    /**
     * PHPMailer send method wrapper, that handles errors and exceptions
     *
     * @throws SMailException
     */
    protected function send()
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
    protected function addAddress($address, $name = "")
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
     * The constructor
     *
     * @throws SMailException
     */
    public function __construct()
    {
        $this->mail = new PHPMailer(true);

        // use SMTP
        if (IS_SMTP)
        {
            $this->mail->isSMTP();
            $this->mail->SMTPAuth = SMTP_AUTH;
            $this->mail->SMTPSecure = SMTP_PREFIX;

            //            if (DEBUG_MODE)
            //            {
            //                $this->mail->SMTPDebug = 3;
            //            }

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
            $this->mail->setFrom(ConfigManager::getConfig("admin_email"), "STK-Addons Administrator");
            $this->mail->addReplyTo(ConfigManager::getConfig("admin_email"), "STK-Addons Administrator");
        }
        catch(phpmailerException $e)
        {
            throw new SMailException($e->getMessage());
        }

        $this->mail->CharSet = "UTF-8";
        $this->mail->Encoding = "8bit";
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
        $message = "Thank you for registering an account on the SuperTuxKart Add-Ons Manager.\n" .
            "Please go to " . SITE_ROOT . "$ver_page?action=valid&num=$ver_code&user=$userid to activate your account.\n\n" .
            "Username: $username";

        $this->addAddress($email);
        $this->mail->Subject = "New Account at " . $_SERVER["SERVER_NAME"];
        $this->mail->Body = $this->mail->AltBody = $message;

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
            "Please go to http://{$_SERVER["SERVER_NAME"]}/$ver_page?action=valid&num=$ver_code&user=$userid to reset your password.\n\n" .
            "Username: $username";

        $this->addAddress($email);
        $this->mail->Subject = "Reset Password on " . $_SERVER["SERVER_NAME"];
        $this->mail->Body = $this->mail->AltBody = $message;

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
     */
    public function moderatorNotification($subject, $message_html)
    {
        $mail_address = ConfigManager::getConfig('list_email');
        if (empty($mail_address))
        {
            trigger_error(_h('No moderator mailing-list email is set.'));

            return;
        }

        $this->addAddress($mail_address);
        $this->mail->Subject = $subject;
        $this->mail->isHTML(true);
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
