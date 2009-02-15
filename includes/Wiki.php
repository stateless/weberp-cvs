<?php

/********************************************/
/** STANDARD MESSAGE HANDLING & FORMATTING **/
/********************************************/

function wikiLink($type, $id) {

	if ($_SESSION['WikiApp']==_('WackoWiki')){
		echo '<A TARGET="_BLANK" HREF="../' . $_SESSION['WikiPath'] . '/index.php?wakka=' . $type .  $id . '">' . _('Wiki ' . $type . ' Knowlege Base') . '</A><BR>';
	}
	else
	if ($_SESSION['WikiApp']==_('MediaWiki')){
		echo '<A TARGET="_BLANK" HREF="../' . $_SESSION['WikiPath'] . '/index.php/' . $type . '/' .  $id . '">' . _('Wiki ' . $type . ' Knowlege Base') . '</A><BR>';
	}
	else
	if ($_SESSION['WikiApp']==_('Deki')){
		echo '<A TARGET="_BLANK" HREF="' . $_SESSION['WikiAddress'] . $_SESSION['WikiPath'] . '/' . $type . '/' .  $id . '">' . _('Wiki ' . $type . ' Knowlege Base') . '</A><BR>';
	}

}//wikiLink

?>
