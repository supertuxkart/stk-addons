<?php
/* copyright 2009 Lucas Baudin <xapantu@gmail.com>                 
                                                                              
 This file is part of stkaddons.                                 
                                                                              
 stkaddons is free software: you can redistribute it and/or      
 modify it under the terms of the GNU General Public License as published by  
 the Free Software Foundation, either version 3 of the License, or (at your   
 option) any later version.                                                   
                                                                              
 stkaddons is distributed in the hope that it will be useful, but
 WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for    
 more details.                                                                
                                                                              
 You should have received a copy of the GNU General Public License along with 
 stkaddons.  If not, see <http://www.gnu.org/licenses/>.   */
?>
<?php
/***************************************************************************
Project: STK Addon Manager

File: index.php
Version: 1
Licence: GPLv3
Description: index page

***************************************************************************/

class menu
{
	var $header = '<table class="viewGlobal"><tr>';
	var $footer = '<div id="disAddon"><h1>Supertuxkart Addon Manager</h1></div></td></div>';
	var $root = "";
	var $sub = array();
	var $contentSub = array();
	var $nbSub = array();
	var $dispDiv ="";
	var $nbDiv = 0;
	var $javascript = "";
	function addRoot($title, $div)
	{
		$this->root.='<a class="root"  id="root'.$div.'" onclick="changeClassRoot(this)" href="javascript:loadSub(\''.$div.'\')">'.$title.'</a>';
		$this->contentSub[sizeOf($this->contentSub)] = $div;
		$this->nbSub[$div] = 0;
		$this->sub[$div] = "";
	}
	function addRootTitle($title)
	{
		$this->root.='<span class="root">'.$title.'</span>';
	}
	function addSub($title, $action, $display, $icon=null)
	{
		if($this->nbSub[$display] == null) $this->nbSub[$display]=0;
		$this->sub[$display] .= '<a class="sub" id="sub'.$display.$this->nbSub[$display].'" onclick="changeClassSub(this)" id="sub"'.$display.$this->nbSub[$display].' href="'.$action.'">'.$icon.$title.'</a>';
		if($display.$title==$_GET['title'])
		{
		$this->javascript = '
			<script type="text/javascript">
				'.$action.';
				loadSub(\''.$display.'\');
				changeClassRoot(document.getElementById("root'.$display.'"));
				changeClassSub(document.getElementById("sub'.$display.$this->nbSub[$display].'"));
				
			</script>';
		}
		$this->nbSub[$display]++;
	}
	function addSubTitle($title)
	{
	}
	function affiche()
	{
		echo $this->header;
		echo '<td class="contentRoot">';
		echo $this->root;
		echo '</td>';
		echo '<td class="contentSub">';
		$i = 0;
		foreach($this->sub as $element)
		{
			echo '<div id="sub'.$this->contentSub[$i].'" class="divSub">';
			echo $element;
			echo '</div>';
			$i++;
		}
		echo '&nbsp;</td><td class="thirdCol">';
		echo $this->dispDiv;
		echo $this->footer;
		echo $this->javascript;
	}
	function addDiv($content)
	{
		$this->dispDiv .= '<div class="dispDiv" id="disp'.$this->nbDiv.'">'.$content.'</div>';
		$this->nbDiv++;
	}
}
?>
