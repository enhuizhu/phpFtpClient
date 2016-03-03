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
			$this->validateGitFolder();
			$this->setDestinationFolder($remotePath);
			$this->getIgnoreFoldersAndFiles();
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

			$files = $this->getFiltedFilesAndDirs($this->folderPath);
           	
			foreach ($files as $file) {
				$this->uploadFile($file);
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

			$this->ignoredFiles = array();
			$this->ignoredFolders = array();
			
			if ($handle) {
			    while (($line = fgets($handle)) !== false) {
					 $filePath = trim($this->folderPath . $line);

					 if (file_exists($filePath)) {
					 	if (is_dir($filePath)) {
					 		array_push($this->ignoredFolders, $filePath);
					 	}else{
					 		array_push($this->ignoredFiles, $filePath);
					 	}
					 }    
			    }

			    fclose($handle);
			} else {
				throw new Exception("can not open ignore file", 1);
			}
		}

		public function uploadFile($filePath) {
			$remoteFilePath = $this->getRmoteFilePathBaseOnLocal($filePath);
			
			if ($this->isSame($filePath, $remoteFilePath)) {
				$this->printToTerminal("same files, ignore $filePath");
				return false;
			}

			if (@ftp_put($this->connectionId, $remoteFilePath, $filePath, FTP_ASCII)) {
				$this->printToTerminal("$filePath has been upload successfully");
				return true;
			}else{
			    $remoteFilePath = $this->getRmoteFilePathBaseOnLocal($filePath);
			    $remotePath = $this->getFilePathWithoutFileName($remoteFilePath);
				$this->mkRemoteDir($remotePath);
				$this->uploadFile($filePath);
			}
		}

		public function printToTerminal($msg) {
			fputs(STDOUT, "$msg \n");
		}

		public function getFilePathWithoutFileName($filePath) {
			$fileName = basename($filePath);
			return rtrim($filePath, $fileName);
		}

		public function hasGit() {
			$gitPath = $this->folderPath . ".git";
			return file_exists($gitPath);
		}

		public function validateGitFolder() {
			if (!$this->hasGit()) {
				throw new Exception($this->folderPath . " does not has git installed", 1);
			}

			return true;
		}

		public function getFiltedFilesAndDirs($filePath) {
			if (in_array($filePath, $this->ignoredFolders)) {
				$this->printToTerminal("git ignore $filePath");
				return array();
			}

			$results = scandir($filePath);

			$results = array_filter($results, function($result) {
				return $result != "." && $result != ".." 
					&& $result != ".git" && $result != ".gitignore"
					&& $result != ".DS_Store";
			});

			if (substr($filePath, -1) !== "/") {
				$filePath .= "/";
			}
			
			$tempArr = array();
			
			foreach ($results as $result) {
				$exactPath = $filePath . $result;

				if (in_array($exactPath, $this->ignoredFiles)) {
					$this->printToTerminal("git ignore $exactPath");
					continue;
				}

				if (is_dir($exactPath)) {
					$tempArr = array_merge($tempArr, $this->getFiltedFilesAndDirs($exactPath));
				}else{
					array_push($tempArr, $exactPath);
				}
			}

			return $tempArr;
		}

		public function deleteRemoteFile($remotePath) {
			try {
				if(ftp_delete($this->connectionId, $remotePath)) {
				 	return true;
				}else{
				   	return false;
				}	
			} catch (Exception $e) {
				return true;
			}
		}

		public function getRmoteFilePathBaseOnLocal($filePath) {
			$startPoint = strlen($this->folderPath);
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
				$this->printToTerminal("some files!");
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

		public function getAllPosiblePaths($filePath) {
			$paths = array_filter(explode("/", $filePath), function($v) {
				return $v !== "/" && !empty($v);
			});
			
			$newPaths = array();
			$index = 0;
			
			foreach ($paths as $key => $value) {
				if ($index === 0) {
					array_push($newPaths, "/" . $value);
				}else{
					array_push($newPaths, $newPaths[$index - 1] . "/" . $value);
				}

				$index ++;
			}
			
			return $newPaths;
		}

		public function mkRemoteDirBaseOnArray($remotePaths) {
			foreach ($remotePaths as $key => $value) {
				if (!$this->isDir($value)) {
					$this->mkRemoteDir($value);
				}	
			}

			return true;
		}

		public function mkRemoteDir($remotePath) {
			try{
				if (ftp_mkdir($this->connectionId, $remotePath)) {
				    $this->printToTerminal("make directory $remoteFilePath successfully!");
				    return true;
				}else{
					$this->printToTerminal("there is some problem of making directory $remoteFilePath.");
					return false;
				}
			}catch(Exception $e){
				$paths = $this->getAllPosiblePaths($remotePath);
				return $this->mkRemoteDirBaseOnArray($paths);
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