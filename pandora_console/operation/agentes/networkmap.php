<?PHP
// Copyright (c) 2008 Ramon Novoa, rnovoa@gmail.com
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

// Load global vars
require("include/config.php");

///////////////////////////////////////////////////////////////////////////////
// DOT related functions
///////////////////////////////////////////////////////////////////////////////

// Generate a dot graph definition for graphviz
function generate_dot() {
	$group_id = -1;
	
	// Open Graph
	$graph = open_graph('Pandora_FMS');

	// Get agent data	
	$agents = mysql_query('SELECT * FROM tagente ORDER BY id_grupo');
	while ($agent = mysql_fetch_array($agents)) {
		
		// Save node and parent information to define edges later
		$nodes[$agent['id_agente']] = $agent['nombre'];
		if ($agent['id_parent'] != "0") {
			$parents[$agent['nombre']] = $agent['id_parent'];
		}
		else {
			$orphans[$agent['nombre']] = 1;
		}
	
		// Start a new subgraph for the group
		//if ($group_id != $agent['id_grupo'] && isset($_POST['group'])) {
			// Close the previous group
			//if ($group_id != -1) {
			//	$graph .= close_group();
			//}
			//$group_id = $agent['id_grupo'];	
			//$graph .= open_group($group_id);
		//}

			
		// Add node
		$graph .= create_node($agent);
	}

	// Close the last subgraph
	//if (isset($_POST['group'])) {
	//	$graph .= close_group();
	//}

	// Create a central node if orphan nodes exist
	if (count($orphans) > 0) {
		$graph .= create_pandora_node ('Pandora_FMS');	
	}
	
	// Define edges
	foreach ($parents as $node => $parent_id) {
		$graph .= create_edge($node, $nodes[$parent_id]);
	
	}

	// Define edges for orphan nodes
	foreach(array_keys($orphans) as $node) {
		$graph .= create_edge('Pandora_FMS', $node);
	}
	
	// Close graph
	$graph .= close_graph();
	
	return $graph;
}

// Returns an edge definition
function create_edge($head, $tail) {

	$edge = str_replace("-", "_", $head) . ' -- ' . str_replace("-", "_", $tail) . '[color="grey35", headclip=false, tailclip=false];';
	return $edge;
}

// Returns a node definition
function create_node($agent) {
	$bad_modules = mysql_query('SELECT estado FROM tagente_estado AS e,
	                           tagente_modulo AS m
	                           WHERE m.id_agente=' . $agent['id_agente'] . 
	                           ' AND m.id_tipo_modulo in (2, 6, 9, 18, 21, 100)
	                           AND e.id_agente_modulo = m.id_agente_modulo
	                           AND e.datos = 0');

	// Set node status
	if (mysql_num_rows($bad_modules) > 0) {
		$status_color = "orangered1";
	}
	else {
		$status_color = "chartreuse1";
	}

	// Set node icon
	if (file_exists('images/networkmap/' . $agent['id_os'] . '.png')) { 
		$img_node = 'images/networkmap/' . $agent['id_os'] . '.png';
	}
	else {
		$img_node = 'images/networkmap/0.png';
	}
	
	$node = str_replace("-", "_", $agent['nombre']) . ' [ color="' . $status_color . '", fontsize=10, style="filled", fixedsize=true, width=0.6, height=0.6, label=<<TABLE BORDER="0">
		  <TR><TD><IMG SRC="' . $img_node . '"/></TD></TR>
		  <TR><TD>\N</TD></TR></TABLE>>,
		  shape="ellipse", tooltip="' . $agent['nombre'] . ' (' . $agent['direccion'] . ')", URL="'
		  . 'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='
		  . $agent['id_agente'] . '"];';

	return $node;
}

// Returns the definition of the central module
function create_pandora_node($name) {

	$node = $name . ' [ color="black", fontsize=10, style="filled", fixedsize=true, width=1.4, height=0.6, label=<<TABLE BORDER="0">
		  <TR><TD><IMG SRC="images/networkmap/pandora_node.png"/></TD></TR>
		  <TR><TD BGCOLOR="white">\N</TD></TR></TABLE>>,
		  shape="ellipse", tooltip="' . $name . '", URL="index.php?sec=estado&sec2=operation/agentes/estado_grupo" ];';

	return $node;
}

// Opens a group definition
function open_group($id) {
	$img = 'images/' . dame_grupo_icono($id) . '.png';
	$name = dame_nombre_grupo($id);
	
	$group = 'subgraph cluster_' . $id . 
	         ' { style=filled; color=darkolivegreen3; label=<<TABLE BORDER="0">
		     <TR><TD><IMG SRC="' . $img . '"/></TD><TD>' . $name . '</TD></TR>
		     </TABLE>>; tooltip="' . $name . '";
		     URL="index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id='
			 . $id . '";';

	return $group;
}

// Closes a group definition
function close_group() {
	return '}';
}

// Opens a graph definition
function open_graph($root) {
	global $config, $layout, $nooverlap, $pure, $zoom;
	$overlap = 'compress';
	$size_x = 8;
	$size_y = 6;
	$size = '';

	if ($layout == '' || $layout == 'radial') {
		$overlap = 'true';
	}

	if ($nooverlap != '') {
		$overlap = 'scalexy';
	}

	if ($pure == 1  && $zoom > 1 && $zoom <= 3) {
			$size_x *= $zoom;
			$size_y *= $zoom;
	}

	$size = $size_x . ',' . $size_y;

	$head = 'graph networkmap { labeljust=l; outputorder="edgesfirst"; overlap=' .
	        $overlap . '; ratio="fill"; root="' . $root .
	        '"; size="' . $size . '"; ';
	
	return $head;
}	

// Closes a graph definition
function close_graph() {
	return '}';
}

///////////////////////////////////////////////////////////////////////////////
// General purpose functions
///////////////////////////////////////////////////////////////////////////////

// Returns the filter used to achieve the desired layout
function set_filter() {	
	global $layout;
	
	switch($layout) {
		case 'flat':		return 'dot';		
		case 'radial':		return 'twopi';
		case 'circular':	return 'circo';
		case 'spring1':		return 'neato';
		case 'spring2':		return 'fdp';
		default:			return 'twopi';
	}
}

///////////////////////////////////////////////////////////////////////////////
// Main code
///////////////////////////////////////////////////////////////////////////////

// Load variables
$layout = (string) get_parameter ('layout');
$nooverlap = (boolean) get_parameter ('nooverlap');
$pure = (int) get_parameter ('pure');
$zoom = (float) get_parameter ('zoom');

// Login check
$id_user = $_SESSION["id_usuario"];
global $REMOTE_ADDR;

if (comprueba_login() != 0) {
	audit_db($id_user, $REMOTE_ADDR, "ACL Violation", "Trying to access node graph builder");
	include("general/noaccess.php");
	exit;
}

if ((give_acl($id_user, 0, "AR") != 1 ) AND (dame_admin($id_user) !=1 )) {
	audit_db($id_user, $REMOTE_ADDR, "ACL Violation", "Trying to access node graph builder");
	include("general/noaccess.php");
	exit;
}

echo '<h2>' . $lang_label['ag_title'] . ' &gt; ' . lang_string("Network Map") . '&nbsp';
if ($pure == 1) {
    echo '<a href="index.php?sec=reporting&sec2=operation/agentes/networkmap&pure=0"><img src="images/monitor.png" title="' . lang_string('Normal screen') . '"></a>';
}
else {
    echo '<a href="index.php?sec=reporting&sec2=operation/agentes/networkmap&pure=1"><img src="images/monitor.png" title="' . lang_string('Full screen') . '"></a>';
}
echo '</h2>';

// Layout selection
$layout_array = array (
	'radial' => 'radial',
	'circular' => 'circular',
	'spring1' => 'spring 1',
	'spring2' => 'spring 2',
	'flat' => 'flat',
);

echo '<form name="input" action="index.php?sec=reporting&sec2=operation/agentes/networkmap&pure=' . $pure . '" method="post">';
echo '<table cellpadding="4" cellspacing="4" class="databox">';
echo '<tr>';
echo '<td valign="top">' . lang_string('Layout') . ' &nbsp';
print_select ($layout_array, 'layout', $layout, '', '', '');
echo '</td>';
if ($pure == "1") {
	// Zoom
	$zoom_array = array (
		'1' => 'x1',
		'1.5' => 'x1.5',
		'2' => 'x2',
		'2.5' => 'x2.5',
		'3' => 'x3',
	);

	echo '<td valign="top">' . lang_string('Layout') . ' &nbsp';
	print_select ($zoom_array, 'zoom', $zoom, '', '', '');
	echo '</td>';
	echo '<td valign="top">' . lang_string('No Overlap') . ' &nbsp';
	print_checkbox ('nooverlap', 'nooverlap', $nooverlap);
	echo '</td>';
}
//echo '  Display groups  <input type="checkbox" name="group" value="group" class="chk"/>';
echo '<td>';
echo '<input name="updbutton" type="submit" class="sub upd" value="'.
     $lang_label["update"] . '">';
echo '</td>';
echo '</table>';
echo '</form>';

// Set filter
$filter = set_filter();

// Generate dot file
$graph = generate_dot();

//DEBUG
//$fh = fopen("networkmap.dot", 'w') or die("can't open file");
//fwrite($fh, $graph);
//fclose($fh);

// Generate image and map
$cmd = "echo " . escapeshellarg($graph) . 
       " | $filter -Tcmapx -onetworkmap.map -Tpng -onetworkmap.png";
       
if (system($cmd) !== false) {
	echo '<img src="networkmap.png" usemap="#networkmap"/>';
	include "networkmap.map";
}

?>
