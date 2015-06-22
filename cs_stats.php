<?php




  // @brief  page principale, le début est extrait de la page index.php de 
  //         testlink
  // @author Cyril SANTUNE
  // @date   2015-10-16: changement du texte pour le contact de l'administrateur
  // @date   2015-10-18: ajout de graphique camembert
  // @date   2015-10-19: remplacement de la fonction round pour les moyennes
  //         puisque round peut faire un arrondie superieur donc par exemple
  //         obtenir 100% alors que tous les tests ne sont pas passés.
  //         Ajout de class name sur les TRs du tableau résultat pour un 
  //         filtrage futur (dans le javascript)




  // afficher les erreurs
  ini_set('display_errors','On');
  error_reporting(E_ALL);




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

// verify the session during a work
$redir2login = true;
if( isset($_SESSION['currentUser']) )
{
  // Session exists we need to do other checks.
  //
  // we use/copy Mantist approach
  $securityCookie = tlUser::auth_get_current_user_cookie();
  $redir2login = is_null($securityCookie);

  if(!$redir2login)
  {
    // need to get fresh info from db, before asking for securityCookie
    doDBConnect($db,database::ONERROREXIT);
    $user = new tlUser();
    $user->dbID = $_SESSION['currentUser']->dbID;
    $user->readFromDB($db);
    $dbSecurityCookie = $user->getSecurityCookie();
    $redir2login = ( $securityCookie != $dbSecurityCookie );
  } 
}

if($redir2login)
{
  // destroy user in session as security measure
  unset($_SESSION['currentUser']);

  // If session does not exists I think is better in order to
  // manage other type of authentication method/schemas
  // to understand that this is a sort of FIRST Access.
  //
  // When TL undertand that session exists but has expired
  // is OK to call login with expired indication, but is not this case
  //
  // Dev Notes:
  // may be we are going to login.php and it will call us again!
  redirect(TL_BASE_HREF ."login.php");
  exit;
}




///////////////////////////////////////////////////////////////////////////////




  require("cs_sql.php");
  require("cs_javascript.php");
  require("cs_tools.php");




  // Gestion de la session php
  
  // créer un session php
  // inutile déjà fait dans le code testlink
  //session_start();

  // detruction des variables session
  if($_GET["session_reset"] == "yes")
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
  echo("    <TITLE>STATISTICS</TITLE>\n");
  echo("    <LINK REL='stylesheet' TYPE='text/css' HREF='cs_stats.css'/>");
  echo("  </HEAD>\n");
  echo("<BODY ONLOAD='disableselect();'>\n");
  echo("<TABLE style='width:100%;'><TR><TD>");
  echo("<A HREF='cs_stats.php?session_reset=yes'>Reset</A>\n");
  echo("</TD><TD style='text-align:right'>");
  echo("<A HREF='mailto:cyril.santune@gmail.com?Subject=testlink-cs_stats'>Contact administrator</A>");
  echo("</TD></TR></TABLE>");




  // FORM
  include("cs_form.php");




  echo("<H2>RESULTS</H2>");
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
  echo("<TR>");
  echo("<TH CLASS='table_result_th'>Total</TH>");
  echo("<TD CLASS='table_result_td_status'>");
  echo(get_status_html_table(
    $stats_table["notrun"],
    $stats_table["passed"],
    $stats_table["failed"],
    $stats_table["blocked"]));
  echo("</TD>");
  echo("<TD CLASS='table_result_td_status'>");
  echo("<DIV><CANVAS ID='total_id' WIDTH='40' HEIGHT='40'>
    </CANVAS></DIV>");
  echo("<SCRIPT TYPE='text/javascript'>");
  echo("draw_pie_chart('total_id',"
    .$stats_table["notrun"].","
    .$stats_table["passed"].","
    .$stats_table["failed"].","
    .$stats_table["blocked"].")");
  echo("</SCRIPT>");
  echo("</TD>");
  echo("<TD CLASS='table_result_td'>");
  $percent_executed = (100 * ($stats_table['passed'] +
    $stats_table['failed']) ) /
    ( $stats_table['notrun'] +
    $stats_table['passed'] +
    $stats_table['failed'] +
    $stats_table['blocked']);
  $percent_executed = floor($percent_executed);
  echo(get_percent_html_table($percent_executed));
  echo("</TD>");
  if($_GET['show_coverage'])
  {
    echo("<TD CLASS='table_result_td'>");
    $percent_coverage = (100 * ($stats_table["notrun"] + $stats_table["passed"] +
      $stats_table["failed"] + $stats_table["blocked"])) / $stats_table["total"];
    $percent_coverage = floor($percent_coverage);
    echo(get_percent_html_table($percent_coverage));
    echo("</TD>");
  }
  echo("</TR>");
  echo("</TABLE>");


  // cacher les testsuites de bas niveau si la case est cochée
  if($_GET['hd_low_ts'] == "on")
  {
    echo("<SCRIPT>toggle_testsuite(2,".$tree_level_max.")</SCRIPT>");
  }

  echo("</BODY>\n");
  echo("</HTML>\n");




?>
