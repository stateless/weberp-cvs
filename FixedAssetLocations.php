<?php
$PageSecurity = 11;

include('includes/session.inc');
$title = _('Fixed Asset Locations');
include('includes/header.inc');
echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/maintenance.png" title="' .
	 _('Search') . '" alt="">' . ' ' . $title;

if (isset($_POST['submit'])) {
	$InputError=0;
	if (!isset($_POST['locationid']) or strlen($_POST['locationid'])<1) {
		prnMsg(_('You must enter at least one character in the location ID'),'error');
		$InputError=1;
	}
	if (!isset($_POST['locdesc']) or strlen($_POST['locdesc'])<1) {
		prnMsg(_('You must enter at least one character in the location description'),'error');
		$InputError=1;
	}
	if ($InputError==0) {
		$sql='INSERT INTO fixedassetlocations
			VALUES (
				"'.$_POST['locationid'].'",
				"'.$_POST['locdesc'].'")';
		$result=DB_query($sql, $db);
	}
}

$sql='SELECT * FROM fixedassetlocations';
$result=DB_query($sql, $db);

echo '<table><tr>';
echo '<th>'._('Location ID').'</th><th>'._('Location Description').'</th></tr>';

while ($myrow=DB_fetch_array($result)) {
	echo '<tr><td>'.$myrow['locationid'].'</td>';
	echo '<td>'.$myrow['locationdescription'].'</td>';
	echo '<td><a href="'.$_SERVER['PHP_SELF'] . '?' . SID.'SelectedLocation='.$myrow['locationid'].'">' .
		 _('Edit') . '</td></tr>';
}
if (isset($_GET['SelectedLocation'])) {
	$sql='SELECT * FROM fixedassetlocations WHERE locationid="'.$_GET['SelectedLocation'].'"';
	$result=DB_query($sql, $db);
	$myrow=DB_fetch_array($result);
	$locationid=$myrow['locationid'];
	$locdesc=$myrow['locationdescription'];
} else {
	$locationid='';
	$locdesc='';	
}
echo '</table><br>';

echo '<form name="LocationForm" method="post" action="' . $_SERVER['PHP_SELF'] . '?' . SID . '"><table>';

echo '<tr><th style="text-align:left">'._('Location ID').'</th>';
echo '<td><input type=text name=locationid size=6 value="'.$locationid.'"></td></tr>';

echo '<tr><th style="text-align:left">'._('Location Description').'</th>';
echo '<td><input type=text name=locdesc size=20 value="'.$locdesc.'"></td></tr>';

echo '</table><br>';

echo '<div class="centre"><input type="Submit" name="submit" value="' . _('Enter Information') . '"></div>';

echo '</form>';

include('includes/footer.inc');
?>