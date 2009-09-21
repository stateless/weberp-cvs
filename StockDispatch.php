<?php
/* $Revision: 1.2 $ */
// StockDispatch.php - Report of parts with overstock at one location that can be transferred
// to another location to cover shortage based on reorder level. Creates loctransfer records 
// that can be processed using Bulk Inventory Transfer - Receive.
$PageSecurity = 2;
include('includes/session.inc');
include('includes/SQL_CommonFunctions.inc');
If (isset($_POST['PrintPDF'])) {

	include('includes/PDFStarter.php');
    if (!is_numeric($_POST['Percent'])) {
        $_POST['Percent'] = 0;
    }
	$FontSize=9;
	$pdf->addinfo('Title',_('Stock Dispatch Report'));
	$pdf->addinfo('Subject',_('Parts to dispatch to another location to cover reorder level'));

	$PageNumber=1;
	$line_height=12;
	$Xpos = $Left_Margin+1;
	
	// Create Transfer Number
	if(!isset($Trf_ID) && $_POST['ReportType'] == 'Batch'){
		$Trf_ID = GetNextTransNo(16,$db);
	}
	
	// Creates WHERE clause for stock categories. StockCat is defined as an array so can choose
	// more than one category
	$wherecategory = " ";
	if ($_POST['StockCat'] != 'All') {
        $wherecategory = " AND stockmaster.categoryid ='" . $_POST['StockCat'] . "' ";	
	}

// The following is if define StockCat as array allowing multiple selects
// 	if ($_POST['StockCat'][0] != 'All') {
// 	    $i = 0;
// 	    $wherecategory = ' AND (';
// 	    foreach ($_POST['StockCat'] as $Cat) {
// 	        if ($i > 0) {
// 	            $wherecategory .= ' OR ';
// 	        }
// 	        $i++;
// 			$wherecategory .= " stockmaster.categoryid='" . $Cat . "' ";
//         }
//         $wherecategory .= ') ';
// 	}
	
	$sql = 'SELECT locstock.stockid,
				stockmaster.description,
				locstock.loccode,
				locstock.quantity,
				locstock.reorderlevel,
				stockmaster.decimalplaces,
				stockmaster.serialised,
				stockmaster.controlled,
				ROUND((locstock.reorderlevel - locstock.quantity) *
            	   (1 + (' . $_POST['Percent'] . '/100)))
            	as neededqty,
               (fromlocstock.quantity - fromlocstock.reorderlevel)  as available,
               fromlocstock.reorderlevel as fromreorderlevel,
               fromlocstock.quantity as fromquantity
			FROM stockmaster, 
				locstock
			LEFT JOIN locstock AS fromlocstock ON 
			  locstock.stockid = fromlocstock.stockid
			  AND fromlocstock.loccode = "' . $_POST['FromLocation'] . '"
			WHERE locstock.stockid=stockmaster.stockid 
			AND locstock.loccode ="' . $_POST['ToLocation'] . '" 
			AND locstock.reorderlevel > locstock.quantity
			AND (fromlocstock.quantity - fromlocstock.reorderlevel) > 0
			AND (stockmaster.mbflag="B" OR stockmaster.mbflag="M") ' .
			$wherecategory . ' ORDER BY locstock.loccode,locstock.stockid';
			
	$result = DB_query($sql,$db,'','',false,true);

	if (DB_error_no($db) !=0) {
	  $title = _('Stock Dispatch') . ' - ' . _('Problem Report');
	  include('includes/header.inc');
	   prnMsg( _('The Stock Dispatch report could not be retrieved by the SQL because') . ' '  . DB_error_msg($db),'error');
	   echo "<br><a href='" .$rootpath .'/index.php?' . SID . "'>" . _('Back to the menu') . '</a>';
	   if ($debug==1){
	      echo "<br>$sql";
	   }
	   include('includes/footer.inc');
	   exit;
	}

	PrintHeader($pdf,$YPos,$PageNumber,$Page_Height,$Top_Margin,$Left_Margin,
	            $Page_Width,$Right_Margin,$Trf_ID);

    $FontSize=8;

	While ($myrow = DB_fetch_array($result,$db)){
			$YPos -=(2 * $line_height);
			// Parameters for addTextWrap are defined in /includes/class.pdf.php
			// 1) X position 2) Y position 3) Width
			// 4) Height 5) Text 6) Alignment 7) Border 8) Fill - True to use SetFillColor
			// and False to set to transparent

				$pdf->addTextWrap(50,$YPos,100,$FontSize,$myrow['stockid'],'',0,$fill);
				$pdf->addTextWrap(150,$YPos,150,$FontSize,$myrow['description'],'',0,$fill);
				$pdf->addTextWrap(300,$YPos,40,$FontSize,number_format($myrow['fromquantity'],
				                                    $myrow['decimalplaces']),'right',0,$fill);
				$pdf->addTextWrap(340,$YPos,40,$FontSize,number_format($myrow['fromreorderlevel'],
				                                    $myrow['decimalplaces']),'right',0,$fill);
				$pdf->addTextWrap(380,$YPos,40,$FontSize,number_format($myrow['quantity'],
				                                    $myrow['decimalplaces']),'right',0,$fill);
				$pdf->addTextWrap(420,$YPos,40,$FontSize,number_format($myrow['reorderlevel'],
				                                    $myrow['decimalplaces']),'right',0,$fill);
				$shipqty = $myrow['available'];
				if ($myrow['neededqty'] < $myrow['available']) {
					    $shipqty = $myrow['neededqty'];
					}
                $pdf->addTextWrap(460,$YPos,40,$FontSize,number_format($shipqty,
				                                    $myrow['decimalplaces']),'right',0,$fill);
			    $pdf->addTextWrap(510,$YPos,40,$FontSize,'_________','right',0,$fill);	                                    
			if ($YPos < $Bottom_Margin + $line_height){
			   PrintHeader($pdf,$YPos,$PageNumber,$Page_Height,$Top_Margin,$Left_Margin,$Page_Width,
			               $Right_Margin,$Trf_ID);
			}

			// Create loctransfers records for each record
			$sql2 = "INSERT INTO loctransfers (reference,
								stockid,
								shipqty,
								shipdate,
								shiploc,
								recloc)
						VALUES ('" . $Trf_ID . "',
							'" . $myrow['stockid'] . "',
							'" . $shipqty . "',
							'" . Date('Y-m-d') . "',
							'" . $_POST['FromLocation']  ."',
							'" . $_POST['ToLocation'] . "')";
			$ErrMsg = _('CRITICAL ERROR') . '! ' . _('Unable to enter Location Transfer record for'). ' '.$_POST['StockID' . $i];
			if ($_POST['ReportType'] == 'Batch') {    
			    $resultLocShip = DB_query($sql2,$db, $ErrMsg);
			}

	} /*end while loop  */

	if ($YPos < $Bottom_Margin + $line_height){
	       PrintHeader($pdf,$YPos,$PageNumber,$Page_Height,$Top_Margin,$Left_Margin,$Page_Width,
	                   $Right_Margin,$Trf_ID);
	}
/*Print out the grand totals */

	$pdfcode = $pdf->output();
	$len = strlen($pdfcode);

	if ($len<=20){
			$title = _('Print Stock Dispatch Report');
			include('includes/header.inc');
			prnMsg(_('There were no items for the stock dispatch report'),'error');
			echo "<br><a href='$rootpath/index.php?" . SID . "'>" . _('Back to the menu') . '</a>';
			include('includes/footer.inc');
			exit;
	} else {
			header('Content-type: application/pdf');
			header("Content-Length: " . $len);
			header('Content-Disposition: inline; filename=StockDispatch.pdf');
			header('Expires: 0');
			header('Cache-Control: private, post-check=0, pre-check=0');
			header('Pragma: public');
	
			$pdf->Stream();
	}
	
} else { /*The option to print PDF was not hit so display form */

	$title=_('Stock Dispatch Report');
	include('includes/header.inc');
echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/inventory.png" title="' . _('Inventory') . '" alt="">' . ' ' . _('Inventory Stock Dispatch Report') . '';
echo '<div class="page_help_text">' . _('Create batch of overstock from one location to transfer to another location that is below reorder level.<br/>
                                         Quantity to ship is based on reorder level minus the quantity on hand at the To Location; if there is a<br/>
                                         dispatch percentage entered, that needed quantity is inflated by the percentage entered.<br/>
                                         Use Bulk Inventory Transfer - Receive to process the batch') . '</div><br>';

	echo '<br/><br/><form action=' . $_SERVER['PHP_SELF'] . " method='post'><table>";
	$sql = "SELECT loccode,
			locationname
		FROM locations";
	$resultStkLocs = DB_query($sql,$db);
	echo '<table><tr><td>' . _('Dispatch Percent') . ":</td><td><input type ='text' name='Percent' size='8'>";
	echo '<tr><td>' . _('From Stock Location') . ':</td><td><select name="FromLocation"> ';
	while ($myrow=DB_fetch_array($resultStkLocs)){
		if ($myrow['loccode'] == $_POST['FromLocation']){
			 echo '<option selected Value="' . $myrow['loccode'] . '">' . $myrow['locationname'];
		} else {
			 echo '<option Value="' . $myrow['loccode'] . '">' . $myrow['locationname'];
		}
	} 
	echo '</select></td></tr>';
	DB_data_seek($resultStkLocs,0);
	echo '<tr><td>' . _('To Stock Location') . ':</td><td><select name="ToLocation"> ';
	while ($myrow=DB_fetch_array($resultStkLocs)){
		if ($myrow['loccode'] == $_POST['ToLocation']){
			 echo '<option selected Value="' . $myrow['loccode'] . '">' . $myrow['locationname'];
		} else {
			 echo '<option Value="' . $myrow['loccode'] . '">' . $myrow['locationname'];
		}
	} 
	echo '</select></td></tr>';
	
	$SQL='SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription';
	$result1 = DB_query($SQL,$db);
	if (DB_num_rows($result1)==0){
		echo '</table></td></tr>
			</table>
			<p>';
		prnMsg(_('There are no stock categories currently defined please use the link below to set them up'),'warn');
		echo '<br><a href="' . $rootpath . '/StockCategories.php?' . SID .'">' . _('Define Stock Categories') . '</a>';
		include ('includes/footer.inc');
		exit;
	}
	
	// Define StockCat with 'name="StockCat[ ]" multiple' so can select more than one
	// Also have to change way define $wherecategory for WHERE clause
	echo '<tr><td>' . _('In Stock Category') . ':</td><td><select name="StockCat">';
	if (!isset($_POST['StockCat'])){
		$_POST['StockCat']='All';
	}
	if ($_POST['StockCat']=='All'){
		echo '<option selected value="All">' . _('All');
	} else {
		echo '<option value="All">' . _('All');
	}
	while ($myrow1 = DB_fetch_array($result1)) {
		if ($myrow1['categoryid']==$_POST['StockCat']){
			echo '<option selected value="' . $myrow1['categoryid'] . '">' . $myrow1['categorydescription'];
		} else {
			echo '<option value="' . $myrow1['categoryid'] . '">' . $myrow1['categorydescription'];
		}
	}
	echo '</select></td></tr>';
	
	echo '<tr></tr><tr></tr><tr><td>' . _('Report Type') . ':</td>';
    echo "<td><select name='ReportType'>";
	echo "<option selected value='Batch'>" . _('Create Batch');
	echo "<option value='Report'>" . _('Report Only');
	echo '</select></td><td>&nbsp</td></tr>';
	
	echo "</table><br/><br/><br/><div class='centre'><input type=submit name='PrintPDF' value='" . _('Print PDF') . "'></div>";

	include('includes/footer.inc');

} /*end of else not PrintPDF */

function PrintHeader(&$pdf,&$YPos,&$PageNumber,$Page_Height,$Top_Margin,$Left_Margin,
                     $Page_Width,$Right_Margin,$Trf_ID) {

	/*PDF page header for Stock Dispatch report */
	if ($PageNumber>1){
		$pdf->newPage();
	}
	$line_height=12;
	$FontSize=9;
	$YPos= $Page_Height-$Top_Margin;
	$YPos -=(3*$line_height);
	
	$pdf->addTextWrap($Left_Margin,$YPos,300,$FontSize,$_SESSION['CompanyRecord']['coyname']);
	$YPos -=$line_height;
	
	$pdf->addTextWrap($Left_Margin,$YPos,150,$FontSize,_('Stock Dispatch ') . $_POST['ReportType']);
	$pdf->addTextWrap(200,$YPos,50,$FontSize,_('From'));
	$pdf->addTextWrap(250,$YPos,50,$FontSize,$_POST['FromLocation']);
	$pdf->addTextWrap(300,$YPos,20,$FontSize,_('To'));
	$pdf->addTextWrap(320,$YPos,50,$FontSize,$_POST['ToLocation']);
	$pdf->addTextWrap($Page_Width-$Right_Margin-150,$YPos,160,$FontSize,_('Printed') . ': ' . 
		 Date($_SESSION['DefaultDateFormat']) . '   ' . _('Page') . ' ' . $PageNumber,'left');
	$YPos -= $line_height;
	$pdf->addTextWrap($Left_Margin,$YPos,50,$FontSize,_('Transfer No.'));
	$pdf->addTextWrap(95,$YPos,50,$FontSize,$Trf_ID);
	$YPos -= $line_height;
	$pdf->addTextWrap($Left_Margin,$YPos,50,$FontSize,_('Category'));
// The following is used if define StockCat as array that allows multiple selects
//     $catpos = 95; // Position to print category
//     foreach ($_POST['StockCat'] as $Cat) {
//         $pdf->addTextWrap($catpos,$YPos,50,$FontSize,$Cat);
//         $catpos += 50;
//     }
	$pdf->addTextWrap(95,$YPos,50,$FontSize,$_POST['StockCat']);
	$pdf->addTextWrap(160,$YPos,150,$FontSize,$catdescription,'left');
	$YPos -= $line_height;
	$pdf->addTextWrap($Left_Margin,$YPos,50,$FontSize,_('Percent'));
	$pdf->addTextWrap(95,$YPos,50,$FontSize,$_POST['Percent']);
	$YPos -=(2*$line_height);	
	/*set up the headings */
	$Xpos = $Left_Margin+1;
				
	$pdf->addTextWrap(50,$YPos,100,$FontSize,_('Part Number'), 'left');
	$pdf->addTextWrap(150,$YPos,150,$FontSize,_('Description'), 'left');
	$pdf->addTextWrap(320,$YPos,40,$FontSize,_('From'), 'right');
	$pdf->addTextWrap(390,$YPos,40,$FontSize,_('To'), 'right');
	$pdf->addTextWrap(460,$YPos,40,$FontSize,_('Shipped'), 'right');
	$pdf->addTextWrap(510,$YPos,40,$FontSize,_('Received'), 'right');
	$YPos -= $line_height;
    $pdf->addTextWrap(300,$YPos,40,$FontSize,_('QOH'), 'right');
    $pdf->addTextWrap(340,$YPos,40,$FontSize,_('Reord'), 'right');
    $pdf->addTextWrap(380,$YPos,40,$FontSize,_('QOH'), 'right');
    $pdf->addTextWrap(420,$YPos,40,$FontSize,_('Reord'), 'right');

	$FontSize=8;
	$PageNumber++;
} // End of PrintHeader() function
?>
