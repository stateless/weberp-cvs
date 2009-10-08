<?php
/* $Revision: 1.1 $ */

include('includes/DefineCartClass.php');
$PageSecurity = 1;
/* Session started in session.inc for password checking and authorisation level check
config.php is in turn included in session.inc*/

include('includes/session.inc');

$title = _('Top Items Searching');


include('includes/header.inc');
include('includes/GetPrice.inc');
include('includes/SQL_CommonFunctions.inc');


if (empty($_POST['identifier'])) {
	$identifier=date('U');
} else {
	$identifier=$_POST['identifier'];
}


if(!($_POST['loc']&&$_POST['nod'] && $_POST['cust'] && $_POST['top']&& $_POST['order']))
{
echo '<p class="page_title_text"><img src="'.$rootpath.'/css/'.$theme.'/images/magnifier.png" title="' . 
	_('Top Sales Order Search') . '" alt="">' . ' ' . _('Top Sales Order Search') . '</p>';
	
	?>
	<form action="TopItems.php" name="SelectCustomer" method=post>
	<b><?php echo '<p>' . $msg; ?></p>	
	<table cellpadding=3 colspan=4>
	<?php 
	
	//to view store location
	echo '<tr><td width=/"150/">'. _('Select Location').'  </td><td>:</td><td><select name=loc>';

		$sql='SELECT loccode,locationname 
		      FROM `locations`';
			  
		$CatResult= DB_query($sql,$db);
		echo"<option value='all'>All";
		While ($myrow = DB_fetch_array($CatResult)){
			echo "<option value='" . $myrow['loccode'] . "'>" . $myrow['loccode'] . " - " . $myrow['locationname'];
		}
		echo "</select></td></tr>";
	
	//to view list of customer	
	
	echo '<tr><td width=/"150/">' ._('Select Customer Type') .'</td><td>:</td><td><select name=cust>';

		$sqlt='SELECT typename,typeid 
			   FROM `debtortype`';
			   
		$CatResultt= DB_query($sqlt,$db);
		
		echo"<option value='all'>" ._('All');
		While ($myrowt = DB_fetch_array($CatResultt)){
			echo "<option value='" . $myrowt['typeid'] . "'>" . $myrowt['typename'] ;
		}
		echo "</select></td></tr>";
	
	//view order by list to display
	echo '<tr><td width=/"150/">'. _('Select Order By').'  </td><td>:</td><td><select name=order>';
		echo"<option value='TotalInvoiced'>". _('Total Pieces');
		echo"<option value='ValueSales'>" ._('Value of Sales');
		echo "</select></td></tr>";
	
	//View number of days
	echo'<tr><td>' ._('Number Of Days') .'</td><td>:</td>
	         <td><input class=number tabindex=/"3/" type=/"Text/" name=nod size=/"8/"	maxlength=/"8/" value=1></td>
		 </tr>';
	//view number of top items
	echo'<tr>
			 <td>'._('Number Of Top Items').' </td><td>:</td>
	         <td><input class=number tabindex=/"4/" type=/"Text/" name=top size=/"8/"	maxlength=/"8/" value=1></td>
		 </tr>
		 <tr>
			 <td></td>
			 <td></td>
			 <td><input tabindex=4 type=submit Value="'. _('Search'). '"></td>
	</form>';
}
else
{

// everything below here to view top items sale on selected store

			//check if input already
			$loc=$_POST['loc'];
			$order=$_POST['order'];
			$nod=$_POST['nod'];
			$cust=$_POST['cust'];
			
			$top=$_POST['top'];
		
		//the situation if the location and customer type selected "All"		
		if(($loc=="all")and($cust=="all"))
		{
			 $SQL="
				SELECT 	salesorderdetails.stkcode, 
						SUM(salesorderdetails.qtyinvoiced) TotalInvoiced,						
						SUM(salesorderdetails.qtyinvoiced * salesorderdetails.unitprice ) AS ValueSales,
						stockmaster.description,
						stockmaster.units
						
						
				FROM 	salesorderdetails, salesorders, debtorsmaster,stockmaster  
				WHERE 	salesorderdetails.orderno = salesorders.orderno
						AND salesorderdetails.stkcode = stockmaster.stockid
						AND salesorders.debtorno = debtorsmaster.debtorno
						AND salesorderdetails.ActualDispatchDate >= DATE_SUB(CURDATE(), INTERVAL $nod DAY)  
				GROUP BY salesorderdetails.stkcode  
				ORDER BY $order DESC
				LIMIT 0,$top";
		}
		else
		{	//the situation if only location type selected "All"
			if($loc=="all")
			{$SQL="
				SELECT 	salesorderdetails.stkcode, 
						SUM(salesorderdetails.qtyinvoiced) TotalInvoiced,						
						SUM(salesorderdetails.qtyinvoiced * salesorderdetails.unitprice ) AS ValueSales,
						stockmaster.description,
						stockmaster.units
						
						
				FROM 	salesorderdetails, salesorders, debtorsmaster,stockmaster  
				WHERE 	salesorderdetails.orderno = salesorders.orderno
						AND salesorderdetails.stkcode = stockmaster.stockid
						AND salesorders.debtorno = debtorsmaster.debtorno
						AND debtorsmaster.typeid = '$cust'
						AND salesorderdetails.ActualDispatchDate >= DATE_SUB(CURDATE(), INTERVAL $nod DAY)  
				GROUP BY salesorderdetails.stkcode  
				ORDER BY $order DESC
				LIMIT 0,$top";
			}
			else 
			{ 
			//the situation if the customer type selected "All"
			if($cust=="all")
				$SQL="
				SELECT 	salesorderdetails.stkcode, 
						SUM(salesorderdetails.qtyinvoiced) TotalInvoiced,						
						SUM(salesorderdetails.qtyinvoiced * salesorderdetails.unitprice ) AS ValueSales,
						stockmaster.description,
						stockmaster.units
						
						
				FROM 	salesorderdetails, salesorders, debtorsmaster,stockmaster  
				WHERE 	salesorderdetails.orderno = salesorders.orderno
						AND salesorderdetails.stkcode = stockmaster.stockid
						AND salesorders.debtorno = debtorsmaster.debtorno
						AND salesorders.fromstkloc = '$loc'
						AND salesorderdetails.ActualDispatchDate >= DATE_SUB(CURDATE(), INTERVAL $nod DAY)  
				GROUP BY salesorderdetails.stkcode  
				ORDER BY $order DESC
				LIMIT 0,$top";
			
			
				else
			{ 
				//the situation if the location and customer type not selected "All"
				$SQL="
				SELECT 	salesorderdetails.stkcode, 
						SUM(salesorderdetails.qtyinvoiced) TotalInvoiced,						
						SUM(salesorderdetails.qtyinvoiced * salesorderdetails.unitprice ) AS ValueSales,
						stockmaster.description,
						stockmaster.units
						
						
				FROM 	salesorderdetails, salesorders, debtorsmaster,stockmaster  
				WHERE 	salesorderdetails.orderno = salesorders.orderno
						AND salesorderdetails.stkcode = stockmaster.stockid
						AND salesorders.debtorno = debtorsmaster.debtorno
						AND salesorders.fromstkloc = '$loc'
						AND debtorsmaster.typeid = '$cust'
						AND salesorderdetails.ActualDispatchDate >= DATE_SUB(CURDATE(), INTERVAL $nod DAY)  
				GROUP BY salesorderdetails.stkcode  
				ORDER BY $order DESC
				LIMIT 0,$top";
			
			}
			}
		}
            $result2 = DB_query($SQL,$db);
            echo '<p class="page_title_text" align="center"><strong>TOP SALE ITEMS LIST </strong></p>';			
			echo '<table class="table1">';
			$TableHeader = '<tr><th><strong>' . _('Code') . '</strong></th>
								<th><strong>' . _('Description') . '</strong></th>
								<th><strong>' . _('Total Invoiced') . '</strong></th>								
								<th><strong>' . _('Units') . '</strong></th>
								<th><strong>' . _('Value Sales') . '</strong></th>
								<th><strong>' . _('On Hand') . '</strong></th>';
			echo $TableHeader;
			$j = 1;
			$k=0; //row colour counter

			while ($myrow=DB_fetch_array($result2)) 
		{
				
				//find the quantity onhand item
				$sqloh="SELECT   sum(quantity)as qty 
						FROM     `locstock` 
						WHERE     stockid='" . $myrow['0'] . "'";
				$oh = db_query($sqloh,$db,$ErrMsg);
				$ohRow = db_fetch_row($oh);
				$onhand=$ohRow[0];
				
				
				if ($k==1){
						echo '<tr class="EvenTableRows">';
						$k=0;
				} else {
						echo '<tr class="OddTableRows">';
						$k=1;
				}
				//$OnOrder = $PurchQty + $WoQty;

				//$Available = $myrow['5'] - $myrow['6'] + $OnOrder;
		
				
			$val=number_format($myrow['2'],2);
					
				printf('<td>%s</font></td>
						<td style="text-align:left">%s</td>
						<td style="text-align:right">%s</td>
						<td style="text-align:left">%s</td>
						<td style="text-align:right">%s</td>
						<td style="text-align:right">%s</td>
						</tr>',
						$myrow['0'],
						$myrow['3'],
						$myrow['1'],//total invoice here
						$myrow['4'],//unit
						$val,//value sales here
						$onhand//on hand
						
						);
				if ($j==1) {
					$jsCall = '<script  type="text/javascript">defaultControl(document.SelectParts.itm'.$myrow['stockid'].');</script>';
				}
				$j++;
#end of page full new headings if
		}
//end of the else statement
}	


include('includes/footer.inc');
?>