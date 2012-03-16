<?php 

	//requires phRETS Class
	require_once('phrets.php');

	/**
	 * MLS RETs helper class for phRETS, a very simple wrapper for the example phRETS functions 
	 * @author danielboorn
	 * @contact daniel.boorn@gmail.com
	 * @license Apache 2.0 -- http://www.apache.org/licenses/LICENSE-2.0.html
	 * @disclaimer see Apache 2.0 license disclaimer
	 * @requires phrets.php -- http://troda.com/
	 */
	class RETSHelper{
		
		protected $settings;//rets login information
		protected $rets;//phrets object
		
		public $debugMode = true;
		public $querySegment = 10;//download segment limit
		
		
		/**
		 * Class contructor
		 * @param {Array} $settings
		 * @return {Void}
		 */
		public function __construct($settings){
			/**
			 * Requirements on settings
			 * login_url => RETS login URL
			 * username => RETS username
			 * password => RETS password
			 */
			$this->settings = (object) $settings;
		}
		
		/**
		 * Debug Output
		 * @params [Object]
		 * @return {Void}
		 */
		protected function sysDebug(){
			if(!$this->debugMode) return;
			$args = func_get_args();
			for($i=0;$i<sizeof($args);$i++){
				var_dump($args[$i]);
			}
		}
		
		
		/**
		 * Start phRETS Class with default information (settings)
		 * @return {Boolean} true on success
		 */
		protected function startPhRets(){
			$this->rets = new phRETS;
			if(($this->rets->Connect($this->settings->login_url, $this->settings->username, $this->settings->password))!==true){
				$this->sysDebug($this->rets->Error());
				return false;
			}
			return true;
		}
		
		/**
		 * Connect to RETS with defualt settings
		 * @return {Void}
		 */
		public function connect(){
			return $this->startPHRETS();
		}
		
		/**
		 * Get create table SQL from meta information
		 * @param {String} $resource
		 * @param {String} $class
		 * @param {String} $tableName
		 */
		public function getTableSqlFromMeta($resource,$class,$tableName = ""){
			$retsResourceInfo = $this->rets->GetMetadataInfo();	
			$retsMetadata = $this->rets->GetMetadata($resource, $class);
			$tableName = $tableName == "" ? "rets_".strtolower($resource)."_".strtolower($class) : $tableName;
			return $this->createTableSqlFromMetadata($tableName, $retsMetadata, $retsResourceInfo[$resource]['KeyField']);
		}
		
		/**
		 * Create database table SQL from meta data
		 * @source http://troda.com/projects/phrets/index.php?title=Create_table_sql_from_metadata
		 * @copyright http://troda.com/projects/phrets/index.php?title=Create_table_sql_from_metadata
		 * @author http://troda.com/projects/phrets/index.php?title=Create_table_sql_from_metadata
		 * @param $table_name
		 * @param $rets_metadata
		 * @param $key_field
		 * @param $fieldPrefix
		 */
		public function createTableSqlFromMetadata($table_name, $rets_metadata, $key_field, $fieldPrefix = "") {
	
	        $sqlQuery = "CREATE TABLE {$table_name} (\n";
	
	        foreach ($rets_metadata as $field) {
	
	                $field['SystemName'] = "`{$fieldPrefix}{$field['SystemName']}`";
	
	                $cleanedComment = addslashes($field['LongName']);
	
	                $sqlMake = "{$field['SystemName']} ";
	
	                if ($field['Interpretation'] == "LookupMulti") {
	                        $sqlMake .= "TEXT";
	                }
	                elseif ($field['Interpretation'] == "Lookup") {
	                        $sqlMake .= "VARCHAR(50)";
	                }
	                elseif ($field['DataType'] == "Int" || $field['DataType'] == "Small" || $field['DataType'] == "Tiny") {
	                        $sqlMake .= "INT({$field['MaximumLength']})";
	                }
	                elseif ($field['DataType'] == "Long") {
	                        $sqlMake .= "BIGINT({$field['MaximumLength']})";
	                }
	                elseif ($field['DataType'] == "DateTime") {
	                        $sqlMake .= "DATETIME default '0000-00-00 00:00:00'";
	                }
	                elseif ($field['DataType'] == "Character" && $field['MaximumLength'] <= 255) {
	                        $sqlMake .= "VARCHAR({$field['MaximumLength']})";
	                }
	                elseif ($field['DataType'] == "Character" && $field['MaximumLength'] > 255) {
	                        $sqlMake .= "TEXT";
	                }
	                elseif ($field['DataType'] == "Decimal") {
	                        $pre_point = ($field['MaximumLength'] - $field['Precision']);
	                        $postPoint = !empty($field['Precision']) ? $field['Precision'] : 0;
	                        $sqlMake .= "DECIMAL({$field['MaximumLength']},{$postPoint})";
	                }
	                elseif ($field['DataType'] == "Boolean") {
	                        $sqlMake .= "CHAR(1)";
	                }
	                elseif ($field['DataType'] == "Date") {
	                        $sqlMake .= "DATE default '0000-00-00'";
	                }
	                elseif ($field['DataType'] == "Time") {
	                        $sqlMake .= "TIME default '00:00:00'";
	                }
	                else {
	                        $sqlMake .= "VARCHAR(255)";
	                }
	
	                $sqlMake .= " COMMENT '{$cleanedComment}'";
	                $sqlMake .= ",\n";
	
	                $sqlQuery .= $sqlMake;
	        }
	
	        $sqlQuery .= "PRIMARY KEY(`{$fieldPrefix}{$key_field}`) )";
	
	        return $sqlQuery;
	
		}

		/**
		 * Get media object properties
		 * @param {String} $resource
		 * @return {Object}
		 */
		function getMetaObjects($resource){
			return $this->rets->GetMetadataObjects("Property");	
		}
		
		/**
		 * Download images of type Photo from server and save with jpg file ext.
		 * @param {String} $resource
		 * @param {String} $uId
		 * @return {Array} results
		 */
		public function downloadImages($resource,$uId){
			return $this->downloadMedia($resource,'Photo','jpg',$uId);
		}
		
		/**
		 * Download media objects from server and save to file
		 * @author http://troda.com/projects/phrets/index.php?title=GetObject
		 * @source http://troda.com/projects/phrets/index.php?title=GetObject
		 * @copyright http://troda.com/projects/phrets/index.php?title=GetObject
		 * @param {String} $resource
		 * @param {String} $type
		 * @param {String} $fileExt
		 * @param {String} $uId
		 * @return {Array} results
		 */
		public function downloadMedia($resource,$type,$fileExt,$uId){
			$items = $this->rets->GetObject($resource,$type,$uId);
			$totalSaved = $totalFailed = 0;
			$errorList = array();
			foreach($items as $object){
				$listing = $object['Content-ID'];
				$number = $object['Object-ID'];
				if ($object['Success'] == true) {
					$totalSaved++;
					file_put_contents(sprintf("_%s_%s_%s.%s", $type, $object['Content-ID'], $object['Object-ID'], $fileExt), $object['Data']);
				}
				else {
					$totalFailed++;
					$errorList[] = array(
						'objectId'=>$object['Object-ID'],
						'replyCode'=>$object['ReplyCode'],
						'replyText'=>$object['ReplyText'],
					);
				}
			}
			return array(
				'totalSaved'=>$totalSaved,
				'totalFailed'=>$totalFailed,
				'errors'=>$errorList,
			);
		}
		
		/**
		 * Import listing CSV file into MySQL database table with columns matching header columns in CSV
		 * @param {String} $fileName
		 * @param {Object} $mysqlConn
		 * @param {String} $tableName
		 * @return {Array} results
		 */
		public function importCsvFile($fileName,$mysqlConn,$tableName){
			if (($handle = fopen($fileName, "rb")) === FALSE){
				return false;	
			}
			
			$row = 0;
			$batchSize = 10;
			$insertStr = "INSERT INTO {$tableName}";
			$sqlList = array();
		    while (($data = fgetcsv($handle, ",")) !== FALSE) {
		    	$row++;
		    	if($row==1){
		    		$insertStr .= "(`" . implode("`,`", $data) . "`) VALUES ";
		    		continue;
		    	}
		    	for($i=0;$i<count($data);$i++){
		    		$data[$i] = trim($data[$i]) != "" ? sprintf("'%s'",mysql_real_escape_string($data[$i],$mysqlConn)) : "NULL";
		    	}
		    	$sqlList[] = "(" . implode(",",$data) . ")";
		    	
		    	if(count($sqlList)%$batchSize==0){
		    		$sql = $insertStr . implode(",",$sqlList);
		    		if(mysql_unbuffered_query($sql,$mysqlConn)!==true){
		    			$this->sysDebug(mysql_error());
		    			return false;
		    		}
		    		$sqlList = array();
		    	}
		    }
		    
			if(count($sqlList)>0){//insert remaining 
	    		$sql = $insertStr . implode(",",$sqlList);
	    		if(mysql_unbuffered_query($sql,$mysqlConn)!==true){
	    			$this->sysDebug(mysql_error());
	    			return false;
	    		}
	    		$sqlList = array();
	    	}
		    
	    	fclose($handle);
		    return array("total"=>$row); 
		}
		
		/**
		 * Download RETS properties by query with offset
		 * @copyright http://troda.com/projects/phrets/index.php?title=Example-2-a
		 * @source http://troda.com/projects/phrets/index.php?title=Example-2-a
		 * @author http://troda.com/projects/phrets/index.php?title=Example-2-a
		 * @param {String} $query
		 * @param {String} $class
		 * @param {Int} $limit
		 * @param {Int} $offset
		 * @return {Array} $result
		 */
		public function search($query,$class){
			$offset = 1;
			$hasRows = true;
	        $totalResults = 0;
			
			$fileName = strtolower("property_{$class}.csv");
	        $fh = fopen($fileName, "w+");
			
			//while results are found
			while($hasRows){
				$search = $this->rets->SearchQuery("Property", $class, $query, array('Limit' => $this->querySegment, 'Offset' => $offset, 'Format' => 'COMPACT-DECODED', 'Count' => 1));
				
				$totalResults = $this->rets->TotalRecordsFound();
				if($totalResults<1){
					$this->sysDebug($this->rets->Error());
					break;	
				}
				
				if($offset==1){
					$columns = $this->rets->SearchGetFields($search);
					fputcsv($fh, $columns);
				}
				
				while ($record = $this->rets->FetchRow($search)) {
					$row = array();
                    foreach ($columns as $column) {
                    	$row[] = $record[$column];
                    }
                    fputcsv($fh, $row);
                }
				
				$offset = ($offset + $this->rets->NumRows());	            
				$this->rets->FreeResult($search);
				$hasRows = $this->rets->IsMaxrowsReached();
	        }
			
	        return array(
	        	"total"=>$totalResults,
	        	"fileName"=>$fileName,
	        );
		}
		
		
		
	}
