<?php
/* $Revision: 1.1.2.1 $ */
$PageSecurity=15;

include('includes/session.inc');

$title = _('Tax Groups');
include('includes/header.inc');
include('includes/DateFunctions.inc');


if (isset($_GET['SelectedGroup'])){
	$SelectedGroup = $_GET['SelectedGroup'];
} elseif (isset($_POST['SelectedGroup'])){
	$SelectedGroup = $_POST['SelectedGroup'];
}

if (isset($_POST['submit']) OR isset($_GET['remove']) OR isset($_GET['add']) ) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */
	//first off validate inputs sensible
	if (isset($_POST['GroupName']) && strlen($_POST['GroupName'])<4){
		$InputError = 1;
		prnMsg(_('The Group description entered must be at least 4 characters long'),'error');
	}
	
	// if $_POST['GroupName'] then it is a modification of a tax group name
	// else it is either an add or remove of a page token 
	unset($sql);
	if (isset($_POST['GroupName']) ){ // Update or Add a tax group
		if(isset($SelectedGroup)) { // Update a tax group
			$sql = "UPDATE taxgroups SET taxgroupdescription = '". DB_escape_string($_POST['GroupName']) ."' 
					WHERE taxgroupid = ".$SelectedGroup;
			$ErrMsg = _('The update of the tax group description failed because');
			$SuccessMsg = _('The tax group description was updated.');
		} else { // Add Security Heading
			$sql = "INSERT INTO taxgroups (taxgroupdescription) VALUES ('". DB_escape_string($_POST['GroupName']) . "')";
			$ErrMsg = _('The addition of the group failed because');
			$SuccessMsg = _('The Group was created.');
		}
		unset($_POST['GroupName']);
		unset($SelectedGroup);
	} elseif (isset($SelectedGroup) ) {
		$TaxAuthority = $_GET['TaxAuthority'];
		if( isset($_GET['add']) ) { // adding a tax authority to a tax group
			$sql = "INSERT INTO taxgrouptaxes ( taxgroupid, 
								taxauthid) 
					VALUES (" . $SelectedGroup . ", 
						" . $TaxAuthority . ")";
					
			$ErrMsg = _('The addition of the tax failed because');
			$SuccessMsg = _('The tax  was added.');
		} elseif ( isset($_GET['remove']) ) { // remove a taxauthority from a tax group
			$sql = "DELETE FROM taxgrouptaxes 
					WHERE taxgroupid = ".$SelectedGroup."
					AND taxauthid = ".$TaxAuthority;
			$ErrMsg = _('The removal of this tax failed because');
			$SuccessMsg = _('This tax was removed.');
		}
		unset($_GET['add']);
		unset($_GET['remove']);
		unset($_GET['TaxAuthority']);
	}
	// Need to exec the query
	if (isset($sql) && $InputError != 1 ) {
		$result = DB_query($sql,$db,$ErrMsg);
		if( $result ) {
			prnMsg( $SuccessMsg,'success');
		}
	}
} elseif (isset($_POST['updateorder'])) {
	//A calculation order update
	foreach ( $_POST as $name => $value ) {
		if (strpos($name,'NEW_') === 0 ) {
			$key = substr($name, 4, strlen($name));
			if ( isset($_POST['OLD_'.$key]) && isset($_POST['NEW_'.$key]) &&
				$_POST['OLD_'.$key] != $_POST['NEW_'.$key] ) {
				$i = strpos($key, '_');
				if ( $i !== false ) {
					$tmpTaxAuth = substr($key,0,$i);
			
					$sql = "UPDATE taxgrouptaxes 
						SET taxcalculationorder=".$_POST['NEW_'.$key]."
						WHERE taxgroupid=" . $SelectedGroup . "
						AND taxauthid=".$tmpTaxAuth;
						
					$result = DB_query($sql,$db);
					if( $result ) {
						DB_free_result($result);
					}
				}
			}
		}
	}
} elseif (isset($_GET['delete'])) { 
	
	/* PREVENT DELETES IF DEPENDENT RECORDS IN 'custbranch, suppliers */
	
	$sql= "SELECT COUNT(*) FROM custbranch WHERE taxgroupid=" . $_GET['SelectedGroup'];
	$result = DB_query($sql,$db);
	$myrow = DB_fetch_row($result);
	if ($myrow[0]>0) {
		prnMsg( _('Cannot delete this tax group because some customer branches are setup using it'),'warn');
		echo '<BR>' . _('There are') . ' ' . $myrow[0] . ' ' . _('customer branches referring to this tax group');
	} else {
		$sql= "SELECT COUNT(*) FROM suppliers WHERE taxgroupid=" . $_GET['SelectedGroup'];
		$result = DB_query($sql,$db);
		$myrow = DB_fetch_row($result);
		if ($myrow[0]>0) {
			prnMsg( _('Cannot delete this tax group because some suppliers are setup using it'),'warn');
			echo '<BR>' . _('There are') . ' ' . $myrow[0] . ' ' . _('suppliers referring to this tax group');
		} else {
	
			$sql="DELETE FROM taxgrouptaxes WHERE taxgroupid=" . $_GET['SelectedGroup'];
			$result = DB_query($sql,$db);
			$sql="DELETE FROM taxgroups WHERE taxgroupid=" . $_GET['SelectedGroup'];
			$result = DB_query($sql,$db);
			prnMsg( $_GET['GroupName'] . ' ' . _('tax group has been deleted') . '!','success');
		}
	} //end if taxgroup used in other tables
	unset($SelectedGroup);
	unset($_GET['GroupName']);
}

if (!isset($SelectedGroup)) {

/* If its the first time the page has been displayed with no parameters then none of the above are true and the list of tax groups will be displayed with links to delete or edit each. These will call the same page again and allow update/input or deletion of tax group taxes*/

	$sql = "SELECT taxgroupid,
			taxgroupdescription
		FROM taxgroups";
	$result = DB_query($sql,$db);

	if( DB_num_rows($result) == 0 ) {
		echo '<CENTER>';
		prnMsg(_('There are no tax groups configured.'),'info');
		echo '</CENTER>';
	} else {
		echo '<CENTER><table border=1>';
		echo "<TR><TD class='tableheader'>" . _('Group No') . "</TD>
			<TD class='tableheader'>" . _('Tax Group') . "</TD></TR>";
	
		$k=0; //row colour counter
		while ($myrow = DB_fetch_array($result)) {
			if ($k==1){
				echo "<tr bgcolor='#CCCCCC'>";
				$k=0;
			} else {
				echo "<tr bgcolor='#EEEEEE'>";
				$k=1;
			}
	
			printf("<td>%s</td>
				<td>%s</td>
				<td><a href=\"%s&SelectedGroup=%s\">" . _('Edit') . "</A></TD>
				<TD><A HREF=\"%s&SelectedGroup=%s&delete=1&GroupID=%s\">" . _('Delete') . "</A></TD>
				</tr>",
				$myrow['taxgroupid'],
				$myrow['taxgroupdescription'],
				$_SERVER['PHP_SELF']  . "?" . SID,
				$myrow['taxgroupid'],
				$_SERVER['PHP_SELF'] . "?" . SID,
				$myrow['taxgroupid'],
				urlencode($myrow['taxgroupdescription']));
	
		} //END WHILE LIST LOOP
		echo '</TABLE></CENTER>';
	}
} //end of ifs and buts!


if (isset($SelectedGroup)) {
	echo "<CENTER><A HREF='" . $_SERVER['PHP_SELF'] ."?" . SID . "'>" . _('Review Existing Groups') . '</A></CENTER>';
}

if (isset($SelectedGroup)) {
	//editing an existing role

	$sql = "SELECT taxgroupid,
			taxgroupdescription
		FROM taxgroups
		WHERE taxgroupid=" . $SelectedGroup;
	$result = DB_query($sql, $db);
	if ( DB_num_rows($result) == 0 ) {
		prnMsg( _('The selected tax group is no longer available.'),'warn');
	} else {
		$myrow = DB_fetch_array($result);
		$_POST['SelectedGroup'] = $myrow['taxgroupid'];
		$_POST['GroupName'] = $myrow['taxgroupdescription'];
	}
}
echo '<BR>';
echo "<FORM METHOD='post' action=" . $_SERVER['PHP_SELF'] . "?" . SID . ">";
if( isset($_POST['SelectedGroup'])) {
	echo "<INPUT TYPE=HIDDEN NAME='SelectedGroup' VALUE='" . $_POST['SelectedGroup'] . "'>";
}
echo '<CENTER><TABLE>';
echo '<TR><TD>' . _('Tax Group') . ":</TD>
	<TD><INPUT TYPE='text' name='GroupName' SIZE=40 MAXLENGTH=40 VALUE='" . $_POST['GroupName'] . "'></TD></TR>";
echo "</TABLE>
	<CENTER><input type='Submit' name='submit' value='" . _('Enter Group') . "'></CENTER></FORM>";

if (isset($SelectedGroup)) {
	$sql = 'SELECT taxid, 
			description as taxname 
			FROM taxauthorities
		ORDER BY taxid';
	
	$sqlUsed = "SELECT taxauthid, 
				calculationorder, 
				taxontax 
			FROM taxgrouptaxes 
			WHERE taxgroupid=". $SelectedGroup . ' 
			ORDER BY calculationorder';
	
	$Result = DB_query($sql, $db);
	
	/*Make an array of the used tax authorities in calculation order */
	$UsedResult = DB_query($sqlUsed, $db);
	$TaxAuthsUsed = array();
	$TaxAuthRow = array();
	$i=0;
	while ($myrow=DB_fetch_row($UsedResult)){
		$TaxAuthsUsed[$i] = $myrow[0];
		$TaxAuthRow[$i] = $myrow;
		$i++;
	}
	
	if (DB_num_rows($Result)>0 ) {
		echo '<BR>';
		echo '<CENTER><TABLE><TR>';
		echo "<TD class='tableheader' colspan=4 ALIGN=CENTER>"._('Assigned Taxes')."</TD>";
		echo '<TD></TD>';
		echo "<TD class='tableheader' colspan=2 ALIGN=CENTER>"._('Available Taxes')."</TD>";
		echo '</TR>';
		echo '<TR>';
		
		echo "<TD class='tableheader'>" . _('Tax Auth ID') . '</TD>';
		echo "<TD class='tableheader'>" . _('Tax Authority Name') . '</TD>';
		echo "<TD class='tableheader'>" . _('Calculation Order') . '</TD>';
		echo "<TD class='tableheader'>" . _('Tax on Prior Tax(es)') . '</TD>';
		echo '<TD></TD>';
		echo "<TD class='tableheader'>" . _('Tax Auth ID') . '</TD>';
		echo "<TD class='tableheader'>" . _('Tax Authority Name') . '</TD>';
		echo '</TR>';
		
	} else {
		echo '<BR><CENTER>' . _('There are no tax authorities defined to allocate to this tax group');
	}
	
	$k=0; //row colour counter
	while($AvailRow = DB_fetch_array($Result)) {
				
		if ($k==1){
			echo "<TR bgcolor='#CCCCCC'>";
			$k=0;
		} else {
			echo "<TR bgcolor='#EEEEEE'>";
			$k=1;
		}
		$TaxAuthUsedPointer = array_search($AvailRow['taxauthid'],$TaxAuthsUsed);
		
		if ($TaxAuthUsedPointer){
			
			if ($TaxAuthsUsed[$TaxAuthUsedPointer]['taxontax'] ==1){
				$TaxOnTax = _('Yes');
			} else {
				$TaxOnTax = _('No');
			}
			
			printf("<TD>%s</TD>
				<TD>%s</TD>
				<TD>%s</TD>
				<TD>%s</TD>
				<TD><A href=\"%s&SelectedGroup=%s&remove=1&TaxAuthority=%s\">" . _('Remove') . "</A></TD>
				<TD>&nbsp;</TD>
				<TD>&nbsp;</TD>",
				$AvailRow['taxid'],
				$AvailRow['taxname'],
				$TaxAuthsUsed[$TaxAuthUsedPointer]['calculationorder'],
				$TaxOnTax,
				$_SERVER['PHP_SELF']  . "?" . SID,
				$SelectedGroup,
				$AvailRow['taxid']
				);
			
		} else {
			printf("<TD>&nbsp;</TD>
				<TD>&nbsp;</TD>
				<TD>&nbsp;</TD>
				<TD>&nbsp;</TD>
				<TD>&nbsp;</TD>
				<TD>%s</TD>
				<TD>%s</TD>
				<TD><A href=\"%s&SelectedGroup=%s&add=1&TaxAuthority=%s\">" . _('Add') . "</A></TD>",
				$AvailRow['taxid'],
				$AvailRow['taxname'],
				$_SERVER['PHP_SELF']  . "?" . SID,
				$SelectedGroup,
				$AvailRow['taxid']
				);
		}	
		echo '</TR>';
	}
	echo '</TABLE></CENTER>';
	
	/* the order and tax on tax will only be an issue if more than one tax authority in the group */
	if (count($TaxAuthsUsed)>1) { 
		echo '<BR><CENTER>'._('Calculation Order').'</CENTER><BR>';
		echo '<FORM METHOD="post" action="' . $_SERVER['PHP_SELF'] . '?' . SID .'">';
		echo '<INPUT TYPE=HIDDEN NAME="SelectedGroup" VALUE="' . $SelectedGroup .'">';
		echo '<CENTER><TABLE>';
			
		echo '<TR><TD class="tableheader">'._('Tax Authority').'</TD>
			<TD class="tableheader">'._('Order').'</TD>
			<TD class="tableheader">'._('Tax on Prior Taxes').'</TD></TR>';
		$k=0; //row colour counter
		foreach ($TaxAuthsUsed as $TaxAuth ) {
			if ($k==1){
				echo "<TR BGCOLOR='#CCCCCC'>";
				$k=0;
			} else {
				echo "<TR BGCOLOR='#EEEEEE'>";
				$k=1;
			}
			echo '<TD>' . $TaxAuth['taxname'] . '</TD><TD>'.
				'<INPUT TYPE="Text" NAME="CalcOrder_' . $TaxAuth['taxauthid'] . '" VALUE="'.$TaxAuth['calculationorder'].'" size=2 maxlength=2></TD>';
			echo '<TD><SELECT NAME="TaxOnTax_' . $TaxAuth['taxauthid'] . '">';
			if ($TaxAuth['taxontax']==1){
				echo '<OPTION SELECTED VALUE=1>' . _('Yes');
				echo '<OPTION VALUE=0>' . _('No');
			} else {
				echo '<OPTION VALUE=1>' . _('Yes');
				echo '<OPTION SELECTED VALUE=0>' . _('No');
			}
			echo '</SELECT></TD></TR>';       
			
		}
		echo '</TABLE></CENTER>';
		echo '<CENTER><input type="Submit" name="updateorder" value="' . _('Update Order') . '"></CENTER>';
	}
	
	echo '</FORM>';
		
}

include('includes/footer.inc');

?>