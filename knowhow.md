
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

	



