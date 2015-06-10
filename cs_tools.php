<?php




  // @brief   fonction diverses
  // @author  Cyril SANTUNE
  // @version 8 (2015-10-06)




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
          // le niveau est supérieur comparer avec le père
          if($a["level"] < $b["level"])
          {
            $table_results = $GLOBALS['table_results']; 
            $flag_swap = compare_testsuite($a, $table_results[$b["parent_id"]]);
          }
          else
          {
            $table_results = $GLOBALS['table_results']; 
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
    $not_run_color = '#A1B5BA';
    $passed_color = '#00FF00';
    $failed_color = '#FF0000';
    $blocked_color = '#1ACAF6';
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

    $output = $output."<TABLE cellspacing='0' cellpadding='2'>";
    $output = $output."  <TR>";
    $output = $output."    <TD BGCOLOR='".$not_run_color."'>";
    $output = $output.$not_run;
    $output = $output."    </TD>";
    $output = $output."    <TD BGCOLOR='".$passed_color."'>";
    $output = $output.$passed;
    $output = $output."    </TD>";
    $output = $output."    <TD BGCOLOR='".$failed_color."'>";
    $output = $output.$failed;
    $output = $output."    </TD>";
    $output = $output."    <TD BGCOLOR='".$blocked_color."'>";
    $output = $output.$blocked;
    $output = $output."    </TD>";
    $output = $output."  </TR>";
    $output = $output."</TABLE>";

    return $output;
  }




  // @brief  créer un tableau pour simuler la représentation d'un pourcentage
  // @param  $percent
  // @return une chaine contenant un tableau
  function get_percent_html_table($percent)
  {
    $percent_color = '#00FF00';
    $empty_color = '#FF0000';

    $output = "<TABLE cellspacing='0' cellpadding='2'>";
    $output = $output."<TR>";
    $i = 1;
    while($i <= ($percent / 10))
    {
      $output = $output."<TD BGCOLOR='".$percent_color."'>";
      $output = $output." </TD>";
      $i = $i + 1;
    }
    while($i <= 10)
    {
      $output = $output."<TD BGCOLOR='".$empty_color."'>";
      $output = $output." </TD>";
      $i = $i + 1;
    }

    $output = $output."<TD>";
    $output = $output.$percent."%";
    $output = $output."</TD>";
    $output = $output."</TR>";
    $output = $output."</TABLE>";
    return $output;
  }




?>
