<?php

function findValueInArrayByIndex ($data, $indexName, $separator){

		if($data instanceof Traversable){
			foreach ($data as $value) {
				return findInArrayIndex($value, $indexName, $separator);					
			}
		}else{
			if(isset($data[$indexName])){
				return $data[$indexName] . $separator;
			}
		}
		return;
	}

$allergies = array( 'Alergias' => array( 
			0 => array(
          	'idAlergia' => '2', 
          	'nombreAlergia' => 'Lana')
          	)
);

//$res = findValueInArrayByIndex($allergies, 'nombreAlergia', ',');

//echo $res;
?>