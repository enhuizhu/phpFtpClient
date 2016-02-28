<?php
	use Monolog\Logger;
	use Monolog\Handler\StreamHandler;

	class FTP {
		protected $connectionId;
		private $log;
		public $folderPath = "";
		public $destinationFolderPath = "";
		
		function __construct($filePath, $remotePath){
			// create a log channel
			$this->log = new Logger('ftp');
			$this->log->pushHandler(new StreamHandler('ftp.log', Logger::INFO));
			$this->connectionId = ftp_connect(FTP_SERVER) or die("Could not connect to ftp server:" . FTP_SERVER);
			$this->login();
			$this->setUploadFolder($filePath);
			$this->setDestinationFolder($remotePath);
		}

		/**
		* set the folder which will be uploaded to server
		**/
		public function setUploadFolder($folderPath) {
			/**
			* should check if the file path exist, if not
			* should throw error
			**/
			if (!file_exists($folderPath)) {
				throw new Exception("$folderPath does not exist!", 1);
			}

			$this->folderPath = substr($folderPath, -1) === "/" ? $folderPath : $folderPath . "/";
		}

		private function login(){
		 	if(ftp_login($this->connectionId, FTP_USER, FTP_PASS)){
		 		ftp_pasv($this->connectionId, true);
		 		$this->log->addInfo("ftp login successfully!");
		 		return true;
		 	}else{
		 		$this->log->addInfo("Counld connect as ". FTP_USER);
		 		return false;
		 	}
		}

		public function setDestinationFolder($folderPath) {
			$result = $this->isDir($folderPath);

			if (!$result) {
				throw new Exception("$folderPath does not exist!", 1);
			}

			$this->destinationFolderPath = substr($folderPath, -1) === "/" ? $folderPath : $folderPath . "/";
		}

		public function isDir($remotePath) {
			/**
			* get original directory
			**/
			$currentPath = $this->getCurrntPath();

			if (@ftp_chdir($this->connectionId,  $remotePath)) {
				ftp_chdir($this->connectionId, $currentPath);
				return true;
			}

			return false;
		}

		public function upload() {			
			if (empty($this->folderPath) || empty($this->destinationFolderPath)){ 
				throw new Exception("source folder or destination folder is not defined", 1);
			}



		}

		public function getIgnoreFoldersAndFiles() {
			if (empty($this->folderPath)) {
				throw new Exception("source folder does not exist", 1);
			}	

			if (!file_exists($this->folderPath . ".gitignore")) {
				return false;
			}

			$handle = fopen($this->folderPath . ".gitignore", "r");

			$result = array();
			
			if ($handle) {
			    while (($line = fgets($handle)) !== false) {
					 $filePath = trim($this->folderPath . $line);

					 if (file_exists($filePath)) {
					     array_push($result, array(
					     	"path" => $filePath,
					     	"type" => is_dir($filePath) ? "dir" : "file"
					     ));
					 }    
			    }

			    fclose($handle);
			} else {
				throw new Exception("can not open ignore file", 1);
			}

			return $result; 
		}

		public function uploadFile($filePath) {

		}

		public function getRmoteFilePathBaseOnLocal($filePath) {
			$startPoint = strlen($this->folderPath) - 1;
			$relativeLength = strlen($filePath) - strlen($this->folderPath) + 1;
			$relativePath = substr($filePath, $startPoint, $relativeLength);
			
			return $this->destinationFolderPath . $relativePath;
		}

		public function isSame($localFile, $remoteFile) {
			if (!basename($localFile) === basename($remoteFile)) {
				return false;
			}

			$remoteContent = $this->getRemoteFileContent($remoteFile);
			$localContent = @file_get_contents($localFile);
			
			if (md5($remoteContent) === md5($localContent)) {
				return true;
			}

			return false;
		}

		public function getRemoteFileContent($remoteFile) {
			$handle = "temp.txt";

			if (@ftp_get($this->connectionId, $handle, $remoteFile, FTP_ASCII)) {
				return file_get_contents($handle);
			}else{
				return false;
			}
		}

		public function listFiles($path) {
			return ftp_nlist($this->connectionId, $path);
		}

		public function getCurrntPath() {
			return ftp_pwd($this->connectionId);
		}

		public function getFileModifyTime($filePath) {
			return ftp_mdtm($this->connectionId, $filePath);
		}

		public function excuteSiteCommand($command) {
			return ftp_site($this->connectionId, $command);
		}
	}