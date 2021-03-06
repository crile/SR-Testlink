<?php



/*

Javascript Code

2017-01-12(Cyril SANTUNE): Retrieve colors of the pie chart from the CSS
2017-01-11(Cyril SANTUNE): nettoyage 
2015-10-01(Cyril SANTUNE): fix sur la fonction reload
2015-06-19(Cyril SANTUNE): modification de toggle_testsuite pour la prise
en compte de plusieurs critères
2015-06-18(Cyril SANTUNE): ajout de la méthode draw_pie_chart qui utilise
le fichier javascript Chart.js pour les grapiques en camembert

*/


// bibliothèque pour les graphiques
echo("<SCRIPT SRC='sr_chart.js'></SCRIPT>");
echo("<SCRIPT TYPE='text/javascript'>");



// lors d'un changement de projet ou testplan recharger la page
// de façon a supprimer les anciens paramètres de l'url
// obj_id: l'id/nom de l'objet de la FORM/SELECT
// urlsuffix: permet de garder les paramètres utiles
echo("function reload(obj_id, urlsuffix) {
var val=document.getElementById(obj_id).value;
if(urlsuffix!='') val=val+'&'+urlsuffix;");
// garder l'etat de show_coverage si il existe
if(isset($_GET['show_coverage']))
	echo("val=val+'&show_coverage=".$_GET['show_coverage']."';");
echo("self.location='sr_testlink.php?'+obj_id+'='+val;}");




// cacher les colonnes du tableau résultat en fonction de la class du
// tr. Il y a 3 class possible:
// - hide_level: cacher parce que le level est faible
// - hide_complete : 100% des tests sont executés
// - hide_passed : 100% des tests sont passed
echo("function toggle_testsuite() {
	var flag_hide;
	var hide_level = document.getElementById('checkbox_hide_level').checked;
	var hide_complete = 
	document.getElementById('checkbox_hide_complete').checked;
	var hide_passed = document.getElementById('checkbox_hide_passed').checked;
	// prendre tous les tr du document
	var tr = document.getElementsByTagName('tr');
	for(j = 0; j < tr.length; j = j + 1) {
		flag_hide = false;
		// une checkbox est coché et la class correspond
		if( hide_level && (tr[j].className.search('hide_level')) > 0 )
			flag_hide = true;
		if( hide_complete && (tr[j].className.search('hide_complete')) > 0)
			flag_hide = true;
		if( hide_passed && (tr[j].className.search('hide_passed')) > 0)
			flag_hide = true;
		// je suis caché et je ne correspond pas au class name
		// faire apparaitre
		if(tr[j].style.display == 'none' && ! flag_hide) 
			tr[j].style.display = 'table-row';
		// je suis affiché et je correspond au class name
		// faire disparaitre
		if( (tr[j].style.display == 'table-row' || tr[j].style.display == '')
		&& flag_hide )
			tr[j].style.display = 'none';
	}
}");



// retrieve CSS class with its name
echo("
function getStyle(className) {
	var styles = document.styleSheets[0].rules || document.styleSheets[0].cssRules;
    for (var x = 0; x < styles.length; x++) {
        if (styles[x].selectorText == className) {
			return styles[x];
        }
    }
}
");



// convert rgb string to hex color
// example 'rgb(209,209,209)' => '#d1d1d1'
echo("
// source: http://haacked.com/archive/2009/12/29/convert-rgb-to-hex.aspx/
function colorToHex(color) {
    if (color.substr(0, 1) === '#') {
        return color;
    }
    var digits = /(.*?)rgb\((\d+), (\d+), (\d+)\)/.exec(color);
    var red = parseInt(digits[2]);
    var green = parseInt(digits[3]);
    var blue = parseInt(digits[4]);
    var rgb = blue | (green << 8) | (red << 16);
    return digits[1] + '#' + rgb.toString(16);
};
");



// dessiner un pie chart
echo("function draw_pie_chart(obj_id, not_run, passed, failed, blocked) {
	// get color from the CSS
	var not_run_color =
		colorToHex(getStyle('.sr_table_status_not_run').style.backgroundColor);
	var passed_color =
		colorToHex(getStyle('.sr_table_status_passed').style.backgroundColor);
	var failed_color =
		colorToHex(getStyle('.sr_table_status_failed').style.backgroundColor);
	var blocked_color =
		colorToHex(getStyle('.sr_table_status_blocked').style.backgroundColor);
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
