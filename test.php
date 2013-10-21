<?php
require_once("/Users/vico/Workspace/MyGassiShop/app/Mage.php");
Mage::app();




$cat = Mage::getModel("catalog/category")->load(99916);

print_r($cat->debug());
print PHP_EOL;
print "getImageUrl()";
print_r($cat->getImageUrl());
print PHP_EOL;
print "getThumbnail()";
print_r($cat->getThumbnail());
print PHP_EOL;
print "getImage()";
print_r($cat->getImage());
print PHP_EOL;
print "getSmallImage()";
print_r($cat->getSmallImage());
