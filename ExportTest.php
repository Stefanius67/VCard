<?php
declare(strict_types=1);

use SKien\VCard\VCard;
use SKien\VCard\VCardAddress;
use SKien\VCard\VCardContact;
require_once 'autoloader.php';
require_once 'blobPortrait.php';

	$oVCard = new VCard();
	$oVCard->setEncoding('Windows-1252');

	// just create new contact
	$oContact = new VCardContact();
	$oContact->setName('von Flake', 'Wiki');
	$oContact->setOrganisation('Company 4711');
	$oContact->addHomepage('www.firstpage.de');
	$oContact->addHomepage('www.secondpage.uk');

	$oContact->setDateOfBirth('1982-07-26');

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
	$oAddress->setType(VCard::WORK);
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
	$oContact->setPortraitBlob(getBlobPortrait());			// Wiki von Flake
	$oVCard->addContact($oContact);

	// change name and portrait ... all other properties remains valid!
	$oContact->setName('von Flake', 'Ilvy');
	$oContact->setPortraitFile('images/sample2.png');	// Ilvy	von Flake
	$oContact->setDateOfBirth(477270000);

	$oVCard->addContact($oContact);

	// change name again and add some additional info....
	$oContact->setName('von Flake', 'Halvar');
	$oContact->setPrefix('Mr.');
	$oContact->setSuffix('Häuptling');
	$oContact->setPortraitFile('images/sample3.bmp');	// Halvar von Flake
	$oContact->setDateOfBirth(new \DateTime('1964-04-19'));
	$oVCard->addContact($oContact);

	// and write to file
	$oVCard->write('test.vcf', isset($_GET['test']));