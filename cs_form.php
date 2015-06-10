<?php




  // @brief   FROM HTML
  // @author  Cyril SANTUNE
  // @version 8 (2015-10-06)




  echo("<FORM METHOD=\"get\" ACTION=\"#\">\n");




  // pour le testproject

  echo("testproject:\n");
  $select_id = "pj_id";
  echo("<SELECT ID='".$select_id."' NAME='".$select_id."' ACTION='#'
    onchange='reload(\"".$select_id."\",\"\")'>\n");
  echo("  <OPTION VALUE=''></OPTION>\n");

  // pour tous les projets
  foreach($db_table_projects as &$project)
  {
    // après avoir recharger le page, placer la liste sur la bonne option
    if($project["id"] == $_GET[$select_id])
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

  echo("<BR>testplan:\n");
  echo("<SELECT ID='tp_id' NAME='tp_id' ACTION='#' onchange='reload(\"tp_id\",\"pj_id="
    .$_GET['pj_id']."\")'>\n");
  echo("<OPTION VALUE=''></OPTION>\n");

  // pour tous les testplans
  $db_table_testplans = get_table_testplans($_GET['pj_id']);
  foreach($db_table_testplans as &$testplan)
  {
    // après avoir recharger le page, placer la liste sur la bonne option
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




  // pour les builds

  // à afficher uniquement si le testplan est selectionné
  if( $_GET['tp_id'] )
  {
    echo("<BR>build:<BR>\n");
    // plutot qu'un select multiple faire plusieurs checkbox
    $db_table_builds = get_table_builds($_GET['tp_id']);
    // reconstruire un tableau d'après la liste de builds selectionnées 
    // ainsi la comparaison sera plus facile dans la boucle pour vérifier "CHECKED"
    $build_id_selected = array();
    $tmp = $_GET["bd_id"];
    foreach($tmp as &$id)
    {
      $build_id_selected[$id] = $id;
    }

    // pour tous les builds
    // pour la présentation sauter une ligne toutes les 3 builds
    $i = 1;
    echo("<TABLE><TR>");
    foreach($db_table_builds as &$build)
    {
      echo("<TD>");
      // vérifier si la build est selectionnée
      if($build_id_selected[$build["id"]])
      {
        echo("<INPUT TYPE='checkbox' NAME='bd_id[]' CHECKED value='".$build["id"]."'>");
      }
      else
      {
        echo("<INPUT TYPE='checkbox' NAME='bd_id[]' value='".$build["id"]."'>");
      }
      echo($build["name"]." (".$build["release_date"].")");
      echo("</INPUT>");
      echo("</TD>");
      // sauter une ligne toutes les 3 builds
      if($i == 3)
      {
        echo("</TR><TR>");
        $i = 1;
      }
      $i = $i + 1;
    }
    echo("</TR></TABLE>");
  }




  // pour la couverture

  echo("<BR>coverage:");
  // après avoir recharger le page, placer la liste sur la bonne option
  $input = "<INPUT TYPE='checkbox' NAME='show_coverage'";
  if($_GET['show_coverage'] == "on")
  {
    $input = $input." CHECKED";
  }
  $input = $input."></INPUT>";
  echo($input);




  // le bouton submit/OK
 
  echo("<BR><INPUT NAME='submit' TYPE='submit' value=' OK '/><BR>\n");




  echo("</FORM>\n");




?>
