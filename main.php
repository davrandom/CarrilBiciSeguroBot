<?php
/**
* Telegram Bot example for bike lanes safety "CarrilBiciSeguroBot". Translation of "ViaLiberaBot".
* @author Francesco Piero Paolicelli
*/
include("settings_t.php");
include("Telegram.php");

class mainloop{
const MAX_LENGTH = 4096;
function start($telegram,$update)
{

	date_default_timezone_set('Europe/Rome');
	$today = date("Y-m-d H:i:s");
	//$data=new getdata();
	// Instances the class
	$db = new PDO(DB_NAME);

	/* If you need to manually take some parameters
	*  $result = $telegram->getData();
	*  $text = $result["message"] ["text"];
	*  $chat_id = $result["message"] ["chat"]["id"];
	*/

	$first_name=$update["message"]["from"]["first_name"];
	$text = $update["message"] ["text"];
	$chat_id = $update["message"] ["chat"]["id"];
	$user_id=$update["message"]["from"]["id"];
	$location=$update["message"]["location"];
	$reply_to_msg=$update["message"]["reply_to_message"];
	$username=$update["message"]["from"]["username"];
	$this->shell($username,$telegram, $db,$first_name,$text,$chat_id,$user_id,$location,$reply_to_msg);
	//$db = NULL;

}

//gestisce l'interfaccia utente
 function shell($username,$telegram,$db,$first_name,$text,$chat_id,$user_id,$location,$reply_to_msg)
{
	$csv_path=dirname(__FILE__).'/./db/map_data.txt';
	$db_path=dirname(__FILE__).'/./db/db.sqlite';

	date_default_timezone_set('Europe/Rome');
	$today = date("Y-m-d H:i:s");

	if ($text == "/inicio" || $text == "info" || $text == "©️info") {
		$reply = "Bienvenido ".$first_name.". Este Bot de pruebas ha sido creado por @piersoft inspirado por este artículo sobre Nueva York https://goo.gl/bUA0nm (in italiano). Este Bot permite señalar infracciones del Código de Tráfico y Seguridad Vial y casos de incivilidad, por ejemplo coches estacionando en el carril bici o en zonas que dificultan la visibilidad generando peligro. El autor no se hace responsable por el uso impropio de esta herramienta y del contenido enviado por los usuarios. Enviando tus informes eres consciente de que el usuario y el contenido de tu mensaje (unívocos en Telegram) serán grabados y visualizados públicamente sobre un mapa con licencia CC0 (público dominio). Estos datos, en tiempo real, se pueden descargar desde aquí  https://goo.gl/vNQYPw in formato CSV.\nPuedes pedir la autorización para participar en este experimento ciudadano comunitario rellenando este formulario: https://goo.gl/forms/29j5UtOx3MUkxoRG2. \n\nLa geo-codificación de los datos está hecha a través de la base de datos Nominatim de openStreetMap con licencia oDBL.";
		$content = array('chat_id' => $chat_id, 'text' => $reply,'disable_web_page_preview'=>true);
		$telegram->sendMessage($content);


		$forcehide=$telegram->buildKeyBoardHide(true);
		$content = array('chat_id' => $chat_id, 'text' => "", 'reply_markup' =>$forcehide, 'reply_to_message_id' =>$bot_request_message_id);
		$bot_request_message=$telegram->sendMessage($content);

		$log=$today. ",new chat started," .$chat_id. "\n";

	}elseif ($text == "/posición" || $text == "🌐posición") {

		$option = array(array($telegram->buildKeyboardButton("Envía tu posición / send your location", false, true)) //this work
											);
	// Create a permanent custom keyboard
	$keyb = $telegram->buildKeyBoard($option, $onetime=false);
	$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => " Activa la localización en tu móvil / Turn on your GPS");
	$telegram->sendMessage($content);
	exit;
	}else if ($text == "/instrucciones" || $text == "instrucciones" || $text == "❓instrucciones") {

		$img = curl_file_create('istruzioni.png','image/png');
		$contentp = array('chat_id' => $chat_id, 'photo' => $img);
		$telegram->sendPhoto($contentp);
		$content = array('chat_id' => $chat_id, 'text' => "[Imagen realizada por Alessandro Ghezzer]");
		$telegram->sendMessage($content);
		$content = array('chat_id' => $chat_id, 'text' => "Después de enviar tu FICHERO puedes añadir un texto.\nTienes que digitar t:numinforme:texto\npor ejemplo <b>t:123:coche aparcado en la acera</b>Solamente el usuario que ha enviado el mensaje puede modificarlo.",'parse_mode'=>"HTML");
		$telegram->sendMessage($content);
		$log=$today. ",istruzioni," .$chat_id. "\n";

	}elseif ($text=="update"){
		$statement = "DELETE FROM ". DB_TABLE_GEO ." WHERE username =' '";
		$db->exec($statement);
		exec(' sqlite3 -header -csv '.$db_path.' "select * from segnalazioni;" > '.$csv_path. ' ');

	}
		elseif ($text=="actualiza" || $text =="/actualiza" || $text =="❌actualiza" )
			{

				$reply = "Para actualizar un informe envía a:numinforme\npor ejemplo a:699";
				$content = array('chat_id' => $chat_id, 'text' => $reply);
				$telegram->sendMessage($content);

			}
			elseif (strpos($text,'a:') !== false) {
				$text=str_replace("a:","",$text);
				$text=str_replace(" ","",$text);

				if ($username==""){
					$content = array('chat_id' => $chat_id, 'text' => "Tienes que poner un nombre de usuario en los ajustes/configuración de Telegram",'disable_web_page_preview'=>true);
					$telegram->sendMessage($content);
					$log=$today.",".$todayd. ",nousernameset," .$chat_id.",".$username.",".$user_id."\n";
					file_put_contents('db/telegram.log', $log, FILE_APPEND | LOCK_EX);
					$this->create_keyboard($telegram,$chat_id);
					exit;
				}else
				{
					$text1=strtoupper($username);
					$homepage="";
					// il GDRIVEKEY2 è l'ID per un google sheet dove c'è l'elenco degli username abilitati.
					$url ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20upper(D)%20LIKE%20%27%25".$text1;
					$url .="%25%27%20&key=".GDRIVEKEY."&gid=".GDRIVEGID1;
				//  $url="https://docs.google.com/spreadsheets/d/1r-A2a47HKuy7dUx4YreSmJxI4KQ-fc4v97J-xt5qqqU/gviz/tq?tqx=out:csv&tq=SELECT+*+WHERE+B+LIKE+%27%25VENERD%25%27+AND+A+LIKE+%27%251%25%27";
					$csv = array_map('str_getcsv', file($url));
					$count = 0;
					foreach($csv as $data=>$csv1){
						$count = $count+1;
					}
						if ($count >1)
							{
			//	$user_id = "193317621";
					$statement = "UPDATE ".DB_TABLE_GEO ." SET aggiornata='gestita' WHERE bot_request_message ='".$text."'";
		//	print_r($reply_to_msg['message_id']);
					$db->exec($statement);
					$reply = "Envío n° ".$text." actualizado";
					$content = array('chat_id' => $chat_id, 'text' => $reply);
					$telegram->sendMessage($content);
					exec(' sqlite3 -header -csv '.$db_path.' "select * from segnalazioni;" > '.$csv_path. ' ');
					$log=$today. ",envío actualizado," .$chat_id. "\n";
					$db1 = new SQLite3($db_path);
					$q = "SELECT user,username FROM ".DB_TABLE_GEO ." WHERE bot_request_message='".$text."'";
					$result=	$db1->query($q);
					$row = array();
					$i=0;

					while($res = $result->fetchArray(SQLITE3_ASSOC))
							{

									if(!isset($res['user'])) continue;

									 $row[$i]['user'] = $res['user'];
									 $row[$i]['username'] = $res['username'];

									 $i++;
							 }
							 $content = array('chat_id' => $row[0]['user'], 'text' => $row[$i]['username'].", estamos gestionando tu envío. Gracias!",'disable_web_page_preview'=>true);
						 	 $telegram->sendMessage($content);
				}else{
					$content = array('chat_id' => $chat_id, 'text' => $username.", no pareces tener autorización para actualizar los informes.",'disable_web_page_preview'=>true);
					$telegram->sendMessage($content);
					$this->create_keyboard($telegram,$chat_id);
					exit;
				}

			}

		}elseif (strpos($text,'t:') !== false || strpos($text,'T:') !== false) {
			//$text=str_replace("/t:",":",$text);
			$text=str_replace("t:",":",$text);
			$text=str_replace("T:",":",$text);
			function extractString($string, $start, $end) {
					$string = " ".$string;
					$ini = strpos($string, $start);
					if ($ini == 0) return "";
					$ini += strlen($start);
					$len = strpos($string, $end, $ini) - $ini;
					return substr($string, $ini, $len);
			}
			//$testo=$_POST["q"];
			//$testo="bm%11/01/2016?5-11";
			$id=extractString($text,":",":");
			$text=str_replace($id,"",$text);
			$text=str_replace(":","",$text);
				$text=str_replace(",","",$text);
			$statement = "UPDATE ".DB_TABLE_GEO ." SET text='".$text."' WHERE bot_request_message ='".$id."' AND username='".$username."'";
//	print_r($reply_to_msg['message_id']);
			$db->exec($statement);
			$reply = "Se ha actualizado el informe n° ".$id.", únicamente si tu eres el usuario que lo creó.";
			$content = array('chat_id' => $chat_id, 'text' => $reply);
			$telegram->sendMessage($content);
			exec(' sqlite3 -header -csv '.$db_path.' "select * from segnalazioni;" > '.$csv_path. ' ');
			$log=$today. ",segnalazione aggiornata," .$chat_id. "\n";

	}
		//gestione segnalazioni georiferite
		elseif($location!=null)

		{
			if ($username==""){
				$content = array('chat_id' => $chat_id, 'text' => "Tienes que poner un nombre de usuario en los ajustes/configuración de Telegram",'disable_web_page_preview'=>true);
				$telegram->sendMessage($content);
				$log=$today.",".$todayd. ",nousernameset," .$chat_id.",".$username.",".$user_id."\n";
				file_put_contents('db/telegram.log', $log, FILE_APPEND | LOCK_EX);
				$this->create_keyboard($telegram,$chat_id);
				exit;
			}else
			{
				$text=strtoupper($username);
				$homepage="";
				// il GDRIVEKEY2 è l'ID per un google sheet dove c'è l'elenco degli username abilitati.
				$url ="https://spreadsheets.google.com/tq?tqx=out:csv&tq=SELECT%20%2A%20WHERE%20upper(D)%20LIKE%20%27%25".$text;
				$url .="%25%27%20&key=".GDRIVEKEY."&gid=".GDRIVEGID;
			//  $url="https://docs.google.com/spreadsheets/d/1r-A2a47HKuy7dUx4YreSmJxI4KQ-fc4v97J-xt5qqqU/gviz/tq?tqx=out:csv&tq=SELECT+*+WHERE+B+LIKE+%27%25VENERD%25%27+AND+A+LIKE+%27%251%25%27";
				$csv = array_map('str_getcsv', file($url));
				$count = 0;
				foreach($csv as $data=>$csv1){
					$count = $count+1;
				}
					if ($count >1)
						{
							$this->location_manager($username,$db,$telegram,$user_id,$chat_id,$location);
							exit;
							}else{
								$content = array('chat_id' => $chat_id, 'text' => $username.", no pareces tener autorización para enviar informes. Rellena este formulario: https://goo.gl/forms/29j5UtOx3MUkxoRG2.",'disable_web_page_preview'=>true);
								$telegram->sendMessage($content);
								$this->create_keyboard($telegram,$chat_id);
								exit;
							}

			}


		}
//elseif($text !=null)

else //($reply_to_msg != NULL)
{
if ($reply_to_msg != NULL){

	$response=$telegram->getData();

	$type=$response["message"]["video"]["file_id"];
	$text =$response["message"]["text"];
	$risposta="";
	$file_name="";
	$file_path="";
	$file_name="";


if ($type !=NULL) {
$file_id=$type;
$text="vídeo adjunto";
$risposta="ID ajunto:".$file_id."\n";
//$content = array('chat_id' => $chat_id, 'text' => "para enviar un adjunto tienes que hacer click sobre \xF0\x9F\x93\x8E y después File");
//$telegram->sendMessage($content);
exit;
}

$file_id=$response["message"]["photo"][0]["file_id"];

if ($file_id !=NULL) {

$telegramtk=TELEGRAM_BOT; // inserire il token
$rawData = file_get_contents("https://api.telegram.org/bot".$telegramtk."/getFile?file_id=".$file_id);
$obj=json_decode($rawData, true);
$file_path=$obj["result"]["file_path"];
$caption=$response["message"]["caption"];
if ($caption != NULL) $text=$caption;
//$risposta="ID dell'allegato: ".$file_id."\n";

$content = array('chat_id' => $chat_id, 'text' => "para enviar un adjunto tienes que hacer click sobre \xF0\x9F\x93\x8E y después File.\nEsto porqué la resolución de las fotos enviadas directamente desde la galería es muy baja. Por eso te pedimos enviar la misma foto como FICHERO así se mantiene la resolución original de tu foto.\nEnvía otra vez tu posición e intentalo de nuevo, por favor.");
$telegram->sendMessage($content);
$statement = "DELETE FROM ". DB_TABLE_GEO ." where bot_request_message = '" . $reply_to_msg['message_id'] . "'";
$db->exec($statement);
exec(' sqlite3 -header -csv '.$db_path.' "select * from segnalazioni;" > '.$csv_path. ' ');

$this->create_keyboard($telegram,$chat_id);
exit;
}
$typed=$response["message"]["document"]["file_id"];

if ($typed !=NULL){
$file_id=$typed;
$file_name=$response["message"]["document"]["file_name"];
$text="documento: ".$file_name." adjunto";
$risposta="ID adjunto:".$file_id."\n";

}

$typev=$response["message"]["voice"]["file_id"];
if ($typev !=NULL){
$file_id=$typev;
$text="audio adjunto";
$risposta="ID adjunto:".$file_id."\n";
//$content = array('chat_id' => $chat_id, 'text' => "para enviar un adjunto tienes que hacer click sobre \xF0\x9F\x93\x8E y después File.");
//$telegram->sendMessage($content);
exit;
}
$csv_path='db/map_data.txt';
$db_path='db/db.sqlite';
//echo $db_path;
$username=$response["message"]["from"]["username"];
$first_name=$response["message"]["from"]["first_name"];

$db1 = new SQLite3($db_path);
$q = "SELECT lat,lng FROM ".DB_TABLE_GEO ." WHERE bot_request_message='".$reply_to_msg['message_id']."'";
$result=	$db1->query($q);
$row = array();
$i=0;

while($res = $result->fetchArray(SQLITE3_ASSOC))
		{

				if(!isset($res['lat'])) continue;

				 $row[$i]['lat'] = $res['lat'];
				 $row[$i]['lng'] = $res['lng'];
				 $i++;
		 }

		 //inserisce la segnalazione nel DB delle segnalazioni georiferite
			 $statement = "UPDATE ".DB_TABLE_GEO ." SET file_id='". $file_id ."',filename='". $file_name ."',first_name='". $first_name ."',file_path='". $file_path ."',username='". $username ."' WHERE bot_request_message ='".$reply_to_msg['message_id']."'";
			 print_r($reply_to_msg['message_id']);
			 $db->exec($statement);

	  $reply = "Se ha registrado el informe n° ".$reply_to_msg['message_id']."\nGracias!\n";
 		$reply .= "Puedes verlo aquí:\nhttp://www.piersoft.it/vialiberabot/#18/".$row[0]['lat']."/".$row[0]['lng'];
 		$content = array('chat_id' => $chat_id, 'text' => $reply);
 		$telegram->sendMessage($content);
		$content = array('chat_id' => $chat_id, 'text' => " Después de enviar tu FICHERO puedes añadir un texto.\nTienes que digitar t:numseñalización:texto\npor ejemplo <b>t:".$reply_to_msg['message_id']."::coche aparcado en la acera</b>Solamente el usuario que ha enviado el mensaje puede modificarlo. ",'parse_mode'=>"HTML");
		$telegram->sendMessage($content);
 		$log=$today. ",information for maps recorded," .$chat_id. "\n";

 		exec(' sqlite3 -header -csv '.$db_path.' "select * from segnalazioni;" > '.$csv_path. ' ');
		$mappa = "Puedes verlo aquí:\nhttp://www.piersoft.it/vialiberabot/#18/".$row[0]['lat']."/".$row[0]['lng'];

		$content = array('chat_id' => GRUPPO, 'text' => "Enviando informe".$reply_to_msg['message_id']." por parte del usuario ".$username." día ".$today."\n".$mappa);
		$telegram->sendMessage($content);


	}
 	//comando errato

 	else{

 		 $reply = "Has enviado un comando inexistente. Acuérdate que tienes que enviar tu posición primero!";
 		 $content = array('chat_id' => $chat_id, 'text' => $reply);
 		 $telegram->sendMessage($content);

 		 $log=$today. ",wrong command sent," .$chat_id. "\n";

 	 }
}
 	//aggiorna tastiera
 	$this->create_keyboard($telegram,$chat_id);
 	//log
 	file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
	$statement = "DELETE FROM ". DB_TABLE_GEO ." WHERE username =' '";
	$db->exec($statement);
	exec(' sqlite3 -header -csv '.$db_path.' "select * from segnalazioni;" > '.$csv_path. ' ');

 }



// Crea la tastiera
function create_keyboard($telegram, $chat_id)
 {
	 			$option = array(["❓instrucciones"],["🌐posición","©️info"]);
				$keyb = $telegram->buildKeyBoard($option, $onetime=true);
				$content = array('chat_id' => $chat_id, 'reply_markup' => $keyb, 'text' => "[guarda la mappa delle segnalazioni su http://www.piersoft.it/vialiberabot/ oppure invia la tua segnalazione cliccando \xF0\x9F\x93\x8E]");
				$telegram->sendMessage($content);

 }




function location_manager($username,$db,$telegram,$user_id,$chat_id,$location)
	{
		if ($username==""){
			$content = array('chat_id' => $chat_id, 'text' => "Tienes que poner un nombre de usuario en los ajustes/configuración de Telegram",'disable_web_page_preview'=>true);
			$telegram->sendMessage($content);
			$log=$today.",".$todayd. ",nousernameset," .$chat_id.",".$username.",".$user_id."\n";
			file_put_contents('db/telegram.log', $log, FILE_APPEND | LOCK_EX);
			$this->create_keyboard($telegram,$chat_id);
			exit;
		}else
		{
			$lng=$location["longitude"];
			$lat=$location["latitude"];


			$reply="http://nominatim.openstreetmap.org/reverse?email=piersoft2@gmail.com&format=json&lat=".$lat."&lon=".$lng."&zoom=18&addressdetails=1";
			$json_string = file_get_contents($reply);
			$parsed_json = json_decode($json_string);
			//var_dump($parsed_json);
			$temp_c1 =$parsed_json->{'display_name'};
			if ($parsed_json->{'address'}->{'city'}) {
			//  $temp_c1 .="\ncittà: ".$parsed_json->{'address'}->{'city'};

			}

			$response=$telegram->getData();

			$bot_request_message_id=$response["message"]["message_id"];
			$time=$response["message"]["date"]; //registro nel DB anche il tempo unix

			$h = "1";// Hour for time zone goes here e.g. +7 or -4, just remove the + or -
			$hm = $h * 60;
			$ms = $hm * 60;
			$timec=gmdate("Y-m-d\TH:i:s\Z", $time+($ms));
			$timec=str_replace("T"," ",$timec);
			$timec=str_replace("Z"," ",$timec);
			//nascondo la tastiera e forzo l'utente a darmi una risposta

            $content = array('chat_id' => $chat_id, 'text' => "qué es lo que nos quieres decir ".$temp_c1."?", 'reply_markup' =>$forcehide, 'reply_to_message_id' =>$bot_request_message_id);

		  $bot_request_message=$telegram->sendMessage($content);

		      	$forcehide=$telegram->buildForceReply(true);

		  			//chiedo cosa sta accadendo nel luogo
		        $content = array('chat_id' => $chat_id, 'text' => "[escribe un mensaje o envía un FICHERO]", 'reply_markup' =>$forcehide, 'reply_to_message_id' =>$bot_request_message_id);


			//chiedo cosa sta accadendo nel luogo
			$bot_request_message=$telegram->sendMessage($content);


			//memorizzare nel DB
			$obj=json_decode($bot_request_message);
			$id=$obj->result;
			$id=$id->message_id;

			//print_r($id);
			$statement = "INSERT INTO ". DB_TABLE_GEO. " (lat,lng,user,username,text,bot_request_message,time,file_id,file_path,filename,first_name) VALUES ('" . $lat . "','" . $lng . "','" . $user_id . "',' ',' ','". $id ."','". $timec ."',' ',' ',' ',' ')";
			$db->exec($statement);


	}

}
}

?>
