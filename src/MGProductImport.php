<?php
/*


	MyGassi Product Import







											*/



class MGProductImport
{
		
	/*
	Fetches a product import list (XML, JSON) from a given HOST | SERVICE
	returns object productList;
	*/
	static public function fetchProductlist()
	{
		MGProductImport::log("fetchProductlist(): " . MGImportSettings::PRODUCTLIST. PHP_EOL);
		$c = curl_init(MGImportSettings::PRODUCTLIST);
		$res = curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		$productlist = curl_exec($c); 
		$res = curl_close($c);
		$res = MGProductImport::bckppProductlist();
		$res = MGProductImport::saveProductlist($productlist);
		return $productlist;
	}

	/*
	Saves the "current" productlist
	-- changes !!---
	*/
	static private function saveProductlist($productlist)
	{
		MGProductImport::log("saveProductlist(): " . PHP_EOL);
		$path = MGImportSettings::PRODUCTLISTCOPY;
		MGProductImport::log("saveProductlist(): to: " . $path . PHP_EOL);
		$res = file_put_contents($path, $productlist);
		return $res;
	}

	/*
	Backups the "old" product list
	*/
	static private function bckppProductlist()
	{
		MGProductImport::log("bckppProductlist(): " . PHP_EOL);
		if(!file_exists(MGImportSettings::PRODUCTLISTCOPY)){
			return false;
		}
		$path = MGImportSettings::PRODUCTLISTBCKPP . date("U");
		MGProductImport::log("Moving productlist copy to: " . $path . PHP_EOL);
		$res = rename(MGImportSettings::PRODUCTLISTCOPY, $path); 
		return $res;	
	}
	
	/*	
	Serializes a product list 
	*/
	static public function serializeProductlist($productlist)
	{
		MGProductImport::log("serializeProductlist(): " . PHP_EOL);
		$chunk = serialize($productlist);
		if(!(file_put_contents(MGImportSettings::PHPEXPORT, $chunk))){
			return false;
		}
		return true;
	}

	/*
	Loads serialized product list
	*/	
	static public function unSerializeProductlist()
	{
		MGProductImport::log("unserializeProductlist(): " . PHP_EOL);
		if(false == ($temp = file_get_contents(MGImportSettings::PHPEXPORT))){
			return false; 	
		}
		return unserialize($temp);
	}
	
	/*
	Parses a given product list
	*/
	static public function parseProductlist($productlist)
	{
		MGProductImport::log("parseProductlist(): " . PHP_EOL);
		$result = false;
		switch(MGImportSettings::DOCTYPE){
			case MGImportSettings::JSON:
				$result = json_decode($productlist);
				// Workaround (as for the missing ids (in some documents))
				foreach($result->products as $key=>$value){
					if(!isset($value->id)){
						$value->id = $key;	
					}
				}
				MGProductImport::$itemlist = $result;		
				break;
		}
		return $result;
	}
	
	/*
	Parses a range of products 
	*/
	static public function parseProducts($from, $to)
	{
	}

	/*
	Exports current import to an XML file
	*/
	static public function exportProductlistToXML()
	{
		MGProductImport::log("exportProductlistToXML(): " . PHP_EOL);
		require_once("Utils.php");
		// Returns without an import DB
		if(!file_exists(MGImportSettings::SQLLITE)){
			MGProductImport::log("No Import DB found" . PHP_EOL);
			return false; 
		}
		// Reads imported products
		$db = new SQLite3(MGImportSettings::SQLLITE);
		$sql = "SELECT * FROM products";		
		$q =$db->query($sql);
		$xmlWriter = new XMLSerializer();
		// Writes XML 
		while($product = $q->fetchArray(SQLITE_ASSOC)){
			$temp = $product["doc"];
			$temp = unserialize($temp);
			$res[]= $xmlWriter->generateValidXmlFromObj($temp, "product", "val");
		}
		// Saves XML
		$data = implode(PHP_EOL, $res);
		$data = "<products>" . PHP_EOL . $data . "</products>" . PHP_EOL;;
		file_put_contents(MGImportSettings::XMLEXPORT, $data);
		return true;
	}
	
	/*
	Exports current import to a CSV file
	*/
	static public function exportProductlistToCSV()
	{
		MGProductImport::log("exportProductlistToCSV(): " . PHP_EOL);
		// Returns without an import DB
		if(!file_exists(MGImportSettings::SQLLITE)){
			MGProductImport::log("No Import DB found" . PHP_EOL);
			return false; 
		}
		// Reads imported products
		$db = new SQLite3(MGImportSettings::SQLLITE);
		$sql = "SELECT * FROM products";		
		$q =$db->query($sql);
		$csv = "";
		$i = 0;
		// Writes CSV
		while($product = $q->fetchArray(SQLITE_ASSOC)){
			$temp = unserialize($product["doc"]);
			$commi = "";
			$quote = '"';
			// Writes header fields
			if(0 == $i){
				foreach($temp as $index=>$value){
					$csv .= $commi . $quote . $index . $quote;
					$commi = ",";	
				}
				$csv .= PHP_EOL;
			}
			$commi = "";
			// Writes values
			foreach($temp as $value){
				if(is_array($value)){
					$value = implode(",", $value);
				}
				$csv .= $commi . $quote . $value . $quote;
				$commi = ",";	
			}
			$csv .= PHP_EOL;
			$i++;
		}
		// Saves CSV 
		file_put_contents(MGImportSettings::CSVEXPORT, $csv);
		return true;
	}
	
	/*
	Does the MemCache Limbo
	*/
	static public function writeProductsDB($products)
	{
		MGProductImport::log("writeProductsDB(): " . PHP_EOL);
		date_default_timezone_set(MGImportSettings::TIMEZONE);
		$stamp = date("U");
		if(file_exists(MGImportSettings::SQLLITE)){
			$path = MGImportSettings::SQLLITEBCKPP . "." . $stamp; 
			MGProductImport("Moving *old DB to: " . $path . PHP_EOL);
			$res = rename(MGImportSettings::SQLLITE, $path); 
		}
		$db = new SQLite3(MGImportSettings::SQLLITE);
		$sql = "CREATE TABLE IF NOT EXISTS products (
				id INTEGER NOT NULL, 
				date VARCHAR(128), 
				doc TEXT, 
				PRIMARY KEY(id)
			);";
		if(!($db->exec($sql))){
			MGProductImport::log("There is something wrong with your seekl: " . $sql . PHP_EOL);
			return false;
		}
		foreach($products as $product){
			$tmp = serialize($product);
			$sql = "INSERT INTO products (date, doc) VALUES('$stamp', '$tmp')";
			if(!($q = $db->query($sql))){
				MGProductImport::log("There is something wrong with your seekl: " . $sql . PHP_EOL);
				return false;
			}
		}
		return true;  
	}
	
	/*
	static public function writeProductsDB($products)
	{
		$stamp = uniqid();
		$db = new SQLite3(MGImportSettings::SQLLITE);
		$sql = "CREATE TABLE IF NOT EXISTS products (
			id INTEGER NOT NULL, 
			import_id VARCHAR(128),
			sku VARCHAR(128), 
			erp VARCHAR(128), 
			base VARCHAR(128), 
			top INT(1), 
			stream INT(1), 
			title VARCHAR(256), 
			description TEXT(1024), 
			price INT(8), 
			rrp INT(8), 
			img VARCHAR(256),
			groups VARCHAR(256),
			categories VARCHAR(256),
			PRIMARY KEY(id)); 
		";
		if(!($db->exec($sql))){
			print "There is something wrong with your seekl: " . $sql . "\n";
			return;
		}
		foreach($products as $product){
			$sql = "INSERT INTO products (import_id, sku, erp, base, top, stream, title, description, price, rrp, img, groups, categories)
				VALUES (
					'$stamp',
					'$product->sku', 
					'$product->erp', 
					'$product->base', 
					'$product->top', 
					'$product->stream', 
					'$product->title', 
					'$product->description',
					'$product->price',
					'$product->rrp',
					'$product->img',
					'$product->groups',
					'$product->categories'
				);";
			if(!($q = $db->prepare($sql))){
				print "There is something wrong with your seekl: " . $sql . "\n";
				return;
			}
			if(!($res = $q->execute())){
				print "There is something wrong with your q: " . $q . "\n";
				return;
			}	
		}
		return; 
	}

	/*
	Imports the products of a product list into the Mage DB
	*/
	static public function importItems($list=null)
	{
		$itemlist = null == $list ? MGProductImport::$itemlist : $list;
		MGProductImport::log("importItems(): " . PHP_EOL);
		MGProductImport::initMagento();
		foreach($itemlist->products as $item){
			switch($item->type){
				case MGImportSettings::PRODUCT_DEFAULT:	
					$res = MGProductImport::importProduct($item);
					break;
				case MGImportSettings::PRODUCT_GROUP:
					$res = MGProductImport::importProductGroup($item);
					break;
				case MGImportSettings::CATEGORY:
					$res = MGProductimport::importCategory($item);	
					break;
			}
		}
		MGProductImport::writeImportTimestamp();
		return true;
	}

	/*
	Writes timestamp of import (as for to (more like so) validate against updates)0
	@returns boolean process state
	*/
	static public function writeImportTimestamp()
	{
		$timestamp = date("U");
		if(@file_put_contents(MGImportSettings::IMPORT_TIMESTAMP_PATH, $timestamp)){
			return true;
		} 
		else {
			return false;
		}
	}

	/*
	Reads import timestamp
	@returns unix time stamp date("U")
	*/
	static public function readImportTimestamp()
	{
		$result = @file_get_contents(MGImportSettings::IMPORT_TIMESTAMP_PATH);
		switch($result){
			case null:
			case NULL:
			case false:
			case "":
			case -1:
			case 0:
				$result = date("U");
				break;	
	
		}	
		return $result;	
	}
	


	/*
	Imports a product group
	
	ProductGroup "becomes" a category since FSCK Magento does not *really supports Product Groups
	*/
	static protected function importProductGroup($group)
	{
		MGProductImport::log("importProductGroup(): " . PHP_EOL);
		MGProductImport::initMagento();
		$cid = MGImportSettings::CATPREFIX . $group->id;
		$cat = new Mage_Catalog_Model_Category();
		$cat->setStoreId(0);
		$cat->setId($cid);
		$cat->setName(trim($group->name));
		$cat->setErpId($group->erp_id);
		$cat->setIsActive(1);
		$cat->setPath(""
			. MGImportSettings::ROOTCATS 
			. MGImportSettings::CATPREFIX . $group->category_ids . DS 
			. $cid
		);
		$cat->setParentId(MGImportSettings::CATPREFIX . $group->category_ids);
		$cat->setImage($group->image);
		$cat->setDescription($group->description);
		
		// do something with category image (product image)...
		$cat->setMetaDescription($group->sku);
		// ???

		$cat->save();
		print $cat->getName() . " " . $cat->getId() . PHP_EOL;;
		
		/*
		$newGroup = false;
		
		// Loads a product group
		$mprod = Mage::getModel("catalog/product")->loadByAttribute("group_id", $group->id);
		
		// fix diss
		// Product an Product Groups are not unique... 
		if(null != $mprod){
			$mprod = $mprod->load($mprod->getId());
			if("grouped" != $mprod->getTypeId()){
				$mprod = null;
			}
		}
		
		// ToDO : serialize product group
		if(null == $mprod){
			$chunk = file_get_contents(MGImportSettings::REFPROD);
			$mprod = unserialize($chunk);
			$newGroup = true;
		}
		// Loads group 
		$mprod = $mprod->load($mprod->getId());
	
		// Evaluates *real id	
		$pid = $mprod->getId();
	
		// Generates *real id	
		if($newGroup){
			// Sets id of new product
			$sql = "select max(entity_id) from catalog_product_entity";
			$res = Mage::getSingleton('core/resource')->getConnection("core_read")->fetchAll($sql);
			$pid = (int)($res[0]["max(entity_id)"]) +1;
		}

		// Do not without a name	
		switch($group->name){
			case null:
			case "":
				return false;
		}

		// Writes group	
		$mprod->setName($group->name);
		
		$mprod->setAttributeID(Mage::getModel("eav/entity_attribute_set")
			->getCollection()
			->setEntityTypeFilter(Mage::getModel("eav/entity")->setType("catalog_product")->getTypeId())
			->addFieldToFilter("attribute_set_name", "Default")
			->getFirstItem()
			->getAttributeSetId());

		$mprod->setId($pid);
		$mprod->setSku($pid);
	
		// sets the group id of this group	
		$mprod->setGroupId($group->id);

		$mprod->setName($group->name);
		$mprod->setDescription($group->description);
		
		$mprod->setTypeId("grouped");
		$mprod->setImage($group->image);

		$mprod->setErp($group->erp);
		$mprod->setBase($group->base);
		$mprod->setCategoryIds(array(MGImportSettings::CATPREFIX . $group->category_ids));
		$mprod->setState($group->karlie_state);
		$mprod->setSmallImage($group->image);
		$mprod->setThumbnail($group->image);
		$mprod->setImage($group->image);
		$mprod->setMediaImage($group->image);
		
		$mprod->setStatus($product->status);
		$mprod->setWebsiteIds(array(1));

		// Saves Group
		$mprod->save();

		print $mprod->getName() . " " . $mprod->getSku() . PHP_EOL;;
		*/
	}	

	/*	
	Imports category
	*/
	static protected function importCategory($category)
	{
		MGProductImport::log("importCategory(): " . PHP_EOL);
		MGProductImport::log("importCategory(): " . $category->name . PHP_EOL);
		MGProductImport::initMagento();
		$cid = MGImportSettings::CATPREFIX . $category->id;
		$cat = new Mage_Catalog_Model_Category();
		$cat->setStoreId(0);
		$cat->setId($cid);
		$cat->setName($category->name);
		$cat->setErpId($category->erp_id);
		$cat->setIsActive(1);
		$cat->setPath("" 
			. MGImportSettings::ROOTCATS
			. $cid);
		$cat->setImage($category->image);
		$cat->setDescription($category->description);
		$cat->save();
	}	


	/*
	Imports default product
	*/
	static protected function importProduct($product)
	{
		MGProductImport::log("importSimpleProduct(): " . $product->sku . PHP_EOL);
		MGProductImport::initMagento();
		// Selects product by SKU
		
		$mprod = Mage::getModel("catalog/product")->loadByAttribute("sku", $product->sku);
		$newProduct = false;
			
		// Writes a new Product	
		if(null == $mprod){
			// $mprod = new Mage_Catalog_Model_Product();
		}

		// Reads ref product from product list
		if(null == $mprod){
			MGProductImport::log("Adding a new product" . PHP_EOL);
			$mprod = Mage::getModel("catalog/product")->getCollection()->getFirstItem();
			$mprod = unserialize($mprod);
			$newProduct = true;
		}
		
	
		// Reads ref product: 
		if(null == $mprod){
			$chunk = file_get_contents(MGImportSettings::REFPROD);
			$mprod = unserialize($chunk);
			$newProduct = true;
		}
		
		// Loads product
		$mprod = $mprod->load($mprod->getId());
		
		// Evaluates product id
		$pid = $mprod->getId();
		if($newProduct){
			// Sets id of new product
			$sql = "select max(entity_id) from catalog_product_entity";
			$res = Mage::getSingleton('core/resource')->getConnection("core_read")->fetchAll($sql);
			$pid = (int)($res[0]["max(entity_id)"]) +1;
		}
			
		// Sets attributes of a product 
			
		/*
		$mprod->setStoreId(MGProductImport::getStoreId("default"));
		$mprod->setName($product->name);
		$mprod->setStoreId(MGProductImport::getStoreId("en"));
		$mprod->setName($product->name);
		*/
		
		// Just don't
		/*
		$mprod->setStoreId(MGProductImport::getStoreId("default"));
		*/
		
		$mprod->setStoreId(0);

		$mprod->setName($product->name);
		
		$mprod->setAttributeID(Mage::getModel("eav/entity_attribute_set")
			->getCollection()
			->setEntityTypeFilter(Mage::getModel("eav/entity")->setType("catalog_product")->getTypeId())
			->addFieldToFilter("attribute_set_name", "Default")
			->getFirstItem()
			->getAttributeSetId());

		$mprod->setId($pid);
		$mprod->setSku($product->sku);
		$mprod->setName($product->name);
		$mprod->setDescription($product->description);
		$mprod->setIsStreamProduct($product->stream);
		// ???
		$mprod->setIsActive(1);
		$mprod->setStatus(1);
		$mprod->setState(1);
		// $mprod->setState($product->karlie_state);
		// $mprod->setStatus($product->status);
		$mprod->setTaxClassId(1);
		$mprod->setIsTopProduct($product->top);
		$mprod->setErp($product->erp_id);
		$mprod->setPrice($product->price);
		$mprod->setRrp($product->suggested_reatial_price);
		$mprod->setBase($product->base);
		
		// sets linked group ids
		// $mprod->setGroupId($product->category_ids);
		
		$mprod->setColor($product->color);
		$mprod->setWidth($product->width);
		$mprod->setHeight($product->height);
		$mprod->setDiameter($product->diameter);
		$mprod->setVolume($product->volume);
		$mprod->setSize($product->price);
		$mprod->setIsTopProduct($product->top_product);
		$mprod->setIsStreamProduct($product->stream_product);
		// $mprod->setCreated($product->created);		
		$mprod->setCreated(date("U"));
		$mprod->setSyncKarlie($product->sync_karlie);		
	
		$mprod->setTypeId("simple");
		$mprod->setSmallImage($product->image);
		$mprod->setThumbnail($product->image);
		$mprod->setImage($product->image);
		$mprod->setMediaImage($product->image);
		$mprod->setWebsiteIds(array(1));

		$catIds = array(MGImportSettings::CATPREFIX . $product->category_ids);
		$cat = Mage::getModel("catalog/category")->load(MGImportSettings::CATPREFIX . $product->category_ids);
		if(null != $cat){
			$catIds[]= $cat->getParentId();
			
		}		
		// $mprod->setCategoryIds(array(MGProductImport::CATPREFIX . $product->category_ids));
		$mprod->setCategoryIds($catIds);

		// Saves product 
		$mprod->save();
		
		// $mprod->setStoreId(MGProductImport::getStoreId("default"));
		// $mprod->save();
		
		// Logs import
		MGProductImport::log(
			"importProduct(): " 
			. $mprod->getSku() 
			. ": " 
			. $mprod->getId() 
			. ": "
			. $mprod->getName()
			. ": "
			. $mprod->getStoreId()
			. " :done" 
			. PHP_EOL
		);

		return true;
	}

	/*
	Links Products to ProductGroups 
	---< DEFEKT >----
	*/
	static public function linkProducts()
	{
		MGProductImport::log("linkProducts(): ");
		MGProductImport::initMagento();
		return;
		
		// 
		$pl = Mage::getModel("catalog/product_link_api");
		$coll = Mage::getModel("catalog/product")->getCollection()->addAttributeToFilter("type_id", "simple");
		// Loop through simple products
		foreach($coll as $product){
			$product = $product->load($product->getId());
			$group = Mage::getModel("catalog/product")
				->getCollection()
				->addAttributeToFilter("type_id", "grouped")
				->addAttributeToFilter("group_id", $product->getGroupId()
			);
			$item = null;
			foreach($group as $item){
				if(null == ($item = $item->load($item->getId()))){
					continue;
				}
			}
			if(null === $item){
				continue;
			}
			print "Group name. " . $item->getName() . PHP_EOL;
			MGProductImport::log("linkProducts(): " 
				. $item->getId() 
				. " : group : " 
				. $item->getSku() 
				. " <-> " 
				. $product->getId() 
				. " : simple : "
				. $product->getSku()
				. PHP_EOL
				);
				try{
				// $pl->assign("grouped", $item->getId(), $product->getId()); 
				// $item->setAssociatedProduct($product);
				// $product->setParentId(array($item->getId()));
				// $item->getTypeInstance(true)->setAssociatedProducts(array($product));
				$item->getTypeInstance(true)->setUsedProductAttributeIds(array($product->getId()));
				$item->save();
			}											
			catch(Exception $e){
				MGProductImport::log("linkProducts(): Exception: " . $e->getMessage() . PHP_EOL);
			}
		}
	}

	/*
	Imports all category images
	*/
	static public function importCategoryImages()
	{
		MGProductImport::log("importCategoryImages(): " . $coll . PHP_EOL);
		MGProductImport::initMagento();
		$coll = Mage::getModel("catalog/category")->getCollection();
		foreach($coll as $category){
			$category = $category->load($category->getId());
			MGProductImport::importCategoryImage($category);	
		}
	}

	/*
	Imports image of one category	
	*/
	static public function importCategoryImage($category)
	{
		MGProductImport::log("importCategoryImage(): " . PHP_EOL);
		MGProductImport::initMagento();
		$sku = $category->getMetaDescription();
		if(null == $sku){
			return false;
		}
		
		// There is no "SKU" field withing the "productGroups" of the export
		// there is no image download by "SKU" ...
		// otdo
		return MGProductImport::importImageOfProduct($sku);
	}

	/*
	Imports all product images
	*/	
	static public function importImages()
	{
		MGProductImport::log("importImages(): " . PHP_EOL);
		MGProductImport::initMagento();
		$coll = Mage::getModel("catalog/product")->getCollection();
		foreach($coll as $prod){
			$prod = $prod->load($prod->getId());
			$sku = $prod->getSku();
			MGProductImport::importImageOfProduct($sku);
		}
	}
	
	/*
	Import image of one product selected by sku
	*/
	static public function importImageOfProduct($sku)
	{
		MGProductImport::log("importImageOfProduct(): " . $sku . PHP_EOL);
		MGProductImport::initMagento();
		
		// Evaluates product
		$prod = Mage::getModel("catalog/product")->loadByAttribute("sku", $sku);
		if(null == $prod){
			MGProductImport::log("importImageOfProduct(): no such product: " . $sku . PHP_EOL);
			return false;
		}
		
		$prod = $prod->load($prod->getSku());
		$prod->setStoreId(0);
		
		// Evaluates image
		$image = $prod->getImage();
		switch($image){
			case null:
			case "":
				MGProductImport::log("importImageOfProduct(): no image in record: " . $sku . PHP_EOL);
				$image = "fantomas.png";
		}

		// Uha...
		/*
		if(0 == strpos($image, "/") ){
			$image = substr($image, 1, strlen($image));
		}
		*/

		// $image = "test.png";

		// RSYNCs the image
		$target = MGImportSettings::RSYNC . "&sku=" . $prod->getSku();	
		MGProductImport::log("importImageOfProduct(): rsync: " . $target . " : " . $image . PHP_EOL);
		$ch = curl_init($target);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$res = curl_exec($ch);
		MGProductImport::log("importImageOfProduct(): rsync: " . $res . PHP_EOL);
		$res = curl_close($ch);
		
		// Downloads image
		// (There is no need for a download... it is a test issue...
		$target = MGImportSettings::IMAGEDOWNLOAD . $image;
		$dest = Mage::getBaseDir("media") . DS . "import" . DS . $image;
		$fp = fopen($dest, "w");
		$ch = curl_init($target);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		$res = curl_exec($ch);
		$res = fclose($fp);
		$res = curl_close($ch);
		MGProductImport::log("importImageOfProduct(): download: " . $target . PHP_EOL);
		MGProductImport::log("downloadImages(): dest: " . $dest . PHP_EOL);

		// Deletes existing image gallery
		$mediaApi = Mage::getModel("catalog/product_attribute_media_api");
		$items = $mediaApi->items($prod->getId());
		$attributes = $prod->getTypeInstance()->getSetAttributes();
		$gallery = $attributes['media_gallery'];
		foreach($items as $item){
			if ($gallery->getBackend()->getImage($prod, $item['file'])) {
				$gallery->getBackend()->removeImage($prod, $item['file']);
			}
		}	
		
		// Imports image asset into magento
		$label = "the1st";
		$target = Mage::getBaseDir("media") . DS . "import" . DS . $image;
		





		// test target (that is to be imported)
		$res = exec("file " . $target);
		if(false === strrpos($res, ("image data"))){
			$target = "/Users/vico/Workspace/ProdImport/mygassi_shop_import/_mygassi_shop_import/data/images/fantomas.png";
			print "imported image is not of type image\n";
		}
	




		// 	
		try{
			$prod->addImageToMediaGallery($target, $visibility, false, false);
			MGProductImport::log("importImageOfProduct(): image imported: " . $target . PHP_EOL);
		}
		catch(Exception $e){
			MGProductImport::log("Exception dump while importing image assets: " . $e->getMessage() . " : ");
			MGProductImport::log($target . PHP_EOL);
		}
		
		// Adds label to the imported image
		$gall = $prod->getData("media_gallery");
		$temp = array_pop($gall["images"]);
		$temp["label"] = $label;
		array_push($gall["images"], $temp);
		$prod->setData("media_gallery", $gall);
		$prod->save();
		
		return true;
	}

	/*
	Downloads images
	*/
	static public function downloadImages()
	{
		MGProductImport::log("downloadImages(): " . PHP_EOL);
		MGProductImport::initMagento();
		$coll = Mage::getModel("catalog/product")->getCollection();
		foreach($coll as $product){
			$product = $product->load($product->getId());
			switch($product->getImages()){
				case "":
				case null:
					continue;
			}
			$target = MGImportSettings::IMAGEDOWNLOAD . $product->getImage();
			$dest = Mage::getBaseDir("media") . DS . "import" . DS . $product->getImage();
			$fp = fopen($dest, "w");
			$ch = curl_init($target);
			curl_setopt($ch, CURLOPT_TIMEOUT, 120);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FILE, $fp);
			$res = curl_exec($ch);
			$res = curl_close($ch);
			MGProductImport::log("downloadImages(): target: " . $target . PHP_EOL);
			MGProductImport::log("downloadImages(): dest: " . $dest . PHP_EOL);
			$res = fclose($fp);
			$res = curl_close($c);
		}
		return true;
	}

	/*
	Inits image cache
	*/
	static public function fillImageCache()
	{
		MGProductImport::log("fillImageCache(): " . PHP_EOL);
		MGProductImport::initMagento();
		$coll = Mage::getModel("catalog/product")->getCollection();
		foreach($coll as $product){
			// Fills up image cache
			$product = $product->load($product->getId());
			foreach($product->getMediaGalleryImages() as $image){
				$path = (string)Mage::helper("catalog/image")->init($product, "thumbnail", $image->getFile())->keepFrame(false)->resize(640);
				MGProductImport::log("fillImageCache(): " . $image->getFile() . " : " . $path . PHP_EOL);
			}
		}
		return true;
	}

	/*
	Reindexes Magento
	*/
	static public function reindexMagento()
	{
		MGProductImport::log("reindexMagento(): " . PHP_EOL);
		MGProductImport::initMagento($admin=true);
		for($i = 1; $i <= 9; $i++){
			MGProductImport::log("reindexMagento(): " . $i . PHP_EOL);
			$proc = Mage::getModel("index/process")->load($i);
			$proc->reindexAll();
		}
		return true;
	}

	/*
	Deletes all Magento Products
	*/
	static public function deleteProducts()
	{
		MGProductImport::log("deleteProducts(): " . PHP_EOL);
		MGProductImport::initMagento($admin=true);
		$products = Mage::getModel("catalog/product")->getCollection();
		foreach($products as $product){
			try{
				MGProductImport::log("deleteProducts(): " . $product->getSku() . PHP_EOL);
				$product->delete();
			}
			catch(Exception $e){
				MGProductImport::log("deleteProducts(): Exception: " . $e->getMessage() . PHP_EOL);
			}
		};
		return true;
	}

	/*
	Writes a backup of the existing products
	*/
	static public function backupProducts()
	{
		MGProductImport::log("backupProducts(): " . PHP_EOL);
		date_default_timezone_set(MGImportSettings::TIMEZONE);
		$dest = MGImportSettings::SQLDUMP . date("U") . ".sql";
		exec("mysqldump --user=" 
			. MGImportSettings::MAGEDBUSER 
			. " --password=" . MGImportSettings::MAGEDBPASS 
			. " --host=" . MGImportSettings::MAGEDBHOST 
			. " " . MGImportSettings::MAGEDBNAME 
			. " > " . $dest
		);	
		return true;
	}

	/*
	Logs messages
	*/
	static private $logFileHandle = null;
	static public function log($message)
	{
		if(MGImportSettings::LOGTOSCREEN){
			print $message;
		}
		if(MGImportSettings::LOGTOFILE){
			if(null == MGProductImport::$logFileHandle){
				date_default_timezone_set(MGImportSettings::TIMEZONE);
				$init = date("U") . ":" . PHP_EOL; 
				MGProductImport::$logFileHandle = fopen(MGImportSettings::LOGFILE, "a");
				fwrite(MGProductImport::$logFileHandle, PHP_EOL . PHP_EOL);
				fwrite(MGProductImport::$logFileHandle, $init);
			}
			fwrite(MGProductImport::$logFileHandle, $message);
		}
	}

	/*
	Cavalier starts a Magento instance
	*/
	static public $minited = false;
	static public function initMagento($admin=false)
	{
		if(MGProductImport::$minited){
			return true;
		}
		require_once(MGImportSettings::MAGEROOT);
		if($admin){
			Mage::app('admin');
		}
		else {
			Mage::app();
		}
		MGProductImport::$minited = true;
		MGProductImport::log("initMagento(): " . PHP_EOL);
		// MGImportSettings::ROOTCATS = "1/" . Mage::app()->getStore()->getRootCategoryId() . "/";
		return true;
	}
	
	/*
	Deletes Magento Cache
	*/
	static public function cleanImageCache()
	{
		MGProductImport::log("cleanImageCache(): " . PHP_EOL);
		require_once(MGImportSettings::MAGEROOT);
		Mage::app()->cleanCache();
		return true;
	}

	/*
	Evaluates store id by index "de" "en" (Store Views)
	*/
	static public function getStoreId($index)
	{
		MGProductImport::log("getStoreId(): " . $index . PHP_EOL);
		$res = 0;
		try{
			$res = Mage::app()->getStore($index)->getId();
		}
		catch(Exception $e){ 
			$res = 0; 
		}
		MGProductImport::log("getStoreId(): res: " . $res . PHP_EOL);
		return $res;
	}

	/*
	Deletes categories
	*/
	static public function deleteCategories()
	{
		MGProductImport::log("deleteCategories(): " . PHP_EOL);
		MGProductImport::initMagento($admin=true);
		$coll = Mage::getModel("catalog/category")->getCollection();
		foreach($coll as $cat){
			if($cat->getId() > 3){
				MGProductImport::log("deleteCategories(): " . $cat->getId() . PHP_EOL);
				$cat->delete();
			}
		}
	}

	/*
	Loads list <export> of "current" product import
	*/
	static private function loadReflist()
	{
		MGProductImport::log("loadReflist(): " . PHP_EOL);
		if(!file_exists(MGImportSettings::PRODUCTLISTCOPY)){
			MGProductImport::log("loadReflist(): no ref list" . MGImportSettings::PRODUCTLISTCOPY . PHP_EOL);
			return false;
		}
		$d = file_get_contents(MGImportSettings::PRODUCTLISTCOPY);
		return json_decode($d);
	}

	/*
	Selects "dirty" or changed products
	ref = the imported product list
	obj = mage product catalog
	*/
	static public function selectDirtyProducts()
	{
		MGProductImport::log("selectDirtyProducts(): " . PHP_EOL);
		if(!($reflist = MGProductImport::loadReflist())){
			MGProductImport::log("selectDirtyProducts(): no ref list" . MGImportSettings::PRODUCTLISTCOPY . PHP_EOL);
			return false;
		}
		if(null == $reflist){
			MGProductImport::log("selectDirtyProducts(): ref list is null" . PHP_EOL);
			return false;
		}
		$dirtyProductIDs = array();
		$dirtyCatIDs = array();
		foreach($reflist->products as $item){
			switch($item->type){
				case MGImportSettings::PRODUCT_DEFAULT:
					if(MGProductImport::isProductDirty($item)){
						MGProductImport::log("selectDirtyProducts(): dirty: " . $item->sku . PHP_EOL);
						$dirtyProductIDs[]= $item->sku;
					}
					break;
				case MGImportSettings::PRODUCT_GROUP:
				case MGImportSettings::CATEGORY:
					if(MGProductImport::isCategoryDirty($item)){
						MGProductImport::log("selectDirtyProducts(): dirty: " . $item->id . PHP_EOL);
						$cid = MGImportSettings::CATPREFIX . $item->id;
						$dirtyCatIDs[]= $cid;
					}
					break;
			}
		}
		print_r($dirtyProductIDs);
		print_r($dirtyCatIDs);
	}
	
	/*
	Checks whether or not a category has changed since the import
	 */
	static private function isCategoryDirty($item)
	{
		// MGProductImport::log("isCategoryDirty(): " . $item->id . PHP_EOL);
		MGProductImport::initMagento();
		$dirty = false;
		$cid = MGImportSettings::CATPREFIX . $item->id;
		$cat = Mage::getModel("catalog/category")->load($cid);
		if($cat->getName() != $item->name){
			MGProductImport::log("isCategoryDirty(): dirty name" . PHP_EOL);
			$dirty = true;
			return $dirty; 
		}
		// ...
		return $dirty;
	}
	
	/*
	Checks whether or not a product has changed since the import
	 */
	static private function isProductDirty($item)
	{
		// MGProductImport::log("isProductDirty(): " . $item->sku . PHP_EOL);
		MGProductImport::initMagento();
		$prod = Mage::getModel("catalog/product")->loadByAttribute("sku", $item->sku);
		$dirty = false;
		if($prod->getName() != $item->name){
			MGProductImport::log("isProductDirty(): name dirty" . PHP_EOL);
			$dirty = true;
			return $dirty;
		}
		if($prod->getDescription() != $item->description){
			MGProductImport::log("isProductDirty(): description dirty" . PHP_EOL);
			$dirty = true;
			return $dirty;
		}
		if($prod->getPrice() != $item->price){
			MGProductImport::log("isProductDirty(): price dirty" . PHP_EOL);
			$dirty = true;
			return $dirty;
		}	
		return $dirty;
	}

	/*
	Submits the changes made since last import of a product
	Der war schwer
	*/
	static public function submitEditedProducts() 
	{
		MGProductImport::initMagento();
		// $lit = date("U") -(60 *60 *24); // assume last import was yesterday
		$lit = MGProductImport::readImportTimestamp();
		// print "imported: " . date("Y-m-d H:i:s", $lit) . PHP_EOL;
		$coll = Mage::getModel("catalog/product")->getCollection();
		foreach($coll as $prod){
			$put = strtotime($prod->getUpdatedAt());
			// print "updated: " . date("Y-m-d H:i:s", $put) . PHP_EOL;
			if($lit < $put){
				print "updated: " . date("Y-m-d H:i:s", $put) . PHP_EOL;
				print "imported: " . date("Y-m-d H:i:s", $lit) . PHP_EOL;
				print "---" . PHP_EOL;
				$prod = $prod->load($prod->getId());
				$imagePath = "";
				foreach($prod->getMediaGalleryImages() as $image){
					if("the1st" === $image->getLabel()){ 
						$imagePath = $image->getPath();
					}
				}
				$postargs = array
				(
					'sku' => $prod->getSku(),
					'mygassi_headline' => $prod->getName(),
					'mygassi_text' => $prod->getDescription(),
					'mygassi_uvp' => $prod->getOldPrice(),
					'mygassi_image' => '@' . "'" . $imagePath . "'"
				);
				$postargs = json_encode($postargs);
				include("post_article.php");
				

				print_r($postargs);	
				print PHP_EOL;
				
				MGProductImport::writeImportTimestamp();	
			}
		}
	}

	/*
	Downloaded list of items
	*/
	static public $itemlist;

}

class MGImportSettings
{
	const JSON = 0; 
	const XML = 1;
	const DOCTYPE = MGImportSettings::JSON;
	const PRODUCTLIST = "http://10.14.10.37/karlie/index.php?forward=webservice/mygassi/view.php";
	// const PRODUCTLIST = "http://10.14.10.37/karlie/index.php?forward=webservice/mygassi/view_status.php&status=3";
	// const PRODUCTLIST = "http://127.0.0.1/testexport/eximp.php";
 	// const PRODUCTLIST = "http://127.0.0.1/testexport/eximp3.php";
	// const PRODUCTLIST = "http://127.0.0.1/testexport/vladimgerdt/mygassi_article.txt";
	const SQLLITE = "./db/sqllite.db";
	const SQLLITEBCKPP = "./db/bckpp/sqllite.db";
	const CSVEXPORT = "./export/product-import.csv";
	const XMLEXPORT = "./export/product-import.xml";
	const PHPEXPORT = "./export/product-export.php";
	const MAGEROOT = "/Users/vico/Workspace/MyGassiShop/app/Mage.php";
	const REFPROD = "./data/mprod.php";
	const SQLDUMP = "./export/sqldump/";
	const MAGEDBUSER = "root";
	const MAGEDBPASS = "2317.187.fuckingsuck";
	const MAGEDBHOST = "localhost";
	// const MAGEDBNAME = "magento7";
	const MAGEDBNAME = "mg3";
	const LOGTOSCREEN = true;
	const LOGTOFILE = true;
	const LOGFILE = "./log/import.log";
	const TIMEZONE = "Europe/Berlin";
	// import item types
	const PRODUCT_DEFAULT = "default";
	const PRODUCT_GROUP = "grouped";
	const CATEGORY = "category";
	// 
	const CATPREFIX = "999";
	// 1 is magic
	// 3 is Mage::app()->getStore()->getRootCategoryId();
	const ROOTCATS = "1/3/";
	//
	const RSYNC = "http://10.14.10.37/karlie/index.php?forward=webservice/mygassi/image_export.php";
	// const RSYNC = "http://127.0.0.1/testexport/image_export.php?nix=nix";
	// const IMAGEDOWNLOAD = "http://10.14.10.20/mygassipic/";
	// const IMAGEDOWNLOAD = "http://10.14.10.20/mygassipic_new/";
	const IMAGEDOWNLOAD = "http://10.14.10.37/karlie/webservice/mygassi/mygassipic/";
	// const IMAGEDOWNLOAD = "http://127.0.0.1/testexport/images/";
	// const PRODUCTLIST 
	const PRODUCTLISTCOPY = "./prodlist/prodlist.json";
	const PRODUCTLISTBCKPP = "./prodlist/bckpp/";
	const IMPORT_TIMESTAMP_PATH = "./prodlist/import_timestamp";
}

