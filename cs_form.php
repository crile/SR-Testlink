<?php




  // @brief  FORM HTML
  // @author Cyril SANTUNE
  // @date   2015-10-18: supprimer le onload pour les testplans
  // @date   2015-10-18: ajouter un bouton pour cacher les testsuites executés
  //         à 100 pourcent
  // @date   2015-10-19: modification de la mise en page pour le partie 
  //         "display" et ajout de la checkbox "full passed testsuite".
  //         onload actif sur les testplans à nouveau.




  echo("<FORM METHOD=\"get\" ACTION=\"#\">\n");




  // pour le testproject

  echo("<BR><FIELDSET>");
  echo("<LEGEND>Project & Testplan</LEGEND>");
  echo("project :
    <SELECT ID='pj_id' NAME='pj_id' ACTION='#'
    onchange='reload(\"pj_id\",\"\")'>
    <OPTION VALUE=''></OPTION>\n");
  // pour tous les projets
  foreach($db_table_projects as &$project)
  {
    // aprÃ¨s avoir recharger le page, placer la liste sur la bonne option
    if($project["id"] == $_GET['pj_id'])
    {
      echo("<OPTION SELECTED VALUE='".$project["id"]."'>".$project["name"]."</OPTION>\n");
    }
    else
    {
      echo("<OPTION VALUE='".$project["id"]."'>".$project["name"]."</OPTION>\n");
    }
  }
  echo("</SELECT>\n");




  // pour les testplans

  echo("testplan :
    <SELECT ID='tp_id' NAME='tp_id' ACTION='#' 
    ONCHANGE='reload(\"tp_id\",\"pj_id=".$_GET['pj_id']."\")'>
    <OPTION VALUE=''></OPTION>");
  // pour tous les testplans
  $db_table_testplans = get_table_testplans($_GET['pj_id']);
  foreach($db_table_testplans as &$testplan)
  {
    // aprÃ¨s avoir recharger le page, placer la liste sur la bonne option
    if($testplan["id"] == $_GET['tp_id'])
    {
      echo "<OPTION SELECTED VALUE='".$testplan["id"]."'>".$testplan["name"]."</OPTION>\n";
    }
    else
    {
      echo "<OPTION VALUE='".$testplan["id"]."'>".$testplan["name"]."</OPTION>\n";
    }
  }
  echo("</SELECT>\n");
  echo("</FIELDSET>");




  // afficher uniquement si le testplan est selectionné
  if( $_GET['tp_id'] )
  {

    // pour les builds
    echo("<BR><FIELDSET>");
    echo("<LEGEND>Build</LEGEND>");
    // plutot qu'un select multiple faire plusieurs checkbox
    $db_table_builds = get_table_builds($_GET['tp_id']);
    // reconstruire un tableau d'après la liste de builds selectionnées 
    // ainsi la comparaison sera plus facile dans la boucle pour vérifier
    // "checked"
    $build_id_selected = array();
    $tmp = $_GET["bd_id"];
    foreach($tmp as &$id)
    {
      $build_id_selected[$id] = $id;
    }

    // pour toutes les builds
    // pour la présentation sauter une ligne toutes les 4 builds
    $i = 1;
    $input = "<TABLE CLASS='css_form_table'><TR>";
    foreach($db_table_builds as &$build)
    {
      $input = $input."<TD>";
      $input = $input."<INPUT TYPE='checkbox' NAME='bd_id[]'";
      // vérifier si la build est selectionnée
      if($build_id_selected[$build["id"]])
      {
        $input = $input." CHECKED";
      }
      $input = $input." value='".$build["id"]."'>";
      $input = $input.$build["name"]." (".$build["release_date"].")";
      $input = $input."</INPUT></TD>";
      // sauter une ligne toutes les 4 builds
      if($i == 4)
      {
        $input = $input."</TR><TR>";
        $i = 0;
      }
      $i = $i + 1;
    }
    $input = $input."</TR></TABLE>";
    echo($input);
    echo("</FIELDSET>");




    // coverture

    // pour la couverture
    echo("<BR><FIELDSET>");
    echo("<LEGEND>Coverage</LEGEND>");
    echo("Show coverage (increase report loading time):");
    // après avoir recharger le page, placer la liste sur la bonne option
    $input = "<INPUT TYPE='checkbox' NAME='show_coverage'";
    if($_GET['show_coverage'] == "on")
    {
      $input = $input." CHECKED";
    }
    $input = $input."></INPUT>";
    echo($input);
    echo("</FIELDSET>");




    // le bouton submit/OK
    echo("<BR><INPUT NAME='submit' TYPE='submit' value=' OK '/><BR>\n");




    // display
   
    // cacher les sous testsuites
    echo("<BR><BR><FIELDSET>");
    echo("<LEGEND>Display</LEGEND>");
    echo("hide:
      <UL CLASS='css_ul_form'>
      <LI>low level testsuite:
        <INPUT TYPE='checkbox' ID='checkbox_hide_level' 
        ONCLICK='toggle_testsuite()'></INPUT>
      </LI>");
    // cacher les testsuites à 100%
    echo("
      <LI>complete testsuite (100% executed): 
        <INPUT TYPE='checkbox' ID='checkbox_hide_complete' 
        ONCLICK='toggle_testsuite()'></INPUT>
      </LI>");

    // cacher les testsuites à 100% de passed
    echo("
      <LI>full passed testsuite (all executed tests are passed): 
        <INPUT TYPE='checkbox' ID='checkbox_hide_passed' 
        ONCLICK='toggle_testsuite()'></INPUT>
      </LI>
      </UL>");
    echo("</FIELDSET>");


  }




  echo("</FORM>\n");




?>
