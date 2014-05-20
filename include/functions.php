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
 * @return mixed
 */
function get_self()
{
    $list = get_included_files();

    return $list[0];
}


/**
 * @param $nbr
 *
 * @return string
 */
function cryptUrl($nbr)
{
    $str = "";
    $chaine = "abcdefghijklmnpqrstuvwxy";
    srand((double)microtime() * 1000000);
    for ($i = 0; $i < $nbr; $i++)
    {
        $str .= $chaine[rand() % strlen($chaine)];
    }

    return $str;
}

/**
 * Resize an image
 *
 * @param string $file
 *
 * @return null
 */
function resizeImage($file)
{
    // Determine image size
    $type = (isset($_GET['type'])) ? $_GET['type'] : null;
    switch ($type)
    {
        default:
            $size = 100;
            break;
        case 'small':
            $size = 25;
            break;
        case 'medium':
            $size = 75;
            break;
        case 'big':
            $size = 300;
            break;
    }
    $cache_name = $size . '--' . basename($file);
    $local_path = UP_LOCATION . $file;

    // Check if image exists, and if it does, check its format
    $orig_file = File::exists($file);
    if ($orig_file === -1)
    {
        if (!file_exists(ROOT . $file))
        {
            header('HTTP/1.1 404 Not Found');

            return;
        }
        else
        {
            $local_path = ROOT . $file;
        }
    }

    // Check if a cached version is available
    if (Cache::fileExists($cache_name) !== array())
    {
        header('Cached-Image: true');
        header('Location: ' . CACHE_DL . '/' . $cache_name);

        return;
    }

    // Start processing the original file
    $image_info = @getimagesize($local_path);
    switch ($image_info[2])
    {
        default:
            $source = imagecreatefrompng(ROOT . 'image/notfound.png');
            $format = 'png';
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($local_path);
            $format = 'png';
            break;
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($local_path);
            $format = 'jpg';
            break;
    }

    // Get length and width of original image
    $width_source = imagesx($source);
    $height_source = imagesy($source);
    if ($width_source > $height_source)
    {
        $width_destination = $size;
        $height_destination = $size * $height_source / $width_source;
    }
    if ($width_source <= $height_source)
    {
        $height_destination = $size;
        $width_destination = $size * $width_source / $height_source;
    }

    // Create new canvas
    $destination = imagecreatetruecolor($width_destination, $height_destination);

    // Preserve transparency
    imagealphablending($destination, false);
    imagesavealpha($destination, true);
    $transparent_bg = imagecolorallocatealpha($destination, 255, 255, 255, 127);
    imagefilledrectangle($destination, 0, 0, $width_destination, $height_destination, $transparent_bg);

    // Resize image
    imagecopyresampled(
        $destination,
        $source,
        0,
        0,
        0,
        0,
        $width_destination,
        $height_destination,
        $width_source,
        $height_source
    );

    // Display image and cache the result
    header('Cached-Image: false');
    if ($format === 'png')
    {
        header('Content-Type: image/png');
        imagepng($destination, CACHE_DIR . '/' . $cache_name, 9);
        imagepng($destination, null, 9);
    }
    else
    {
        header("Content-Type: image/jpeg");
        imagejpeg($destination, CACHE_DIR . '/' . $cache_name, 90);
        imagejpeg($destination, null, 90);
    }

    // Create a record of the cached file
    $orig_file_addon = File::getAddon($orig_file);
    Cache::createFile($cache_name, $orig_file_addon, sprintf('w=%d,h=%d', $width_destination, $height_destination));
}

/**
 * @param string $text
 *
 * @return string
 */
function img_label($text)
{
    $write_dir = UP_LOCATION . 'temp/';
    $read_dir = DOWN_LOCATION . 'temp/';
    $text_noaccent = preg_replace('/\W/s', '_', $text);

    if (!file_exists($write_dir . 'im_' . $text_noaccent . '.png'))
    {
        $text_size = 11;
        $text_angle = 90;
        $font = ROOT . 'include/DejaVuSans.ttf';
        $bbox = imagettfbbox($text_size, $text_angle, $font, $text);

        $min_x = min(array($bbox[0], $bbox[2], $bbox[4], $bbox[6]));
        $max_x = max(array($bbox[0], $bbox[2], $bbox[4], $bbox[6]));
        $min_y = min(array($bbox[1], $bbox[3], $bbox[5], $bbox[7]));
        $max_y = max(array($bbox[1], $bbox[3], $bbox[5], $bbox[7]));

        $width = $max_x - $min_x + 2;
        $height = $max_y - $min_y + 2;

        $image = imagecreatetruecolor($width, $height);

        $bgcolor = imagecolorallocate($image, 0, 0, 0);
        imagecolortransparent($image, $bgcolor);
        $textcolor = imagecolorallocate($image, 2, 2, 2);

        imagettftext($image, $text_size, $text_angle, $width, $height, $textcolor, $font, $text);

        imagepng($image, $write_dir . 'im_' . $text_noaccent . '.png');
        imagedestroy($image);
    }

    return '<img src="' . $read_dir . 'im_' . $text_noaccent . '.png' . '" alt="' . htmlentities($text) . '" />';
}

/**
 * Macro function for htmlspecialchars(_($message))
 *
 * @param string $message
 *
 * @return string
 */
function _h($message)
{
    return htmlspecialchars(_($message));
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
        echo '<span class="warning">' . htmlspecialchars(
                _('No moderator mailing-list email is set.')
            ) . '</span><br />';

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

function loadUsers()
{
    global $js;
    $users = User::getAllData();
    echo <<< EOF
<ul>
<li>
<a class="menu-item" href="javascript:loadFrame({$_SESSION['userid']},'users-panel.php')">
<img class="icon" src="image/user.png" />
EOF;
    echo htmlspecialchars(_('Me')) . '</a></li>';
    foreach ($users as $user)
    {
        // Make sure that the user is active, or the viewer has permission to
        // manage this type of user
        if ($_SESSION['role']['manage' . $user['role'] . 's']
            || $user['active'] == 1
        )
        {
            echo '<li><a class="menu-item';
            if ($user['active'] == 0)
            {
                echo ' unavailable';
            }
            echo '" href="javascript:loadFrame(' . $user['id'] . ',\'users-panel.php\')">';
            echo '<img class="icon"  src="image/user.png" />';
            echo $user['user'] . "</a></li>";
            // When running for the list of users, check if we want to load this
            // user's profile. Doing this here is more efficient than searching
            // for the user name with another query. Also, leaving this here
            // cause the lookup to fail if permissions were invalid.
            if ($user['user'] === $_GET['user'])
            {
                $js .= 'loadFrame(' . $user['id'] . ',\'users-panel.php\')';
            }
        }
    }
    echo "</ul>";
}

/**
 * Set the permission in the session
 *
 * @param string $role
 */
function setPermissions($role)
{
    switch ($role)
    {
        case "basicUser":
            $_SESSION['role'] = array(
                "basicPage"               => true,
                "addAddon"                => true,
                "manageaddons"            => false,
                "managebasicUsers"        => false,
                "managemoderators"        => false,
                "manageadministrators"    => false,
                "managesupAdministrators" => false,
                "manageroots"             => false,
                "managesettings"          => false
            );
            break;
        case "moderator":
            $_SESSION['role'] = array(
                "basicPage"               => true,
                "addAddon"                => true,
                "manageaddons"            => true,
                "managebasicUsers"        => true,
                "managemoderators"        => false,
                "manageadministrators"    => false,
                "managesupAdministrators" => false,
                "manageroots"             => false,
                "managesettings"          => false
            );
            break;
        case "administrator":
            $_SESSION['role'] = array(
                "basicPage"               => true,
                "addAddon"                => true,
                "manageaddons"            => true,
                "managebasicUsers"        => true,
                "managemoderators"        => true,
                "manageadministrators"    => false,
                "managesupAdministrators" => false,
                "manageroots"             => false,
                "managesettings"          => true
            );
            break;
        case "supAdministrator":
            $_SESSION['role'] = array(
                "basicPage"               => true,
                "addAddon"                => true,
                "manageaddons"            => true,
                "managebasicUsers"        => true,
                "managemoderators"        => true,
                "manageadministrators"    => true,
                "managesupAdministrators" => false,
                "manageroots"             => false,
                "managesettings"          => true
            );
            break;
        case "root":
            $_SESSION['role'] = array(
                "basicPage"               => true,
                "addAddon"                => true,
                "manageaddons"            => true,
                "managebasicUsers"        => true,
                "managemoderators"        => true,
                "manageadministrators"    => true,
                "managesupAdministrators" => true,
                "manageroots"             => true,
                "managesettings"          => true
            );
            break;
    }
    //support for translations :
    htmlspecialchars(_("root"));
    htmlspecialchars(_("supAdministrator"));
    htmlspecialchars(_("administrator"));
    htmlspecialchars(_("moderator"));
    htmlspecialchars(_("basicUser"));
}
