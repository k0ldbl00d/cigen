<?php

if(isset($_GET['step'])){$step=$_GET['step'];} else {$step=0;}

switch( @$_GET['step'] ){

case 1:

	$conn = mysql_connect($_POST['mysql_host'],$_POST['mysql_user'],$_POST['mysql_pass']) or die('Unable to connect to <i>'.$_POST['mysql_user'].'@'.$_POST['mysql_host'].'</i>');
	$db = mysql_select_db($_POST['mysql_db']) or die('Unable to select database "'.$_POST['mysql_db'].'"');
	
	// Application path
	$app = $_POST['app_folder'];
	$ctl = $_POST['controller_name'];
	
	// Смещение папки ci относительно папки приложения
	$cioffset = $_POST['ci_offset'];
	
	// Включать ли шапку и подвал
	$inctop = @$_POST['include_top_view'];
	$incbot = @$_POST['include_bottom_view'];
	
	// Имена файлов представления шапки и подвала
	$topname = @$_POST['top_view_name'];
	$botname = @$_POST['bottom_view_name'];
	
	// Имя таблицы MySQL
	$table = $_POST['table_name'];
	
	// Получаем первичный ключ
	$q = mysql_fetch_array(mysql_query("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` where `TABLE_NAME`='$table' AND `COLUMN_KEY`='PRI';"));
	$primakey = $q['COLUMN_NAME'];
	
	// Fetching columns list
	$columns=array();
	$q = mysql_query("SHOW COLUMNS FROM `$table`;");
	while($r = mysql_fetch_array($q)){
		$columns[$r['Field']]['COLUMN_NAME'] = $r['Field'];
	}
	
	// Fetching table structure
	$q = mysql_query("SELECT * FROM `INFORMATION_SCHEMA`.`COLUMNS` where `TABLE_NAME`='$table';");
	$destruct = array();
	while ($r = mysql_fetch_array($q)){
		$destruct[ $r['COLUMN_NAME'] ] = $r;
	}

	$struct = array();
	foreach($columns as $key=>$column){
		$struct[$key] = $destruct[$key];
	}
		
	// Creating folders if not exists
	if(!file_exists($app.'/models')){ mkdir($app.'/models'); }
	if(!file_exists($app.'/controllers')){ mkdir($app.'/controllers'); }
	if(!file_exists($app.'/views')){ mkdir($app.'/views'); }
	if(!file_exists($app.'/views/'.$ctl)){ mkdir($app.'/views/'.$ctl); }
	
	
	//
	// Creating / updating MODEL
	//
	
	$f = fopen($app.'/models/m'.$ctl.'.php','w');
	fputs($f,"<?php if( ! defined('BASEPATH') ){ exit('No direct script access allowed!'); }\n");
	fputs($f,"class M$ctl extends CI_Model {\n");
	fputs($f,"\n");
	
	// CREATE ENTRY
	fputs($f,"	function addItem() { // add record to table $table\n");
	fputs($f,"		\$data = array(); \n");
	foreach($struct as $field){
		if($field['COLUMN_KEY']!='PRI'){
			fputs($f,"		if( (\$this->input->post('".$field['COLUMN_NAME']."')) ){ \$data['".$field['COLUMN_NAME']."'] = \$this->input->post('".$field['COLUMN_NAME']."'); }\n");
		}
	}
	fputs($f,"		\$this->db->insert('$table',\$data);\n");
	fputs($f,"		return(\$this->db->insert_id());\n");
	fputs($f,"	}\n");
	fputs($f,"\n");

	// READ - GET ENTRIES (ALL BY DEFAULT)
	fputs($f,"	function getItems(\$options=array()) { // get all entries $table\n");
	fputs($f,"		if(@\$options['order_dir']){ \$order_dir=\$options['order_dir']; } else { \$order_dir='ASC'; } \n");
	fputs($f,"		if(@\$options['order_by']){ \$this->db->order_by(\$options['order_by'],\$order_dir);  }\n");
	fputs($f,"		if(@\$options['page']){ \$page=\$options['page']; } else { \$page=1; } \n"); // OFFSET IN PAGES!!!
	fputs($f,"		if(@\$options['limit']){ \$this->db->limit(\$options['limit'],(\$page-1)*\$options['limit']); }\n");
	fputs($f,"		if(@\$options['where']){ \$this->db->where(\$options['where']); }\n");
	fputs($f,"		if(@\$options['like']){ \$this->db->like(\$options['like']); }\n");
	fputs($f,"		\$items=\$this->db->get('$table')->result_array();\n");
	fputs($f,"		return(\$items);\n");
	fputs($f,"	}\n");
	fputs($f,"\n");
	
	// READ - GET ENTRY BY ID
	fputs($f,"	function getItemByID(\$id) { // get one entry from $table\n");
	fputs($f,"		\$this->db->where(array('$primakey'=>\$id));\n");
	fputs($f,"		\$item=\$this->db->get('$table')->row_array();\n");
	fputs($f,"		return(\$item);\n");
	fputs($f,"	}\n");
	fputs($f,"\n");
	
	// READ - GET ITEMS COUNT
	fputs($f,"	function getItemsCount(\$options=array()) { // get number of specified entries from $table\n");
	fputs($f,"		if(@\$options['where']){ \$this->db->where(\$options['where']); }\n");
	fputs($f,"		if(@\$options['like']){ \$this->db->like(\$options['like']); }\n");
	fputs($f,"		\$items=\$this->db->get('$table')->result_array();\n");
	fputs($f,"		return(count(\$items));\n");
	fputs($f,"	}\n");
	fputs($f,"\n");
	
	// UPDATE ENTRY
	fputs($f,"	function updateItem(\$id) { // add record to table $table\n");	
	fputs($f,"		\$data = array();\n");
	foreach($struct as $field){
		if($field['COLUMN_KEY']!='PRI'){
			fputs($f,"		if( (\$this->input->post('".$field['COLUMN_NAME']."')) ){ \$data['".$field['COLUMN_NAME']."'] = \$this->input->post('".$field['COLUMN_NAME']."'); }\n");
		}
	}
	fputs($f,"		\n");
	fputs($f,"		\$this->db->where(array('$primakey'=>\$id));\n");
	fputs($f,"		\$this->db->update('$table',\$data);\n");
	fputs($f,"		return(\$this->db->affected_rows());\n");
	fputs($f,"	}\n");
	fputs($f,"\n");
	
	// DELETE - REMOVE ENTRY BY ID
	fputs($f,"	function deleteItem(\$id) { // add record to table $table\n");
	fputs($f,"		\$this->db->where(array('$primakey'=>\$id));\n");
	fputs($f,"		\$this->db->delete('$table');\n");
	fputs($f,"		return(\$this->db->affected_rows());\n");
	fputs($f,"	}\n");
	fputs($f,"\n");
	
	// DONE MODEL!
	fputs($f,"} // end class\n");
	fclose($f);
	
	
	
	
	//
	// Creating / updating CONTROLLER
	//
	$f = fopen($app.'/controllers/'.$ctl.'.php','w');
	fputs($f,"<?php if( ! defined('BASEPATH') ){ exit('No direct script access allowed!'); }\n");
	fputs($f,"class $ctl extends CI_Controller {\n");
	fputs($f,"\n");
	
	// CONSTRUCTOR
	fputs($f,"	function __construct() \n");
	fputs($f,"	{\n");
	fputs($f,"		parent::__construct();\n");
	fputs($f,"	}\n");
	fputs($f,"\n");
	
	// INDEX - DISPLAY ALL ENTRIES
	fputs($f,"	function index(\$page=1) \n");
	fputs($f,"	{\n");
	fputs($f,"		\$this->load->model('m$ctl'); \n");
	fputs($f,"		\$query['page'] = \$page; \n");
	fputs($f,"		\$query['limit'] = 20; \n");
	fputs($f,"		// if (\$this->input->post('search')){ \$query['like']['search_field'] = \$this->input->post('search'); }\n");
	fputs($f,"		\$data['search']=\$this->input->post('search');\n");
	fputs($f,"		\$data['items'] = \$this->m".$ctl."->getItems(\$query);\n");
	fputs($f,"		\$data['pages'] = ceil(\$this->m".$ctl."->getItemsCount(\$query) / \$query['limit'] );\n");
	if(@$inctop==1){ fputs($f,"		\$this->load->view('$topname');\n"); }
	fputs($f,"		\$this->load->view('$ctl/index',\$data);\n");
	if(@$incbot==1){ fputs($f,"		\$this->load->view('$botname');\n"); }
	fputs($f,"		\n");
	fputs($f,"	}\n");
	fputs($f,"\n");
	
	// DISPLAY ENTRY
	fputs($f,"	function entry(\$id) \n");
	fputs($f,"	{\n");
	fputs($f,"		\$this->load->model('m$ctl'); \n");
	fputs($f,"		\$data['entry'] = \$this->m".$ctl."->getItemByID(\$id);\n");
	if(@$inctop==1){ fputs($f,"		\$this->load->view('$topname');\n"); }
	fputs($f,"		\$this->load->view('$ctl/entry',\$data);\n");
	if(@$incbot==1){ fputs($f,"		\$this->load->view('$botname');\n"); }
	fputs($f,"		\n");
	fputs($f,"	}\n");
	fputs($f,"\n");
	
	// EDIT ENTRY
	fputs($f,"	function edit(\$id) \n");
	fputs($f,"	{\n");
	fputs($f,"		\$this->load->model('m$ctl'); \n");
	fputs($f,"		\$data['entry'] = \$this->m".$ctl."->getItemByID(\$id);\n");
	if(@$inctop==1){ fputs($f,"		\$this->load->view('$topname');\n"); }
	fputs($f,"		\$this->load->view('$ctl/entry-edit',\$data);\n");
	if(@$incbot==1){ fputs($f,"		\$this->load->view('$botname');\n"); }
	fputs($f,"		\n");
	fputs($f,"	}\n");
	fputs($f,"\n");
	
	// ADD ENTRY
	fputs($f,"	function add() \n");
	fputs($f,"	{\n");
	if(@$inctop==1){ fputs($f,"		\$this->load->view('$topname');\n"); }
	fputs($f,"		\$this->load->view('$ctl/entry-add');\n");
	if(@$incbot==1){ fputs($f,"		\$this->load->view('$botname');\n"); }
	fputs($f,"		\n");
	fputs($f,"	}\n");
	fputs($f,"\n");
	
	// UPDATE ENTRY
	fputs($f,"	function save(\$id) \n");
	fputs($f,"	{\n");
	fputs($f,"		\$this->load->model('m$ctl'); \n");
	fputs($f,"		\$this->m".$ctl."->updateItem(\$id);\n");
	fputs($f,"		header('Location: /$ctl/entry/'.\$id);\n");
	fputs($f,"		\n");
	fputs($f,"	}\n");
	fputs($f,"\n");
	
	// CREATE ENTRY
	fputs($f,"	function create() \n");
	fputs($f,"	{\n");
	fputs($f,"		\$this->load->model('m$ctl'); \n");
	fputs($f,"		\$id = \$this->m".$ctl."->addItem();\n");
	fputs($f,"		header('Location: /$ctl/entry/'.\$id);\n");
	fputs($f,"		\n");
	fputs($f,"	}\n");
	fputs($f,"\n");
	
	// DELETE ENTRY
	fputs($f,"	function delete(\$id) \n");
	fputs($f,"	{\n");
	fputs($f,"		\$this->load->model('m$ctl'); \n");
	fputs($f,"		\$this->m".$ctl."->deleteItem(\$id);\n");
	fputs($f,"		header('Location: /$ctl/index');\n");
	fputs($f,"		\n");
	fputs($f,"	}\n");
	fputs($f,"\n");
	
	// DONE CONTROLLER!
	fputs($f,"} // end class\n");
	fclose($f);
	
	
	
	//
	// Creating / updating VIEWS - INDEX
	//
	$f = fopen($app.'/views/'.$ctl.'/index.php','w');
	
	fputs($f,"<div class=\"ci_buttons\">\n");
	fputs($f,"<a href=\"$cioffset/$ctl/add\">Добавить новый элемент</a>\n");
	fputs($f,"</div>\n");
	
	fputs($f,"<div class=\"ci_search\">\n");
	fputs($f,"<form method=\"POST\" action=\"\"><input type=\"text\" size=\"50\" name=\"search\" value=\"<?=@\$search?>\" /> <input type=\"submit\" value=\"Поиск\" /></form>\n");
	fputs($f,"</div>\n");
	
	fputs($f,"<table class=\"ci_table\">\n");
	
	// Table Header
	fputs($f,"	<tr>\n");
	foreach($struct as $field){
		if($field['COLUMN_KEY']!='PRI'){
			fputs($f,"		<th title=\"".$field['COLUMN_COMMENT']."\">".$field['COLUMN_NAME']."</th>\n");
		}
	}
	fputs($f,"	</tr>\n");
	
	// Table Data
	fputs($f,"	<? foreach(\$items as \$key=>\$value ) { ?>\n");
	fputs($f,"	<tr>\n");
	foreach($struct as $field){
		if($field['COLUMN_KEY']!='PRI'){
			fputs($f,"		<td><a href=\"$cioffset/$ctl/entry/<?=@\$value['".$primakey."']?>\"><?=@\$value['".$field['COLUMN_NAME']."']?></a></td>\n");
		}
	}
	fputs($f,"	</tr>\n");
	fputs($f,"	<? } ?>\n");
	fputs($f,"</table>\n");
	
	// Pagination
	fputs($f,"<? if(\$pages > 1) { ?>");
	fputs($f,"<div class=\"pagination\">");
	fputs($f,"<? for(\$page=1;\$page < (\$pages+1);\$page++) { echo('<a class=\"page\" href=\"$cioffset/$ctl/index/'.\$page.'\">'.\$page.'</a>'); } ?>");
	fputs($f,"</div>");
	fputs($f,"<? } ?>");
	
	// DONE VIEW INDEX!
	fputs($f,"\n");
	fclose($f);
	
	
	
	//
	// Creating / updating VIEWS - ENTRY VIEW
	//
	$f = fopen($app.'/views/'.$ctl.'/entry.php','w');
	
	// Buttons
	fputs($f,"<div class=\"ci_buttons\">\n");
	fputs($f,"<a href=\"$cioffset/$ctl\">&larr; Вернуться к списку</a>\n");
	fputs($f,"<a href=\"$cioffset/$ctl/edit/<?=\$entry['".$primakey."']?>\">Редактировать</a>\n");
	fputs($f,"<a href=\"$cioffset/$ctl/delete/<?=\$entry['".$primakey."']?>\">Удалить</a>\n");
	fputs($f,"</div>\n");
	
	fputs($f,"<table class=\"ci_table\">\n");
	
	// Table Data
	foreach($struct as $field){
		if($field['COLUMN_KEY']!='PRI'){
			fputs($f,"	<tr>\n");
			fputs($f,"		<td title=\"".$field['COLUMN_COMMENT']."\">".$field['COLUMN_NAME']."</td>\n");
			fputs($f,"		<td><?=@\$entry['".$field['COLUMN_NAME']."']?></td>\n");
			fputs($f,"	</tr>\n");
		}
	}

	fputs($f,"</table>\n");
	// DONE VIEW ENTRY!
	fputs($f,"\n");
	fclose($f);
	
	
	//
	// Creating / updating VIEWS - ENTRY EDIT
	//
	$f = fopen($app.'/views/'.$ctl.'/entry-edit.php','w');

	// Buttons
	fputs($f,"<div class=\"ci_buttons\">\n");
	fputs($f,"<a href=\"$cioffset/$ctl/entry/<?=\$entry['".$primakey."']?>\">Вернуться к просмотру</a>\n");
	fputs($f,"</div>\n");
	
	// Form
	fputs($f,"<form method=\"post\" action=\"$cioffset/$ctl/save/<?=\$entry['".$primakey."']?>\">\n");
	fputs($f,"<table class=\"ci_table\">\n");
	
	// Table Data
	foreach($struct as $field){
		if($field['COLUMN_KEY']!='PRI'){
			fputs($f,"	<tr>\n");
			fputs($f,"		<td title=\"".$field['COLUMN_COMMENT']."\">".$field['COLUMN_NAME']."</td>\n");
			switch($field['DATA_TYPE']){
				
				case "text":
				case "longtext":
				case "mediumtext":
				case "tinytext":
				case "blob":
					fputs($f,"		<td><textarea rows=\"8\" cols=\"40\" name=\"".$field['COLUMN_NAME']."\"><?=@\$entry['".$field['COLUMN_NAME']."']?></textarea></td>\n");
					break;
				case "varchar":
				default:
					fputs($f,"		<td><input type=\"text\" name=\"".$field['COLUMN_NAME']."\" value=\"<?=@\$entry['".$field['COLUMN_NAME']."']?>\" /></td>\n");
					break;
			}
			fputs($f,"	</tr>\n");
		}
	}

	fputs($f,"</table>\n");
	fputs($f,"<input type=\"submit\" value=\"Сохранить изменения\" />\n");
	fputs($f,"</form>\n");
	// DONE VIEW ENTRY!
	fputs($f,"\n");
	fclose($f);
	
	
	
	//
	// Creating / updating VIEWS - ENTRY ADD
	//
	$f = fopen($app.'/views/'.$ctl.'/entry-add.php','w');
	
	// Buttons
	fputs($f,"<div class=\"ci_buttons\">\n");
	fputs($f,"<a href=\"$cioffset/$ctl\">Вернуться к списку</a>\n");
	fputs($f,"</div>\n");
	
	// Form
	fputs($f,"<form method=\"post\" action=\"$cioffset/$ctl/create\">\n");
	fputs($f,"<table class=\"ci_table\">\n");
	
	// Table Data
	foreach($struct as $field){
		if($field['COLUMN_KEY']!='PRI'){
			fputs($f,"	<tr>\n");
			fputs($f,"		<td title=\"".$field['COLUMN_COMMENT']."\">".$field['COLUMN_NAME']."</td>\n");
			switch($field['DATA_TYPE']){
				
				case "text":
				case "longtext":
				case "mediumtext":
				case "tinytext":
				case "blob":
					fputs($f,"		<td><textarea rows=\"8\" cols=\"40\" name=\"".$field['COLUMN_NAME']."\"></textarea></td>\n");
					break;
				case "varchar":
				default:
					fputs($f,"		<td><input type=\"text\" name=\"".$field['COLUMN_NAME']."\" value=\"\" /></td>\n");
					break;
			}
			fputs($f,"	</tr>\n");
		}
	}

	fputs($f,"</table>\n");
	fputs($f,"<input type=\"submit\" value=\"Сохранить изменения\" />\n");
	fputs($f,"</form>\n");
	// DONE VIEW ENTRY!
	fputs($f,"\n");
	fclose($f);
	
	
	
	/*
	?><pre><?
	print_r($struct);
	?></pre><?
	*/
	
	
	echo('СВЕРШИЛОСЬ!');
	

break;



case "ajax_getdatabases":
	
	$conn = mysql_connect($_POST['mysql_host'],$_POST['mysql_user'],$_POST['mysql_pass']) or die('Unable to connect to <i>'.$_POST['mysql_user'].'@'.$_POST['mysql_host'].'</i>');
	$q = mysql_query('SHOW DATABASES;');
	$tables = array();
	while ($res = mysql_fetch_array($q)){
		$dbs[] = $res[0];
	}
	if(count($dbs)>0){
		foreach($dbs as $db) { ?><option value="<?=$db?>"><?=$db?></option><? }				
	}
break;

case "ajax_gettables":
	
	$conn = mysql_connect($_POST['mysql_host'],$_POST['mysql_user'],$_POST['mysql_pass']) or die('Unable to connect to <i>'.$_POST['mysql_user'].'@'.$_POST['mysql_host'].'</i>');
	$db = mysql_select_db($_POST['mysql_db']) or die('Unable to select database "'.$_POST['mysql_db'].'"');
		
	$q = mysql_query('SHOW TABLES;');
	$tables = array();
	while ($res = mysql_fetch_array($q)){
		$tables[] = $res[0];
	}
	if(count($tables)>0){
		foreach($tables as $tbl) { ?><option value="<?=$tbl?>"><?=$tbl?></option><? }				
	}
break;







default:
	
	define('BASEPATH', '/');
	if(file_exists('./application/config/database.php')) { require_once('./application/config/database.php'); }
	
?><!DOCTYPE html>
<html>
<head>
	<!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.3/css/bootstrap.min.css">
	<!-- Latest compiled and minified JavaScript -->
	<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.3/js/bootstrap.min.js"></script>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!-- jQuery -->
	<script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
	<!-- AJAX PART -->
	<script>
		$(document).ready(function(){
			$("#next_db").click(function(){ $("div#settings_db").show(); $(this).hide(); });
			$("#next_db2").click(function(){
				$.ajax({
					type: "POST",
					url: "?step=ajax_getdatabases",
					data: "mysql_host="+$("#mysql_host").val()+"&mysql_user="+$("#mysql_user").val()+"&mysql_pass="+$("#mysql_pass").val(),
					success: function(data){
						$("#list_db_name").html(data);
						$("div#settings_db2").show();
						$("#next_db2").hide();
					}
				});
				
			});
			$("#next_db3").click(function(){
				$.ajax({
					type: "POST",
					url: "?step=ajax_gettables",
					data: "mysql_host="+$("#mysql_host").val()+"&mysql_user="+$("#mysql_user").val()+"&mysql_pass="+$("#mysql_pass").val()+"&mysql_db="+$("#list_db_name").val(),
					success: function(data){
						$("#list_table_name").html(data);
						$("#settings_db3").show();
						$("#next_db3").hide();
					}
				});
			});
			$("#next_other").click(function(){
				$("#controller_name").val($("#list_table_name").val());
				$("#settings_other").show();
				$("#next_other").hide();
			});
		});
	</script>
	
	<title>CodeIgniter CRUD Generator &copy; Kirill &laquo;k0ldbl00d&raquo; Shmakov, 2014</title>
</head>
<body>
<div class="container">
<form action="?step=1" method="post" class="form-signin">
<h1>CI CRUD Generator</h1>
<div class="alert alert-danger">
  <strong>Внимание!</strong> CI CRUD Generator предназначен только для разработчиков и не должен быть доступен из интернета. Добавьте соответствующие правила в файл .htaccess или удалите файл <strong>cigen.php</strong> сразу после завершения необходимых действий.  
</div>

<div id="settings_app">
	
	<h3>Настройки приложения CodeIgniter</h3>
	
	<dl>
		<dt>Адрес каталога application:</dt>
		<dd><input class="form-control" type="text" name="app_folder" size="40" value="./application" /></dd>
	</dl>
	
	<dl>
		<dt>Смещение каталога CI относительно домена (без слеша в конце!):</dt>
		<dd><input class="form-control" type="text" name="ci_offset" size="40" value="" /></dd>
	</dl>
	
	<button type="button" class="btn btn-primary btn-lg" id="next_db">Далее</button>
	
</div>

<div id="settings_db" style="display: none;">

	<h3>Присоединение к базе данных</h3>
	
	<dl>
		<dt>MySQL host:</dt>
		<dd><input class="form-control" type="text" name="mysql_host" id="mysql_host" size="40" value="<? if(@$db['default']['hostname']){ echo ($db['default']['hostname']); } else { echo('localhost'); } ?>" placeholder="" /></dd>
	</dl>
	
	<dl>
		<dt>MySQL user:</dt>
		<dd><input class="form-control" type="text" name="mysql_user" id="mysql_user" value="<? if(@$db['default']['username']){ echo ($db['default']['username']); } else { echo('root'); } ?>" placeholder="root" /></dd>
	</dl>
	
	<dl>
		<dt>MySQL password:</dt>
		<dd><input class="form-control" type="password" name="mysql_pass" id="mysql_pass" value="<? if(@$db['default']['password']){ echo ($db['default']['password']); } ?>" /></dd>
	</dl>
	
	<button type="button" class="btn btn-primary btn-lg" id="next_db2">Далее</button>
</div>

<div id="settings_db2" style="display: none;">
	<h3>Выберите базу данных</h3>
	
	<dl>
		<dt>MySQL DB name:</dt>
		<dd>
			<select class="form-control" name="mysql_db" id="list_db_name">
			
			</select>
		</dd>
	</dl>
	<button type="button" class="btn btn-primary btn-lg" id="next_db3">Далее</button>
</div>

<div id="settings_db3" style="display: none;">
	<h3>Выберите таблицу</h3>
	<dl>
		<dt>MySQL table name:</dt>
		<dd>
		<select class="form-control" name="table_name" id="list_table_name">
			
		</select>
		</dd>
	</dl>
	<button type="button" class="btn btn-primary btn-lg" id="next_other">Далее</button>
</div>

<div id="settings_other" style="display: none;">
	
	<h3>Настройка параметров приложения</h3>
	
	<dl>
		<dt>Наименование контроллера (например, blog):</dt>
		<dd><input class="form-control" type="text" name="controller_name" id="controller_name" size="40" value="" placeholder="" /></dd>
	</dl>
	
	<dl>
		<dt><input type="checkbox" value="1" checked name="include_top_view" id="include_top_view"> <label for="include_top_view">VIEW шапки сайта:</label></dt>
		<dd><input class="form-control" type="text" name="top_view_name" size="40" value="_top" placeholder="_top" /></dd>
	</dl>
	
	<dl>
		<dt><input type="checkbox" value="1" checked name="include_bottom_view" id="include_bottom_view"><label for="include_bottom_view">VIEW подвала сайта:</label></dt>
		<dd><input class="form-control" type="text" name="bottom_view_name" size="40" value="_bottom" placeholder="_bottom" /></dd>
	</dl>
	<input type="submit" class="btn btn-primary btn-lg" value="Сгенерировать">
</div>

<br /><br /><br /><br />
</form>
</div>
</body>
</html>
<?
}
?>