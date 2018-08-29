
# was passiert beim Löschen

(1) Löschen -> Trash
(2) Trash -> Permanent Delete
(3) Trash -> Undo

Abgeleiteten Kennzahlen bzw. Abhängigkeiten von Items
* Anzahl der Reviews => Review löschen, aktualisiert #Reviews
* Schwierigkeit => TestResult löschen, aktualisiert Schwierigkeit
* Learning Outcome => Learning Outcome löschen, setzt LO auf NULL

Abhängigkeiten von Reviews
* zugeordnetes Item  => Item Löschen, löscht Review

Abhängigkeiten von Learning Outcome
* zugeordnete Items => Item Löschen, aktualisiert #Items

Vorgehen
(1) bei Plugin-Aktivierung: SQL-Skript, der alle abgeleiteten Werte neu berechnet
(2) bei Löschoperationen (siehe oben 1-3): SQL-Skript, was nur für beteiligten CPT die Werte berechnet	



