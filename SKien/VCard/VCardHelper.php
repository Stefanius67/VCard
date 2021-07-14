<?php
declare(strict_types=1);

namespace SKien\VCard;

/**
 * Helper trait containing some methods used by multiple classes in package
 *
 * history:
 * date         version
 * 2020-02-23   initial version.
 * 2020-05-15   added html_entity_decode before mask string for export.
 * 2020-05-28   renamed namespace to fit PSR-4 recommendations for autoloading.
 * 2020-07-22   added missing PHP 7.4 type hints / docBlock changes
 *
 * @package SKien-VCard
 * @since 1.0.0
 * @version 1.0.3
 * @author Stefanius <s.kien@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
trait VCardHelper
{
    /**
     * Build property to insert in vcard.
     * If line exceeds max length, data will be split into multiple lines
     * @param string    $strName
     * @param string    $strValue
     * @param bool      $bMask      have value to be masked (default: true)
     * @return string
     */
    public function buildProperty(string $strName, string $strValue, bool $bMask=true) : string
    {
        $buffer = '';
        if (!empty($strValue)) {
            if ($bMask) {
                $strValue = $this->maskString($strValue);
            }
            $strLine = $strName . ':' . $strValue;
            if (strlen($strLine) > VCard::MAX_LINE_LENGTH) {
                $buffer = substr($strLine, 0, VCard::MAX_LINE_LENGTH) . PHP_EOL;
                $strLine = substr($strLine, VCard::MAX_LINE_LENGTH);
                $iMax = VCard::MAX_LINE_LENGTH - 1;
                while (strlen($strLine) > $iMax) {
                    $buffer .= ' ' . substr($strLine, 0, $iMax) . PHP_EOL;
                    $strLine = substr($strLine, $iMax);
                }
                $buffer .= ' ' . $strLine . PHP_EOL;
            } else {
                $buffer = $strLine . PHP_EOL;
            }
        }
        return $buffer;
    }

    /**
     * Mask delimiter and newline if inside of value.
     * @param string $strValue
     * @return string
     */
    public function maskString(string $strValue) : string
    {
        // decode entities before ';' is replaced !!
        $strValue = html_entity_decode($strValue, ENT_HTML5);
        $strValue = str_replace("\r\n", "\n", $strValue);
        $strValue = str_replace("\r", "\n", $strValue);
        $strValue = str_replace("\n", "\\n", $strValue);
        $strValue = str_replace(",", "\\,", $strValue);
        $strValue = str_replace(";", "\\;", $strValue);

        $strFrom = mb_detect_encoding($strValue);
        if ($strFrom !== false && $strFrom != VCard::getEncoding()) {
            $strValue = iconv($strFrom, VCard::getEncoding(), $strValue);
        }

        return $strValue;
    }

    /**
     * Unmask delimiter and newline.
     * @param string $strValue
     * @return string
     */
    public function unmaskString(string $strValue) : string
    {
        $strValue = str_replace("\\n", "\n", $strValue);
        $strValue = str_replace("\\,", ",", $strValue);
        $strValue = str_replace("\\;", ";", $strValue);

        $strFrom = mb_detect_encoding($strValue);
        if ($strFrom !== false && $strFrom != VCard::getEncoding()) {
            $strValue = iconv($strFrom, VCard::getEncoding() . "//IGNORE", $strValue);
        }

        return $strValue;
    }

    /**
     * Explode a masked string.
     * to ignore masked delimiters belonging to value
     * @param string $strDelim
     * @param string $strValue
     * @return array
     */
    function explodeMaskedString(string $strDelim, string $strValue) : array
    {
        // save masked delimiters, tag unmasked, resore saved and explode on new taged delimiter
        $strSave = "\\" . $strDelim;
        $strValue = str_replace($strSave, "\x00", $strValue);
        $strValue = str_replace($strDelim, "\x01", $strValue);
        $strValue = str_replace("\x00", $strSave, $strValue);

        $a = explode("\x01", $strValue);
        return $a == false ? [] : $a;
    }

    /**
     * Parse image date (base64) to extract type and raw data.
     * @param string $blobImage
     * @param string $strType
     * @param string $strImage
     */
    public function parseImageData(string $blobImage, string &$strType, string &$strImage) : void
    {
        // extract image type from binary data (e.g. data:image/jpg;base64,)
        $i = strpos($blobImage, ',');
        if ($i > 0) {
            $strType = substr($blobImage, 0, $i);
            $iFrom = strpos($strType, '/');
            $iTo = strpos($strType, ';');
            $strType = strtoupper(substr($strType, $iFrom + 1, $iTo - $iFrom - 1));
            $strImage = substr($blobImage, $i + 1);
        }
    }

    /**
     * Parse param string
     * @param array $aParamsIn
     * @return array
     */
    protected function parseParams(array $aParamsIn) : array
    {
        $aParams = array();
        for ($i = 1; $i < count($aParamsIn); $i++) {
            $aSplit = explode('=',$aParamsIn[$i]);
            if (count($aSplit)< 2) {
                // version 2.1 allows paramvalues without paramname
                $strName = $this->paramName($aSplit[0]);
                $strValue = strtoupper($aSplit[0]);
            } else {
                $strName = strtoupper($aSplit[0]);
                $strValue = strtoupper($aSplit[1]);
            }
            if (isset($aParams[$strName])) {
                $aParams[$strName] .= ',' . $strValue;
            } else {
                $aParams[$strName] = $strValue;
            }
        }
        return $aParams;
    }

    /**
     * Find paramname to paramvalue.
     * vcard version 2.1 allows params without name of the param
     * e.g.    TEL;HOME: short for TEL;TYPE=HOME:
     * @param string $strValue
     * @return string
     */
    protected function paramName(string $strValue) : string
    {
        static $aNames = array (
            'INLINE'            => 'VALUE',
            'URI'               => 'VALUE',
            'URL'               => 'VALUE',
            'CID'               => 'VALUE',
            '7BIT'              => 'ENCODING',
            'QUOTED-PRINTABLE'  => 'ENCODING',
            'BASE64'            => 'ENCODING',
            'DOM'               => 'TYPE',
            'INTL'              => 'TYPE',
            'POSTAL'            => 'TYPE',
            'PARCEL'            => 'TYPE',
            'HOME'              => 'TYPE',
            'WORK'              => 'TYPE',
            'PREF'              => 'TYPE',
            'VOICE'             => 'TYPE',
            'FAX'               => 'TYPE',
            'MSG'               => 'TYPE',
            'CELL'              => 'TYPE',
            'PAGER'             => 'TYPE',
            'BBS'               => 'TYPE',
            'MODEM'             => 'TYPE',
            'CAR'               => 'TYPE',
            'ISDN'              => 'TYPE',
            'VIDEO'             => 'TYPE',
            'ATTMAIL'           => 'TYPE',
            'CIS'               => 'TYPE',
            'EWORLD'            => 'TYPE',
            'INTERNET'          => 'TYPE',
            'PRODIGY'           => 'TYPE',
            'TLX'               => 'TYPE',
            'X400'              => 'TYPE',
            'GIF'               => 'TYPE',
            'CGM'               => 'TYPE',
            'WMF'               => 'TYPE',
            'BMP'               => 'TYPE',
            'MET'               => 'TYPE',
            'PMB'               => 'TYPE',
            'DIB'               => 'TYPE',
            'PICT'              => 'TYPE',
            'TIFF'              => 'TYPE',
            'PDF'               => 'TYPE',
            'PS'                => 'TYPE',
            'JPEG'              => 'TYPE',
            'QTIME'             => 'TYPE',
            'MPEG'              => 'TYPE',
            'MPEG2'             => 'TYPE',
            'AVI'               => 'TYPE',
            'WAVE'              => 'TYPE',
            'PCM'               => 'TYPE'
        );

        $strName = 'UNKNOWN';
        if (isset($aNames[$strValue])) {
            $strName = $aNames[$strValue];
        }
        return $strName;
    }

    /**
     * Create image ressource from encoded string.
     * Special processing for BMP cause there is no support in PHP before 7.2.
     * @param string    $strImage   base64 encoded image
     * @param string    $strType    image types supported by imagecreatefromstring
     * @return mixed    image resource
     */
    public function imageFromString(string $strImage, string $strType)
    {
        $strImage = base64_decode($strImage);
        if ($strType != 'BMP') {
            $img = imagecreatefromstring($strImage);
        } else {
            // imagecreatefromstring don't supports BMP
            // ...imagecreatefrombmp() is available from PHP version >= 7.2!
            //
            // thanks to Tomáš Grasl for following code from
            // https://gist.github.com/freema/df8e7bae83c0e2a50ea4
            $temp = unpack("H*", $strImage);
            $hex = $temp[1];
            $header = substr($hex, 0, 108);
            $width = 0;
            $height = 0;
            if (substr($header, 0, 4) == "424d") {
                $parts = str_split($header, 2);
                $width = hexdec($parts[19] . $parts[18]);
                $height = hexdec($parts[23] . $parts[22]);
                unset($parts);
            }
            $x = 0;
            $y = 1;
            $img = imagecreatetruecolor($width, $height);
            $body = substr($hex, 108);
            $body_size = (strlen($body) / 2);
            $header_size = ($width * $height);
            $usePadding = ($body_size > ($header_size * 3) + 4);
            for ($i = 0; $i < $body_size; $i+=3) {
                if ($x >= $width) {
                    if ($usePadding)
                        $i += $width % 4;
                    $x = 0;
                    $y++;
                    if ($y > $height)
                        break;
                }
                $i_pos = $i * 2;
                $r = hexdec($body[$i_pos + 4] . $body[$i_pos + 5]);
                $g = hexdec($body[$i_pos + 2] . $body[$i_pos + 3]);
                $b = hexdec($body[$i_pos] . $body[$i_pos + 1]);
                $color = imagecolorallocate($img, $r, $g, $b);
                imagesetpixel($img, $x, $height - $y, $color);
                $x++;
            }
        }
        return $img;
    }
}