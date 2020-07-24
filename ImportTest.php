<?php
use SKien\VCard\VCard;
require_once 'autoloader.php';

	echo '<!DOCTYPE html>' . PHP_EOL;
	echo '<html lang="de">' . PHP_EOL;
	echo '<head><title>vCard Importtest</title>' . PHP_EOL;
	echo '<meta charset="ISO-8859-1">' . PHP_EOL;
	echo '</head>' . PHP_EOL;
	echo '<body>' . PHP_EOL;

	$strFilename = 'test.vcf';
	$strEncoding = 'Windows-1252';
	if (isset($_FILES['vcfFile']) && $_FILES['vcfFile']['tmp_name'] != '') {
		// to test different own files use ImportSelect.html...)
		$strFilename = $_FILES['vcfFile']['tmp_name'];
	}
	if (isset($_REQUEST['encoding'])) {
		$strEncoding = $_REQUEST['encoding'];
	}
	
	
	VCard::setEncoding($strEncoding);
	
	// create object and read file
	$oVCard = new VCard();
	$iCC = $oVCard->read($strFilename);
	for ($i = 0; $i < $iCC; $i++) {
		// iterate to importetd contacts
		$oContact = $oVCard->getContact($i);
		echo '<h1>' . $oContact->getName() . '</h1>' . PHP_EOL;
		$strPortraitBlob = $oContact->getPortraitBlob();
		if (strlen($strPortraitBlob) > 0 ) {
			echo '<img style="float: left; margin: 0px 20px;" src="' . $strPortraitBlob . '">' . PHP_EOL;
			// just save image as blob in db table ...
			// ... or may create file on server (it's on you to set appropriate path and filename)
			$oContact->savePortrait('myimage.jpg');
		}
		echo $oContact->getPrefix() . '<br/>' . PHP_EOL;
		echo $oContact->getLastName() . ', ' . $oContact->getFirstName() . '<br/>' . PHP_EOL;
		echo 'Nickname: ' . $oContact->getNickName() . '<br/>' . PHP_EOL;
		echo 'Birthday: ' . $oContact->getDateOfBirth() . '<br/><br/>' . PHP_EOL;
		echo 'Company: ' . $oContact->getOrganisation() . '<br/><br/><br/>' . PHP_EOL;

		// test iterating through all addresses
		$iAC = $oContact->getAddressCount();
		for ($j = 0; $j < $iAC; $j++) {
			$oAddress = $oContact->getAddress($j);
			echo '<div style="width: ' . (100.0/$iAC) . '%; float: left;">' . PHP_EOL;
			echo '	<b>Address: ' . $oAddress->getType() . '</b><br>' . PHP_EOL;
			echo '	' . $oAddress->getStr() . '<br>' . PHP_EOL;
			echo '	' . $oAddress->getPostcode() . ' ' . $oAddress->getCity() . '<br>' . PHP_EOL;
			echo '	' . $oAddress->getRegion() . ' ' . $oAddress->getCountry() . '<br>' . PHP_EOL;
			echo '</div>' . PHP_EOL;
		}
		
		// test for direct access via type
		echo '<div style="clear: both;"><br/>' . PHP_EOL;
		$oAddress = $oContact->getAddress(VCard::WORK);
		if ($oAddress) {
			echo '<div style="width: 50%; float: left;">' . PHP_EOL;
			echo '	<b>Address at Work:</b><br>' . PHP_EOL;
			echo '	' . $oAddress->getStr() . '<br>' . PHP_EOL;
			echo '	' . $oAddress->getPostcode() . ' ' . $oAddress->getCity() . '<br>' . PHP_EOL;
			echo '	' . $oAddress->getRegion() . ' ' . $oAddress->getCountry() . '<br>' . PHP_EOL;
			echo '</div>' . PHP_EOL;
		}		
		$oAddress = $oContact->getAddress(VCard::HOME);
		if ($oAddress) {
			echo '<div style="width: 50%; float: left;">' . PHP_EOL;
			echo '	<b>Address at Home:</b><br>' . PHP_EOL;
			echo '	' . $oAddress->getStr() . '<br>' . PHP_EOL;
			echo '	' . $oAddress->getPostcode() . ' ' . $oAddress->getCity() . '<br>' . PHP_EOL;
			echo '	' . $oAddress->getRegion() . ' ' . $oAddress->getCountry() . '<br>' . PHP_EOL;
			echo '</div>' . PHP_EOL;
		}

		// phonenumbers
		echo '<div style="clear: both;"><br/>' . PHP_EOL;
		echo '<b>Phonenumbers:</b><br/>' . PHP_EOL;
		$iPC = $oContact->getPhoneCount();
		for ($j = 0; $j < $iPC; $j++) {
			$aPhone = $oContact->getPhone($j);
			echo $aPhone['strType'] . ': ' . $aPhone['strPhone'] . '<br>' . PHP_EOL;
		}

		// mailaddresses
		echo '<br/>' . PHP_EOL;
		echo '<b>e-Mailaddresses:</b><br/>' . PHP_EOL;
		$iPC = $oContact->getEMailCount();
		for ($j = 0; $j < $iPC; $j++) {
			echo 'Mail' . ($j+1) . ': ' . $oContact->getEMail($j) . '<br>' . PHP_EOL;
		}
		echo '<br/>' . PHP_EOL;
		
		$strNote = $oContact->getNote();
		if (strlen($strNote) > 0 )
		{
			echo '<b>Annotation (may contain multiline value)</b><br/>' . PHP_EOL;
			echo '<textarea cols="80" rows="5">' . $strNote . '</textarea>' . PHP_EOL;
			echo '<br/>' . PHP_EOL;
		}
	}
	
	echo '</body>' . PHP_EOL;
	echo '</html>' . PHP_EOL;
