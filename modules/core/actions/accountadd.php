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

/*
 * types of account:
 *    shell = 1 (0000000000000001)
 *    mail = 2, (0000000000000010)
 *    www = 4,  (0000000000000100)
 *    ftp = 8	(0000000000001000)
 */

$layout['pagetitle'] = trans('New Account');

if(isset($_POST['account']))
{
	$account = $_POST['account'];
	$quota = $_POST['quota'];
	
	foreach($quota as $type => $value)
		$quota[$type] = sprintf('%d', $value);

	if(!($account['login'] || $account['passwd1'] || $account['passwd2']))
	{
		$SESSION->redirect('?m=accountlist');
	}
	
	$account['type'] = array_sum($account['type']);

	if(!eregi("^[a-z0-9._-]+$", $account['login']))
    		$error['login'] = trans('Login contains forbidden characters!');
	elseif($LMS->GetAccountIdByLogin($account['login']))
		$error['login'] = trans('Account with that login name exists!');
	
	if($account['passwd1'] != $account['passwd2'])
		$error['passwd'] = trans('Passwords does not match!');
	    
	if($account['passwd1'] == '')
		$error['passwd'] = trans('Empty passwords are not allowed!');
	
	if($account['expdate'] == '')
		$account['expdate'] = 0;
	else
	{
		$date = explode('/',$account['expdate']);
		if(!checkdate($date[1],$date[2],$date[0]))
			$error['expdate'] = trans('Incorrect date format! Enter date in YYYY/MM/DD format!');
		elseif(!$error)
			$account['expdate'] = mktime(0,0,0,$date[1],$date[2],$date[0]);
	}

	if(!$account['domainid'] && (($account['type'] & 2) == 2))
		$error['domainid'] = trans('E-mail account must contain domain part!');

	if(!$error)
	{
		$DB->Execute('INSERT INTO passwd (ownerid, login, password, home, expdate, domainid, type, realname, quota_sh, quota_mail, quota_www, quota_ftp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
				array(	$account['ownerid'],
					$account['login'],
					crypt($account['passwd1']),
					$account['home'],
					$account['expdate'],
					$account['domainid'],
					$account['type'],
					$account['realname'],
					$quota['sh'],
					$quota['mail'],
					$quota['www'],
					$quota['ftp']
					));
		$DB->Execute('UPDATE passwd SET uid = id+2000 WHERE login = ?',array($account['login']));
		$LMS->SetTS('passwd');
		
		if(!isset($account['reuse']))
		{
			$SESSION->redirect('?m=accountlist');
		}
		
		unset($account['login']);
		unset($account['home']);
		unset($account['realname']);
		unset($account['passwd1']);
		unset($account['passwd2']);
	}
	$SMARTY->assign('quota', $quota);
}

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

if(!isset($account['type'])) $account['type'] = 32767;

$SMARTY->assign('error', $error);
$SMARTY->assign('customers', $LMS->GetCustomerNames());
$SMARTY->assign('domainlist', $DB->GetAll('SELECT id, name FROM domains ORDER BY name'));
$SMARTY->assign('account', $account);
$SMARTY->display('accountadd.html');

?>