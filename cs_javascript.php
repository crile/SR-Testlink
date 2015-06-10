<?php




  // @brief   le code javascript
  // @author  Cyril SANTUNE
  // @version 8 (2015-10-06)




  echo("<SCRIPT TYPE='text/javascript'>");




  // @brief désactiver les éléments inutiles
  echo("function disableselect()");
  echo("{");
  if(isset($_GET['pj_id']) and strlen($_GET['pj_id']) > 0)
  {
    echo("document.getElementById('tp_id').disabled = false;");
  }
  else
  {
    echo("document.getElementById('tp_id').disabled = true;");
  }
  echo("}");


  // @brief lors d'un changement de projet ou testplan recharger la page
  //        de façon a supprimer les anciens paramètres de l'url
  // @param obj_id: l'id/nom de l'objet de la FORM/SELECT
  // @param urlsuffix: permet de garder les paramètres utiles
  echo("function reload(obj_id, urlsuffix)");
  echo("{");
  echo("var val = document.getElementById(obj_id).value;");
  echo("if(urlsuffix != '')");
  echo("val = val + '&' + urlsuffix;");
  // dans tous les cas garder l'état de la checkbox 'show coverage'
  echo("val = val + '&show_coverage=".$_GET['show_coverage']."';");
  echo("self.location = 'cs_stats.php?' + obj_id + '=' + val;");
  echo("}");




  echo("</SCRIPT>");




?>
