<?php
/*
 * Author: Vladimir Gerdt
 * Company: Karlie Heimtierbedarf GmbH
 * Date: 21st Oktober 2013
 * Update: 08th November 2013
 * Version: 1.1
 * Description:
 *      POST: update one or more attributes for an item by sku.
 * Parameters:
 *      sku: article number ( string ).
 *      mygassi_headline: ( string ), if empty then there is no changes.
 *      mygassi_text: ( string ), if empty then there is no changes.
 *      mygassi_uvp: ( decimal ), if empty then there is no changes.
 *      mygassi_image: @path/to/image ( jpg, png, git, tif ), if empty then there is no changes.
 */
$request =  'http://cs.karlie.de/karlie/index.php?forward=webservice/mygassi/article.php';

$postargs = array
(
    'sku' => '02270',
    'mygassi_headline' => "Rondo Halsbänder mit Halstuch",
    'mygassi_text' => "Genietet.\nMit Zierreifel.\nVerchromte Beschläge.",
    'mygassi_uvp' => 8.95,
    'mygassi_image' => '@' . '/var/www/CS12/karlie/webservice/mygassi/image/Tulips.jpg'
);

// 02260_MG_01.tif

$headers = array(
    'Accept: application/json',
    'Content-Type: application/json'
);

$handle = curl_init();

curl_setopt($handle, CURLOPT_URL, $request);
curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

curl_setopt($handle, CURLOPT_POST, true);
curl_setopt($handle, CURLOPT_POSTFIELDS, $postargs);

$response = curl_exec($handle);
$code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

// close cURL resource, and free up system resources
curl_close($handle);
// output
echo $code . '<br />';
echo '[' . $response . ']';
?>