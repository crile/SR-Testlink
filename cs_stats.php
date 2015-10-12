<?php




  // @brief  page principale
  // @author Cyril SANTUNE
  // @date   2015-06-16: changement du texte pour le contact de l'administrateur
  // @date   2015-06-18: ajout de graphique camembert
  // @date   2015-06-19: remplacement de la fonction round pour les moyennes
  //         puisque round peut faire un arrondie superieur donc par exemple
  //         obtenir 100% alors que tous les tests ne sont pas passés.
  //         Ajout de class name sur les TRs du tableau résultat pour un 
  //         filtrage futur (dans le javascript)
  // @date   2015-09-30: rendre la page accessible sans login
  // @date   2015-10-01: suppression de la ligne *total* du tableau




  // afficher les erreurs php
  ini_set('display_errors','On');
  error_reporting(E_ALL);




  require("cs_sql.php");
  require("cs_javascript.php");
  require("cs_tools.php");




  // ====== code extrait de index.php pour la connexion à la base ======
  //
  function initEnv()
  {
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
  $redir2login = ( $securityCookie != $dbSecurityCookie );

  // ====== fin du code testlink ======





  // Gestion de la session php
  
  // créer un session php
  // inutile déjà fait dans le code testlink
  // session_start();

  // detruction des variables session
  if(isset($_GET["session_reset"]))
  {
    // session destroy est trop "violent" (logout de testlink)
    //session_destroy();
    unset($_SESSION['db_table_node_types']);
    unset($_SESSION['db_table_projects']);
    // le faire uniquement en début de session
    //unset($_SESSION['views_created']);
  }

  // vérifier si les tables sont déjà chargées
  // node_types
  if(isset($_SESSION['db_table_node_types']))
  {
    $db_table_node_types = $_SESSION['db_table_node_types'];
  }
  else
  {
    generate_db_table_node_types();
    $_SESSION['db_table_node_types'] = $db_table_node_types;
  }
  // projets
  if(isset($_SESSION['db_table_projects']))
  {
    $db_table_projects = $_SESSION['db_table_projects'];
  }
  else
  {
    generate_db_table_projects();
    $_SESSION['db_table_projects'] = $db_table_projects;
  }
  // views
  if(! isset($_SESSION['views_created']))
  {
    delete_views();
    create_views();
    $_SESSION['views_created'] = "yes";
  }





  // construire un tableau de résultat si le formulaire est rempli
  if($_GET['tp_id'] and $_GET['pj_id'])
  {
    // il y a au moins une build de selectionné
    if($_GET['bd_id'])
    {
      generate_result_table(
        $_GET['pj_id'],
        $_GET['tp_id'],
        $_GET['bd_id'],
        $_GET['show_coverage']);
    }
    else
    {
      generate_result_table(
        $_GET['pj_id'],
        $_GET['tp_id'],
        null,
        $_GET['show_coverage']);
    }
  }




  // AFFICHER LES RESULTATS




  echo("<HTML>\n");
  echo("  <HEAD>\n");
  echo("    <TITLE>Statistics</TITLE>\n");
  echo("    <LINK REL='stylesheet' TYPE='text/css' HREF='cs_stats.css'/>");
  echo("  </HEAD>\n");
  echo("<BODY ONLOAD='disableselect();'>\n");
  echo("<TABLE style='width:100%'>
      <TR>
        <TD style='width:80%'>
        </TD>
        <TD style='text-align:right'>
          <A STYLE='font-size:12px' HREF='cs_changelog.html'>Version 2.1</A>
        </TD>
        <TD style='text-align:right'>
          <A STYLE='font-size:12px' HREF='mailto:cyril.santune@gmail.com?Subject=testlink-cs_stats'>Contact administrator</A>
        </TD>
      </TR>
    </TABLE>");




  // FORM
  include("cs_form.php");




  echo("<TABLE CLASS='table_result'>");
  echo("<TR>");
  echo("<TH CLASS='table_result_th'>Testsuite</TD>");
  echo("<TH CLASS='table_result_th' COLSPAN=2>Status</TD>");
  echo("<TH CLASS='table_result_th'>Executed %</TD>");
  if($_GET['show_coverage'])
  {
    echo("<TH CLASS='table_result_th'>Coverage %</TD>");
  }
  echo("</TR>");






  foreach($table_results as &$testsuite)
  {

    // pourcentage de passed ou failed
    $percent_executed = (100 * ($testsuite["passed"] + $testsuite["failed"])) /
      ($testsuite["notrun"] + $testsuite["passed"] + $testsuite["failed"] +
      $testsuite["blocked"]);
    $percent_executed = floor($percent_executed);

    // les TR du tableau auront plusieurs class suivant différent critère ainsi 
    // il est possible de les faire disparaitre facilement
    $class_name = "";
    // les sous testsuite
    if( $testsuite["level"] >= 2 )
    {
      $class_name = $class_name." hide_level";
    }
    // cacher les testsuites executées à 100%
    if($percent_executed == 100)
    {
      $class_name = $class_name." hide_complete";
    }
    // cacher les testsuites 100% passed
    if( $testsuite["notrun"] + $testsuite["blocked"] + $testsuite["failed"] 
      == 0) 
    {
      $class_name = $class_name." hide_passed";
    }

    // dans le javascript je peux ensuite utiliser ce class_name comme critère 
    // pour cacher ou non une ligne
    echo("<TR CLASS='".$class_name."'>");
    echo("<TD CLASS='table_result_td'>");
    // créer des espaces en fonction du level
    echo(str_repeat("&nbsp;", $testsuite["level"] * 3));
    echo($testsuite["name"]);
    echo("</TD>");
    echo("<TD CLASS='table_result_td_status'>");
    echo(get_status_html_table(
      $testsuite["notrun"],
      $testsuite["passed"],
      $testsuite["failed"],
      $testsuite["blocked"]));
    echo("</TD>");
    echo("<TD CLASS='table_result_td_status'>");
    echo("<DIV><CANVAS ID='".$testsuite["id"]."' WIDTH='40' HEIGHT='40'>
      </CANVAS></DIV>");
    echo("<SCRIPT TYPE='text/javascript'>");
    echo("draw_pie_chart(".$testsuite["id"].",
      ".$testsuite["notrun"].",
      ".$testsuite["passed"].",
      ".$testsuite["failed"].",
      ".$testsuite["blocked"].")");
    echo("</SCRIPT>");
    echo("</TD>");
    echo("<TD CLASS='table_result_td'>");
    echo(get_percent_html_table($percent_executed));
    echo("</TD>");
    if($_GET['show_coverage'])
    {
      echo("<TD CLASS='table_result_td'>");
      $percent_coverage = (100 * ($testsuite["notrun"] + $testsuite["passed"] +
        $testsuite["failed"] + $testsuite["blocked"])) / $testsuite["total"];
      $percent_coverage = floor($percent_coverage);
      echo(get_percent_html_table($percent_coverage));
      echo("</TD>");
    }
    echo("</TR>");
  }
  unset($testsuite);
  echo("</TABLE>");


  // cacher les testsuites de bas niveau si la case est cochée
  // impossible de le faire avant puisque l'id n'existe pas encore
  if($_GET['checkbox_hide_level'] == "on")
  {
    // forcer l'appel de la fonction javascript pour cacher les testsuites de petit level
    echo("<SCRIPT TYPE='text/javascript'>");
    echo("toggle_testsuite();");
    echo("</SCRIPT>");
  }


  echo("</BODY>\n");
  echo("</HTML>\n");




?>
