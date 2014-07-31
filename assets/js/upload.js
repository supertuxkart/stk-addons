/**
 * copyright 2014 Daniel Butum <danibutum at gmail dot com>
 *
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

(function($) {
    "use strict";

    $("#file_addon").fileinput({showUpload: false});

    var $radio_author1 = $("#l_author1"),
        $radio_author2 = $("#l_author2"),
        $checkbox_license1 = $("#l_licensefile1"),
        $checkbox_license2 = $("#l_licensefile2"),
        $label_license1 = $("#l_licensetext1"),
        $label_license2 = $("#l_licensetext2"),
        $select_file_type = $("#upload-type");

    function agreementToggle() {
        if ($radio_author1.prop("checked")) {
            $checkbox_license1.attr("disabled", false);
            $checkbox_license2.attr("disabled", true);
            $label_license1.css("color", '#000000');
            $label_license2.css("color", '#999999');
        }
        else {
            $checkbox_license1.attr("disabled", true);
            $checkbox_license2.attr("disabled", false);
            $label_license1.css("color", '#999999');
            $label_license2.css("color", '#000000');
        }
    }

    function uploadFormFieldToggle() {
        agreementToggle();

        if ($select_file_type) {
            if ($select_file_type.val() === "image") {
                $checkbox_license1.attr("disabled", true);
                $checkbox_license2.attr("disabled", true);
                $label_license1.css("color", '#999999');
                $label_license2.css("color", '#999999');
            } else {
                agreementToggle();
            }
        }
    }

    $radio_author1.change(function() {
        uploadFormFieldToggle();
    });

    $radio_author2.change(function() {
        uploadFormFieldToggle();
    });

    $select_file_type.change(function() {
        uploadFormFieldToggle();
    });

    uploadFormFieldToggle();

})(jQuery);
