<?php




  // @brief   fonctions liées à la base de donnée
  // @author  Cyril SANTUNE
  // @version 9 (2015-10-16): ajout de $tree_level_max




  // aller chercher dans les fichiers de configuration de
  // testlink le type de base de donnée
  @include_once('config_db.inc.php');
  // mysql ou postgres ou mssql
  global $database_name;
  $database_name = DB_TYPE;




  // @brief  fournir l'id, le nom, le parent etc d'un testsuite en fonction de son id
  //         rempli également le variable global $table_results
  // @return un tableau avec les infos du testsuite
  function add_or_get_testsuite_info($testsuite_id)
  {
    $db_table_node_types = $GLOBALS['db_table_node_types']; 
    $table_results = $GLOBALS['table_results']; 
    $output = array();


    // ce testsuite n'existe pas dans la table
    if($table_results[$testsuite_id] == null)
    {
      $sql = " SELECT NH.id,
        NH.name,
        NH.node_order,
        NH.parent_id,
        NHP.node_type_id AS parent_node_type_id
        FROM nodes_hierarchy NH
        JOIN nodes_hierarchy NHP
        ON NH.parent_id = NHP.id
        WHERE NH.node_type_id = ".$db_table_node_types['testsuite']."
        AND NH.id = ".$testsuite_id;
      $request = cs_database_query($sql);
      $result = cs_database_fetch_object($request);
      if($result != null)
      {
        $output["id"] = $result->id;
        $output["name"] = $result->name;
        $output["node_order"] = $result->node_order;
        $output["parent_id"] = $result->parent_id;
        if($result->parent_node_type_id == $db_table_node_types['testproject'])
        {
          // niveau dans l'arbre
          $output["level"] = 0;
        }
        else
        {
          $parent_id = $output["parent_id"];
          add_or_get_testsuite_info($parent_id);
          // la variable global a pu changer
          $table_results = $GLOBALS['table_results']; 
          $output["level"] = $table_results[$parent_id]["level"] + 1;
        }
      }
      // sauver dans la variable globale
      $table_results[$output["id"]] = $output;
      $GLOBALS['table_results'] = $table_results;
    }
    else
    {
      $output = $table_results[$testsuite_id];
    }
    return $output;
  }




  // @brief créer une vue au debut de la session utilisateur
  function create_views()
  {
    $sql = "CREATE VIEW cs_vw_testplans AS
      SELECT TP.testplan_id,
      TP.tcversion_id,
      NH_TCV.parent_id AS tc_internal_id,
      NH_TCASE.parent_id AS testsuite_id,
      E1.status, 
      E1.build_id,
      E1.id AS execution_id
      FROM testplan_tcversions TP
      LEFT OUTER JOIN executions E1
      ON TP.testplan_id = E1.testplan_id 
      AND TP.tcversion_id = E1.tcversion_id
      JOIN nodes_hierarchy NH_TCV
      ON NH_TCV.id = TP.tcversion_id
      JOIN nodes_hierarchy NH_TCASE
      ON NH_TCV.parent_id = NH_TCASE.id";
    $request = cs_database_query($sql);
  }




  // @brief  fetch en fonction de la base de donnée
  function cs_database_fetch_object($query_result, $database)
  {
    $database = $GLOBALS['database_name'];
    if($database == "mysql")
    {
      $output = mysql_fetch_object($query_result);
    }
    if($database == "postgres")
    {
      $output = pg_fetch_object($query_result);
    }
    if($database == "mssql")
    {
      $output = mssql_fetch_object($query_result);
    }
    return $output;
  }




  // @brief  query en fonction de la base de donnée
  function cs_database_query($sql)
  {
    $database = $GLOBALS['database_name'];
    if($database == "mysql")
    {
      $output = mysql_query($sql);
    }
    if($database == "postgres")
    {
      $output = pg_exec($sql);
    }
    if($database == "mssql")
    {
      $output = mssql_query($sql);
    }
    return $output;
  }




  // @brief effacer la vue
  function delete_views()
  {
    $sql = "DROP VIEW cs_vw_testplans";
    $request = cs_database_query($sql);
  }




  // @brief   générer un tableau qui contient les types de node
  function generate_db_table_node_types()
  {
    global $db_table_node_types;
    // récupérer les types de noeuds directement pour éviter les jointures
    //  pour connaitre l'id de testcase, il suffira de faire:
    //  $db_table_node_types["testcase"]
    // db_table_node_types contient
    //  ["testproject"] => "1"
    //  ["testsuite"] => "2"
    //  ["testcase"] => "3"
    //  etc
    $sql = "SELECT id,description FROM node_types";
    $request = cs_database_query($sql);
    while( $result = cs_database_fetch_object($request) )
    {
      $db_table_node_types[$result->description] = $result->id;
    }
  }




  // @brief  générer un tableau avec la liste des projets
  function generate_db_table_projects()
  {
    global $db_table_projects;
    $db_table_node_types = $GLOBALS['db_table_node_types'];
    $sql = "SELECT id, name 
      FROM nodes_hierarchy 
      WHERE node_type_id = '".$db_table_node_types["testproject"]."'";
    $request = cs_database_query($sql);
    while( $result = cs_database_fetch_object($request) )
    {
      $db_table_projects[$result->id]["id"] = $result->id;
      $db_table_projects[$result->id]["name"] = $result->name;
    }
  }




  // @brief créer le tableau $table_results et $stats_table
  //        sauver aussi le niveau max comme variable global $tree_level_max
  // @param array_build_id est une liste d'id de builds
  function generate_result_table($testproject_id, $testplan_id, $array_build_id, $show_coverage)
  {
    $db_table_node_types = $GLOBALS['db_table_node_types'];
    // pour les totaux
    global $stats_table;
    // pour le niveau max dans l'arbre
    global $tree_level_max;

    initialize_table_results($testplan_id);
    $table_results = $GLOBALS['table_results']; 
    $table_results = 
      quick_sort_testsuite_table($table_results);

    $list_build_id = implode(",",$array_build_id);

    $select = "SELECT 
      status,
      COUNT(status) AS number";
    $from = " FROM cs_vw_testplans TP";
    $where_clause_1 = " WHERE TP.testplan_id = ".$testplan_id;
    // éliminer les doublons
    $where_clause_2 = " AND (execution_id = (
      SELECT COALESCE(MAX(e.id),0) AS execution_id
      FROM executions E
      WHERE  E.testplan_id = TP.testplan_id
      AND E.status IS NOT NULL
      AND E.tcversion_id = TP.tcversion_id
      GROUP BY tcversion_id) OR execution_id IS NULL)";

    // la build est défini
    // éliminer les doublons
    $where_clause_2_build= " AND (execution_id = (
      SELECT COALESCE(MAX(e.id),0) AS execution_id
      FROM executions E
      WHERE  E.testplan_id = TP.testplan_id
      AND E.status IS NOT NULL
      AND E.tcversion_id = TP.tcversion_id
      AND E.build_id IN (".$list_build_id.")
      GROUP BY tcversion_id) OR execution_id IS NULL)";
    $where_clause_2_build = " AND TP.build_id IN (".$list_build_id.")".$where_clause_2_build;


    $group_by = " GROUP BY status";


    // définir le résultat des tests pour chaque testsuite
    $level_max = 0;
    foreach($table_results as &$testsuite)
    {
      $testsuite_id = $testsuite["id"];
      $where_clause_3 = " AND testsuite_id = ".$testsuite_id;

      $sql = $select.$from.$where_clause_1.$where_clause_2.$where_clause_3.$group_by;
      if($array_build_id != null)
      {
        $sql = $select.$from.$where_clause_1.$where_clause_2_build.$where_clause_3.$group_by;
      }

      $request = cs_database_query($sql);
      while( $result = cs_database_fetch_object($request) )
      {
        // remplir le tableau
        // le nombre de passed, failed, etc
        if($result->status == "p")
          $status = "passed";
        if($result->status == "f")
          $status = "failed";
        if($result->status == "b")
          $status = "blocked";
        $table_results[$testsuite_id][$status] = $result->number;
      }

      // pour les not run, tous les tests d'un testsuite
      $sql = "SELECT COUNT(DISTINCT NH.id) AS number
        FROM nodes_hierarchy NH
        JOIN cs_vw_testplans TP
        ON NH.id = TP.tc_internal_id
        WHERE NH.parent_id = ".$testsuite_id."
        AND NH.node_type_id = ".$db_table_node_types['testcase']."
        AND TP.testplan_id= ".$testplan_id;
      $request = cs_database_query($sql);
      $result = cs_database_fetch_object($request);
      $table_results[$testsuite_id]["notrun"] = $result->number -
       ($table_results[$testsuite_id]["passed"] +
       $table_results[$testsuite_id]["failed"] +
       $table_results[$testsuite_id]["blocked"] );

      // sauvegarder le level max pour la suite
      if($testsuite["level"] > $level_max)
      {
        $level_max = $testsuite["level"];
      }

    }
    unset($testsuite);




    // calcul pour le couverture de tests
    if($show_coverage == "on")
    {
      $testcase_number_by_testsuite = get_testcase_number_by_testsuite($testproject_id);
      foreach($table_results as &$testsuite)
      {
        $testsuite["total"] = $testcase_number_by_testsuite[$testsuite["id"]]["testcase_number"];
      }
      // pour le total (nombre de testcase du project)
      $stats_table["total"] = $testcase_number_by_testsuite[$testproject_id]["testcase_number"];
    }



    // pour l'instant, si C a 2 testcases et D a 2 testcases,
    // mais qui C est inclus dans D, alors le nombre de testcases
    // est faux pour D. D = 2 + 2 = 4
    // Pour résoudre ce problème, il faut prendre les testsuites
    // les plus profond de l'arbre et remonter vers la racine 
    // avec une somme
    // $level_max contient le niveau le plus profond
    // Parcourir l'arbre en fonction des niveaux
    for($i = $level_max; $i > 0; $i = $i - 1)
    {
      // pour chaque testsuite du level
      foreach($table_results as &$testsuite)
      {
        if($testsuite["level"] == $i)
        {
          // ajouter le nombre de testcase à mon père
          $table_results[$testsuite["parent_id"]]["passed"] = 
            $table_results[$testsuite["parent_id"]]["passed"] +
            $testsuite["passed"];
          $table_results[$testsuite["parent_id"]]["failed"] = 
            $table_results[$testsuite["parent_id"]]["failed"] +
            $testsuite["failed"];
          $table_results[$testsuite["parent_id"]]["blocked"] = 
            $table_results[$testsuite["parent_id"]]["blocked"] +
            $testsuite["blocked"];
          $table_results[$testsuite["parent_id"]]["notrun"] = 
            $table_results[$testsuite["parent_id"]]["notrun"] +
            $testsuite["notrun"];
        }
      }
      unset($testsuite);
    }


    // enfin les totaux
    // pour chaque testsuite de la racine faire la somme des testsuites
    $stats_table["passed"] = 0;
    $stats_table["failed"] = 0;
    $stats_table["blocked"] = 0;
    $stats_table["notrun"] = 0;
    foreach($table_results as &$testsuite)
    {
      if($testsuite["level"] == 0)
      {
        $stats_table["passed"] = $stats_table["passed"] + $testsuite["passed"];
        $stats_table["failed"] = $stats_table["failed"] + $testsuite["failed"];
        $stats_table["blocked"] = $stats_table["blocked"] + $testsuite["blocked"];
        $stats_table["notrun"] = $stats_table["notrun"] + $testsuite["notrun"];
      }
    }

    $GLOBALS["tree_level_max"] = $level_max;
    $GLOBALS["table_results"] = $table_results;
  }




  // @brief initialiser le tableau des résultats
  function initialize_table_results($testplan_id)
  {
    $db_table_node_types = $GLOBALS['db_table_node_types']; 
    global $table_results;
    $table_results = array();

    // problème avec cette requête, un testsuite sans testcase enfant
    // n'apparait pas
    $sql = "SELECT DISTINCT
      TP.testsuite_id AS id
      FROM cs_vw_testplans TP
      WHERE TP.testplan_id = '".$testplan_id."'";
    $request = cs_database_query($sql);
    while( $result = cs_database_fetch_object($request) )
    {
      add_or_get_testsuite_info($result->id);
    }
  }




  // @brief  tableau des builds
  // @param  testplan_id
  // @return tableau des builds
  function get_table_builds($testplan_id)
  {
    $table_builds = array();
    $sql = "SELECT id,
      testplan_id,
      name,
      active,
      is_open,
      creation_ts,
      release_date,
      closed_on_date
      FROM builds
      WHERE testplan_id = ".$testplan_id."
      ORDER BY release_date";
    $request = cs_database_query($sql);
    while( $result = cs_database_fetch_object($request) )
    {
      $table_builds[$result->id]["id"] = $result->id;
      $table_builds[$result->id]['testplan_id'] = $result->testplan_id;
      $table_builds[$result->id]['name'] = $result->name;
      $table_builds[$result->id]['active'] = $result->active;
      $table_builds[$result->id]['is_open'] = $result->is_open;
      $table_builds[$result->id]['creation_ts'] = $result->creation_ts;
      $table_builds[$result->id]['release_date'] = $result->release_date;
      $table_builds[$result->id]['closed_on_date'] = $result->closed_on_date;
    }
    return $table_builds;
  }




  // @brief  tableau des testplans
  // @param  project_id
  // @return tableau des testplans
  function get_table_testplans($project_id)
  {
    $db_table_node_types = $GLOBALS['db_table_node_types'];
    $table_testplans = array();
    if(isset($project_id))
    {
      $sql = "SELECT id, name
        FROM nodes_hierarchy 
        WHERE node_type_id = '".$db_table_node_types["testplan"]."'
        AND parent_id = ".$project_id;
      $request = cs_database_query($sql);
      while( $result = cs_database_fetch_object($request) )
      {
        $table_testplans[$result->id]["id"] = $result->id;
        $table_testplans[$result->id]["name"] = $result->name;
      }
    }
    return $table_testplans;
  }




  // @brief  connaître le nombre de testcase pour un testsuite ou un project
  //         peut importe le testplan
  // @param  $id du project ou du testsuite
  // @return un tableau avec le nombre de testcase par testsuite
  function get_testcase_number_by_testsuite($id)
  {
    $db_table_node_types = $GLOBALS['db_table_node_types'];
    $level = 0;
    
    // créer une liste d'id de testsuite compris dans ce projet
    $testsuite_list = array();
    // nombre de testcases
    $sql = "SELECT COUNT(TC.id) as number
      FROM nodes_hierarchy TC
      WHERE node_type_id = ".$db_table_node_types['testcase']."
      AND TC.parent_id = ".$id;
    $request = cs_database_query($sql);
    $result = cs_database_fetch_object($request);
    $testsuite_list[$id]["id"] = $id;
    $testsuite_list[$id]["testcase_number"] = $result->number;

    // select de tous les testsuites enfants
    $sql = "SELECT id
      FROM nodes_hierarchy
      WHERE node_type_id = ".$db_table_node_types['testsuite']."
      AND parent_id = ".$id;
    $request = cs_database_query($sql);
    while( $result = cs_database_fetch_object($request) )
    {
      $children = get_testcase_number_by_testsuite($result->id);
      // copie du résultat dans le tableau courant
      // et ajout au parent
      while( $child = array_shift($children) )
      {
        $testsuite_list[$child["id"]] = $child;
        // compute permet de savoir si ce fils à déjà été ajouté dans un noeud
        if($child["compute"] == "yes")
        {
          $testsuite_list[$child["id"]]["compute"] = "no";
          $testsuite_list[$id]["testcase_number"] = 
            $testsuite_list[$id]["testcase_number"] + $child["testcase_number"];
        }
      }
    }


    // je viens d'être calculer mon parent peut m'ajouter dans sa somme
    $testsuite_list[$id]["compute"] = "yes";
    return $testsuite_list;
  }

    


?>
