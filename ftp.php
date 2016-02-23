<?php
	use Monolog\Logger;
	use Monolog\Handler\StreamHandler;

	class FTP {
		protected $connectionId;
		private $log;
		private $folderPath;
		
		function __construct($filePath){
			// create a log channel
			$this->log = new Logger('ftp');
			$this->log->pushHandler(new StreamHandler('ftp.log', Logger::INFO));

			$this->connectionId = ftp_connect(FTP_SERVER) or die("Could not connect to ftp server:" . FTP_SERVER);
			$this->setUploadFolder($filePath);
			$this->login();
		}

		/**
		* set the folder which will be uploaded to server
		**/
		protected function setUploadFolder($folderPath) {
			/**
			* should check if the file path exist, if not
			* should throw error
			**/
			if (!file_exists($folderPath)) {
				throw new Exception("$folderPath does not exist!", 1);
			}

			$this->folderPath = $folderPath;
		}

		private function login(){
		 	if(ftp_login($this->connectionId, FTP_USER, FTP_PASS)){
		 		$this->log->addInfo("ftp login successfully!");
		 		return true;
		 	}else{
		 		$this->log->addInfo("Counld connect as ". FTP_USER);
		 		return false;
		 	}
		}

	}