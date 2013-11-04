mygassi_shop_import
===================

MyGassi Magento Import Utilzer

Usage: 
#=++

Kommandozeile im Projektroot:

>vim src/MGProductImport.php
>G
 [Karlie und MyGassi Pfade justieren]
 [MGImportSettings]
 
>vim src/test/TestMGProductImport.php 
 []

>php src/test/TestMGProductImport.php 
 [Importmassakker starten]


Karlie liefert JSON mit Kategorien, Produktgruppen und Produkten darin (Feld: "type");
die zu Produkten und Produkten werden.

Die Bilder werden anschliessend einzeln oder "batched" (aus dem Produktbestand) importiert.

Die Bilder können heruntergeladen werden; oder sie werden via "rsync" in das "import" Verzeichnis gelegt; 
/var/www/www.mygassi.com/htdocs/shop/media/import/ und von dort "importiert".


"eximp.php" und "exim3.php" sind "locale" Mockups der Karlie-Export-Dienste.
