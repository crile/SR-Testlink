<?php



/*

  fonction diverses

  2017-03-24(Cyril SANTUNE): Improve result table diplay
  2017-01-11(Cyril SANTUNE): nettoyage
  2015-10-08(Cyril SANTUNE): réécriture de la fonction get_percent_html_table()
	et utilisation du fichier css
  2015-06-19(Cyril SANTUNE): modification liée au feuille de style
  2015-06-16(Cyril SANTUNE): modification liée au feuille de style

*/



// créer un tableau html avec des couleurs pour les différents status
function get_status_html_table($not_run, $passed, $failed, $blocked) {
	$output = "";
	if($not_run == "")
		$not_run = 0;
	if($passed == "")
		$passed = 0;
	if($failed == "")
		$failed = 0;
	if($blocked == "")
		$blocked = 0;
	$output = $output."<TD CLASS='sr_table_status_not_run'>".$not_run."</TD>";
	$output = $output."<TD CLASS='sr_table_status_passed'>".$passed."</TD>";
	$output = $output."<TD CLASS='sr_table_status_failed'>".$failed."</TD>";
	$output = $output."<TD CLASS='sr_table_status_blocked'>".$blocked."</TD>";
	return $output;
}



// créer un tableau pour simuler la représentation d'un pourcentage
function get_percent_html_table($percent) {
	// pour afficher le pourcentage, je dois afficher un caractère par cellule
	// du tableau donc je commence par spliter la chaine en tableau
	$percent_array = str_split($percent);
	// ajouter le symbole %
	array_push($percent_array, '%');
	$output = "<TABLE id='sr_table_percent'><TR>";
	$i = 1;
	$j = 0;
	while($i <= 10) {
		$tmp_string = "";
		// mettre la bonne couleur sur la case
		if($i <= ($percent / 10))
			$output = $output."<TD CLASS='sr_table_status_passed'>";
		else
			$output = $output."<TD CLASS='sr_table_status_failed'>";

		// afficher le pourcentage en lettre a peu pres au milieu
		if($i >= 4) {
			// prendre les caractères un par un jusqu'a la fin du tableau
			if($j < count($percent_array))
				$tmp_string = $percent_array[$j];
			else
				$tmp_string = "";
			$j = $j + 1;
		}
		$output = $output.$tmp_string;
		$output = $output."</TD>";
		$i = $i + 1;
	}
	$output = $output."</TR></TABLE>";
	return $output;
}



?>
