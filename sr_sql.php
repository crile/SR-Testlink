<?php



/*

fonctions liées à la base de donnée

2017-01-03(Cyril SANTUNE): ajout d'un "order by" pour le requete sur les testplans
2015-06-18(Cyril SANTUNE): ajout de commentaire, initialisation de variable pour
éviter les érreurs
2015-06-16(Cyril SANTUNE): ajout de $tree_level_max

*/



// aller chercher dans les fichiers de configuration de
// testlink le type de base de donnée
@include_once('config_db.inc.php');
// mysql ou postgres ou mssql
global $database_name;
$database_name = DB_TYPE;



// @brief créer une vue au debut de la session utilisateur
function create_views() {
	echo("<BR>Create the view sr_vw_testplans");
	// Une fiche qui n'a jamais été executée n'apparait pas
	// dans cette vue, ci-dessous une requête qui renvoie des nulls dans ce
	// cas.
	//$sql="CREATE VIEW sr_vw_testplans
	//SELECT 
	//	TPV.testplan_id,
	//	NH_TCV.id AS tcversion_id,
	//	NH_TCV.parent_id AS tc_id,
	//	NH_TCASE.parent_id AS testsuite_id,
	//	TP.testproject_id
	//FROM nodes_hierarchy NH_TCV
	//LEFT OUTER JOIN testplan_tcversions TPV ON NH_TCV.id=TPV.tcversion_id
	//JOIN nodes_hierarchy NH_TCASE ON NH_TCV.parent_id=NH_TCASE.id
	//LEFT OUTER JOIN testplans TP ON TPV.testplan_id=TP.id";
	$sql="CREATE VIEW sr_vw_testplans AS
		SELECT 
			TPV.testplan_id,
			TPV.tcversion_id,
			NH_TCV.parent_id AS tc_id,
			NH_TCASE.parent_id AS testsuite_id,
			TP.testproject_id
		FROM testplan_tcversions TPV
		JOIN nodes_hierarchy NH_TCV ON NH_TCV.id=TPV.tcversion_id
		JOIN nodes_hierarchy NH_TCASE ON NH_TCV.parent_id = NH_TCASE.id
		JOIN testplans TP ON TPV.testplan_id=TP.id";
	$request=sr_database_query($sql);
}



// fetch en fonction de la base de donnée
function sr_database_fetch_object($query_result) {
	$database = $GLOBALS['database_name'];
	if($database == "mysql")
		$output = mysql_fetch_object($query_result);
	if($database == "postgres")
		$output = pg_fetch_object($query_result);
	if($database == "mssql")
		$output = mssql_fetch_object($query_result);
	return $output;
}



// query en fonction de la base de donnée
function sr_database_query($sql) {
	$database = $GLOBALS['database_name'];
	if($database == "mysql")
		$output = mysql_query($sql);
	if($database == "postgres")
		$output = pg_exec($sql);
	if($database == "mssql")
		$output = mssql_query($sql);
	return $output;
}



// effacer la vue
function delete_views() {
	echo("<BR>Delete the view sr_vw_testplans");
	$sql="DROP VIEW sr_vw_testplans";
	$request=sr_database_query($sql);
}



// générer un tableau avec la liste des projets
function generate_db_table_projects() {
	$output=array();
	$sql = "SELECT id, name 
	FROM nodes_hierarchy 
	WHERE node_type_id=1";
	$request = sr_database_query($sql);
	while( $result = sr_database_fetch_object($request) ) {
		$output[$result->id]["id"] = $result->id;
		$output[$result->id]["name"] = $result->name;
	}
	return $output;
}



// Retourner le nombre de testcase d'un testsuite (ne prend pas en compte
// les sous-testsuites, uniquement les enfants direct du testsuite)
function testcase_number_by_testsuite($testproject_id, $testsuite_id) {
	$number_of_testcases=0;
	// FIXME impossible d'utiliser COUNT avec une sous requête, donc je fais
	// une boucle pour compter les résultats
	$sql="SELECT tcversion_id,MAX(testplan_id)
		FROM sr_vw_testplans
		WHERE testproject_id=".$testproject_id." AND
		testsuite_id=".$testsuite_id."
		GROUP BY tcversion_id";
	$request=sr_database_query($sql);
	while($result=sr_database_fetch_object($request)) {
		$number_of_testcases+=1;
	}
	return $number_of_testcases;
}



// créer un arbre de testsuite representant les résultats des tests
// Ce n'est pas vraiment un arbre mais plutôt une liste avec une valeur
// "level" qui permet de déterminer la profondeur dans l'arbre
// node_id: id du testsuite/project par ou commencer
// testsuite_filter: le tableau avec l'agregation des testcases et leur
// status pour les testsuites (feuille dans l'arbre). Ce tableau servira de
// filtre pour la construction de l'arbre
// level: le niveau actuel dans l'arbre
function build_testsuite_tree($testproject_id, $node_id, $testsuite_filter,
$level, $show_coverage) {
	$tree=array();
	// verifier si le noeud doit etre ajouté ou non
	$filter_ok=false;
	$testsuite=array();
	$testsuite["passed"]=0;
	$testsuite["failed"]=0;
	$testsuite["blocked"]=0;
	$testsuite["notrun"]=0;
	// chercher tous les testsuites enfant du testsuite/projet courant
	// node_types=2 => testsuite
	$sql="SELECT id FROM nodes_hierarchy WHERE parent_id=".$node_id."
	AND node_type_id=2 ORDER BY node_order";
	$request=sr_database_query($sql);
	while($result=sr_database_fetch_object($request)) {
		$sub_tree=build_testsuite_tree($testproject_id, $result->id,
		$testsuite_filter, $level+1, $show_coverage);
		// si le sous arbre est vide, il n'y pas de sous testsuite qui
		// correspondent au filtre
		if(isset($sub_tree)) {
			// Agréger tous les sous arbres de niveau==n+1
			// Ajouter tous les sous arbres de niveau!=n+1 (pas d'agrégation)
			foreach($sub_tree as &$sub_testsuite) {
				$tmp=$level+1;
				// agréger les résultats uniquement pour le niveau n+1
				if($sub_testsuite["level"]==$tmp) {
					$testsuite["passed"]+=$sub_testsuite["passed"];
					$testsuite["failed"]+=$sub_testsuite["failed"];
					$testsuite["blocked"]+=$sub_testsuite["blocked"];
					$testsuite["notrun"]+=$sub_testsuite["notrun"];
				}
				// ajouter tous les sous arbres (testsuite) dans
				// l'arbre (ceux qui ne sont pas de niveau n+1 par rapport
				// au noeud courant)
				array_push($tree, $sub_testsuite);
				// définir le flag puisque le testsuite (node_id) n'est pas
				// forcement dans le filtre mais l'un de ces testsuites en
				// fait partie
				$filter_ok=true;
			}
		}
	}

	// soit le noeud fait partie du filtre, soit il faut montrer la
	// couverture (tous sélectionner), soit un sous testsuite fait partie du
	// filtre
	if(array_key_exists($node_id, $testsuite_filter) or $show_coverage or
		$filter_ok) {
		$testsuite["id"]=$node_id;
		$sql="SELECT name FROM nodes_hierarchy WHERE id=".$node_id;
		$request=sr_database_query($sql);
		$result=sr_database_fetch_object($request);
		$testsuite["name"]=$result->name;
		$testsuite["level"]=$level;
		// le testsuite courant est dans la liste filter, ajouter ces
		// résultats à celui de ses sous testsuites calculé précédemment
		if(array_key_exists($node_id, $testsuite_filter)) {
			// ajouter les résultat des testsuites au testsuite courant
			// "passed", "failed", ... peuvent contenir des valeurs
			// différentes de zero si le noeud courent contient des testcases
			// du filtre
			$testsuite["passed"]+=$testsuite_filter[$node_id]["passed"];
			$testsuite["failed"]+=$testsuite_filter[$node_id]["failed"];
			$testsuite["blocked"]+=$testsuite_filter[$node_id]["blocked"];
			// remplacer dans testsuite_filter le "notrun" par la valeur
			// total de "notrun" (nombre total de testcase dans ce testsuite
			// moin les exécutées)
			if($show_coverage) {
				$total=testcase_number_by_testsuite($testproject_id, $node_id);
				$testsuite_filter[$node_id]["notrun"] = $total -
					$testsuite_filter[$node_id]["passed"] -
					$testsuite_filter[$node_id]["failed"] -
					$testsuite_filter[$node_id]["blocked"];
			}
			$testsuite["notrun"]+=$testsuite_filter[$node_id]["notrun"];
		}
		else {
			// le testsuite n'est pas directement dans les filtres
			if($show_coverage) {
				$testsuite["notrun"]+=
				testcase_number_by_testsuite($testproject_id,
				$node_id);
			}
		}
		// si le testsuite contient des fiches qui n'ont jamais été exécuté
		// (dans aucun testplan) ne pas l'insérer (tout est égal à zero y
		// compris le "notrun" puisque le
		// testcase_number_by_testsuite retourne 0 si le testcase
		// n'est dans aucun testplan)
		$tmp = $testsuite["passed"] + $testsuite["failed"] +
		$testsuite["blocked"] + $testsuite["notrun"];
		if($tmp!=0) {
			// inserer au debut de la liste
			array_unshift($tree,$testsuite);
		}
	}
	return $tree;
}




// @brief créer le tableau $table_results et $stats_table
//        sauver aussi le niveau max comme variable global $tree_level_max
// @param array_build_id est une liste d'id de builds
function generate_result_table($testproject_id, $array_testplan_id,
	$array_build_id, $show_coverage) {
	// transformer le tableau array_testplan_id en chaine séparée par des
	// virgules
	$list_testplan_id = "";
	if($array_testplan_id!=null) {
		$list_testplan_id=implode(",", $array_testplan_id);
	}

	// Créer la liste de tous les testcases exécuté ou non
	// liste avec tous les testcases du plan de test avec leur status
	$list_testcase_exec=array();
	// même chose que pour array_testplan_id, array_build_id doit être
	// transformé en chaine séparée par des virgules s'il n'est pas vide
	$list_build_id = "";
	// la requete change en fonction des options (build selectionné ou non)
	if($array_build_id!=null) {
		$list_build_id=implode(",", $array_build_id);
		$sql2=$list_build_id;
	}
	else {
		$sql2="SELECT id FROM builds WHERE testplan_id IN
		(".$list_testplan_id.")";
	}
	// si la fiche a ete executée dans plus d'un build, prend le plus grand
	// id d'excution (max) et groupe les resultats sur l'id du testcase
	// (tcversion_id)
	// FIXME si les testplans contiennent des versions différentes de
	// testcases, il y aura des erreurs (même fiche compté n fois avec n le
	// nombre de version de la fiche)
	$sql="SELECT tcversion_id,status FROM executions WHERE id IN (SELECT
	MAX(id) FROM executions WHERE build_id IN (".$sql2.") GROUP BY
	tcversion_id) ORDER BY tcversion_id";
	$request=sr_database_query($sql);
	while($result=sr_database_fetch_object($request)) {
		$list_testcase_exec[$result->tcversion_id]["id"]=$result->tcversion_id;
		$list_testcase_exec[$result->tcversion_id]["status"]=$result->status;
	}
	// ajouter à la liste tous les tests non joués
	$sql="SELECT tcversion_id,MAX(testplan_id) FROM testplan_tcversions
	WHERE testplan_id IN (".$list_testplan_id.") GROUP BY tcversion_id";
	$request=sr_database_query($sql);
	while($result=sr_database_fetch_object($request)) {
		if(! isset($list_testcase_exec[$result->tcversion_id])) {
			$list_testcase_exec[$result->tcversion_id]["id"]=$result->tcversion_id;
			$list_testcase_exec[$result->tcversion_id]["status"]="n";
		}
	}

	// agréger la liste de testcase+execution par testsuite
	$list_testsuite_exec=array();
	foreach($list_testcase_exec as &$tc) {
		// récupérer le testsuite associé au testcase (tcversion_id)
		$sql="SELECT testsuite_id FROM sr_vw_testplans WHERE
		tcversion_id=".$tc["id"]." GROUP BY testsuite_id";
		$request=sr_database_query($sql);
		$result=sr_database_fetch_object($request);
		// initialiser les variables pour ce testsuite
		if(! isset($list_testsuite_exec[$result->testsuite_id])) {
			$list_testsuite_exec[$result->testsuite_id]["passed"]=0;
			$list_testsuite_exec[$result->testsuite_id]["failed"]=0;
			$list_testsuite_exec[$result->testsuite_id]["blocked"]=0;
			$list_testsuite_exec[$result->testsuite_id]["notrun"]=0;
		}
		// si le testsuite existe ajouter 1, au nombre de testcase en
		// fonction de son status
		if($tc["status"]=="p")
			$list_testsuite_exec[$result->testsuite_id]["passed"]+=1;
		if($tc["status"]=="f")
			$list_testsuite_exec[$result->testsuite_id]["failed"]+=1;
		if($tc["status"]=="b")
			$list_testsuite_exec[$result->testsuite_id]["blocked"]+=1;
		if($tc["status"]=="n")
			$list_testsuite_exec[$result->testsuite_id]["notrun"]+=1;
	}
	// construire l'arbre de testsuite à partir de l'agrégat
	$tree=build_testsuite_tree($testproject_id, $testproject_id,
	$list_testsuite_exec, 0, $show_coverage);
	return $tree;
}



// tableau des builds
function get_table_builds($array_testplan_id) {
	// generer une liste séparée par de virgule pour la requête SQL à partir
	// du tableau php
	$list_testplan_id = "";
	if($array_testplan_id != null)
		$list_testplan_id = implode(",", $array_testplan_id);
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
		WHERE testplan_id IN (".$list_testplan_id.")
		ORDER BY release_date";
	$request = sr_database_query($sql);
	while($result = sr_database_fetch_object($request)) {
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



// tableau des testplans
function get_table_testplans($project_id) {
	$table_testplans = array();
	if(isset($project_id)) {
		$sql = "SELECT id, name
			FROM nodes_hierarchy 
			WHERE node_type_id=5
			AND parent_id = ".$project_id."
			ORDER BY name ASC";
		$request = sr_database_query($sql);
		while( $result = sr_database_fetch_object($request) ) {
			$table_testplans[$result->id]["id"] = $result->id;
			$table_testplans[$result->id]["name"] = $result->name;
		}
	}
	return $table_testplans;
}



?>
