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
// éditez les 2 variables ci-dessous en fonction du résultat souhaité :
$largeur = "100"; // correspond à la largeur de l'image souhaitée
$hauteur ="100"; // correspond à la hauteur de l'image souhaitée

// et voici la création de la miniature...
header("Content-Type: image/jpeg");
reduitimage($_GET['pic']);
function reduitImage($entryname)
{
	global $fichier;
	$source = imagecreatefrompng($entryname);
		// Les fonctions imagesx et imagesy renvoient la largeur et la hauteur d'une image
		$largeur_source = imagesx($source);
		$hauteur_source = imagesy($source);
	    if($_GET['type'] == "big")
		    {
		    $size=300;
		    }
	    if($_GET['type'] == "small")
		    {
		    $size=25;
		    }
		if($largeur_source > $hauteur_source)
		{

			$largeur_destination = $size;
			$hauteur_destination = $size*$hauteur_source/$largeur_source;
		}
		if($largeur_source <= $hauteur_source)
		{
			$hauteur_destination = $size;
			$largeur_destination = $size*$largeur_source/$hauteur_source;
		}
		$destination = imagecreatetruecolor($largeur_destination, $hauteur_destination); // On crée la miniature vide
		// On crée la miniature
		imagecopyresampled($destination, $source, 0, 0, 0, 0, $largeur_destination, $hauteur_destination, $largeur_source, $hauteur_source);

		imagepng($destination);
		
	
}
?>
