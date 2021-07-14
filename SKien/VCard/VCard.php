<?php
declare(strict_types=1);

/**
 * package to create or read vCard (*.vcf) file
 *
 * creates vCard Version 3.0 (RFC 2426)
 * imports vCards Version 2.1 and 3.0
 */
namespace SKien\VCard;

/**
 * Base class to create or read vcard (*.vcf) file
 * file may contain multiple contacts
 *
 * history:
 * date         version
 * 2020-02-23   initial version.
 * 2020-05-28   renamed namespace to fit PSR-4 recommendations for autoloading.
 * 2020-07-22   added missing PHP 7.4 type hints / docBlock changes.
 *
 * @package SKien-VCard
 * @since 1.0.0
 * @version 1.0.3
 * @author Stefanius <s.kien@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class VCard
{
    use VCardHelper;

    /** preferrred entry     */
    const PREF      = 'PREF';
    /** information for work     */
    const WORK      = 'WORK';
    /** information for home     */
    const HOME      = 'HOME';
    /** cellular     */
    const CELL      = 'CELL';
    /** cellular     */
    const FAX       = 'FAX';
    /** postal address   */
    const POSTAL    = 'POSTAL';
    /** parcel address for delivery      */
    const PARCEL    = 'PARCEL';

    /** max. length of line in vcard - file    */
    const MAX_LINE_LENGTH = 75;

    /** no error    */
    const OK = 0;

    /** @var string  encoding for values    */
    static protected string $strEncoding = 'UTF-8';
    /** @var array    data write buffer */
    protected array $aContacts = array();

    /**
     * @return string
     */
    public static function getEncoding() : string
    {
        return VCard::$strEncoding;
    }

    /**
     * Set the encoding of the file.
     * For export:
     * - always use UTF-8 (default).
     *   only exception i found so far is MS-Outlook - it comes in trouble with german
     *   umlauts, so use 'Windwos-1252' instead.
     *   please send note to s.kien@online.de if you found any further exceptions...
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
            $buffer .= $oContact->buildData();
        }
        // vcf-file generation doesn't make sense if some errormessage generated before...
        if (!$bTest && ob_get_contents() == '') {
            header('Content-Type: text/x-vCard; name=' . $strFilename);
            header('Content-Length: ' . strlen($buffer));
            header('Connection: close');
            header('Content-Disposition: attachment; filename=' . $strFilename);
        } else {
            // output for test or in case of errors
            $buffer = str_replace(PHP_EOL, '<br>', $buffer);
            echo  'Filename: ' . $strFilename . '<br><br>';
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
            } elseif (strtoupper($strLine) == 'END:VCARD') {
                $this->aContacts[] = $oContact;
                $oContact = null;
            } elseif ($oContact) {
                // split property name/params from value
                $aSplit = explode(':', $strLine, 2);
                if (count($aSplit) == 2) {
                    $aNameParams = explode(';', $aSplit[0]);
                    $strName = $aNameParams[0];
                    $aParams = $this->parseParams($aNameParams);
                    $oContact->addProperty($strName, $aParams, $aSplit[1]);
                }
            }
        }
        return count($this->aContacts);
    }

    /**
     * Number of contacts the vcard containing.
     * @return int
     */
    public function getContactCount()  : int
    {
        return count($this->aContacts);
    }

    /**
     * Get requested contact.
     * @param int $i
     * @return VCardContact|null
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
