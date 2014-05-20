<?php
/**
 * Copyright 2011-2013 Stephen Just <stephenjust@users.sf.net>
 *                2014 Daniel Butum <danibutum at gmail dot com>
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

class Statistic
{
    /**
     * Return the most downloaded addon of a given type
     *
     * @param string $addonType the type of addon eg: kart, track, arena etc
     * @param string $fileType
     *
     * @return null|string the id of the addon or null on empty selection
     * @throws StatisticException
     */
    public static function mostDownloadedAddon($addonType, $fileType = 'addon')
    {
        if (!Addon::isAllowedType($addonType))
        {
            throw new StatisticException(_h('Invalid addon type.'));
        }

        try
        {
            $download_counts = DBConnection::get()->query(
                'SELECT `addon_id`, SUM(`downloads`) AS `count`
                FROM `' . DB_PREFIX . 'files`
                WHERE `addon_type` = :addon_type
                AND `file_type` = :file_type
                GROUP BY `addon_id`
                ORDER BY SUM(`downloads`) DESC',
                DBConnection::FETCH_FIRST,
                array(
                    ':addon_type' => $addonType,
                    ':file_type'  => $fileType
                )
            );
            if (empty($download_counts))
            {
                return null;
            }

            return $download_counts['addon_id'];
        }
        catch(DBException $e)
        {
            throw new StatisticException(htmlspecialchars(
                _('An error occurred while performing your statistic query') . ' ' .
                _('Please contact a website administrator.')
            ));
        }
    }

    /**
     * Return the newest addon of a given type
     *
     * @param string $addonType
     *
     * @return null|string the id of the addon or null on empty selection
     * @throws StatisticException
     */
    public static function newestAddon($addonType)
    {
        if (!Addon::isAllowedType($addonType))
        {
            throw new StatisticException(_h('Invalid addon type.'));
        }

        try
        {
            $newest_addon = DBConnection::get()->query(
                'SELECT `a`.`id`
                FROM `' . DB_PREFIX . 'addons` `a`
                LEFT JOIN `' . DB_PREFIX . $addonType . '_revs` `r`
                ON `a`.`id` = `r`.`addon_id`
                WHERE `r`.`status` & ' . F_APPROVED . '
                ORDER BY `a`.`creation_date` DESC
                LIMIT 1',
                DBConnection::FETCH_FIRST
            );
            if (empty($newest_addon))
            {
                return null;
            }

            return $newest_addon['id'];
        }
        catch(DBException $e)
        {
            throw new StatisticException(htmlspecialchars(
                _('An error occurred while performing your statistic query') . ' ' .
                _('Please contact a website administrator.')
            ));
        }
    }
}
