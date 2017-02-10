<?php



/*

HTML FORM 

2017-02-10(Cyril SANTUNE): Diplay "filter" on the testplans based on its first letter
2017-01-12(Cyril SANTUNE): Code cleaning
2017-01-11(Cyril SANTUNE): Modification de la partie testplans
2015-10-12(Cyril SANTUNE): ajouter *executed %* dans la légende
2015-10-08(Cyril SANTUNE): ajouter la légende
2015-10-07(Cyril SANTUNE): cocher la checkbox(low level testsuite)
 par défaut
2015-06-19(Cyril SANTUNE): modification de la mise en page pour le partie
 "display" et ajout de la checkbox "full passed testsuite". onload actif sur
 les testplans à nouveau.
2015-06-18(Cyril SANTUNE): ajouter un bouton pour cacher les testsuites executés
 à 100 pourcent
2015-06-18(Cyril SANTUNE): supprimer le onload pour les testplans

*/



echo("
<FORM ID='sr_form' METHOD=\"get\" ACTION=\"#\">
<BR><FIELDSET>
<LEGEND>Project & Testplan</LEGEND>
Project :
<SELECT
	ID='pj_id'
	NAME='pj_id'
	ACTION='#'
	onchange='reload(\"pj_id\",\"\")'>
<OPTION VALUE=''></OPTION>");
// pour tous les projets
foreach($db_table_projects as &$project) {
	// après avoir recharger le page, placer la liste sur la bonne option
	if(isset($_GET['pj_id'])) {
		if($project["id"] == $_GET['pj_id']) {
			// utf8_decode remplace le utf8 en ISO-8859-1 pour l'affiche dans le
			// browser, problème avec le caractère numéro 
			echo("<OPTION SELECTED 
				VALUE='".$project["id"]."'>"
				.utf8_decode($project["name"])."</OPTION>");
		}
		else {
			echo("<OPTION VALUE='".$project["id"]."'>"
			.utf8_decode($project["name"])."</OPTION>");
		}
	}
	else {
		echo("<OPTION VALUE='".$project["id"]."'>"
		.utf8_decode($project["name"])."</OPTION>");
	}
}
echo("</SELECT>");



// testplan list
// FIXME lorsqu'il y a un build de selectionné si l'on change de testplan,
// le champs build n'est pas effacé donc il reste sur un build d'un testplan
// non selectionné
// verify if the project is set
if(isset($_GET['pj_id'])) {
	$db_table_testplans = get_table_testplans($_GET['pj_id']);
	$checkbox_grid = "<BR>Testplan: ";
	// filter the testplan by the first letter
	$prefix = "A";
	// flag to know what to do about the ul tag (open, close, in)
	$ul_state = "open";
	foreach($db_table_testplans as &$testplan) {
		while($testplan["name"][0] != $prefix) {
			$prefix++;
			if($ul_state == "in")
				$ul_state = "close";
		}
		if($ul_state == "close") {
			$checkbox_grid .= "</UL>";
			$ul_state = "open";
		}
		if($ul_state == "open") {
			$checkbox_grid .= "<P>".$prefix."...</P>";
			$checkbox_grid .= "<UL CLASS='checkbox_grid'>";
			$ul_state = "in";
		}
		$checkbox_grid .= "<LI><INPUT TYPE='checkbox' NAME='tp_id[]'";
		// verify if the testplan is selected
		if(isset($_GET["tp_id"])) {
			if(in_array($testplan["id"], $_GET["tp_id"]))
				$checkbox_grid .= " CHECKED";
		}
		$checkbox_grid .= " VALUE='".$testplan["id"]."'/>";
		$checkbox_grid .= utf8_decode($testplan["name"]);
		$checkbox_grid .= "</LI>";
	}
	if($ul_state == "in")
		$checkbox_grid .= "</UL>";
	echo($checkbox_grid);
}
echo("</FIELDSET>");



// afficher uniquement si le testplan est selectionné
if(isset($_GET['tp_id'])) {
	// pour les builds
	// plutot qu'un select multiple faire plusieurs checkbox
	echo("<BR><FIELDSET>");
	echo("<LEGEND>Build</LEGEND>");
	// recuperer la liste de build pour ce testplan
	$db_table_builds = get_table_builds($_GET['tp_id']);
	// pour toutes les builds
	// pour la présentation sauter une ligne toutes les 4 builds
	$i = 1;
	$input = "<TABLE><TR>";
	foreach($db_table_builds as &$build) {
		$input = $input."<TD>";
		$input = $input."<INPUT TYPE='checkbox' NAME='bd_id[]'
		onchange='clean_build()'";
		// vérifier si la build est selectionnée
		// le tableau dans l'url "bd_id" contient les builds selectionnées
		// in_array permet de verifier si une valeur existe dans un tableau
		if(isset($_GET["bd_id"])) {
			if(in_array($build["id"], $_GET["bd_id"]))
				$input = $input." CHECKED";
		}
		$input = $input." value='".$build["id"]."'>";
		$input = $input.$build["name"]." (".$build["release_date"].")";
		$input = $input."</INPUT></TD>";
		// sauter une ligne toutes les 4 builds
		if($i == 4) {
			$input = $input."</TR><TR>";
			$i = 0;
		}
		$i+=1;
	}
	$input = $input."</TR></TABLE>";
	echo($input);
	echo("</FIELDSET>");
	// pour la couverture
	echo("<BR><FIELDSET>");
	echo("<LEGEND>Coverage</LEGEND>");
	echo("Show coverage (increase report loading time):");
	// après avoir recharger le page, placer la liste sur la bonne option
	$input = "<INPUT TYPE='checkbox' NAME='show_coverage'";
	if(isset($_GET['show_coverage']))
		$input = $input." CHECKED";
	$input = $input."></INPUT>";
	echo($input);
	echo("</FIELDSET>");
}



// Légende
echo("
<BR>
<TABLE>
<TR>
<TD>
<BR>
<FIELDSET>
<LEGEND>Display</LEGEND>
Hide:
<UL>
	<LI>low level testsuite:");
$input = "<INPUT 
	TYPE='checkbox'
	ID='checkbox_hide_level'
	NAME='checkbox_hide_level'";
// cocher la checkbox si dans l'url il y a *checkbox_hide_level=on* ou
// si le testplan id n'est pas défini. Cela permet d'avoir la checkbox cocher par défaut.
if(isset($_GET['checkbox_hide_level']) or (! isset($_GET['tp_id']))) 
	$input = $input." CHECKED";
$input = $input." ONCLICK='toggle_testsuite()'></INPUT>";
echo($input);
echo("
	</LI>
	<LI>complete testsuite (100% executed): 
		<INPUT TYPE='checkbox' ID='checkbox_hide_complete'
		ONCLICK='toggle_testsuite()'></INPUT>
	</LI>
	<LI>full passed testsuite (all executed tests are passed): 
	<INPUT TYPE='checkbox' ID='checkbox_hide_passed' 
	ONCLICK='toggle_testsuite()'></INPUT>
	</LI>
</UL>
</FIELDSET>
</TD>
<TD STYLE='width:10%'></TD>
<TD><FIELDSET>
<LEGEND>Legend</LEGEND>
<TABLE>
	<TR>
		<TD>Not run</TD>
		<TD CLASS='sr_table_status_not_run sr_form_table_legend_td'></TD>
	</TR>
	<TR>
		<TD>Passed</TD>
		<TD CLASS='sr_table_status_passed sr_form_table_legend_td'></TD>
	</TR>
	<TR>
		<TD>Failed</TD>
		<TD CLASS='sr_table_status_failed sr_form_table_legend_td'></TD>
	</TR>
	<TR>
		<TD>Blocked</TD>
		<TD CLASS='sr_table_status_blocked sr_form_table_legend_td'></TD>
	</TR>
	<TR>
		<TD>Executed %</TD>
		<TD CLASS='sr_form_table_legend_td'>=</TD>
	<TD>
		(<SPAN CLASS='sr_table_status_passed'>Passed</SPAN> + 
		<SPAN CLASS='sr_table_status_failed'>Failed</SPAN>) / Total </TD>
	</TR>
</TABLE>
</FIELDSET>
</TD>
</TR>
</TABLE>
<BR>
<INPUT
	CLASS='sr_form_button'
	NAME='submit'
	TYPE='submit'
	VALUE=' Apply '/>
<A HREF='sr_testlink.php?session_reset=yes'>
	<BUTTON type='button' CLASS='sr_form_button'> Reset </BUTTON>
</A>
</FORM>
");



?>
