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
$request =  'http://10.14.10.37/karlie/index.php?forward=webservice/mygassi/article.php';
/*
$postargs = array
(
    'sku' => '01840',
    'mygassi_headline' => "Rondo Halsbänder, breit",
    'mygassi_text' => "Breites Halsband.\r\n\r\nDieses unterlegt gen\u00e4hte Rondo Halsband ist ein 2-lagig verarbeitetes Halsband. Vollrindleder ist mit einer weichen Unterlage unterf\u00fcttert. Die N\u00e4hte sind farblich passend auf das Halsband abgestimmt. Die weiche Kante ist besonders fellschonend und beugt Haarbruch vor. Das Halsband ist mit stabilen, verchromten Beschl\u00e4gen verarbeitet.\r\nMade in Germany. 5 Jahre Garantie.\r\nAus der Rondo-Linie gibt es zahlreiche passende Leinen.",
    'mygassi_uvp' => 19.99
);
*/
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
