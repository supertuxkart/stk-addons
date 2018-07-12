<?php
/**
 * copyright 2011      Stephen Just <stephenjust@users.sf.net>
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
 * Macro function for htmlspecialchars() with additional options
 *
 * @param string $message
 *
 * @return string
 */
function h($message)
{
    return htmlspecialchars($message, ENT_QUOTES | ENT_HTML5, "UTF-8", false);
}

/**
 * Macro function that calls exit and json_encode
 *
 * @param string $message
 * @param array  $other_values other options to send back
 */
function exit_json_error($message, array $other_values = [])
{
    exit(json_encode(["error" => $message] + $other_values));
}

/**
 * Macro function that calls exit and json_encode
 *
 * @param string $message
 * @param array  $other_values other options to send back
 */
function exit_json_success($message, array $other_values = [])
{
    exit(json_encode(["success" => $message] + $other_values));
}

/**
 * Get the default exception message when something is wrong with the database
 *
 * @param string $message
 *
 * @return string
 */
function exception_message_db($message)
{
    return h(
        sprintf(_('A database error occurred while trying to %s.'), $message) . ' ' .
        _('Please contact a website administrator.')
    );
}

/**
 * Minify html
 *
 * @param string                   $tpl_output the html to minify
 * @param Smarty_Internal_Template $template
 *
 * @return string
 */
function minify_html($tpl_output, Smarty_Internal_Template $template)
{
    return preg_replace(['/\>[^\S ]+/s', '/[^\S ]+\</s', '/(\s)+/s'], ['>', '<', '\\1'], $tpl_output);
}
