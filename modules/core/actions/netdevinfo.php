<?php

/*
 * LMS version 1.9-cvs
 *
 *  (C) Copyright 2001-2006 LMS Developers
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

if(! $LMS->NetDevExists($_GET['id']))
{
	$SESSION->redirect('?m=netdevlist');
}

$netdevinfo = $LMS->GetNetDev($_GET['id']);
$netdevconnected = $LMS->GetNetDevConnectedNames($_GET['id']);
$netcomplist = $LMS->GetNetdevLinkedNodes($_GET['id']);
$netdevlist = $LMS->GetNotConnectedDevices($_GET['id']);
$nodelist = $LMS->GetNodeList();
$replacelist = $LMS->GetNetDevList();

$replacelisttotal = $replacelist['total'];
unset($replacelist['order']);
unset($replacelist['total']);
unset($replacelist['direction']);

$nodelist = $LMS->GetUnlinkedNodes();
$netdevips = $LMS->GetNetDevIPs($_GET['id']);

unset($nodelist['total']);
unset($nodelist['order']);
unset($nodelist['totalon']);
unset($nodelist['totaloff']);
unset($nodelist['direction']);

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$layout['pagetitle'] = trans('Device Info: $0 $1 $2', $netdevinfo['name'], $netdevinfo['producer'], $netdevinfo['model']);

$netdevinfo['id'] = $_GET['id'];

$SMARTY->assign('netdevinfo',$netdevinfo);
$SMARTY->assign('netdevlist',$netdevconnected);
$SMARTY->assign('netcomplist',$netcomplist);
$SMARTY->assign('restnetdevlist',$netdevlist);
$SMARTY->assign('netdevips',$netdevips);
$SMARTY->assign('nodelist',$nodelist);
$SMARTY->assign('replacelist',$replacelist);
$SMARTY->assign('replacelisttotal',$replacelisttotal);
$SMARTY->assign('devlinktype',$SESSION->get('devlinktype'));
$SMARTY->assign('nodelinktype',$SESSION->get('nodelinktype'));
if(isset($_GET['ip']))
	$SMARTY->assign('nodeipdata',$LMS->GetNode($_GET['ip']));
$SMARTY->display('netdevinfo.html');

?>