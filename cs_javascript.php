<?php




  // @brief   le code javascript
  // @author  Cyril SANTUNE
  // @version 9 (2015-10-16)




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
  echo("function reload(obj_id, urlsuffix)
    {
    var val = document.getElementById(obj_id).value;
    if(urlsuffix != '')
    val = val + '&' + urlsuffix;
    // dans tous les cas garder l'état de la checkbox 'show coverage'
    val = val + '&show_coverage=".$_GET['show_coverage']."';
    self.location = 'cs_stats.php?' + obj_id + '=' + val;
    }");




  echo("function toggle_testsuite(level,level_max)
    {
      // prendre tous les tr du document
      var tr = document.getElementsByTagName('tr');
      var hide_name = [];
      // forcer l'entier sinon si level = 1, level + 1 = 11 :)
      var level = parseInt(level);

      // level qu'il faut cacher
      for(i = level; i <= level_max; i = i + 1)
      {
        hide_name[i] = 'hide_' + i;
        for(j = 0; j < tr.length; j = j + 1)
        {
          if(tr[j].className == hide_name[i])
          { 
            // si il est caché l'afficher
            if(tr[j].style.display == 'table-row' ||
              tr[j].style.display == '')
            {
              tr[j].style.display = 'none';
            }
            else
            {
              tr[j].style.display = 'table-row';
            }
          }
        }
      }
    }");




  echo("</SCRIPT>");




?>
