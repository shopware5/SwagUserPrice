<?php
/**
 * Shopware 4.0
 * Copyright � 2012 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 *
 * @category   Shopware
 * @package    Shopware_Controllers_Backend_UserPrice
 * @subpackage Result
 * @copyright  Copyright (c) 2012, shopware AG (http://www.shopware.de)
 * @version    $Id$
 * @author     Stefan Hamann
 * @author     $Author$
 */
class Shopware_Controllers_Backend_UserPrice extends Enlight_Controller_Action
{
    public function preDispatch()
    {
        if(!in_array($this->Request()->getActionName(), array('index', 'skeleton'))) {
            Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();
        }
    }

    public function indexAction() { }
    public function skeletonAction() { }

    public function getValuesAction() {
        //@todo: Missing value? $feedID
        $feedID = '';

        $delete = intval($this->Request()->delete);

        if(!empty($delete))
        {
        	switch ($this->Request()->name)
        	{
        		case "customerpricegroups":
        			Shopware()->Db()->query("DELETE FROM s_core_customerpricegroups_prices WHERE pricegroup=?", array('PG' . $delete));
//                    Shopware()->Db()->query("DELETE FROM s_articles_groups_prices WHERE groupkey=?", array('PG' . $delete));
                    Shopware()->Db()->query("UPDATE s_user SET pricegroupID=NULL WHERE pricegroupID=?", array($delete));
                    Shopware()->Db()->query("DELETE FROM s_core_customerpricegroups WHERE id=?", array($delete));
        			break;
        		default:
        			exit();
        	}
        }

        $limit = empty($this->Request()->limit) ? 25 : (int)$this->Request()->limit;
        $start = empty($this->Request()->start) ? 0 : (int)$this->Request()->start;

        switch ($this->Request()->name)
        {
        	case "customergroup":
        		$sql = "
        			SELECT 0 as id, 'allgemein g�ltig' as name
        			UNION (
        				SELECT id, description as name
        				FROM s_core_customergroups
        				ORDER BY name
        			)
        		";
        		break;
        	case "multishop":
        		$sql = "
        			SELECT 0 as id, 'allgemein g�ltig' as name
        			UNION (
        				SELECT id, name
        				FROM s_core_multilanguage
        				ORDER BY `default` DESC, name
        			)
        		";
        		break;
        	case "language":
        		$sql = "
        			SELECT 0 as id, 'Standard' as name
        			UNION
        			SELECT id, isocode as name
        			FROM s_core_multilanguage
        			WHERE skipbackend=0
        			GROUP BY isocode
        		";
        		break;
        	case "tax":
        		$sql = "
        			SELECT 0 as id, 'h�chster Steuersatz aus dem Warenkorb nehmen' as name
        			UNION
        			SELECT id, description as name
        			FROM `s_core_tax`
        		";
        		break;
        	case "supplier":
        		if(empty($this->Request()->active))
        		{
        			$sql = "
        				SELECT s.id, s.name, s.img
        				FROM s_articles_supplier s
        				LEFT JOIN s_articles AS a ON a.supplierID = s.id
        				LEFT JOIN s_export_suppliers AS es ON es.supplierID = s.id AND feedID = $feedID
        				WHERE es.supplierID IS NULL
        				GROUP BY s.id ORDER BY name
        			";
        		}
        		else
        		{
        			$sql = "
        				SELECT s.id, s.name, s.img
        				FROM s_articles_supplier s
        				LEFT JOIN s_articles AS a ON a.supplierID = s.id
        				JOIN s_export_suppliers AS es ON es.supplierID = s.id AND feedID = $feedID
        				GROUP BY s.id ORDER BY name
        			";
        		}
        		break;
        	case "currency":
        		$sql = "
        			SELECT id, name
        			FROM s_core_currencies
        		";
        		break;
        	case "category";
        		$node = (empty($_REQUEST["node"])||!is_numeric($_REQUEST["node"])) ? 1 : (int) $_REQUEST["node"];
        		$sql = "
        			SELECT c.id, c.description as text, c.parent as parentId, IF(COUNT(c2.id)>0,0,1) as leaf FROM s_categories c LEFT JOIN s_categories c2 ON c2.parent=c.id  WHERE c.parent=$node GROUP BY c.id ORDER BY c.position, c.description
        		";
        		break;
        	case "holiday";
        		$sql = "
        			SELECT id, CONCAT(name,' (',DATE_FORMAT(`date`,'%d.%m.%Y'),')') as name
        			FROM s_premium_holidays
        			ORDER BY `date`, name
        		";
        		break;
        	case "article":
        		if(empty($this->Request()->active))
        		{
        			$sql = "
        				SELECT a.id, a.name
        				FROM s_articles a, s_export_articles ea
        				WHERE a.id=ea.articleID
        				AND ea.feedID=$feedID
        			";
        		}
        		elseif(!empty($this->Request()->filter))
        		{
        			$sql_filter = Shopware()->Db()->quote('%' . trim($this->Request()->filter) . '%');
        			$sql = "
        				SELECT
        					a.id, a.name
        				FROM
        					s_articles as a,
        				(
        					SELECT DISTINCT articleID
        					FROM
        					(
        							SELECT DISTINCT articleID
        							FROM s_articles_details
        							WHERE ordernumber LIKE $sql_filter
        							LIMIT 10
        						UNION
        							SELECT DISTINCT articleID
        							FROM s_articles_translations
        							WHERE name LIKE $sql_filter
        							LIMIT 10
        						UNION
        							SELECT DISTINCT articleID
        							FROM s_articles_translations
        							WHERE name LIKE $sql_filter
        							LIMIT 10
        						UNION
        							SELECT id as articleID
        							FROM s_articles
        							WHERE name LIKE $sql_filter
        							LIMIT 10
        					) as amu
        				) as am
        				WHERE am.articleID=a.id
        				ORDER BY a.name ASC
        				LIMIT 20
        			";
        		}
        		break;
        	case "paymentmean":
        		if(empty($this->Request()->active))
        		{
        			$sql = "
        				SELECT p.id, p.description as name
        				FROM s_core_paymentmeans p
        				LEFT JOIN s_premium_dispatch_paymentmeans AS dp ON dp.paymentID = p.id AND dispatchID = $feedID
        				WHERE dispatchID IS NULL
        				ORDER BY name
        			";
        		}
        		else
        		{
        			$sql = "
        				SELECT p.id, p.description as name
        				FROM s_core_paymentmeans p
        				JOIN s_premium_dispatch_paymentmeans AS dp ON dp.paymentID = p.id AND dispatchID = $feedID
        				ORDER BY name
        			";
        		}
        		break;
        	case "countries":
        		if(empty($this->Request()->active))
        		{
        			$sql = "
        				SELECT c.id, c.countryname as name
        				FROM s_core_countries c
        				LEFT JOIN s_premium_dispatch_countries AS dc ON dc.countryID = c.id AND dispatchID = $feedID
        				WHERE dispatchID IS NULL
        				ORDER BY name
        			";
        		}
        		else
        		{
        			$sql = "
        				SELECT c.id, c.countryname as name
        				FROM s_core_countries c
        				JOIN s_premium_dispatch_countries AS dc ON dc.countryID = c.id AND dispatchID = $feedID
        				ORDER BY name
        			";
        		}
        		break;
        	case "dispatch":
        		$sql = "
        			SELECT id, name
        			FROM s_shippingcosts_dispatch d
        		";
        		break;
        	case "premium_dispatch":
        		$sql = "
        			SELECT id, name
        			FROM s_premium_dispatch
        			ORDER BY position, name
        		";
        		break;
        	case "users":
        		if(!empty($this->Request()->pricegroupID))
        			$sql_where = 'AND u.pricegroupID='.intval($this->Request()->pricegroupID);
        		else
        			$sql_where = 'AND u.pricegroupID IS NULL';
        		$dir = (empty($this->Request()->dir)|| $this->Request()->dir=='ASC') ? 'ASC' : 'DESC';
        		$sort = (empty($this->Request()->sort)||is_array($this->Request()->sort)) ? 'customernumber' : preg_replace('#[^\w]#','',$this->Request()->sort);
        		if(!empty($_REQUEST["search"]))
        		{
        			$search = Shopware()->Db()->quote(trim($this->Request()->search) . '%');
        			$search2 = Shopware()->Db()->quote('%' . trim($this->Request()->search) . '%');
        			$sql_where .= " AND ( ub.customernumber LIKE  $search ";
        			$sql_where .= "OR u.email LIKE $search2 ";
        			$sql_where .= "OR ub.company LIKE $search ";
        			$sql_where .= "OR ub.firstname LIKE $search ";
        			$sql_where .= "OR ub.lastname LIKE $search )";
        		}
        		$sql = "
        			SELECT SQL_CALC_FOUND_ROWS u.id, ub.customernumber, u.email, u.customergroup, ub.company, ub.firstname ,ub.lastname
        			FROM s_user u, s_user_billingaddress ub
        			WHERE u.id=ub.userID
        			$sql_where
        			ORDER BY $sort $dir
        			LIMIT $start, $limit
        		";
        		break;
        	case "customerpricegroups":
        		$sql = "
        			SELECT *
        			FROM s_core_customerpricegroups
        		";
        		break;
        	default:
        		exit();
        }

        $result = Shopware()->Db()->fetchAll($sql);
        $nodes = array();
        if (!empty($result)){
        foreach ($result as $row)
        {
        	if(isset($row["id"]))
        		$row["id"] = intval($row["id"]);
        	if(isset($row["leaf"]))
        		$row["leaf"] = !empty($row["leaf"]);
        	if(isset($row["netto"]))
        		$row["netto"] = empty($row["netto"]) ? 0 : 1;
        	if(isset($row["active"]))
        		$row["active"] =empty($row["active"]) ? 0 : 1;
        	if(!empty($row["count"]))
        		$row["name"] .= " (".$row["count"].")";
        	$nodes[] = $row;
        }
        }

        if(!empty($limit))
        {
            $count = Shopware()->Db()->fetchOne('SELECT FOUND_ROWS() as count');
        }
        else
        {
        	$count = count($nodes);
        }

        switch ($this->Request()->name) {
        	case "category":
        		echo  json_encode($nodes);
        		break;
        	default:
        		echo  json_encode(array("articles"=>$nodes,"count"=>$count));
        		break;
        }
    }

    public function savePricegroupsAction()
    {
        $upset = array();

        $upset[] = "active=".(empty($this->Request()->active) ? 0 : 1);
        $upset[] = "netto=".(empty($this->Request()->netto) ? 0 : 1);

        $name = str_replace("\xe2\x82\xac","&euro;",$this->Request()->name);
        $name = trim($name);
        $upset[] = "name=".((empty($name)) ? "''" : Shopware()->Db()->quote($name));

        $upset = implode(",",$upset);
        if(!empty($this->Request()->id)&&is_numeric($this->Request()->id))
        {
        	$id = (int) $this->Request()->id;
        	Shopware()->Db()->query("UPDATE s_core_customerpricegroups SET $upset WHERE id=?", array($id));
        }
        else
        {
        	Shopware()->Db()->query("REPLACE INTO s_core_customerpricegroups SET $upset");
        	//$id = Shopware()->Db()->lastInsertId('s_core_customerpricegroups');
        }
    }

    public function getArticlesAction()
    {
        if(isset($this->Request()->pricegroupID))
        {
        	$sql_pricegroup = "PG".intval($this->Request()->pricegroupID);
        }

        if(!empty($this->Request()->delete))
        {
            $delete = trim($this->Request()->delete);
//            Shopware()->Db()->query("
//        		DELETE gp FROM s_articles_groups_value gv, s_articles_groups_prices gp
//        		WHERE gv.ordernumber=? AND gp.groupkey=? AND gp.valueID=gv.valueID
//        	", array($delete, $sql_pricegroup));

            Shopware()->Db()->query("
        		DELETE p FROM s_articles_details d, s_core_customerpricegroups_prices p
        		WHERE d.ordernumber=?	AND p.pricegroup=? AND p.articledetailsID=d.id
        	", array($delete, $sql_pricegroup));
        }

        if(!empty($this->Request()->search))
        {
        	$search = Shopware()->Db()->quote(trim($this->Request()->search) . "%");
        	$sql_where = "WHERE d.ordernumber LIKE  $search ";
        	$sql_where .= "OR a.name LIKE $search";
        }
        $limit = empty($this->Request()->limit) ? 25 : (int)$this->Request()->limit;
        $start = empty($this->Request()->start) ? 0 : (int)$this->Request()->start;
        $dir = (empty($this->Request()->dir)||$this->Request()->dir=='ASC') ? 'ASC' : 'DESC';
        $sort = (empty($this->Request()->sort)||is_array($this->Request()->sort)) ? 'ordernumber' : preg_replace('#[^\w]#','',$this->Request()->sort);

            $result = Shopware()->Db()->fetchAll("
        		SELECT SQL_CALC_FOUND_ROWS
        			a.id as articleID,
        			d.ordernumber as ordernumber,
        			TRIM( CONCAT( a.name, ' ', d.additionaltext ) ) AS name,
        			p.pricegroup AS pricegroup,
        			p.price as price,
        			p2.price as defaultprice,
        			0 as config,
        			t.tax
        		FROM s_articles a
        		INNER JOIN s_articles_details d
        		ON d.articleID=a.id
        		INNER JOIN s_core_tax t
        		ON t.id=a.taxID

        		LEFT JOIN s_core_customerpricegroups_prices p
        		ON p.articledetailsID = d.id
        		AND p.`to` = 'beliebig'
        		AND p.pricegroup = ?

        		LEFT JOIN s_articles_prices p2
        		ON p2.articledetailsID = d.id
        		AND p2.`to` = 'beliebig'
        		AND p2.pricegroup = 'EK'

        		$sql_where

        		ORDER BY $sort $dir

        		LIMIT $start, $limit
        	", array($sql_pricegroup));
        	$rows = array();

        	if(!empty($result))
        	foreach($result as $row)
        	{
        		$row['name'] = trim($row['name']);
        		$row['ordernumber'] = trim($row['ordernumber']);
        		$row['pricegroup'] = trim($row['pricegroup']);
        		$row['config'] = empty($row['config']) ? 0 : 1;
        		if(!empty($row['tax'])&&empty($this->Request()->netto))
        		{
        			$row['price'] = $row['price']*(100+$row['tax'])/100;
        			$row['defaultprice'] = $row['defaultprice']*(100+$row['tax'])/100;
        		}
        		if(!empty($row['price']))
        		{
        			$row['price'] = number_format($row['price'],2,',','');
        		}
        		else
        		{
        			$row['price'] = '';
        		}
        		if(!empty($row['defaultprice']))
        		{
        			$row['defaultprice'] = number_format($row['defaultprice'],2,',','');
        		}
        		$rows[] = $row;
        	}
            $count = Shopware()->Db()->fetchOne("
        		SELECT FOUND_ROWS() as count
        	");

        	echo  json_encode(array("articles"=>$rows,"count"=>$count));
    }

    public function getPricescaleAction()
    {
        $minChange = (empty($this->Request()->minChange)||!is_numeric($this->Request()->minChange)) ? 0 : (float) $this->Request()->minChange;
        $startValue = (empty($this->Request()->startValue)||!is_numeric($this->Request()->startValue)) ? 0 : (float) $this->Request()->startValue;
        if(empty($this->Request()->netto)&&!empty($this->Request()->tax))
        {
        	$tax = (float) $this->Request()->tax;
        }
        else
        {
        	$tax = 0;
        }

        $result = Shopware()->Db()->fetchAll("
            SELECT `from`, `price`, `pseudoprice`, `baseprice`, `percent`
            FROM s_articles_details d, s_core_customerpricegroups_prices p
            WHERE d.ordernumber=?
            AND p.articledetailsID=d.id
            AND p.pricegroup=?
            ORDER BY `from`
        ", array($this->Request()->ordernumber, $this->Request()->pricegroup));
        if(empty($result))
        {
            $result = Shopware()->Db()->fetchAll("
                SELECT `from`, `price`, `pseudoprice`, `baseprice`, `percent`
                FROM s_articles_details d, s_core_customerpricegroups_prices p
                WHERE d.ordernumber=?
                AND p.articledetailsID=d.id
                AND p.pricegroup='EK'
                ORDER BY `from`
            ", array($this->Request()->ordernumber));
        }

        $nodes = array();

        if (!empty($result)){
            $i=0;
        	foreach($result as $node)
        	{
        		if($i)
        		{
        			$nodes[$i-1]["to"] = $node["from"]-$minChange;
        		}
        		$node["price"] = round($node["price"]*(100+$tax)/100,2);
        		if(empty($node["pseudoprice"])) $node["pseudoprice"] = '';
        		if(empty($node["baseprice"])) $node["baseprice"] = '';
        		if(empty($node["percent"])) $node["percent"] = '';
        		$nodes[$i] = $node;

                $i++;
        	}
//        	for($i=0;$node = mysql_fetch_assoc($result);$i++)
//        	{
//        		if($i)
//        		{
//        			$nodes[$i-1]["to"] = $node["from"]-$minChange;
//        		}
//        		$node["price"] = round($node["price"]*(100+$tax)/100,2);
//        		if(empty($node["pseudoprice"])) $node["pseudoprice"] = '';
//        		if(empty($node["baseprice"])) $node["baseprice"] = '';
//        		if(empty($node["percent"])) $node["percent"] = '';
//        		$nodes[$i] = $node;
//        	}
        }
        if(empty($nodes)&&!empty($this->Request()->ordernumber))
        {
        	$nodes[] = array(
        		"from"=>$startValue,
        		"value"=>"",
        		"factor"=>""
        	);
        }
        echo  json_encode(array("articles"=>array_values($nodes),"count"=>count($nodes)));
    }

    public function savePricescaleAction()
    {
        $from = (empty($this->Request()->from)||!is_numeric($this->Request()->from)) ? 1 : (int) $this->Request()->from;
        $price = (empty($this->Request()->price)||!is_numeric($this->Request()->price)) ? "0" : (float) $this->Request()->price;
        $pseudoprice = (empty($this->Request()->pseudoprice)||!is_numeric($this->Request()->pseudoprice)) ? "0" : (float) $this->Request()->pseudoprice;
        $baseprice = (empty($this->Request()->baseprice)||!is_numeric($this->Request()->baseprice)) ? "0" : (float) $this->Request()->baseprice;
        $percent = (empty($this->Request()->percent)||!is_numeric($this->Request()->percent)) ? "0" : (float) $this->Request()->percent;
        $pricegroup =  "PG" . (int) $this->Request()->pricegroupID;
        $tax = empty($this->Request()->tax) ? 0 : (float) $this->Request()->tax;
        if(!empty($tax))
        	$price = $price/(100+$tax)*100;
        if(!empty($tax)&&!empty($pseudoprice))
        	$pseudoprice = $pseudoprice/(100+$tax)*100;

//        $sql = "
//        	SELECT articleID, valueID
//        	FROM s_articles_groups_value
//        	WHERE ordernumber='".mysql_real_escape_string($this->Request()->ordernumber)."'
//        ";
//        $result = mysql_query($sql);
//        if(!$result)
//        	exit();
//        if(mysql_num_rows($result))
//        {
//        	list($articleID, $valueID) = mysql_fetch_row($result);
//        	if($from!=1)
//        	{
//        		exit();
//        	}
//        	$sql = "
//        		DELETE FROM s_articles_groups_prices WHERE groupkey=$pricegroup AND valueID=$valueID
//        	";
//        	mysql_query($sql);
//        	if(!empty($price))
//        	{
//        		$sql = "
//        			INSERT INTO s_articles_groups_prices
//        				(articleID, valueID, groupkey, price)
//        			VALUES
//        				($articleID, $valueID, '$pricegroup', $price);
//        		";
//        		mysql_query($sql);
//        	}
//        	exit();
//        }

        $result = Shopware()->Db()->fetchRow("
        	SELECT articleID, id as articledetailsID
        	FROM s_articles_details
        	WHERE ordernumber=?
        ", array($this->Request()->ordernumber));
        if(!empty($result))
        {
            $articleID = $result['articleID'];
            $articledetailsID = $result['articledetailsID'];

        	Shopware()->Db()->query("
        		DELETE FROM s_core_customerpricegroups_prices WHERE pricegroup=? AND articledetailsID=? AND `from`>=?
        	", array($pricegroup, $articledetailsID, $from));

        	if($from!=1)
        	{
                Shopware()->Db()->query("
        			UPDATE `s_core_customerpricegroups_prices`
        			SET `to` = ?
        			WHERE pricegroup = ?
        			AND articledetailsID = ?
        			ORDER BY `from` DESC
        			LIMIT 1
        		", array($from-1, $pricegroup, $articledetailsID));
        	}
        	if(!empty($price))
        	{
                Shopware()->Db()->query("

        			INSERT INTO `s_core_customerpricegroups_prices`
        				(`pricegroup`, `from`, `to`, `articleID`, `articledetailsID`, `price`, `pseudoprice`, `baseprice`, `percent`)
        			VALUES
        				(?, ?, 'beliebig', ?, ?, ?, ?, ?, ?);
        		", array(
                    $pricegroup,
                    $from,
                    $articleID,
                    $articledetailsID,
                    $price,
                    $pseudoprice,
                    $baseprice,
                    $percent
                ));
        	}
        }
    }

    public function saveUsersAction()
    {
        $pricegroupID = $this->Request()->pricegroupID;
        if(!empty($this->Request()->userIDs))
        {
        	foreach ($this->Request()->userIDs as &$userID)
        	{
        		$userID = (int) $userID;
        	}
        	$userIDs = implode(',',$this->Request()->userIDs);
        	Shopware()->Db()->query("
        	    UPDATE s_user SET pricegroupID=NULL WHERE pricegroupID=? AND id NOT IN ($userIDs)
            ", array($pricegroupID));

            Shopware()->Db()->query("
                UPDATE s_user SET pricegroupID=? WHERE id IN ($userIDs)
            ", array($pricegroupID));
        }
        else
        {
            Shopware()->Db()->query("
                UPDATE s_user SET pricegroupID=NULL WHERE pricegroupID=?
            ", array($pricegroupID));
        }
    }
}