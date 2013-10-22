<?php
require_once("src/MGProductImport.php");

class TestMGProductImport
{
	static public function testFetchProductlist()
	{
		$doc = MGProductImport::fetchProductlist();	
		print $doc; 
		return true;
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
		$doc = MGProductImport::fetchProductlist();
		$productlist = MGProductImport::parseProductlist($doc);
		$res = MGProductImport::importItems($productlist);
		return $res;
	}

	// ONE ITEM
	// Imports one item (category, product, productgroup) 
	// (selected by the Karlie export id (the *increment id kind of
	// id id id id id id id id 
	// from the export list (karlie service)
	static public function testImportItemWithExportIndex($index)
	{
		$doc = MGProductImport::fetchProductlist();
		$itemlist = MGProductImport::parseProductlist($doc);
		$temp = new stdClass();
		$temp->products = array(); 
		foreach($itemlist->products as $item){
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
		$res = MGProductImport::initMagento();
		$doc = MGProductImport::fetchProductlist();
		$itemlist = MGProductImport::parseProductlist($doc);
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
	
	static public function testImportImages()
	{
		$doc = MGProductImport::fetchProductlist();
		$itemlist = MGProductImport::parseProductlist($doc);
		$res = MGProductImport::importCategoryImages($itemlist);
		$res = MGProductImport::importProductImages($itemlist);
		return $res;
	}

	static public function testDownloadImages()
	{
		$doc = MGProductImport::fetchProductlist();
		$itemlist = MGProductImport::parseProductlist($doc);
		$items = array();
		$items = new stdClass();
		$items->products = array();
		foreach($itemlist->products as $item){
			$items->products[] =$items;
		}
		$res = MGProductImport::importCategoryImages($items);
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
		$res =MGProductImport::backupProducts();
		return $res;
	}

	static public function testDeleteImageGalleries()
	{
		$res = MGProductImport::deleteImageGalleries();
		return $res;
	}

	static public function testFillImageCache()
	{
		$res = MGProductImport::fillImageCache();
		return $res;
	}

	static public function testCleanMagentoCache()
	{
		$res = MGProductImport::cleanMagentoCache();
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
		$doc = MGProductImport::fetchProductlist();
		$itemlist = MGProductImport::parseProductlist($doc);
		foreach($itemlist->products as $item){
			$res = TestMGProductImport::testImportItemWithExportIndex($item->id);
			$res = TestMGProductImport::testImportImageWithExportIndex($item->id);
		}
		return $res;
	}
}



// TestMGProductImport::testDeleteImageGalleries();
// TestMGProductImport::testDeleteCategories();
// TestMGProductImport::testDeleteProducts();

// TestMGProductImport::testImportItems();
// TestMGProductImport::testDownloadImages();
// TestMGProductImport::testImportImages();
// TestMGProductImport::testCleanMagentoCache();

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
TestMGProductImport::testImportImageWithExportIndex("87051");
