<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sf.net>
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
 * Macro function for htmlspecialchars(_($message)) with additional options
 *
 * @param string $message
 *
 * @return string
 */
function _h($message)
{
    return h(_($message));
}


/**
 * Macro function for htmlspecialchar() with additional options
 *
 * @param $message
 *
 * @return string
 */
function h($message)
{
    return htmlspecialchars($message, ENT_QUOTES | ENT_HTML5, "UTF-8", false);
}

/**
 * @param string $subject
 * @param string $message_html
 *
 * @return null
 */
function moderator_email($subject, $message_html)
{
    $mail_address = ConfigManager::getConfig('list_email');
    if (empty($mail_address))
    {
        echo '<span class="warning">' .
            _h('No moderator mailing-list email is set.')
            . '</span><br />';

        return null;
    }

    $boundary = "-----=" . md5(rand());
    $header = "From: \"STK-Addons Administrator\" <" . ConfigManager::getConfig('admin_email') . ">\n"
        . "Reply-to: \"STK-Addons Administrator\" <" . ConfigManager::getConfig('admin_email') . ">\n"
        . "MIME-Version: 1.0\n"
        . "Content-Type: multipart/alternative;\n boundary=\"$boundary\"\n";
    $message = "\n--" . $boundary . "\n"
        . "Content-Type: text/html; charset=\"ISO-8859-1\"\n"
        . "Content-Transfer-Encoding: 8bit\n"
        . "\n" . $message_html . "\n"
        . "\n--" . $boundary . "--\n"
        . "\n--" . $boundary . "--\n";
    mail($mail_address, $subject, $message, $header);
}
