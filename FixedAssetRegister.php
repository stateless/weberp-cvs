<?php

$PageSecurity = 11;

include('includes/session.inc');

$title = _('Fixed Asset Register');

include('includes/header.inc');
echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/magnifier.png" title="' .
	 _('Search') . '" alt="">' . ' ' . $title;
	
$sql = "SELECT * FROM stockcategory WHERE stocktype='".'A'."'";
$result = DB_query($sql,$db);
	 
echo '<form name="RegisterForm" method="post" action="' . $_SERVER['PHP_SELF'] . '?' . SID . '"><table>';
echo '<tr><th>'._('Asset Category').'</th>';
echo '<td><select name=assetcategory>';
while ($myrow=DB_fetch_array($result)) {
	if (isset($_POST['assetcategory']) and $myrow['categoryid']==$_POST['assetcategory']) {
		echo '<option selected value='.$myrow['categoryid'].'>'.$myrow['categorydescription'].'</option>';
	} else {
		echo '<option value='.$myrow['categoryid'].'>'.$myrow['categorydescription'].'</option>';
	}
}
echo '</select></table><br>';

echo '<div class="centre"><input type="Submit" name="submit" value="' . _('Show Assets') . '"></div>';

echo '</form>';

if (isset($_POST['submit'])) {
	$sql='SELECT assetmanager.id, 
			assetmanager.stockid,
			stockmaster.longdescription,
			assetmanager.serialno,
			fixedassetlocations.locationdescription, 
			assetmanager.cost, 
			assetmanager.datepurchased,
			assetmanager.depn 
		FROM assetmanager 
		LEFT JOIN stockmaster ON assetmanager.stockid=stockmaster.stockid 
		LEFT JOIN stockcategory ON stockmaster.categoryid=stockcategory.categoryid 
		LEFT JOIN fixedassetlocations ON assetmanager.location=fixedassetlocations.locationid
		WHERE stockmaster.categoryid="'.$_POST['assetcategory'].'"';
	$result=DB_query($sql, $db);

	echo '<br><table width=80%><tr>';
	echo '<th>'._('Asset ID').'</th>';
	echo '<th>'._('Stock ID').'</th>';
	echo '<th>'._('Description').'</th>';
	echo '<th>'._('Serial Number').'</th>';
	echo '<th>'._('Location').'</th>';
	echo '<th>'._('Date Acquired').'</th>';
	echo '<th>'._('Cost').'</th>';
	echo '<th>'._('Depreciation').'</th>';
	echo '<th>'._('NBV').'</th></tr>';
		
	while ($myrow=DB_fetch_array($result)) {
		echo '<tr><td>'.$myrow['id'].'</td>';
		echo '<td>'.$myrow['stockid'].'</td>';
		echo '<td>'.$myrow['longdescription'].'</td>';
		echo '<td>'.$myrow['serialno'].'</td>';
		echo '<td>'.$myrow['locationdescription'].'</td>';
		echo '<td>'.ConvertSQLDate($myrow['datepurchased']).'</td>';
		echo '<td class=number>'.number_format($myrow['cost'],2).'</td>';
		echo '<td class=number>'.number_format($myrow['depn'],2).'</td>';
		echo '<td class=number>'.number_format($myrow['cost']-$myrow['depn'],2).'</td>';
	}
}

include('includes/footer.inc');

?>