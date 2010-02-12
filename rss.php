<?php 
$rss = simplexml_load_file('http://supertuxkart.blogspot.com/feeds/posts/default'); 
$arr_xml = $rss->entry[0]->title;
$arr_xml2 = $rss->entry[0]->link;
$content = '<a href="'.$arr_xml2[4]['href'].'">';
$content .= $arr_xml; 
$content .=  '</a>';

$fichier = fopen("rss", "w+");
fputs($fichier, $content);
fclose($fichier);
?>
