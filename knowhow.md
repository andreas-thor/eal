
# was passiert beim Löschen

(1) Löschen -> Trash
(2) Trash -> Permanent Delete
(3) Trash -> Undo

Abgeleiteten Kennzahlen bzw. Abhängigkeiten von Items
+ * Anzahl der Reviews: Review save/delete/(un)trash => update #Reviews
+ * Schwierigkeit + Anzahl TestResults: TestResult save/delete => update 
+ * Learning Outcome: delete Learning Outcome löschen => SET NULL

Abhängigkeiten von Learning Outcome
* zugeordnete Items => Item Löschen, aktualisiert #Items


Abhängigkeiten von Reviews
* zugeordnetes Item  => Item Löschen, löscht Review


Vorgehen
(1) bei Plugin-Aktivierung: SQL-Skript, der alle abgeleiteten Werte neu berechnet
(2) bei Löschoperationen (siehe oben 1-3): SQL-Skript, was nur für beteiligten CPT die Werte berechnet	



