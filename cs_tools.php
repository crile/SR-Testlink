<?php




  // @brief  fonction diverses
  // @author Cyril SANTUNE
  // @date   2015-06-16: modification liée au feuille de style
  // @date   2015-06-19: modification liée au feuille de style
  // @date   2015-10-08(Cyril SANTUNE): réécriture de la fonction 
  //         get_percent_html_table() et utilisation du fichier css




  // @brief  pour trier les testsuites 
  // @param  $a: testsuite
  // @param  $b: testsuite
  // @return  -1 si le testsuite $a est avant dans l'arbre
  //          +1 si le testsuite $a est après dans l'arbre
  function compare_testsuite($a, $b)
  {
    $flag_swap = 1;
    // inférieur pour 3 raison
    // c'est mon père
    // même parent voir node order
    // sinon voir les parents
    if($a["id"] == $b["parent_id"])
    {
      $flag_swap = -1;
    }
    else
    {
      if($a["parent_id"] == $b["id"])
      {
        $flag_swap = 1;
      }
      else
      {
        if($a["parent_id"] == $b["parent_id"])
        {
          if($a["node_order"] < $b["node_order"])
          {
            $flag_swap = -1;
          }
          else
          {
            $flag_swap = 1;
          }
        }
        else
        {
          $table_results = $GLOBALS['table_results']; 
          // le niveau est supérieur comparer avec le père
          if($a["level"] < $b["level"])
          {
            $flag_swap = compare_testsuite($a, $table_results[$b["parent_id"]]);
          }
          else
          {
            $flag_swap = compare_testsuite($table_results[$a["parent_id"]], $b);
          }
        }
      }
    }
    return $flag_swap;
  }




  // @brief  trier le tableau de testsuites
  function quick_sort_testsuite_table($table)
  {
    uasort($table, 'compare_testsuite');
    return $table;
  }



  // @brief créer un tableau html avec des couleurs pour les différents status
  function get_status_html_table($not_run, $passed, $failed, $blocked)
  {
    $output = "";

    if($not_run == "")
    {
      $not_run = 0;
    }
    if($passed == "")
    {
      $passed = 0;
    }
    if($failed == "")
    {
      $failed = 0;
    }
    if($blocked == "")
    {
      $blocked = 0;
    }

    $output = $output."<DIV id='cs_table_status'><TABLE><TR>";
    $output = $output."<TD CLASS='cs_table_status_not_run'>".$not_run."</TD>";
    $output = $output."<TD CLASS='cs_table_status_passed'>".$passed."</TD>";
    $output = $output."<TD CLASS='cs_table_status_failed'>".$failed."</TD>";
    $output = $output."<TD CLASS='cs_table_status_blocked'>".$blocked."</TD>";
    $output = $output."</TR></TABLE></DIV>";

    return $output;
  }





  // @brief  créer un tableau pour simuler la représentation d'un pourcentage
  // @param  $percent
  // @return une chaine contenant un tableau
  function get_percent_html_table($percent)
  {
    // pour afficher le pourcentage, je dois afficher un caractère par cellule
    // du tableau donc je commence par spliter la chaine en tableau
    $percent_array = str_split($percent);
    // ajouter le symbole %
    array_push($percent_array, '%');

    $output = "<DIV id='cs_table_percent'><TABLE><TR>";
    $i = 1;
    $j = 0;
    while($i <= 10)
    {
	  $tmp_string = "";

      // mettre la bonne couleur sur la case
      if($i <= ($percent / 10))
      {
        $output = $output."<TD CLASS='cs_table_percent_color_1'>";
      }
      else
      {
        $output = $output."<TD CLASS='cs_table_percent_color_2'>";
      }

      // afficher le pourcentage en lettre a peu pres au milieu
      if($i >= 4)
      {
        // prendre les caractères un par un jusqu'a la fin du tableau
        if($j < count($percent_array))
        {
          $tmp_string = $percent_array[$j];
        }
        else
        {
          $tmp_string = "";
        }
        $j = $j + 1;
      }

      $output = $output.$tmp_string;
      $output = $output."</TD>";
      $i = $i + 1;
    }

    $output = $output."</TR></TABLE></DIV>";
    return $output;
  }




?>
