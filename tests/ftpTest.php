<?php
class ftpTest extends PHPUnit_Framework_TestCase
{
	public $ftp = null;

	public function setUp() {
		$this->ftp = new FTP("/var/www/cnrestraurant", "/LeeGarden");
	}

	public function testSetUploadFoler() {		
		try {
			$this->ftp->setUploadFolder("test");
		} catch (Exception $e) {
			$this->assertEquals("test does not exist!", $e->getMessage());
			return ;
		}

		$this->fail("Expected Exception has not been raised.");
	}

	public function testSetUploadFolerWithRihtFolder() {
		$this->ftp->setUploadFolder("/var/");
		$this->assertEquals("/var/", $this->ftp->folderPath);
	}

	public function testListFiles() {
		$result = $this->ftp->listFiles("/");
		$this->assertEquals(is_array($result), true);
	}

	public function testGetCurrntPath() {
		$result = $this->ftp->getCurrntPath();
		$this->assertEquals($result, "/");
	}

	public function testGetFileModifyTime() {
		$result = $this->ftp->getFileModifyTime("/LeeGarden/index.php");
		$this->assertEquals(preg_match("/\d/", $result), true);

		$result = $this->ftp->getFileModifyTime("/LeeGarden/test.php");
		$this->assertEquals($result, -1);

		$result = $this->ftp->getFileModifyTime("/LeeGarden/test");
		$this->assertEquals($result, -1);
	}

	public function testSetDestinationFolder(){
		try{
			$this->ftp->setDestinationFolder("test");
		}catch(Exception $e){
			$this->assertEquals("test does not exist!", $e->getMessage());
			return ;			
		}

		$this->fail("Expected Exception has not been raised.");
	}

	public function testSetDestinationFolderWithCorrectPath() {
		$this->ftp->setDestinationFolder("/LeeGarden");
		$this->assertEquals("/LeeGarden/", $this->ftp->destinationFolderPath);
	}

	public function testIsDir() {
		$this->assertEquals($this->ftp->isDir("/test"), false);
		$this->assertEquals($this->ftp->isDir("/LeeGarden"), true);
		$this->assertEquals($this->ftp->isDir("/index.php"), false);
	}

	public function testUpload() {				
		try {
			$this->ftp->upload();
		} catch (Exception $e) {
			$this->assertEquals("source folder or destination folder is not defined", $e->getMessage());
			return ;
		}

		$this->fail("Expected Exception has not been raised.");
	}

	public function testUploadWithRightPath() {

	}

	public function testGetGitIgnorFilesNoSourePath() {
		$this->ftp->folderPath = "";
		
		try {
			$this->ftp->getIgnoreFoldersAndFiles();
		} catch (Exception $e) {
			$this->assertEquals("source folder does not exist", $e->getMessage());
			return ;
		}

		$this->fail("Expected Exception has not been raised.");
	}

	public function testGetGitIgnorFilesWhenFileNotExist() {
		$this->assertEquals($this->ftp->getIgnoreFoldersAndFiles(), false);
	}

	public function testgetIgnoreFoldersAndFiles() {
		$this->ftp->setUploadFolder("/var/www/cnrestraurant");
		$result = $this->ftp->getIgnoreFoldersAndFiles();
		$this->assertEquals(is_array($result), true);
	}

	public function testIsSame() {
		$this->assertEquals($this->ftp->isSame("/var/www/cnrestraurant/index.php", "/LeeGarden/index.php"), true);
		$this->assertEquals($this->ftp->isSame("/var/www/cnrestraurant/test.php", "/LeeGarden/index.php"), false);
		$this->assertEquals($this->ftp->isSame("/var/www/cnrestraurant/index.php", "/LeeGarden/test.php"), false);
	}

	public function testGetRmoteFilePathBaseOnLocal() {
		$result = $this->ftp->getRmoteFilePathBaseOnLocal("var/www/cnrestraurant/wp-admin/index.php");
		$this->assertEquals($result, "/LeeGarden/wp-admin/index.php");

		$result = $this->ftp->getRmoteFilePathBaseOnLocal("var/www/cnrestraurant/wp-admin/test/index.php");
		$this->assertEquals($result, "/LeeGarden/wp-admin/test/index.php");

	}		
}