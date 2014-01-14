<?php
require_once("src/MGProductImport.php");

class TestMGProductImport
{
	static public function testFetchProductlist()
	{
		$doc = MGProductImport::fetchProductlist();	
		print $doc; 
		print PHP_EOL;
		return true;
	}

	static public function testParseProductlist()
	{
		$res = MGProductImport::$itemlist;
		if(false == $res){
			print "TestMGProductImport::testParseProductlist: itemlist is false\n";
			return false;
		}
		switch(MGImportSettings::DOCTYPE){
			case MGImportSettings::JSON:
				$res = json_encode($res);
				break;
		}
		if(null == $res){
			print "TestMGProductImport::testParseProductlist: itemlist is null\n";
			return false;
		}
		print_r($res);
		print PHP_EOL;
		return true;
	}

	static public function testWriteProductlist()
	{
		$doc = MGProductImport::fetchProductlist();
		$col = MGProductImport::parseProductlist($doc);
		$res = MGProductImport::writeProductsDB($col);	
		return $res;
	}

	static public function testExportProductlistToCSV()
	{
		MGProductImport::exportProductlistToCSV();
		if(!(is_file(MGImportSettings::CSVEXPORT))){
			print "TestMGProductimport::testExportProductlist: file is null" . PHP_EOL;
			return false;
		}
		return true;
	}

	static public function testExportProductlistToXML()
	{
		$res = MGProductImport::exportProductlistToXML();
		return $res;
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
		return $res;
	}

	static public function testFetchProduct()
	{
		$doc = MGProductImport::fetchProduct(8);
		$col = MGProductImport::parseProductlist($doc);
		return $res;
	}

	static public function testInitMage()
	{
		$res = MGProductImport::initMagento();
		return $res;
	}

	static public function testImportItems()
	{
		$res = MGProductImport::importItems();
		return $res;
	}

	// ONE ITEM
	// Imports one item (category, product, productgroup) 
	// (selected by the Karlie export id (the *increment id kind of
	// id id id id id id id id 
	// from the export list (karlie service)
	static public function testImportItemWithExportIndex($index)
	{
		$itemlist = MGProductImport::$itemlist;
		$temp = new stdClass();
		$temp->products = array(); 
		foreach($itemlist->products as $item){
			print $index . " : " . $item->sku . PHP_EOL;
			switch($item->type){
				// Products get selected by their SKU
				case MGImportSettings::PRODUCT_DEFAULT:
					if($item->sku == $index){
						$temp->products[]= $item;
					}
					break;
				// Everything else but a products get selected by its category id prefix and id 
				case MGImportSettings::PRODUCT_GROUP:
				case MGImportSettings::CATEGORY:
					if((MGImportSettings::CATPREFIX . $item->id) == $index){
						$temp->products[]= $item;
					}
					break;
			}
		}
		$res = MGProductImport::importItems($temp);
		return $res;
	}

	// ONE ITEM : ASSET
	// Imports image of a given item (index from the karlie export list)
	// Selected by the Karlie export id
	static public function testImportImageWithExportIndex($index)
	{
		$itemlist = MGProductImport::$itemlist;
		foreach($itemlist->products as $item){
			$coll = array();
			switch($item->type){
				case MGImportSettings::PRODUCT_DEFAULT:
					// Selects product by sku
					if($item->sku == $index){
						if(null != ($res = Mage::getModel("catalog/product")->loadByAttribute("sku", $item->sku))){
							$coll[]= Mage::getModel("catalog/product")->loadByAttribute("sku", $item->sku);
							$res = MGProductImport::importProductImages($coll);
						}
						break;
					}
				case MGImportSettings::PRODUCT_GROUP:
				case MGImportSettings::CATEGORY:
					// Selects product by 999 +id
					if(MGImportSettings::CATPREFIX . $item->id == $index){
						if(null != ($res = Mage::getModel("catalog/category")->load(MGImportSettings::CATPREFIX . $item->id))){
							$coll[]= Mage::getModel("catalog/category")->load(MGImportSettings::CATPREFIX . $item->id);
							$res = MGProductImport::importCategoryImages($coll);
						}
					}
					break;
			}
		}
		return $res;
	}
	
	static public function testDownloadImages()
	{
		$res = MGProductImport::downloadImages();
		return $res;
	}

	static public function testReindexMagento()
	{
		$res = MGProductImport::reindexMagento();
		for($i = 1; $i <= 9; $i++){
			$proc = Mage::getModel("index/process")->load($i);
			$proc->reindexAll();
		}
		return $res;
	}

	static public function testDeleteProducts()
	{
		$res = MGProductImport::deleteProducts();
		return $res;
	}

	static public function testBackupProducts()
	{
		$res = MGProductImport::backupProducts();
		return $res;
	}

	static public function testFillImageCache()
	{
		$res = MGProductImport::fillImageCache();
		return $res;
	}

	static public function testCleanImageCache()
	{
		$res = MGProductImport::cleanImageCache();
		return $res;
	}

	static public function testLinkProducts()
	{
		$res = MGProductImport::linkProducts();
		return $res;
	}

	static public function testDeleteCategories()
	{
		$res = MGProductImport::deleteCategories();
		return $res;
	}

	static public function testBatchImport()
	{
		$itemlist = MGProductImport::$itemlist;
		foreach($itemlist->products as $item){
			$res = TestMGProductImport::testImportItemWithExportIndex($item->id);
			$res = TestMGProductImport::testImportImageWithExportIndex($item->id);
		}
		return $res;
	}

	static public function testImportImages2($skus)
	{
		foreach($skus as $sku){
			MGProductImport::importImageOfProduct($sku);
		}				
	}

	static public function testImportImages3()
	{
		MGProductImport::importImages();
	}

	static public function testSelectDirtyProducts()
	{
		// assume somethong
		MGProductImport::selectDirtyProducts();
	}

	static public function testSubmitEditedProducts()
	{
		MGProductImport::submitEditedProducts();	
	}

	static public function testWriteImportTimestamp()
	{
		// assume something
		// i don't care
		$res = MGProductImport::writeImportTimestamp();
	}

	static public function testReadImportTimestamp()
	{
		$res = MGProductImport::readImportTimestamp();
	}
}

date_default_timezone_set("Europe/Berlin");

// MGProductImport::parseProductlist(MGProductImport::fetchProductlist());

// TestMGProductImport::testSelectDirtyProducts();
// 
// TestMGProductImport::testWriteImportTimestamp();
// TestMGProductImport::testReadImportTimestamp();
TestMGProductImport::testSubmitEditedProducts();

/*
TestMGProductImport::testDeleteCategories();
TestMGProductImport::testDeleteProducts();
TestMGProductImport::testImportItems();
TestMGProductImport::testCleanImageCache();
TestMGProductImport::testImportImages3();
*/

// TestMGProductImport::testImportImages2(array("1", "31473"));
// TestMGProductImport::testDownloadImages();
// TestMGProductImport::testFillImageCache();

/*
TestMGProductImport::testImportImageWithExportIndex("191919191");
TestMGProductImport::testImportImageWithExportIndex("15037");
TestMGProductImport::testImportItemWithExportIndex("2");
TestMGProductImport::testImportImageWithExportIndex("2");
TestMGProductImport::testReindexMagento();
*/

// TestMGProductImport::testBatchImport();
// TestMGProductImport::testImportItemWithExportIndex("87051");
// TestMGProductImport::testImportImageWithExportIndex("87051");
