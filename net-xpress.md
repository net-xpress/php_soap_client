## CalculateDailyNeed ##

Benötigte Felder des Scripts CalculateDailyNeed

### Zuordnung 1 ###

* OrderHead.OrderID
* OrderItem.OrderID

### Zuordnung 2 ###

* OrderItem.ItemID
* ItemsBase.ItemID
* ItemFreeTextFields.ItemID
* ItemAvailability.ItemID

### Datenfelder ###

* OrderItem.SKU  
enthält ItemID + AttributeValueSetId (+ PriceID, hier irrelevant)

* OrderItem.Quantity  
Anzahl der bestellten Artikelvariante des aktuellen Order

* ItemsBase.Marking1ID  
Markierung 1 rot! | gelb | rot | grün | schwarz

* ItemFreeTextFields.Free5  
war als ActivationDate geplant, u.U. ungenutzt

### Selectoren ###

* OrderHead.OrderStatus  
betrachte "abgeschlossene" Aufträge ≙ `8 < Status <= 9`

* OrderHead.OrderType  
betrachte nur "echte" Aufträge ≙ `OrderType = 'order'` 

* ItemAvailability.Inactive  
betrachte nur aktive Produkte


## CalculateWriteBackSuggestions ##

Benötigte Felder des Scripts CalculateWriteBackSuggestions

## Zuordnung 1 ##

* ItemsBase.ItemID
* ItemFreeTextFields.ItemID
* ItemAvailability.ItemID
* ItemAttributeValueSets.ItemID
* ItemsSuppliers.ItemID
* CalculatedDailyNeeds.ItemID
* ItemsWarehouseSettings.ItemID

## Zuordnung 2 ##

* ItemAttributeValueSets.AttributeValueSetID
* CalculatedDailyNeeds.AttributeValueSetID

## Datenfelder ##

* ItemFreeTextFields.Free4  
wird als eigene VPE genutzt und zur Berechnung des SupplierMinimumPurchase genutzt (wichtig!)

* ItemsSuppliers.SupplierDeliveryTime  
Lieferzeit, wird für Berechnung des ReorderLevel genutzt (wichtig!)

* CalculatedDailyNeeds.DailyNeed  
von CalculateDailyNeed berechneter Tagesbedarf pro Artikel(-variante), wird auch für Berechnung des ReorderLevel und SupplierMinimumPurchase genutzt (wichtig!)

* ItemsWarehouseSettings.StockTurnover  
wird für Berechnung des SupplierMinimumPurchase genutzt (wichtig!)

### Selectoren ###

* ItemAvailability.Inactive  
betrachte nur aktive Produkte


## CalculateWriteBackPermissions ##

Benötigte Felder des Scripts CalculateWriteBackPermissions

## Zuordnung 1 ##

* ItemsBase.ItemID
* ItemAvailability.ItemID
* ItemAttributeValueSets.ItemID
* ItemsSuppliers.ItemID
* ItemsWarehouseSettings.ItemID

## Zuordnung 2 ##

* ItemsWarehouseSettings.AttributeValueSetID
* ItemAttributeValueSets.AttributeValueSetID

## Datenfelder ##

* ItemsBase.Marking1ID  
Markierung 1 rot! | gelb | rot | grün | schwarz

* ItemsBase.BundleType  
entweder null | bundle | bundle_item  
(Produkte mit `BundleType = 'bundle'` erhalten KEINE WritePermission, Produkte mit `BundleType = 'bundle_item'` order Produkte die nicht Teil eines Bundles sind ggf. schon)

* ItemsWarehouseSettings.StockTurnover  
für WritePermission muss `StockTurnover > 0` gelten

* ItemsWarehouseSettings.ReorderLevel  
für WritePermission muss `ReorderLevel > 0` gelten

* ItemsSuppliers.SupplierDeliveryTime  
für WritePermission muss `SupplierDeliveryTime > 0` gelten

* ItemsSuppliers.SupplierMinimumPurchase  
für Variantenartikel muss `SupplierMinimumPurchase = 0` gelten sonst wird eine ggf. erteilte WritePermission vorenhalten

### Selectoren ###

* ItemAvailability.Inactive  
betrachte nur aktive Produkte

## MatchJansenToNx ##

Die Entwicklung an diesem Script ist noch nicht vollständig abgeschlossen.




