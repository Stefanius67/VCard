<?php
namespace SKien\VCard;

/**
 * class representing address within a contact.
 * Each contact may contains multiple adresses.
 *
 * uses helpers from trait VCardHelper
 * @see VCardHelper
 *
* history:
 * date         version
 * 2020-02-23   initial version.
 * 2020-05-28   renamed namespace to fit PSR-4 recommendations for autoloading.
 * 2020-07-22   added missing PHP 7.4 type hints / docBlock changes 
 * 
 * @package SKien-VCard
 * @since 1.0.0
 * @version 1.0.3
 * @author Stefanius <s.kien@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class VCardAddress
{
    use VCardHelper;
    
    /** @var string  street (including house number)    */
    protected string $strStr = '';
    /** @var string  city   */
    protected string $strCity = '';
    /** @var string  postcode   */
    protected string $strPostcode = '';
    /** @var string  country */
    protected string $strCountry = '';
    /** @var string  region */
    protected string $strRegion = '';
    /** @var string  type (VCard::HOME, VCard::WORK, VCard::POSTAL, VCard::PARCEL)  */
    protected string $strType = '';
    
    /**
     * full address information.
     * build semicolon delimitered string containing:
     *  - post office address (not supported)
     *  - extended address (not supported)
     *  - street (including house number)
     *  - city
     *  - region
     *  - postal code
     *  - country
     * 
     * @param bool $bPreferred
     * @return string
     */
    public function buildFullAddress(bool $bPreferred) : string
    {
        $strField = 'ADR;TYPE=' . $this->strType;
        if ($bPreferred) {
            $strField .= ',PREF';
        }
        
        // values separated by semikolon
        $strValue  = ';';                                           // post office address (not supported)
        $strValue .= ';';                                           // extended address (not supported)
        $strValue .= $this->maskString($this->strStr) . ';';        // street (including house number)
        $strValue .= $this->maskString($this->strCity) . ';';       // city
        $strValue .= $this->maskString($this->strRegion) . ';';     // region
        $strValue .= $this->maskString($this->strPostcode) . ';';   // postal code
        $strValue .= $this->maskString($this->strCountry);          // country
        
        return $this->buildProperty($strField, $strValue, false);
    }

    /**
     * label for address
     * @param bool $bPreferred
     * @return string
     */
    public function buildLabel(bool $bPreferred): string
    {
        $strField = 'LABEL;TYPE=' . $this->strType;
        if ($bPreferred) {
            $strField .= ',PREF';
        }
        
        // values separated by semikolon
        $strValue  = $this->strStr . PHP_EOL;
        $strValue .= $this->strPostcode . ' ' . $this->strCity . PHP_EOL;
        if (strlen($this->strRegion) > 0 || strlen($this->strCountry) > 0 ) {
            $strSep = (strlen($this->strRegion) > 0 && strlen($this->strCountry) > 0 ) ? ' - ' : '';
            $strValue .= $this->strRegion . $strSep . $this->strCountry . PHP_EOL;
        }
        
        return $this->buildProperty($strField, $strValue);
    }
    
    /**
     * explode string into address components:
     *  - post office address (not supported)
     *  - extended address (not supported)
     *  - street (including house number)
     *  - city
     *  - region
     *  - postal code
     *  - country
     *  delimitered by semicolon (be aware of masked delimiters)
     *  
     * @param string $strValue
     * @param array  $aParams
     */
    public function parseFullAddress(string $strValue, array $aParams)
    {
        $aSplit = $this->explodeMaskedString(';', $strValue);
        if (isset($aSplit[2])) {
            $this->strStr = $this->unmaskString($aSplit[2]);        // street (including house number)
        }
        if (isset($aSplit[3])) {
            $this->strCity = $this->unmaskString($aSplit[3]);       // city
        }
        if (isset($aSplit[4])) {
            $this->strRegion = $this->unmaskString($aSplit[4]);     // region
        }
        if (isset($aSplit[5])) {
            $this->strPostcode = $this->unmaskString($aSplit[5]);   // postal code
        }
        if (isset($aSplit[6])) {
            $this->strCountry = $this->unmaskString($aSplit[6]);    // country
        }
        if (isset($aParams['TYPE'])) {
            $this->strType = $aParams['TYPE'];
        } else {
            $this->strType = VCard::HOME;
        }
    }

    /**
     * @param string $strStr
     */
    public function setStr(string $strStr) 
    {
        $this->strStr = $strStr;
    }
    
    /**
     * @param string $strCity
     */
    public function setCity(string $strCity) 
    {
        $this->strCity = $strCity;
    }
    
    /**
     * @param string $strPostcode
     */
    public function setPostcode(string $strPostcode) 
    {
        $this->strPostcode = $strPostcode;
    }
    
    /**
     * @param string $strCountry
     */
    public function setCountry(string $strCountry) 
    {
        $this->strCountry = $strCountry;
    }
    
    /**
     * @param string $strRegion
     */
    public function setRegion(string $strRegion) 
    {
        $this->strRegion = $strRegion;
    }
    
    /**
     * @param string $strType
     */
    public function setType(string $strType) 
    {
        $this->strType = $strType;
    }
    
    /**
     * @return string $strStr
     */
    public function getStr() : string
    {
        return $this->strStr;
    }

    /**
     * @return string  $strCity
     */
    public function getCity() : string
    {
        return $this->strCity;
    }

    /**
     * @return string  $strPostcode
     */
    public function getPostcode() : string
    {
        return $this->strPostcode;
    }

    /**
     * @return string  $strCountry
     */
    public function getCountry() : string
    {
        return $this->strCountry;
    }

    /**
     * @return string  $strRegion
     */
    public function getRegion() : string
    {
        return $this->strRegion;
    }

    /**
     * @return string  $strType
     */
    public function getType() : string
    {
        return $this->strType;
    }
}