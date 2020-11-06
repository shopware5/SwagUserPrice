INSERT INTO `s_plugin_pricegroups` (`id`, `name`, `gross`, `active`) VALUES
(1, 'NiceGroup', 1, 1),
(2, 'AnotherGroup', 1, 0);

INSERT INTO `s_plugin_pricegroups_prices` (`id`, `pricegroup`, `from`, `to`, `articleID`, `articledetailsID`, `price`) VALUES
(9, '1', 1, '9', 178, 407, 15.924369747899),
(10, '1', 10, '19', 178, 407, 15.084033613445),
(11, '1', 20, 'beliebig', 178, 407, 13.403361344538);
