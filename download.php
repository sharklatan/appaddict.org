<?php

// download script really
require_once 'init.php';

if ($API->account)
    $reportjs = 'var report = prompt("' . $API->LANG->_('REPORT_WARNING') . '");
	
	if (!report) {
		alert("' . $API->LANG->_('You must provide reason of report') . '");
		return false;
	}
	
	window.location="report.php?id="+id+"&reason="+report;
	
	return true;';
else
    $reportjs = 'var confirmed = confirm("' . $API->LANG->_('You must be registered to submit a report') . '. ' . $API->LANG->_('Do you want to sign up now?') . '");
if (confirmed) { window.location="' . $API->SEO->make_link('signup') . '"; return true; }
    else return false;';
$API->TPL->assign('headeradd', '
<script>
function report(id) {
 ' . $reportjs . '
}
</script>

');
$trackid = $API->getval('trackid', 'int');
$API->TPL->assign('pagetitle', $API->LANG->_('Downloading Content...'));
$API->TPL->assign('footername', $API->LANG->_('Downloading Content...'));
// check that app is uloaded:

$appdata = $API->DB->query_row("SELECT apps.* FROM apps WHERE trackid={$trackid}");

if (!$appdata) {
    app_error_message($trackid);
}


update_app_downloads($trackid);

$decoded_links = $API->DB->query_return("SELECT links.*, IF(verified_crackers.account_id=accounts.id AND accounts.name=links.cracker,1,0) AS verified FROM links LEFT JOIN verified_crackers ON links.uploader_id=verified_crackers.account_id LEFT JOIN accounts ON accounts.id=links.uploader_id WHERE trackid={$trackid} AND state='accepted' ORDER BY verified DESC, links.added ASC, id DESC");

$wait = ($API->account ? 0 : $API->CONFIG['redirection_wait']); //(is_premium()?0:$API->CONFIG['redirection_wait']);

if ($decoded_links) {
    foreach ($decoded_links as $ldetails) {

        $ldata = parse_url($ldetails['link']);
        if ($ldata['scheme'] == 'magnet')
            $links[] = array('id' => $ldetails['id'], 'no_redirection' => true, 'host' => $API->LANG->_('.torrent magnet link'), 'link_ticket' => $ldetails['link'], 'cracker' => $ldetails['cracker'], 'verified' => ($ldetails['verified'] ? true : false), 'di_compatible' => false, 'ss_compatible' => false, 'protected' => $ldetails['protected']);
        else {
            $link_ticket = urlencode(encrypt(json_encode(array('link' => $ldetails['link'], 'wait' => $wait, 'ua' => $_SERVER['HTTP_USER_AGENT'], 'ip' => $API->getip())), $API->CONFIG['REDIRECTOR_SECRET']));


            $links[] = array('id' => $ldetails['id'], 'no_redirection' => false, 'host' => $API->LANG->_('Download from %s', $ldata['host']), 'link_ticket' => $link_ticket, 'cracker' => $ldetails['cracker'], 'verified' => ($ldetails['verified'] ? true : false), 'di_compatible' => (($appdata['compatibility'] != 4) && $appdata['type'] != 'book' && is_directinstaller_compatible($ldetails['link'])), 'ss_compatible' => (($appdata['compatibility'] != 4) && $appdata['type'] != 'book' && is_signservice_compatible($ldetails['link'])), 'protected' => $ldetails['protected']);
        }
    }
}
$API->TPL->assign('links', $links);

$API->TPL->assign('appdata', $appdata);

$API->TPL->display('download.tpl');
?>