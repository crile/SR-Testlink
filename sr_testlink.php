<?php



/*

Page principale

2015-10-01(Cyril SANTUNE): suppression de la ligne *total* du tableau
2015-09-30(Cyril SANTUNE): rendre la page accessible sans login
2015-06-19(Cyril SANTUNE): remplacement de la fonction round pour les moyennes
puisque round peut faire un arrondie superieur donc par exemple obtenir 100%
alors que tous les tests ne sont pas passés. Ajout de class name sur les TRs du
tableau résultat pour un filtrage futur (dans le javascript)
2015-06-18(Cyril SANTUNE): ajout de graphique camembert
2015-06-16(Cyril SANTUNE): changement du texte pour le contact de l'administrateur

*/



// afficher les erreurs php
ini_set('display_errors','On');
error_reporting(E_ALL);



require("sr_sql.php");
require("sr_javascript.php");
require("sr_tools.php");



// ====== code extrait de index.php pour la connexion à la base ======
//
function initEnv() {
	$iParams = array("reqURI" => array(tlInputParameter::STRING_N,0,4000));
	$pParams = G_PARAMS($iParams);
	$args = new stdClass();
	$args->reqURI = ($pParams["reqURI"] != '') ? $pParams["reqURI"] : 'lib/general/mainPage.php';
	$args->tproject_id = isset($_REQUEST['tproject_id']) ? intval($_REQUEST['tproject_id']) : 0;
	$args->tplan_id = isset($_REQUEST['tplan_id']) ? intval($_REQUEST['tplan_id']) : 0;
	$gui = new stdClass();
	$gui->title = lang_get('main_page_title');
	$gui->titleframe = "lib/general/navBar.php?tproject_id={$args->tproject_id}&tplan_id={$args->tplan_id}" .
					   "&updateMainPage=1";
	$gui->mainframe = $args->reqURI;
	return array($args,$gui);
}
require_once('lib/functions/configCheck.php');
checkConfiguration();
require_once('config.inc.php');
require_once('common.php');
doSessionStart();
unset($_SESSION['basehref']);  // will be very interesting understand why we do this
setPaths();
list($args,$gui) = initEnv();
doDBConnect($db,database::ONERROREXIT);
$user = new tlUser();
$user->dbID = $_SESSION['currentUser']->dbID;
$user->readFromDB($db);
$dbSecurityCookie = $user->getSecurityCookie();
// ====== fin du code testlink ======



// Gestion de la session php

// créer un session php
// inutile déjà fait dans le code testlink
// session_start();

// detruction des variables session
if(isset($_GET["session_reset"])) {
	// session destroy est trop "violent" (logout de testlink)
	//session_destroy();
	unset($_SESSION['db_table_projects']);
}
// vérifier si les tables sont déjà chargées
// projets
if(isset($_SESSION['db_table_projects'])) {
	$db_table_projects = $_SESSION['db_table_projects'];
}
else {
	$db_table_projects = generate_db_table_projects();
	$_SESSION['db_table_projects'] = $db_table_projects;
}
// views
if(isset($_GET["admin"])) {
	echo("<BR>Admin mode<BR>");
	delete_views();
	create_views();
}



// construire un tableau de résultat si le formulaire est rempli
if(isset($_GET['tp_id']) and isset($_GET['pj_id'])) {
	if(isset($_GET['show_coverage']))
		$show_coverage=true;
	else
		$show_coverage=false;
	// il y a au moins une build de selectionné
	if(isset($_GET['bd_id'])) {
		$table_results=generate_result_table($_GET['pj_id'], $_GET['tp_id'],
		$_GET['bd_id'], $show_coverage);
	}
	else {
		$table_results=generate_result_table($_GET['pj_id'], $_GET['tp_id'],
		null, $show_coverage);
	}
}



echo("<HTML>
	<HEAD>
		<TITLE>Statistics</TITLE>
		<LINK REL='stylesheet' TYPE='text/css' HREF='sr_testlink.css'/>
	</HEAD>
	<BODY>
		<TABLE style='width:100%'>
			<TR>
				<TD style='width:80%'></TD>
				<TD style='text-align:right'>
					<A STYLE='font-size:12px' HREF='sr_changelog.html'>Version
					3.0</A>
				</TD>
			</TR>
		</TABLE>
");



// form
include("sr_form.php");



// afficher les resultats
echo("<TABLE CLASS='table_result'>
	<TR>
	<TH CLASS='table_result_th'>Testsuite</TD>
	<TH CLASS='table_result_th' COLSPAN=2>Status</TD>");
if($show_coverage)
	echo("<TH CLASS='table_result_th'>Coverage %</TD>");
else
	echo("<TH CLASS='table_result_th'>Executed %</TD>");
echo("</TR>");
if(isset($table_results)) {
	foreach($table_results as &$testsuite) {
		// pourcentage de passed ou failed
		$percent_executed = (100 * ($testsuite["passed"] +
		$testsuite["failed"])) / ($testsuite["notrun"] + $testsuite["passed"] +
		$testsuite["failed"] + $testsuite["blocked"]);
		$percent_executed = floor($percent_executed);
		// les TR du tableau auront plusieurs class suivant différent critère
		// ainsi il est possible de les faire disparaitre facilement
		$class_name = "";
		// les sous testsuite
		if($testsuite["level"] >= 3)
			$class_name = $class_name." hide_level";
		// cacher les testsuites executées à 100%
		if($percent_executed == 100)
			$class_name = $class_name." hide_complete";
		// cacher les testsuites 100% passed
		if($testsuite["notrun"] + $testsuite["blocked"] + $testsuite["failed"]
		== 0)
			$class_name = $class_name." hide_passed";
		// dans le javascript je peux ensuite utiliser ce class_name comme
		// critère pour cacher ou non une ligne
		echo("<TR CLASS='".$class_name."'> <TD CLASS='table_result_td'>");
		// créer des espaces en fonction du level
		echo(str_repeat("&nbsp;", $testsuite["level"] * 3));
		echo($testsuite["name"]);
		echo("</TD><TD CLASS='table_result_td_status'>");
		echo(get_status_html_table($testsuite["notrun"], $testsuite["passed"],
			$testsuite["failed"], $testsuite["blocked"]));
		echo("</TD><TD CLASS='table_result_td_status'><DIV><CANVAS
			ID='".$testsuite["id"]."' WIDTH='40' HEIGHT='40'> </CANVAS></DIV>");
		echo("<SCRIPT TYPE='text/javascript'>");
		echo("draw_pie_chart(".$testsuite["id"].", ".$testsuite["notrun"].",
			".$testsuite["passed"].", ".$testsuite["failed"].",
			".$testsuite["blocked"].")");
		echo("</SCRIPT></TD>
			<TD CLASS='table_result_td'>");
		echo(get_percent_html_table($percent_executed)); echo("</TR>");
	}
}
unset($testsuite);
echo("</TABLE>");

// cacher les testsuites de bas niveau si la case est cochée
// impossible de le faire avant puisque l'id n'existe pas encore
if( isset($_GET['checkbox_hide_level']) ) {
	// forcer l'appel de la fonction javascript pour cacher les testsuites de petit level
	echo("<SCRIPT TYPE='text/javascript'>toggle_testsuite();</SCRIPT>");
}



echo("</BODY></HTML>");



?>
