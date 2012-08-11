<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sf.net>
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

/**
 * Handles the multi-panel view layout
 *
 * @author Stephen
 */
class PanelInterface {
    private $rightContent;
    private $statusContent;
    
    private $menu_items = array();
    
    public function setMenuItems($menu_items) {
        foreach ($menu_items AS $item) {
            $new_item = array('url' => '#', 'label' => NULL, 'class' => 'menu-item');
            $this->menu_items[] = array_merge($new_item, $item);
        }
    }
    
    public function setStatusContent($content) {
        $this->statusContent = $content;
    }
    
    public function setContent($content) {
        $this->rightContent = $content;
    }
    
    public function __toString() {
        $content = "<div id=\"panels\">\n";
        $content .= "\t<div id=\"left-menu\">\n";
        $content .= "\t\t<div id=\"left-menu_top\"></div>\n";
        $content .= "\t\t<div id=\"left-menu_body\">\n";
        $content .= "\t\t\t<ul>\n";
        for ($i = 0; $i < count($this->menu_items); $i++) {
            $content .= "\t\t\t\t<li>\n";
	    if (isset($this->menu_items[$i]['disp'])) {
		$content .= "\t\t\t\t\t<a class=\"{$this->menu_items[$i]['class']}\" href=\"{$this->menu_items[$i]['disp']}\">\n";
		$content .= "\t\t\t\t\t\t<meta itemprop=\"realUrl\" content=\"{$this->menu_items[$i]['url']}\" />\n";
	    } else {
		$content .= "\t\t\t\t\t<a class=\"{$this->menu_items[$i]['class']}\" href=\"{$this->menu_items[$i]['url']}\">\n";
		
	    }
            $content .= "\t\t\t\t\t\t{$this->menu_items[$i]['label']}\n";
            $content .= "\t\t\t\t\t</a>\n";
            $content .= "\t\t\t\t</li>\n";
        }
        $content .= "\t\t\t</ul>\n";
        $content .= "\t\t</div>";
        $content .= "\t\t<div id=\"left-menu_bottom\"></div>\n";
        $content .= "\t</div>\n";
        $content .= "\t<div id=\"right-content\">\n";
        $content .= "\t\t<div id=\"right-content_top\"></div>\n";
        $content .= "\t\t<div id=\"right-content_status\">{$this->statusContent}</div>\n";
        $content .= "\t\t<div id=\"right-content_body\">{$this->rightContent}</div>\n";
        $content .= "\t\t<div id=\"right-content_bottom\"></div>\n";
        $content .= "\t</div>\n";
        $content .= "</div>\n";
        return $content;
    }
}

?>
