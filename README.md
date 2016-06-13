# eal
E-Assessment Literacy


## File Upload Size vergrößern (Standard 2MB zu klein für Upload von Items)

## 

für die reviews:
* aggregierten wert mit in item tabelle bei insert/delete review aktualisieren
* den auslesen zum sortieren ("welche Items habe noch kein Review?")
* beim Anzeigen: "Add" immer anzeigen und eine collapsable Liste der einzelnen Reviews (mit Javascript; Reviews durch Datum + evtl. Autor gekennzeichnet) 


für die topic taxonomy
* nicht mit Rekursion; sondern mit gertterms alle holen und dann in eigenes Array parentid -> list of termids speichern
// (term_id -> Term [was ja parent_id hat]) speichern
* hierarchie in "Show all topic" filter ?
* man kann in topics klicken
* topic2 als Pfad?


## Install

place single-{posttype}.php files into the active theme directory so that they refer to the php files in the plugin, e.g.,

file "...themes/active-theme/single-itemmc.php" contains
<?php include(dirname(__FILE__) . "/../../plugins/eal/theme/single-itemmc.php"); ?>