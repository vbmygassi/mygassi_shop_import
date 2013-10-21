<?php
require_once("src/MGProductImport.php");

class TestMGProductImport
{
	static public function testFetchProductlist()
	{
		$doc = MGProductImport::fetchProductlist();	
		print $doc; 
	}

	static public function testParseProductlist()
	{
		$doc = MGProductImport::fetchProductlist();
		$res = MGProductImport::parseProductlist($doc);
		if(false == $res){
			print "TestMGProductImport::testParseProductlist: doc is false\n";
			return false;
		}
		switch(MGImportSettings::DOCTYPE){
			case MGImportSettings::JSON:
				$res = json_encode($res);
				break;
		}
		if(null == $res){
			print "TestMGProductImport::testParseProductlist: doc is null\n";
			return false;
		}
		print_r($res);
		print "\n";
		return true;
	}

	static public function testWriteProductlist()
	{
		$doc = MGProductImport::fetchProductlist();
		$col = MGProductImport::parseProductlist($doc);
		$res = MGProductImport::writeProductsDB($col);	
	}

	static public function testExportProductlistToCSV()
	{
		MGProductImport::exportProductlistToCSV();
		if(!(is_file(MGImportSettings::CSVEXPORT))){
			print "TestMGProductimport::testExportProductlist: file is null\n";
			return false;
		}
	}

	static public function testExportProductlistToXML()
	{
		MGProductImport::exportProductlistToXML();
	}

	static public function testSerializeProductlist()
	{
		$doc = MGProductImport::fetchProductlist();
		$res = MGProductImport::serializeProductlist($doc);
		print $res;
	}

	static public function testUnserializeProductlist()
	{
		$res = MGProductImport::unSerializeProductlist();
		print_r($res);
	}

	static public function testFetchProduct()
	{
		$doc = MGProductImport::fetchProduct(8);
		$col = MGProductImport::parseProductlist($doc);
		print_r($col);
	}

	static public function testInitMage()
	{
		$res = MGProductImport::initMagento();
		print_r($res);
	}

	static public function testImportItems()
	{
		$doc = MGProductImport::fetchProductlist();
		$productlist = MGProductImport::parseProductlist($doc);
		$res = MGProductImport::importItems($productlist);
	}

	static public function testImportImages()
	{
		$res = MGProductImport::importImages();
	}

	static public function testDownloadImages()
	{
		$res = MGProductImport::downloadImages();
	}

	static public function testReindexMagento()
	{
		$res = MGProductImport::reindexMagento();
		for($i = 1; $i <= 9; $i++){
			$proc = Mage::getModel("index/process")->load($i);
			$proc->reindexAll();
		}
	}

	static public function testDeleteProducts()
	{
		$res = MGProductImport::deleteProducts();
	}

	static public function testBackupProducts()
	{
		$res =MGProductImport::backupProducts();
	}

	static public function testDeleteImageGalleries()
	{
		$res = MGProductImport::deleteImageGalleries();
	}

	static public function testFillImageCache()
	{
		$res = MGProductImport::fillImageCache();
	}

	static public function testCleanMagentoCache()
	{
		$res = MGProductImport::cleanMagentoCache();
	}

	static public function testLinkProducts()
	{
		$res = MGProductImport::linkProducts();
	}

	static public function testDeleteCategories()
	{
		$res = MGProductImport::deleteCategories();
	}
}


TestMGProductImport::testDeleteCategories();
TestMGProductImport::testDeleteProducts();
TestMGProductImport::testDeleteImageGalleries();
TestMGProductImport::testImportItems();
TestMGProductImport::testDownloadImages();
TestMGProductImport::testImportImages();
TestMGProductImport::testCleanMagentoCache();
TestMGProductImport::testFillImageCache();
// TestMGProductImport::testReindexMagento();
