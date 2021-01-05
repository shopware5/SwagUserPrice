INSERT INTO `s_plugin_pricegroups` (`id`, `name`, `gross`, `active`) VALUES
(1, 'fooBar', 1, 1);

INSERT INTO `s_plugin_pricegroups_prices` (`id`, `pricegroup`, `from`, `to`, `articleID`, `articledetailsID`, `price`) VALUES
(1, '1', 1, 'beliebig', 178, 407, 12.605042016807);

INSERT INTO `s_user_attributes` (`id`, `userID`, `swag_pricegroup`) VALUES
(1, 1, 1);
