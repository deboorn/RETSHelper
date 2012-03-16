<?php

	/**
	 * Example of How to Use RETSHelper Class
	 */
	
 	//ini_set('display_errors','1');
 	require_once('retshelper.php');	//fetch helper class
	
	/**
	 * login_url, username, password are required for class
	 */
	$settings = array(
		'login_url' => '',
		'username' => '',
		'password' => '',
	);
	
	
	$feed = new RETSHelper($settings);
	if(($feed->connect())===false) die('Failed to connect to feed.');
	
	
	//example to get meta data
	/*
	$r = $feed->getMetaObjects('Property');
	var_dump($r);
	*/
	
	//example to download property image
	/*
	$r = $feed->downloadImages('Property', '1204437');
	var_dump($r);
	*/
	
	//example for downloading data
	/*
	//query all for sale listings that are active, check column names for your local feed
	$query = "(ListDate=2012-03-01T00:00:00+),(L_SaleRent=S),(ListingStatus=1)";
	$r = $feed->search($query, "RE_1");
	var_dump($query, $r);
	*/
	
	//example for creating table sql
	/*
	$sql = $feed->getTableSqlFromMeta('Property', 'RE_1','paragon_rets_property_re');
	die("<pre>$sql");
	*/
	
	//example of mysql import feature using the created table
	/*
	$conn = mysql_connect('localhost','root','root') or die(mysql_error());
	mysql_select_db('phrets',$conn) or die(mysql_error());
	$r = $feed->importCsvFile('property_re_1.csv', $conn, 'paragon_rets_property_re');
	var_dump($r);
	*/