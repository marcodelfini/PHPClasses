<?PHP
/**
 *	MySQL Class | PHP 5
 *
 *	Copyright (c) 2012
 *	Marco Delfini <info@marcodelfini.com>
 *	http://marcodelfini.com
 *
 *	This library is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU Lesser General Public
 *	License as published by the Free Software Foundation; either
 *	version 2.1 of the License, or (at your option) any later version.
 *
 *	This library is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *	Lesser General Public License for more details.
 *
 *	You should have received a copy of the GNU Lesser General Public
 *	License along with this library; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 *  http://www.gnu.org/copyleft/lesser.html
 *
 *  
 *  EXAMPLE:
 *  

// Inizializzazione instanza MySQL
// valorizza la variabile con la classe applicando i parametri:
// 1. Visualizza errori con die() - (bool) - Default: true
// 2. Invia errore via email - (string) - Default:
// 3. Salva un log degli errore - (string) - Default:
// 4. Includi le query andate a buon fine nel log - (bool) - Default: false
// 5. Seleziona il charset: latin1 (default), utf8 ...
$db = new MySQL(true, "", "logs/MySQL.log", true, "utf8");

// utilizza una connessione gia stabilita in precedenza
//$db->usaRisorsa($connessione);

// connette al db (host, port, user, pass, db)
$db->Open("localhost", 3306, "user", "pass", "db_name");

// esegue la query settando un errore personale in caso di fallimento
$ri = $db->Query("SELECT * FROM cmsv_sezioni", "Errore Query Clienti!!");
// ottiene i dati della SELECT da ciclare restituendoli come oggetti
while($riga = $db->getObject($ri)){
	echo $riga->campo;
}
// se trova almeno un valore
if($db->Found($ri)){
	// stampa i valori a partire dal terzo
		//$db->dataSeek($ri, 3);
	// ottiene un array di valori, e impostando l'array interno come associativo (true)
	$array_val = $db->getArray($ri, true);
	// conta i record restituiti
	$righe = $db->Count($ri);
		// ottiene l'ultimo ID inserito dopo una query INSERT
		$numID = $db->lastID();
	// libera la memoria
	$db->Free($ri);
	// Visualizza i Dati Ottenuti
	echo "<pre>";
	echo "N° Records: " . $righe;
	echo "<br /> <br />";
	print_r($array_val);
	echo "</pre>";
}

// altre funzioni
	// inserisce un array in una tabella
	$dati = array();
	$dati["campo"] = "valore";
	$dati["campo2"] = "valore2";
	$db->insertArray("tabella", $dati);
	// resetta l'autoincrement della tabella portandolo al primo id disponibile
	$db->resetIncrement("tabella");
	effettua una select e restituisce un campo
	echo $db->getField("campo", "tabella", "id = 1");
**/

class MySQL
{
	var $risorsa; // risorsa del db
	var $dieerror = true; // esce con il mysql error
	var $mailerror = ""; // invia una mail con l'errore della query.
	var $logfile = ""; // percorso dove salvare il log delle query
	var $logall = false; // decide se mostrare anche le query che hanno avuto successo

	function MySQL($dieerror = true, $mailerror = "", $logfile = "", $logall = false){
		$this->dieerror = $dieerror;
		$this->mailerror = $mailerror;
		$this->logfile = $logfile;
		$this->logall = $logall;
	}
	/*********************************** Funzioni Base ****************************************/

	/**
	 * Permette di usare una risorsa già aperta
	 *
	 * Param: $res(resource) - risorsa da utilizzare
	 */
	function usaRisorsa($res){
		// se $res è settata, non vuota, ed è una risorsa
		if(isset($res) && !empty($res) && is_resource($res)){
			// applicala come risorsa globale
			$this->risorsa = $res;
		}
	}

	/**
	 * Connette lo script al Database <acronym title="My Structured Query Language">MySQL</acronym>
	 *
	 * Param: $host(string) - server del database
	 * Param: $user(string) - username del database
	 * Param: $pass(string) - password del database
	 * Param: $db(string) - nome del database
	 */
	function Open($host, $port, $user, $pass, $db, $charset = "latin1"){
		// se i campi sono inseriti
		if(empty($host) || empty($user) || empty($db))
			exit();

		if(empty($port))
			$port = 3306;

		// connetto al' host
		$ris = mysql_connect($host.":".$port, $user, $pass) or $this->getErr("[CONNESSIONE] Errore di connessione all'host!");
		
		// seleziono il db
		mysql_select_db($db, $ris) or $this->getErr("[DB SELEZIONE] Errore nel selezione il database $db!");

		// setto la risorsa come globale della classe
		$this->risorsa = $ris;
		
		// setto il Charset
		$this->SetCharset($charset);
	}
	
	/**
	 * Permette di settare il Charset
	 *
	 * Param: $charset - può essere: latin1 (default), utf8 e molti altri
	 */
	function SetCharset($charset = "latin1"){
		mysql_set_charset($charset, $this->risorsa) or $this->getErr("[CHARSET] Errore nell'impostare il charset: ".$charset."!");
		$this->Query("SET NAMES ".$charset, "Settaggio NAMES su UTF-8");
		$this->Query("SET CHARACTER SET ".$charset, "Settaggio del CHARACTER SET su URF-8");
	}

	/**
	 * Libera le risorse della risorsa risultante dalla query
	 *
	 * Param: $query (resource link) - la risorsa ottenuta dalla funzione doQuery
	 */
	function Free($query){
		if(@mysql_free_result($query)){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * Restituisce l'ID generato dall' ultima query INSERT
	 */
	function lastID(){
		return @mysql_insert_id();
	}

	/**
	 * Restituisce le righe contate nella query
	 *
	 * Param: $query (resource link) - la risorsa ottenuta dalla funzione doQuery
	 */
	function Count($query){
		return @mysql_num_rows($query);
	}

	/**
	 * Restituisce true se trova almeno un record
	 *
	 * Param: $query (resource link) - la risorsa ottenuta dalla funzione doQuery
	 */
	function Found($query){
		if($this->Count($query) != 0){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * Mostra l'errore o manda la mail con l'errore
	 */
	private function ScriviLog($stringa = ""){
		if(!empty($this->logfile) && !empty($stringa)){
			$fp = @fopen($this->logfile, "a");
			@fwrite($fp, $tipo.date("d/m/Y H:i:s")." $stringa\r\n");
			@fclose($fp);
		}
	}

	/**
	 * Mostra l'errore o manda la mail con l'errore
	 *
	 * Param: $errore (string) - stringa di errore restituita da <acronym title="My Structured Query Language">MySQL</acronym>
	 * Param: $mErr (string) - stringa di errore da mostrare scelta da noi
	 */
	private function getErr($errore = ""){
		if(empty($errore)) $errore = mysql_error();

		// se bisogna mandare la mail
		if(!empty($this->mailerror)){
			// testo mail
			$testo = "Si è verificato un errore: ".$errore." \r\n \r\n Indirizzo del file chiamato: ".$_SERVER['REQUEST_URI'];

			// destinatario
			$to = $this->mailerror;

			// soggetto
			$subject = "MySQL: Errore query.";

			// headers
			$headers = "From: $to\r\n";
			$headers .= "Reply-To: $to\r\n";
			$headers .= "Return-Path: $to\r\n";

			// manda la mail
			if (!mail($to, $subject, $testo, $headers)){
				// se non va a buon fine, mostra l'errore
			   die("Errore durante l'invio della Segnalazione!");
			}
		}

		if(!empty($this->logfile)){
			$this->ScriviLog("[QUERY ERROR] ".$errore);
		}

		if($this->dieerror){
			// mostra errore personale
			die($errore);
		}
	}

	/**
	 * Esegue la query al database
	 *
	 * Param: $query (string) - la query da eseguire al database
	 * Param: $manualError (string) - errore personalizzato in caso di fallimento della query
	 */
	function Query($query, $manualError = "", $ScrivereLogs = true){
		// eseguo la query
		$rs = mysql_query($query, $this->risorsa) or $this->getErr("[QUERY ERRORE] ".$manualError." - ".mysql_error());

		// se è andata bene
		if($rs){
			if(!empty($this->logfile) && $this->logall == true && $ScrivereLogs == true){
				$this->ScriviLog("[QUERY OK] $query");
			}
			// restituisco il link di risorsa
			return $rs;
		}
	}

	/**
	 * Ottiene i dati come oggetti (da usare come mysql_fetch_object)
	 *
	 * Param: $query (resource link) - la risorsa ottenuta dalla funzione doQuery
	 */
	function getObject($query){
		// ottiene le righe come oggetto
		$rig = @mysql_fetch_object($query);

		// restituisce il tutto
		return $rig;
	}

	/**
	 * Chiude la connessione al Database
	 */
	function Close(){
		mysql_close($this->risorsa);
	}

	/*********************************** Funzioni Utili ****************************************/

	/**
	 * Ottiene i dati ottenuti da una query SELECT in un array
	 *
	 * Param: $query (resource link) - la risorsa ottenuta dalla funzione doQuery
	 * Param: $associativo (boolean) - determina se creare un sotto-array associativo per ogni riga, oppure no
	 */
	function getArray($query, $associativo = true){
		// dichiaro e svuoto l'array
		$arrayCampi = array();

		// ciclo i nomi dei campi nella SELECT e li metto in array
		for($i = 0; $i < @mysql_num_fields($query); $i++){
			$arrayCampi[] = @mysql_fetch_field($query)->name;
		}

		// dichiarazione e svuotamento array $dati
		$dati = array();

		// ciclo per ottenere i valori associativi in $linea
		while($linea = @mysql_fetch_array($query, MYSQL_ASSOC)){

			// dichiaro e svuoto l'array $par
			$par = array();
			// ciclo i nomi passati nell'array $arrayCampi
			foreach($arrayCampi as $nomi){
				// se è impostato l'associativo
				if($associativo){
					// mette in $par i valori come array associativo
					$par[$nomi] = $linea[$nomi];
				}else{ // ... altrimenti ...
					// mette in $par i valori come array numerato
					$par[] = $linea[$nomi];
				}
			}
			
			// aggiunge all'array $dati, l'array $par
			$dati[] = $par;
		}

		// restituisce i dati
		return $dati;
	}

	/**
	 * Muove il puntatore interno ad una riga
	 *
	 * Param: $query (resource) - la risorsa della query
	 * Param: $riga (int) - la riga da cui iniziare - Default: 0
	 */
	function dataSeek($query, $riga = 0){
		return @mysql_data_seek($query, $riga);
	}

	/**
	 * Inserisce un array (chiave => valore) nel database
	 *
	 * Param: $table (string) - la tabella del database
	 * Param: $array (array) - l'array da cui prendere i valori
	 */
	function insertArray($table, $array){
		$keys = array_keys($array);

		$values = array_values($array);

		$sql = 'INSERT INTO ' . $table . '(' . implode(', ', $keys) . ') VALUES ("' . implode('", "', $values) . '")';

		return($this->Query($sql));
	}

	/**
	 * Resetta l'ultimo ID autoincrement
	 *
	 * Param: $table (string) - la tabella del database
	 */
	function resetIncrement($table){
		$get = $this->Query("SELECT MAX(id) as mxid FROM $table");

		if($this->Found($get)){
			$max = $this->getObject($get);

			$mxid = (int)$max->mxid;

			$mxid++;

			$this->Query("ALTER TABLE $table AUTO_INCREMENT = $mxid");
		}
	}

	/**
	 * Ottiene un campo specifico
	 *
	 * Param: $campo (string) - il campo da restituire
	 * Param: $table (string) - la tabella da cui estrarre il campo
	 * Param: $where (string) - la clausula where
	 */
	function getField($campo, $table, $where){
		$query = $this->Query("SELECT $campo FROM $table WHERE $where LIMIT 1");

		$risultato = $this->getObject($query);

		return $risultato->$campo;
	}
}
?>