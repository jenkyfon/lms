<?php

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2005 LMS Developers
 *
 *  Please, see the doc/AUTHORS for more information about authors!
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License Version 2 as
 *  published by the Free Software Foundation.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
 *  USA.
 *
 *  $Id$
 */

$nodedata = $_POST['nodedata'];

if(isset($nodedata))
{
	$nodedata['ipaddr'] = $_POST['nodedataipaddr'];
	$nodedata['ipaddr_pub'] = $_POST['nodedataipaddr_pub'];
	$nodedata['mac'] = $_POST['nodedatamac'];
	$nodedata['mac'] = str_replace('-',':',$nodedata['mac']);

	foreach($nodedata as $key => $value)
		$nodedata[$key] = trim($value);

	if($nodedata['ipaddr']=='' && $nodedata['ipaddr_pub'] && $nodedata['mac']=='' && $nodedata['name']=='')
		if($_GET['ownerid'])
		{
			$SESSION->redirect('?m=customerinfo&id='.$_GET['ownerid']);
		}else{
			$SESSION->redirect('?m=nodelist');
		}
	
	if($nodedata['name']=='')
		$error['name'] = trans('Node name is required!');
	elseif(strlen($nodedata['name']) > 16)
		$error['name'] = trans('Node name is too long (max.16 characters)!');
	elseif($LMS->GetNodeIDByName($nodedata['name']))
		$error['name'] = trans('Specified name is in use!');
	elseif(!eregi('^[_a-z0-9-]+$',$nodedata['name']))
		$error['name'] = trans('Specified name contains forbidden characters!');		

	if(!$nodedata['ipaddr'])
		$error['ipaddr'] = trans('Node IP address is required!');
	elseif(!check_ip($nodedata['ipaddr']))
		$error['ipaddr'] = trans('Incorrect node IP address!');
	elseif(!$LMS->IsIPValid($nodedata['ipaddr']))
		$error['ipaddr'] = trans('Specified IP address doesn\'t overlap with any network!');
	elseif(!$LMS->IsIPFree($nodedata['ipaddr']))
		$error['ipaddr'] = trans('Specified IP address is in use!');
	elseif($LMS->IsIPGateway($nodedata['ipaddr']))
		$error['ipaddr'] = trans('Specified IP address is network gateway!');

	if($nodedata['ipaddr_pub']!='0.0.0.0' && $nodedata['ipaddr_pub']!='')
	{
		if(!check_ip($nodedata['ipaddr_pub']))
                	$error['ipaddr_pub'] = trans('Incorrect node IP address!');
        	elseif(!$LMS->IsIPValid($nodedata['ipaddr_pub']))
                	$error['ipaddr_pub'] = trans('Specified IP address doesn\'t overlap with any network!');
		elseif(!$LMS->IsIPFree($nodedata['ipaddr_pub']))
			$error['ipaddr_pub'] = trans('Specified IP address is in use!');
		elseif($LMS->IsIPGateway($nodedata['ipaddr_pub']))
			$error['ipaddr_pub'] = trans('Specified IP address is network gateway!');
	}
	else
    		$nodedata['ipaddr_pub'] = '0.0.0.0';

	if(!$nodedata['mac'])
		$error['mac'] = trans('MAC address is required!');
	elseif(!check_mac($nodedata['mac']))
		$error['mac'] = trans('Incorrect MAC address!');
	elseif($LMS->CONFIG['phpui']['allow_mac_sharing'] == FALSE)
		if($LMS->GetNodeIDByMAC($nodedata['mac']))
			$error['mac'] = trans('Specified MAC address is in use!');

	if(strlen($nodedata['passwd']) > 32)
		$error['passwd'] = trans('Password is too long (max.32 characters)!');

	if(! $LMS->CustomerExists($nodedata['ownerid']))
		$error['customer'] = trans('You have to select owner!');
	elseif($LMS->GetCustomerStatus($nodedata['ownerid']) != 3)
		$error['customer'] = trans('Selected customer is not connected!');

	if($nodedata['netdev'])
	{
		$netdev = $LMS->GetNetDev($nodedata['netdev']); 
		if($netdev['ports'] <= $netdev['takenports']) 
			$error['netdev'] = trans('No free ports on device!');
	}
	else
		$nodedata['netdev'] = 0;

	if(!$error)
	{
		$nodeid = $LMS->NodeAdd($nodedata);
		if($nodedata['reuse']=='')
		{
			$SESSION->redirect('?m=nodeinfo&id='.$nodeid);
		}
		unset($nodedata);
		$nodedata['reuse'] = '1';
	}
	
	if($nodedata['ipaddr_pub']=='0.0.0.0')
		$nodedata['ipaddr_pub'] = '';
}

if($LMS->CustomerExists($_GET['ownerid']) < 0)
{
	$SESSION->redirect('?m=customerinfo&id='.$_GET['ownerid']);
}

$nodedata['access'] = 1;

if($_GET['ownerid'] && $LMS->CustomerExists($_GET['ownerid']) > 0)
{
	$nodedata['ownerid'] = $_GET['ownerid'];
	$customerinfo = $LMS->GetCustomer($_GET['ownerid']);
}

if(isset($_GET['preip']) && $nodedata['ipaddr']=='')
	$nodedata['ipaddr'] = $_GET['preip'];

if(isset($_GET['premac']) && $nodedata['mac']=='')
	$nodedata['mac'] = $_GET['premac'];

if(isset($_GET['prename']) && $nodedata['name']=='')
	$nodedata['name'] = $_GET['prename'];
		

$layout['pagetitle'] = trans('New Node');

$customers = $LMS->GetCustomerNames();

if($nodedata['ownerid'])
{
	$SMARTY->assign('balancelist', $LMS->GetCustomerBalanceList($nodedata['ownerid']));
	$SMARTY->assign('assignments', $LMS->GetCustomerAssignments($nodedata['ownerid']));
	$SMARTY->assign('customergroups', $LMS->CustomergroupGetForCustomer($nodedata['ownerid']));
	$SMARTY->assign('othercustomergroups', $LMS->GetGroupNamesWithoutCustomer($nodedata['ownerid']));
	$documents = $LMS->GetDocuments($nodedata['ownerid'], 10);
	$taxeslist = $LMS->GetTaxes();
	$tariffs = $LMS->GetTariffs();
}

$SMARTY->assign('netdevices',$LMS->GetNetDevNames());
$SMARTY->assign('tariffs',$tariffs);
$SMARTY->assign('taxeslist',$taxeslist);
$SMARTY->assign('documents',$documents);
$SMARTY->assign('customers',$customers);
$SMARTY->assign('error',$error);
$SMARTY->assign('customerinfo',$customerinfo);
$SMARTY->assign('nodedata',$nodedata);
$SMARTY->display('nodeadd.html');

?>