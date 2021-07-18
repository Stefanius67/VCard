# PHP vCard Library: Import and export contacts in vCard format

 ![Latest Stable Version](https://img.shields.io/badge/release-v1.0.5-brightgreen.svg) 
 ![License](https://img.shields.io/packagist/l/gomoob/php-pushwoosh.svg) 
 [![Donate](https://img.shields.io/static/v1?label=donate&message=PayPal&color=orange)](https://www.paypal.me/SKientzler/5.00EUR)
 [![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.4-8892BF.svg)](https://php.net/)
 [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Stefanius67/VCard/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Stefanius67/VCard/?branch=master)
----------

This package can be used to import and export contact lists in vCard format.

It can create a new contact list from scratch and export it in vCard format - .vcf - compliant 
with the version 3.0 of the format specification (RFC 2426).

The package can also import an existing VCF file and makes it available to the PHP applications 
as an iterator that returns the details of each contact that was found in the vCard file. The 
package supports vCard format version 2.1 and 3.0.

## Installation   
You can download the  Latest [release version ](https://www.phpclasses.org/package/11545-PHP-Import-and-export-contact-lists-in-vCard-format.html) from PHPClasses.org


## Usage

**Import VCF**
```php
    // create object and read file
    $oVCard = new VCard();
    $iContactCount = $oVCard->read($strFilename);
    for ($i = 0; $i < $iContactCount; $i++) {
        // iterate to importetd contacts
        $oContact = $oVCard->getContact($i);
        $strName = $oContact->getName();
        // ... read more properties
        
        // iterating through all addresses
        $iCount = $oContact->getAddressCount();
        for ($j = 0; $j < $iCount; $j++) {
            $oAddress = $oContact->getAddress($j);
            $strStr = $oAddress->getStr();
            // ... read more properties
        }

        // phonenumbers
        $iCount = $oContact->getPhoneCount();
        for ($j = 0; $j < $iCount; $j++) {
            $aPhone = $oContact->getPhone($j);
            $strType = $aPhone['strType'];
            $strPhone = $aPhone['strPhone'];
        }

        $iCount = $oContact->getEMailCount();
        for ($j = 0; $j < $iCount; $j++) {
            $strMail = $oContact->getEMail($j);
        }
    }
```

> **Note:**  
> If data to be exported comes from a database/table with collation latinX_yyyyy, the character 
> encoding detection order from PHP may have to be set with 
> `mb_detect_order('UTF-8, Windwos-1252, ISO-8859-XX')` before using the class (replace X,y to fit your encoding).


**Export VCF**

```php
    // create object
    $oVCard = new VCard();

    // just create new contact
    $oContact = new VCardContact();
    $oContact->setName('von Flake', 'Wiki');
    $oContact->setOrganisation('Company 4711');

    // HOME address
    $oAddress = new VCardAddress();
    $oAddress->setType(VCard::HOME);
    $oAddress->setStr('Bärenweg. 4');
    // ... set more properties of oAddress
    $oContact->addAddress($oAddress, true);

    // WORK address
    $oAddress = new VCardAddress();
    $oAddress->setType('WORK');
    $oAddress->setStr('Companystr. 8');
    // ... set more properties of oAddress
    $oContact->addAddress($oAddress, false);

    // phones
    $oContact->addPhone('01234 5678', VCard::HOME, false);
    $oContact->addPhone('0123 89765456', VCard::CELL, true);
    $oContact->addPhone('01234 98356', VCard::WORK, false);
    
    // e-mails
    $oContact->addEMail('private@web.de', true);
    $oContact->addEMail('president@club.de', false);
    $oContact->addEMail('work@company.de', false);
    
    // insert contact
    $oVCard->addContact($oContact);
    
    // ... may continue with further contacts

    // and write to file
    $oVCard->write('test.vcf', false);    
```

