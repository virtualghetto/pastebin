<?php
/**
 * $Project: Pastebin $
 * $Id: config.inc.php,v 1.1 2006/04/23 16:10:09 paul Exp $
 *
 * Configuration file, pulls in layered domain specific configs
 *
 * Copyright (C) 2006 Paul Dixon (paul@elphin.com)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the Affero General Public License
 * Version 1 or any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * Affero General Public License for more details.
 *
 * You should have received a copy of the Affero General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

$CONF=array();


//include a default
require_once('lib/config/default.conf.php');

//get domain parts and ensure no naughtiness is being attempted
$domain=explode('.', preg_replace('/[^A-Za-z0-9\-\._]/', '', $_SERVER['HTTP_HOST']));
foreach($domain as $idx=>$element)
{
	if (strlen($element)==0)
	{
		$element='bad';
	}
	$domain[$idx]=$element;
}

//now pull in overides for each level of domain
$config='';
$sep='';
for ($i=count($domain)-1; $i>=0; $i--)
{
	$config=$domain[$i].$sep.$config;
	$sep='.';

	@include_once("lib/config/$config.conf.php");
}

$subdomain="";
if (count($domain) > $CONF['base_domain_elements'])
{
	$sub=array_slice ($domain, 0, count($domain)-$CONF['base_domain_elements']);
	$subdomain=implode('.', $sub);
}

//store the name of the subdomain (empty for "main" pastebin)
$CONF['subdomain']=$subdomain;

//store the base domain
$dom=explode('.', $_SERVER['HTTP_HOST']);
while (count($dom)>2)
	array_shift($dom);
$basedomain=implode('.',$dom);
$CONF['basedomain']=$basedomain;

$fulldomain=$basedomain;

if(strlen($subdomain))
	$fulldomain = $subdomain . '.' . $basedomain;

$CONF['fulldomain']=$fulldomain;

$scheme="http";
if (isset($_SERVER['HTTPS']))
	$scheme="https";

if (isset($CONF['force_scheme']) && strlen($CONF['force_scheme']))
	$scheme=$CONF['force_scheme'];

$CONF['SCHEME']=$scheme;

//pull in required database class
require_once('lib/pastebin/db.'.$CONF['dbsystem'].'.class.php');

?>
