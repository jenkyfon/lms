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

function GetReceiptList($registry, $order='', $search=NULL, $cat=NULL, $from=0, $to=0)
{
	global $CONFIG, $DB;

	list($order,$direction) = sscanf($order, '%[^,],%s');

	($direction != 'desc') ? $direction = 'asc' : $direction = 'desc';

	switch($order)
	{
		case 'number':
			$sqlord = " ORDER BY documents.number $direction";
		break;
		case 'name':
			$sqlord = " ORDER BY documents.name $direction, documents.cdate";
		break;
		case 'user':
			$sqlord = " ORDER BY users.name $direction, documents.cdate";
		break;
		case 'cdate':
		default:
			$sqlord = " ORDER BY documents.cdate $direction, number";
		break;
	}

	if($search && $cat)
	{
		switch($cat)
		{
			case 'value':
				$having = ' HAVING SUM(value) = '.str_replace(',','.',$search);
				break;
			case 'number':
				$where = ' AND number = '.intval($search);
				break;
			case 'ten':
				$where = ' AND ten = \''.$search.'\'';
				break;
			case 'customerid':
				$where = ' AND customerid = '.intval($search);
				break;
			case 'name':
				$where = ' AND documents.name ?LIKE? \'%'.$search.'%\'';
				break;
			case 'address':
				$where = ' AND address ?LIKE? \'%'.$search.'%\'';
				break;
		}
	}

	if($from)
		$where .= ' AND cdate >= '.$from;
	if($to)
		$where .= ' AND cdate <= '.$to;

	if($list = $DB->GetAll(
	        'SELECT documents.id AS id, SUM(value) AS value, number, cdate, customerid, 
		documents.name AS customer, address, zip, city, template, extnumber,
		MIN(description) AS title, COUNT(*) AS posnumber, users.name AS user 
		FROM documents 
		LEFT JOIN numberplans ON (numberplanid = numberplans.id)
		LEFT JOIN users ON (userid = users.id)
		LEFT JOIN receiptcontents ON (documents.id = docid AND type = ?) 
		WHERE regid = ?'
		.$where
		.' GROUP BY documents.id, number, cdate, customerid, documents.name, address, zip, city, template, users.name, extnumber '
		.$having
		.($sqlord != '' ? $sqlord : ''), 
		array(DOC_RECEIPT, $registry)
		))
	{
		foreach($list as $idx => $row)
		{
			$list[$idx]['number'] = docnumber($row['number'], $row['template'], $row['cdate'], $row['extnumber']);
			$list[$idx]['customer'] = $row['customer'].' '.$row['address'].' '.$row['zip'].' '.$row['city'];
			
			// don't retrive descriptions of all items to not decrease speed
			// but we want to know that there is something hidden ;)
			if($row['posnumber'] > 1) $list[$idx]['title'] .= ' ...';
			
			// summary
			if($row['value'] > 0)
				$list['totalincome'] += $row['value'];
			else
				$list['totalexpense'] += -$row['value'];
		}
		
		$list['order'] = $order;
		$list['direction'] = $direction;

		return $list;
	}
}

$SESSION->restore('rlm', $marks);
$marked = $_POST['marks'];
if(sizeof($marked))
        foreach($marked as $id => $mark)
	                $marks[$id] = $mark;
$SESSION->save('rlm', $marks);

if(isset($_POST['search']))
	$s = $_POST['search'];
else
	$SESSION->restore('rls', $s);
$SESSION->save('rls', $s);

if(isset($_GET['o']))
	$o = $_GET['o'];
else
	$SESSION->restore('rlo', $o);
$SESSION->save('rlo', $o);

if(isset($_POST['cat']))
	$c = $_POST['cat'];
else
	$SESSION->restore('rlc', $c);
$SESSION->save('rlc', $c);

if(isset($_GET['regid']))
	$regid = $_GET['regid'];
else
	$SESSION->restore('rlreg', $regid);
$SESSION->save('rlreg', $regid);

if(isset($_POST['from']))
{
	if($_POST['from'] != '')
	{
		list($year, $month, $day) = explode('/', $_POST['from']);
		$from = mktime(0,0,0, $month, $day, $year);
	}
}
elseif($SESSION->is_set('rlf'))
	$SESSION->restore('rlf', $from);
else
	$from = mktime(0,0,0);
$SESSION->save('rlf', $from);

if(isset($_POST['to']))
{
	if($_POST['to'] != '')
	{
		list($year, $month, $day) = explode('/', $_POST['to']);
		$to = mktime(23,59,59, $month, $day, $year);
	}
}
elseif($SESSION->is_set('rlt'))
	$SESSION->restore('rlt', $to);
else
	$to = mktime(23,59,59);
$SESSION->save('rlt', $to);

if(! $DB->GetOne('SELECT rights FROM cashrights WHERE userid=? AND regid=?', array($AUTH->id, $regid)) )
{
        $SMARTY->display('noaccess.html');
	$SESSION->close();
	die;
}

$receiptlist = GetReceiptList($regid, $o, $s, $c, $from, $to);

$SESSION->restore('rlc', $listdata['cat']);
$SESSION->restore('rls', $listdata['search']);
$SESSION->restore('rlf', $listdata['from']);
$SESSION->restore('rlt', $listdata['to']);

$listdata['order'] = $receiptlist['order'];
$listdata['direction'] = $receiptlist['direction'];
$listdata['totalincome'] = $receiptlist['totalincome'];
$listdata['totalexpense'] = $receiptlist['totalexpense'];
$listdata['regid'] = $regid;

unset($receiptlist['order']);
unset($receiptlist['direction']);
unset($receiptlist['totalincome']);
unset($receiptlist['totalexpense']);

$listdata['totalpos'] = sizeof($receiptlist);
$listdata['cashstate'] = $DB->GetOne('SELECT SUM(value) FROM receiptcontents WHERE regid=?', array($regid));
if($from > 0)
	$listdata['startbalance'] = $DB->GetOne('SELECT SUM(value) FROM receiptcontents
						LEFT JOIN documents ON (docid = documents.id AND type = ?) 
						WHERE cdate < ? AND regid = ?',
						array(DOC_RECEIPT, $from, $regid));

$listdata['endbalance'] = $listdata['startbalance'] + $listdata['totalincome'] - $listdata['totalexpense'];

$pagelimit = $CONFIG['phpui']['receiptlist_pagelimit'];
$page = (! $_GET['page'] ? ceil($listdata['totalpos']/$pagelimit) : $_GET['page']);
$start = ($page - 1) * $pagelimit;

$layout['pagetitle'] = trans('Cash Registry: $0', $DB->GetOne('SELECT name FROM cashregs WHERE id=?', array($regid)));
$SESSION->save('backto', 'm=receiptlist&regid='.$regid);

$SMARTY->assign('listdata',$listdata);
$SMARTY->assign('pagelimit',$pagelimit);
$SMARTY->assign('start',$start);
$SMARTY->assign('page',$page);
$SMARTY->assign('marks',$marks);
$SMARTY->assign('newreceipt', $_GET['receipt']);
$SMARTY->assign('which', $_GET['which']);
$SMARTY->assign('receiptlist',$receiptlist);
$SMARTY->display('receiptlist.html');

?>