<?php
use lib\VCard\VCard;
use lib\VCard\VCardContact;
use lib\VCard\VCardAddress;
require_once 'lib/VCard/VCard.php';
require_once 'blobPortrait.php';

	$oVCard = new VCard();

	// just create new contact
	$oContact = new VCardContact();
	$oContact->setName('von Flake', 'Wiki');
	$oContact->setOrganisation('Company 4711');
	
	$oAddress = new VCardAddress();
	$oAddress->setStr('Bärenweg. 4');
	$oAddress->setPostcode('54321');
	$oAddress->setCity('Musterstadt');
	$oAddress->setType(VCard::HOME);
	$oContact->addAddress($oAddress, true);
	
	$oContact->addPhone('01234 5678', VCard::HOME, false);
	$oContact->addPhone('0123 89765456', VCard::CELL, true);

	$oAddress = new VCardAddress();
	$oAddress->setStr('Companystr. 8');
	$oAddress->setPostcode('65432');
	$oAddress->setCity('Musterstadt');
	$oAddress->setRegion('Baden-Würtemberg');
	$oAddress->setCountry('Deutschland');
	$oAddress->setType('WORK');
	$oContact->addAddress($oAddress, false);

	$oContact->addPhone('01234 98356', VCard::WORK, false);
	
	$oContact->addEMail('private@web.de', true);
	$oContact->addEMail('president@club.de', false);
	$oContact->addEMail('work@company.de', false);
	
	$oContact->addCategory('Krieger');
	$oContact->addCategory('Wikinger');
	
	$strNote  = "Hier steht ein mehrzeiliger Text," . PHP_EOL;
	$strNote .= "der auch Umlaute (ä,ö,ü) und Sonderzeichen" . PHP_EOL;
	$strNote .= "(@,~,§,$) enthä....";
	$oContact->setNote($strNote);

	// insert multiple contacts
	$oContact->setPortraitBlob($blobPortrait);			// Wiki von Flake
	$oVCard->addContact($oContact);
	
	// change name and portrait
	$oContact->setName('von Flake', 'Ilvy');
	$oContact->setPortraitFile('images/sample2.png');	// Ilvy	von Flake
	$oVCard->addContact($oContact);
	
	// change name again and add some additional info....
	$oContact->setName('von Flake', 'Halvar');
	$oContact->setPrefix('Mr.');
	$oContact->setStrSuffix('Häuptling');
	$oContact->setPortraitFile('images/sample3.bmp');	// Halvar von Flake
	$oVCard->addContact($oContact);

	// and write to file
	$oVCard->write('test.vcf', false);