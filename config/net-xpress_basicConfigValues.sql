INSERT INTO `db473835270`.`MetaConfig` (`ConfigKey`, `Domain`, `ConfigValue`, `ConfigType`, `LastUpdate`, `Active`)
VALUES
  ('MinimumOrdersA', 'stock', '8', 'int', 1391510512, 1),
  ('MinimumOrdersB', 'stock', '3', 'int', 0, 1),
  ('SpikeTolerance', 'stock', '0.6', 'float', 1398417938, 1),
  ('WritebackActive', 'stock', '1', 'int', 1397212534, 1),
  ('CalculationTimeA', 'stock', '120', 'int', 1399364357, 1),
  ('CalculationTimeB', 'stock', '30', 'int', 0, 1),
  ('CalculationActive', 'stock', '1', 'int', 1397212524, 1),
  ('MinimumToleratedSpikesA', 'stock', '4', 'int', 0, 1),
  ('MinimumToleratedSpikesB', 'stock', '2', 'int', 0, 1),
  ('StandardDeviationFactor', 'stock', '1', 'float', 0, 1)
ON DUPLICATE KEY UPDATE
  `ConfigKey`   = VALUES(`ConfigKey`),
  `Domain`      = VALUES(`Domain`),
  `ConfigValue` = VALUES(`ConfigValue`),
  `ConfigType`  = VALUES(`ConfigType`),
  `LastUpdate`  = VALUES(`LastUpdate`),
  `Active`      = VALUES(`Active`);

