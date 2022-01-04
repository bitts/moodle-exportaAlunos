<?php

/**
* Create By Marcelo Valvassori Bittencourt
* 01/01/2022 - Exportação de alunos matriculados para o formato CSV
*/

require(__DIR__.'/../config.php');

defined('MOODLE_INTERNAL') || die();

// Force a debugging mode regardless the settings in the site administration
// $CFG->debug = (E_ALL | E_STRICT);   // === DEBUG_DEVELOPER - NOT FOR PRODUCTION SERVERS!
// $CFG->debugdisplay = 1;

function listaAll(){
	global $DB;
	global $CFG;
	global $OUTPUT;

	$sql = "SELECT id, fullname, summary, DATE_FORMAT(from_unixtime(startdate),'%d/%m/%Y') as data_inicio, DATE_FORMAT(from_unixtime(enddate),'%d/%m/%Y') as data_final, c.visible FROM {$CFG->prefix}course as c WHERE c.visible=1 ORDER BY startdate DESC";// AND DATE_FORMAT(from_unixtime(startdate),'%Y') >= 2021 ORDER BY startdate DESC";
  $turma = $DB->get_records_sql($sql);

	//$retorno = print_r($turma);

	$retorno = "<h2>Listagem de Turmas para exportação de Dados</h2><table id='my-table' style='width:90%; font-size:9pt;' cellspacing='0' cellpadding='0' summary='Tabela para exportação de alunos matriculadas.'  class='flexible table table-striped table-hover boxaligncenter generaltable'><thead><tr><th>Nome</th><th>Inicio</th><th></th></tr></thead><tbody>\n";
  foreach($turma as $tr => $item){
          $retorno .= "\t\t\t<tr><td class='cell'><a class='aalink' href='/course/view.php?id={$item->id}'>{$item->fullname}</td><td>{$item->data_inicio}</td><td><a href='?op=exporta&id={$item->id}&export=0'><button class='btn btn-secondary'>Exportar</button></a></td></tr>\n";
  }
  $retorno .= "</tbody></table>";

	echo $OUTPUT->header();
	echo $retorno;
	echo $OUTPUT->footer();
}


function getUsersData() {
  global $DB;
  global $CFG;
	global $OUTPUT;


	//$DB->set_debug(true)
	//$CFG->debug = (E_ALL | E_STRICT);   // === DEBUG_DEVELOPER - NOT FOR PRODUCTION SERVERS!
	//$CFG->debugdisplay = 1;

	$turma_id = required_param('id', PARAM_INT);
	$export = required_param('export', PARAM_BOOL);

	try {

		$sql = "SELECT id, fullname, summary, DATE_FORMAT(from_unixtime(startdate),'%d/%m/%Y') as data_inicio, c.* FROM {$CFG->prefix}course as c WHERE id={$turma_id}";
		$turma = $DB->get_records_sql($sql);

    $sql = "
		SELECT
      u.id as UserID, u.username UserName, CONCAT(u.firstname, ' ', u.lastname) as Nome,
      u.email as UserEmail,
      (SELECT data FROM {$CFG->prefix}user_info_data WHERE fieldid = 14 AND userid=u.id) as OM,
      (SELECT data FROM {$CFG->prefix}user_info_data WHERE fieldid = 15 AND userid=u.id) as Posto,
      (SELECT data FROM {$CFG->prefix}user_info_data WHERE fieldid = 17 AND userid=u.id) as Doc,
      (SELECT data FROM {$CFG->prefix}user_info_data WHERE fieldid = 18 AND userid=u.id) as Tempo,
      (SELECT data FROM {$CFG->prefix}user_info_data WHERE fieldid = 19 AND userid=u.id) as Funcao,
      (SELECT data FROM {$CFG->prefix}user_info_data WHERE fieldid = 20 AND userid=u.id) as Contato1,
      (SELECT data FROM {$CFG->prefix}user_info_data WHERE fieldid = 21 AND userid=u.id) as Contato2,
      (SELECT data FROM {$CFG->prefix}user_info_data WHERE fieldid = 22 AND userid=u.id) as email,
      (SELECT data FROM {$CFG->prefix}user_info_data WHERE fieldid = 23 AND userid=u.id) as Educacao,
      (SELECT data FROM {$CFG->prefix}user_info_data WHERE fieldid = 24 AND userid=u.id) as Cidade
    FROM
      {$CFG->prefix}course AS c
      JOIN {$CFG->prefix}context AS ctx ON c.id = ctx.instanceid
      JOIN {$CFG->prefix}role_assignments AS ra ON ra.contextid = ctx.id
      JOIN {$CFG->prefix}user AS u ON u.id = ra.userid
      JOIN {$CFG->prefix}user_info_data as DT
    WHERE
      u.confirmed=1 AND
      DT.userid=u.id AND
      c.id = {$turma_id}
    ORDER BY
      u.firstname ASC
    ";

	 	//$params = array('id' => 14);
    //$alunos = $DB->get_records_sql($sql, $params);

		$alunos = $DB->get_records_sql($sql);

		if($export && $alunos){

			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename=Alunos.csv');

			$fp = fopen('php://output', 'w');

			fputcsv($fp, array('userid','username','name','useremail', 'om','posto','doc','tempo','funcao','contato1','contato2','email','educacao','cidade'));

			foreach($alunos as $aluno => $fields){
			    if( is_object($fields) ){
			       $fields = (array) $fields;
			    }
			    fputcsv($fp, $fields);
			}
			fclose($fp);
		}else {
			$lista[] = array('userid','username','name','useremail', 'om','posto','doc','tempo','funcao','contato1','contato2','email','educacao','cidade');
      foreach($alunos as $aluno => $fields){
        if( is_object($fields) ){
				  $lista[] = (array) $fields;
        }
			}

			$lista = listage($lista, $turma_id, $turma);

			echo $OUTPUT->header();
			echo $lista;
			echo $OUTPUT->footer();
		}

	} catch(Exception $e) {
	 	print_r($e);
	}
}

function listage($itens, $turma_id, $turma){

	$retorno = "<h3>{$turma[$turma_id]->fullname}</h3><p>Data de Inicio: {$turma[$turma_id]->data_inicio}</p><p>{$turma[$turma_id]->summary}</p>";

	$retorno .= "
	<p><a href='?op=exporta&id={$turma_id}&export=1'><button class='btn btn-secondary'>Exportar para CSV</button></a></p>

	<table id='my-table' style='width:90%; font-size:9pt;'
		cellspacing='0'
    cellpadding='0'
    summary='Tabela para exportação de alunos matriculadas.'
    class='flexible table table-striped table-hover boxaligncenter generaltable'
	><thead><tr><th></th>\n";

  foreach($itens[0] as $item){
    $retorno .= "\t\t\t<th class='header'>{$item}</th>\n";
  }

	$retorno .= "</tr></thead><tbody>\n";
	$j = 1;
  foreach(array_slice($itens, 1, null, true) as $it => $item){
    $retorno .= "\t\t\t<tr><td>{$j}</td>";
		$i = 0;
		foreach($item as $vlr){
			$retorno .= "<td class='cell c{$i}'>{$vlr}</td>";
			$i++;
		}
		$j++;
		$retorno .= "</tr>\n";
  }
  $retorno .= "</tbody></table>";
	return $retorno;
}



$PAGE->set_title('Exportação de alunos para arquivo CSV');
$PAGE->set_pagelayout('standard');

$opcao = @optional_param('op','lista',PARAM_TEXT);
if($opcao){
	switch($opcao){
		case 'lista':
			listaAll();
		break;
		case 'exporta':
			getUsersData();
		break;
	}
}else listaAll();
