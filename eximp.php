<?php

// <possible> Model of an imported product
Class Product
{
	public $type = "default";
	public $sku = "000";
	public $erp = "201";
	public $base = "202";
	public $price = 200;
	public $rrp = 203;
	public $name = "Name des Produkts";
	public $description = "Beschreibung des Produkts";
	public $groups = array(1, 100, 1000);
	public $categories = array(2, 200, 2000);
	public $image = "test.png";
	public $state = "updated";
	public $stream = 1;
	public $top = 1;
	public $status = 1;
};;
 
Class Error
{
	public $message = "Error";
	public $code = 606;
	public function __construct($code, $message){
		$this->code = $code;
		$this->message = $message;
	}
};;

Class Result
{
	public $products = array();
};;

/*
$i = 10;
$result = new Result();
while($i--){
	$product = new Product();
	$product->sku = "00: " . $i;
	$product->name = "Prodz ::" . $i . "";
	$result->products[$i] = $product;
}
print json_encode($result);
exit(1);
*/

print file_get_contents("prodz.json");
exit(1); 
