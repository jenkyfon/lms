<?php

/*
 * LMS version 1.6-cvs
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

function GetHostList()
{
	global $LMS;
	$list = $LMS->DB->GetAll("SELECT id, name, description, lastreload FROM daemonhosts ORDER BY name");
	return $list;
}

$layout['pagetitle'] = trans('Hosts List');

$hostlist = GetHostList();

$SESSION->save('backto', $_SERVER['QUERY_STRING']);

$SMARTY->assign('hostlist', $hostlist);
$SMARTY->display('daemonhostlist.html');
?>