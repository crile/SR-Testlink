<?php




  // @brief  le code javascript
  // @author Cyril SANTUNE
  // @date   2015-06-18: ajout de la m�thode draw_pie_chart qui utilise le 
  //         fichier javascript Chart.js pour les grapiques en camembert
  // @date   2015-06-19: modification de toggle_testsuite pour la prise en 
  //         compte de plusieurs crit�res




  // biblioth�que pour les graphiques
  echo("<SCRIPT SRC='cs_chart.js'></SCRIPT>");
  echo("<SCRIPT TYPE='text/javascript'>");




  // @brief d�sactiver les �l�ments inutiles
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
  //        de fa�on a supprimer les anciens param�tres de l'url
  // @param obj_id: l'id/nom de l'objet de la FORM/SELECT
  // @param urlsuffix: permet de garder les param�tres utiles
  echo("function reload(obj_id, urlsuffix)
    {
    var val = document.getElementById(obj_id).value;
    if(urlsuffix != '')
    val = val + '&' + urlsuffix;
    // dans tous les cas garder l'�tat de la checkbox 'show coverage'
    val = val + '&show_coverage=".$_GET['show_coverage']."';
    self.location = 'cs_stats.php?' + obj_id + '=' + val;
    }");




  // @brief cacher les colonnes du tableau r�sultat en fonction de la class du
  //        tr. Il y a 3 class possible:
  //        - hide_level: cacher parce que le level est faible
  //        - hide_complete : 100% des tests sont execut�s
  //        - hide_passed : 100% des tests sont passed
  echo("function toggle_testsuite()
    {
      var flag_hide;

      var hide_level = document.getElementById('checkbox_hide_level').checked;
      var hide_complete = 
        document.getElementById('checkbox_hide_complete').checked;
      var hide_passed = document.getElementById('checkbox_hide_passed').checked;

      // prendre tous les tr du document
      var tr = document.getElementsByTagName('tr');
      for(j = 0; j < tr.length; j = j + 1)
      {
        flag_hide = false;

        // une checkbox est coch� et la class correspond
        if( hide_level && (tr[j].className.search('hide_level')) > 0 )
          flag_hide = true;
        if( hide_complete && (tr[j].className.search('hide_complete')) > 0)
          flag_hide = true;
        if( hide_passed && (tr[j].className.search('hide_passed')) > 0)
          flag_hide = true;

        // je suis cach� et je ne correspond pas au class name
        // faire apparaitre
        if(tr[j].style.display == 'none' && ! flag_hide)
        {
       
          tr[j].style.display = 'table-row';
        }

        // je suis affich� et je correspond au class name
        // faire disparaitre
        if( (tr[j].style.display == 'table-row' || tr[j].style.display == '')
          && flag_hide )
        {
          tr[j].style.display = 'none';
        }
      }
    }");




  // @brief dessiner un diagrame camembert
  echo("function draw_pie_chart(obj_id, not_run, passed, failed, blocked)
    {
      var not_run_color = '#A1B5BA';
      var passed_color = '#00FF00';
      var failed_color = '#FF0000';
      var blocked_color = '#1ACAF6';
      if(blocked == null || blocked == 'undefined' || blocked == '')
        blocked = 0;

      var data = [
        {
          value: not_run,
          color: not_run_color,
          highlight: '#EEEEEE',
          label: 'not run'
        },
        {
          value: passed,
          color: passed_color,
          highlight: '#EEEEEE',
          label: 'passed'
        },
        {
          value: failed,
          color: failed_color,
          highlight: '#EEEEEE',
          label: 'failed'
        },
        {
          value: blocked,
          color: blocked_color,
          highlight: '#EEEEEE',
          label: 'blocked'
        }
      ];

      var ctx = document.getElementById(obj_id).getContext('2d');
      var myPieChart = new Chart(ctx).Pie(data, {segmentShowStroke: false});
    }");




  echo("</SCRIPT>");




?>
