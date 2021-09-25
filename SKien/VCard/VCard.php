<?php
declare(strict_types=1);

namespace SKien\VCard;

/**
 * Base class to create or read vcard (.vcf) file.
 *
 * A vcard file may contain multiple contacts.
 * - Creation of vCard Files Version <b>3.0</b> (RFC 2426)
 * - Import of vCard Files Version <b>2.1</b> and <b>3.0</b>
 *
 * #### Create a VCard for writing:
 * Create an instance of `VCard`, add the desired `VCardContacts` to it and save
 * it as file with the `write()` method.
 *
 * #### Retrieve contacts from an existing VCard file:
 * Open the file with the `read()` method and retrieve the containing contacts with
 * the `getContact()` method.
 *
 * You can either iterate over all contacts from `0 ... getContactCount()` (instead
 * of getContactCount() the return value of `read()` can be used) or you can use
 * `getContactList()` to call up a list of names of all contacts contained so you be
 * able to access a specific contact.
 *
 * @package VCard
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class VCard
{
    use VCardHelper;

    /** preferrred entry     */
    public const PREF      = 'PREF';
    /** information for work     */
    public const WORK      = 'WORK';
    /** information for home     */
    public const HOME      = 'HOME';
    /** postal address   */
    public const POSTAL    = 'POSTAL';
    /** parcel address for delivery      */
    public const PARCEL    = 'PARCEL';
    /** international address for delivery      */
    public const INTER     = 'INTL';
    /** domestic address for delivery      */
    public const DOMESTIC  = 'DOM';

    /** communication number: standard phone    */
    public const VOICE     = 'VOICE';
    /** communication number: cellular phone    */
    public const CELL      = 'CELL';
    /** communication number: facsimile device  */
    public const FAX       = 'FAX';
    /** communication number: number has voice messaging support     */
    public const MSG       = 'MSG';
    /** communication number: video conferencing telephone number    */
    public const VIDEO     = 'VIDEO';
    /** communication number: paging device telephone number    */
    public const PAGER     = 'PAGER';
    /** communication number: bulletin board system telephone number    */
    public const BBS       = 'BBS';
    /** communication number: modem connected telephone number    */
    public const MODEM     = 'MODEM';
    /** communication number: car-phone telephone number    */
    public const CAR       = 'CAR';
    /** communication number: ISDN service telephone number    */
    public const ISDN      = 'ISDN';
    /** communication number: <b>p</b>ersonal <b>c</b>ommunication <b>s</b>ervices telephone number    */
    public const PCS       = 'PCS';


    /** @internal max. length of line in a vcard - file    */
    public const MAX_LINE_LENGTH = 75;

    /** @var string  encoding for values    */
    static protected string $strEncoding = 'UTF-8';
    /** @var VCardContact[] all contacts in the VCard file */
    protected array $aContacts = array();

    /**
     * Get the encoding currently set.
     * @return string
     */
    public static function getEncoding() : string
    {
        return VCard::$strEncoding;
    }

    /**
     * Set the encoding for the file.
     * For export:
     * - always use UTF-8 (default).
     *   only exception i found so far is MS-Outlook - it comes in trouble with german
     *   umlauts, so use 'Windows-1252' instead.
     *   please send note to s.kientzler@online.de if you found any further exceptions...
     *
     * For import:
     * -  feel free to use your preferred charset (may depends on configuration of your system)
     * @param string $strEncoding
     */
    public static function setEncoding(string $strEncoding) : void
    {
        VCard::$strEncoding = $strEncoding;
    }

    /**
     * Add contact to vcard file.
     * @param VCardContact $oContact
     */
    public function addContact(VCardContact $oContact) : void
    {
        $this->aContacts[] = clone $oContact;
    }

    /**
     * Write vcard to file.
     * @param string $strFilename
     * @param bool $bTest   output to browser for internal testing...
     */
    public function write(string $strFilename, bool $bTest = false) : void
    {
        $buffer = '';
        foreach ($this->aContacts as $oContact) {
            $oContactWriter = new VCardContactWriter($oContact);
            $buffer .= $oContactWriter;
        }
        // vcf-file generation doesn't make sense if some errormessage generated before...
        if (!$bTest && ob_get_contents() == '') {
            header('Content-Type: text/x-vCard; charset=utf-8; name=' . $strFilename);
            header('Content-Length: ' . strlen($buffer));
            header('Connection: close');
            header('Content-Disposition: attachment; filename=' . $strFilename);
        } else {
            // output for test or in case of errors
            $buffer = str_replace(PHP_EOL, '<br>', $buffer);
            echo '<!DOCTYPE html>' . PHP_EOL;
            echo '<head><title>vCard Exporttest Display</title>' . PHP_EOL;
            echo '</head>' . PHP_EOL;
            echo '<body>' . PHP_EOL;
            echo '<h1>Filename: ' . $strFilename . '</h1>';
            $buffer = '<pre>' . $buffer . '</pre></body>';
        }

        echo $buffer;
    }

    /**
     * Read vcard - file.
     * @param string $strFilename
     * @return int  count of contacts imported
     */
    public function read(string $strFilename) : int
    {
        $aLines = @file($strFilename);
        if ($aLines === false) {
            return 0;
        }
        $iLn = 0;
        $oContact = null;
        $oReader = null;
        while ($iLn < count($aLines)) {
            $strLine = rtrim($aLines[$iLn++], "\r\n");

            // QUOTED-PRINTABLE multiline values: (supported by vcard version 2.1 only)
            // if line ends with '=', go on next line (ignore ending '=' sign!)
            if (substr($strLine, -1) == '=') {
                while ($iLn < count($aLines) && substr($strLine, -1) == '=') {
                    $strLine = rtrim($strLine, '='); // remove ending '='
                    if (strlen(trim($aLines[$iLn])) == 0) {
                        break;
                    }
                    $strLine .= rtrim($aLines[$iLn++]);
                }
            }
            // for multiline values suceeding line starts with blank
            while ($iLn < count($aLines) && substr($aLines[$iLn], 0, 1) == ' ') {
                if (strlen(trim($aLines[$iLn])) == 0) {
                    break;
                }
                $strLine .= rtrim(substr($aLines[$iLn++], 1), "\r\n"); // ignore leading blank
            }

            if (strtoupper($strLine) == 'BEGIN:VCARD') {
                $oContact = new VCardContact();
                $oReader = new VCardContactReader($oContact);
            } elseif (strtoupper($strLine) == 'END:VCARD' && $oContact !== null) {
                $this->aContacts[] = $oContact;
                $oContact = null;
                $oReader = null;
            } elseif ($oReader) {
                // split property name/params from value
                $aSplit = explode(':', $strLine, 2);
                if (count($aSplit) == 2) {
                    $aNameParams = explode(';', $aSplit[0]);
                    $strName = $aNameParams[0];
                    $aParams = $this->parseParams($aNameParams);
                    $oReader->addProperty($strName, $aParams, $aSplit[1]);
                }
            }
        }
        return count($this->aContacts);
    }

    /**
     * Number of contacts the vcard containing.
     * @return int
     */
    public function getContactCount() : int
    {
        return count($this->aContacts);
    }

    /**
     * Get a named list of all contacts.
     * The complete VCardContact object can be called up with the method
     * `getContact ()` via the corresponding index.
     * @return array<string>
     */
    public function getContactList() : array
    {
        $aList = array();
        foreach ($this->aContacts as $oContact) {
            $aList[] = $oContact->getName();
        }
        return $aList;
    }

    /**
     * Get contact data.
     * @param int $i    index of the requested contact (`0 <= $i < getContactCount`)
     * @return VCardContact|null    null, if index is out of the contact count
     */
    public function getContact(int $i) : ?VCardContact
    {
        $oContact = null;
        if ($i >= 0 && $i < count($this->aContacts)) {
            $oContact = $this->aContacts[$i];
        }
        return $oContact;
    }
}
