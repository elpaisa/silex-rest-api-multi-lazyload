<?php
/**
 * GNU General Public License, version 3.0 (GPL-3.0)
 *
 * Class Purifier
 * This class executes the most common tasks for user input sanitization
 *
 *
 * Created by PhpStorm.
 * User: johndiaz
 * Date: 30/03/16
 * Time: 3:08 PM
 *
 * @created 01/01/2014
 * @author John L. Diaz, support@secaudit.co
 */
namespace App;

class Purifier
{

    /**
     * @var array
     */
    public $letters = array("A" => 1, "B" => 2, "C" => 3, "D" => 4, "E" => 5, "F" => 6, "=" => 9);
    /**
     * @var array
     */
    public $indexes = array("1" => "A", "2" => "B", "3" => "C", "4" => "D", "5" => "E", "6" => "F");

    /**
     * @var string
     */
    public static $PBKDF2_HASH_ALGORITHM = "sha256";
    /**
     * @var int
     */
    public static $PBKDF2_ITERATIONS = 1000;
    /**
     * @var int
     */
    public static $PBKDF2_SALT_BYTE_SIZE = 24;
    /**
     * @var int
     */
    public static $PBKDF2_HASH_BYTE_SIZE = 24;
    /**
     * @var int
     */
    public static $HASH_SECTIONS = 4;
    /**
     * @var int
     */
    public static $HASH_ALGORITHM_INDEX = 0;
    /**
     * @var int
     */
    public static $HASH_ITERATION_INDEX = 1;
    /**
     * @var int
     */
    public static $HASH_SALT_INDEX = 2;
    /**
     * @var int
     */
    public static $HASH_PBKDF2_INDEX = 3;

    /**
     * Filter data with given configuration
     *
     * @created 01/01/2014
     * @param type $obj - object with string and filters
     * @return boolean
     */
    public static function filterDataValue($obj)
    {
        if (is_object($obj)) {
            if (isset($obj->string) && $obj->string != '') {
                $stringValue = $obj->string;
                if (isset($obj->filters) && is_array($obj->filters)) {
                    foreach ($obj->filters as $filterFunction => $filterArgs) {
                        if (is_callable($filterFunction)) {
                            //put the string to be filtered at the top of the array
                            array_unshift($filterArgs, $stringValue);
                            $stringValue = call_user_func_array($filterFunction, $filterArgs);
                        }
                    }
                }

                return $stringValue;
            }

            return false;
        }

        return false;
    }

    /**
     * Create an object with the string and the config array
     *
     * @created 01/01/2014
     *
     * @param $string
     * @param array $filterArray
     * @return bool|stdClass
     */
    public static function prepareDataObject($string, $filterArray = array())
    {
        if (isset($string) && $string != '') {
            $objNew          = new stdClass();
            $objNew->string  = $string;
            $objNew->filters = array(
                'strip_tags'       => array(),
                'addslashes'       => array(),
                'htmlspecialchars' => array(ENT_QUOTES)
            );
            if (count($filterArray) > 0) {
                $objNew->filters = $filterArray;
            }

            return $objNew;
        }

        return false;
    }

    /**
     * Filters XSS for GET, POST and REQUEST
     *
     * @created 01/01/2014
     * @param $filterVarArray
     * @param array $skipArray
     * @return array|bool
     */
    public function filterXSS($filterVarArray, $skipArray = array())
    {
        if (is_array($filterVarArray) && count($filterVarArray) > 0) {
            foreach ($filterVarArray as $gKey => $gValue) {
                if (!in_array($gKey, $skipArray)) {
                    if ($gValue != '' && !is_array($gValue) && !is_object($gValue)) {
                        $objString             = self::prepareDataObject(
                            $gValue,
                            array('htmlspecialchars' => array(ENT_QUOTES))
                        );
                        $filterVarArray[$gKey] = self::filterDataValue($objString);
                    }
                }
            }

            return $filterVarArray;
        }

        return false;
    }

    /**
     * Determines the filter constant based on the given string
     *
     * @created 01/01/2014
     * @param string $type
     * @param $validate
     * @return array
     */
    private function getType($type, $validate)
    {
        $return = array('constant', 'flags' => null);
        switch ($type) {
            case'string':
                $return['constant'] = FILTER_SANITIZE_STRING;
                $return['flags']    = FILTER_FLAG_ENCODE_LOW | FILTER_FLAG_ENCODE_HIGH |
                    FILTER_FLAG_ENCODE_AMP;
                break;
            case'int':
                $return['constant'] = ($validate ? FILTER_VALIDATE_INT : FILTER_SANITIZE_NUMBER_INT);
                break;
            case'url':
                $return['constant'] = ($validate ? FILTER_VALIDATE_URL : FILTER_SANITIZE_URL);
                break;
            case'email':
                $return['constant'] = ($validate ? FILTER_VALIDATE_EMAIL : FILTER_SANITIZE_EMAIL);
                break;
            case'float':
                $return['constant'] = ($validate ? FILTER_VALIDATE_FLOAT : FILTER_SANITIZE_NUMBER_FLOAT);
                break;
            case'ip':
                $return['constant'] = FILTER_VALIDATE_IP;
                break;
            case'bool':
                $return['constant'] = FILTER_VALIDATE_BOOLEAN;
                break;
            default:
                $return['constant'] = FILTER_UNSAFE_RAW;
        }

        return $return;
    }

    /**
     * Sanitize the variable
     *
     * @param string $string String to be sanitized
     * @param string $type
     * @param bool $validate
     * @return bool|mixed|string
     */
    public function getVar($string, $type = null, $validate = false)
    {

        $objString = self::prepareDataObject($string, array('htmlspecialchars' => array(ENT_QUOTES)));
        $sanitized = self::filterDataValue($objString);

        if (isset($type)) {
            $sanitizationType = $this->getType($type, $validate);
            $sanitized        = filter_var($sanitized, $sanitizationType['constant'], $sanitizationType['flags']);
        }

        return $sanitized;
    }

    /**
     * Gets safely the given input var
     *
     * @param string $varName
     * @param string $type
     * @param bool $validate
     * @return null|string
     */
    public function getInputVar($varName, $type = 'string', $validate = false)
    {
        $postRequest = (isset($_POST) ? $_POST : array());
        $getRequest  = (isset($_GET) ? $_GET : array());
        $request     = (isset($_REQUEST) ? $_REQUEST : array());

        if (isset($request[$varName])) {
            return $this->getVar(trim($request[$varName]), $type, $validate);
        } else {
            if (isset($getRequest[$varName])) {
                return $this->getVar(trim($getRequest[$varName]), $type, $validate);
            } else {
                if (isset($postRequest[$varName])) {
                    return $this->getVar(trim($postRequest[$varName]), $type, $validate);
                } else {
                    return null;
                }
            }
        }
    }

    /**
     * Cleans some string to be used as filename
     *
     * @param string $str the string to be cleaned
     * @param bool $tansLit
     * @param array $replace
     * @param string $delimiter
     * @return string
     */
    public function cleanString($str, $tansLit = true, $replace = array(), $delimiter = '-')
    {
        setlocale(LC_ALL, 'en_US.UTF8');

        if (!empty($replace)) {
            $str = str_replace((array) $replace, ' ', $str);
        }

        if ($tansLit) {
            if (function_exists('transliterator_transliterate')) {
                $str = transliterator_transliterate('Accents-Any', $str);
            } else {
                $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
            }
        }

        //If the transliteration didn't work
        $str = strtolower(
            trim(
                preg_replace(
                    '/[^0-9a-z\.]+/i',
                    '-',
                    preg_replace(
                        '/&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);/i',
                        '$1',
                        htmlentities($str, ENT_QUOTES, 'UTF-8')
                    )
                ),
                ' '
            )
        );

        $str = preg_replace("/[^a-zA-Z0-9@\.\/_|+ -](\.)?/is", '', $str);
        $str = strtolower(trim($str, '-'));
        $str = preg_replace("/[\/_|+ -]+/", $delimiter, $str);
        $str = str_replace(array('"', "'", "/", "\\"), "", $str);
        $str = strip_tags($str);

        if (strpos($str, '.') === 0) {
            $str = time() . $str;
        }

        return trim($str);
    }

    /**
     * Generates a secure password
     *
     * @param $length
     * @return string
     */
    public static function genSecurePassword($length)
    {
        $sChars  = "abcdefghjmnpqrstuvwxyz234567890?$=+@&#ABCDEFGHJKLMNPQRSTUVWYXZ";
        $iLength = strlen($sChars) - 1;
        $key     = '';

        for ($i = 0; $i < $length; $i++) {
            $key .= $sChars[mt_rand(0, $iLength)];
        }

        return ($key);
    }

    /**
     * Generates a unique key hash
     *
     * @return string
     */
    public static function genKey()
    {
        $hash = self::genSecurePassword(16);
        $hash = sha1($hash . time()) . md5($hash . time());

        return ($hash);
    }

    /**
     * Encrypts a string into a secure password with random salts
     *
     * @param string $password
     * @return string
     */
    public function encryptPassword($password)
    {
        // Using the format: algorithm:iterations:salt:hash
        $salt = base64_encode(mcrypt_create_iv(self::$PBKDF2_SALT_BYTE_SIZE, MCRYPT_DEV_URANDOM));

        $password = self::$PBKDF2_HASH_ALGORITHM . ":" . self::$PBKDF2_ITERATIONS . ":" . $salt . ":" .
            base64_encode(
                $this->pbkdf2(
                    self::$PBKDF2_HASH_ALGORITHM,
                    $password,
                    $salt,
                    self::$PBKDF2_ITERATIONS,
                    self::$PBKDF2_HASH_BYTE_SIZE,
                    true
                )
            );

        return $password;
    }

    /**
     * Validates the password against the current salt encryption
     *
     * @param string $password
     * @param string $correctHash
     * @return bool
     */
    public function validatePassword($password, $correctHash)
    {
        $params = explode(":", $correctHash);

        if (count($params) < self::$HASH_SECTIONS) {
            return false;
        }
        $pbkdf2 = base64_decode($params[self::$HASH_PBKDF2_INDEX]);

        $valid = $this->slowEquals(
            $pbkdf2,
            $this->pbkdf2(
                $params[self::$HASH_ALGORITHM_INDEX],
                $password,
                $params[self::$HASH_SALT_INDEX],
                (int) $params[self::$HASH_ITERATION_INDEX],
                strlen($pbkdf2),
                true
            )
        );

        return $valid;
    }

    /**
     * Compares two strings $a and $b in length-constant time.
     *
     * @param string $a
     * @param string $b
     * @return bool
     */
    public function slowEquals($a, $b)
    {
        $diff = strlen($a) ^ strlen($b);
        for ($i = 0; $i < strlen($a) && $i < strlen($b); $i++) {
            $diff |= ord($a[$i]) ^ ord($b[$i]);
        }

        return $diff === 0;
    }

    /**
     * PBKDF2 key derivation function as defined by RSA's PKCS #5: https://www.ietf.org/rfc/rfc2898.txt
     * $algorithm - The hash algorithm to use. Recommended: SHA256
     * $password - The password.
     * $salt - A salt that is unique to the password.
     * $count - Iteration count. Higher is better, but slower. Recommended: At least 1000.
     * $keyLength - The length of the derived key in bytes.
     * $rawOutput - If true, the key is returned in raw binary format. Hex encoded otherwise.
     * Returns: A $keyLength-byte key derived from the password and salt.
     *
     * Test vectors can be found here: https://www.ietf.org/rfc/rfc6070.txt
     *
     * This implementation of PBKDF2 was originally created by https://defuse.ca
     * With improvements by http://www.variations-of-shadow.com
     *
     * @param string $algorithm sha256
     * @param string $password
     * @param string $salt
     * @param int $count
     * @param int $keyLength
     * @param bool $rawOutput
     * @return string
     */
    private function pbkdf2($algorithm, $password, $salt, $count, $keyLength, $rawOutput = false)
    {
        $algorithm = strtolower($algorithm);
        if (!in_array($algorithm, hash_algos(), true)) {
            trigger_error('PBKDF2 ERROR: Invalid hash algorithm.', E_USER_ERROR);
        }
        if ($count <= 0 || $keyLength <= 0) {
            trigger_error('PBKDF2 ERROR: Invalid parameters.', E_USER_ERROR);
        }

        if (function_exists("hash_pbkdf2")) {
            // The output length is in NIBBLES (4-bits) if $rawOutput is false!
            if (!$rawOutput) {
                $keyLength = $keyLength * 2;
            }

            return hash_pbkdf2($algorithm, $password, $salt, $count, $keyLength, $rawOutput);
        }

        $hash_length = strlen(hash($algorithm, "", true));
        $blockCount  = ceil($keyLength / $hash_length);

        $output = "";
        for ($i = 1; $i <= $blockCount; $i++) {
            // $i encoded as 4 bytes, big endian.
            $last = $salt . pack("N", $i);
            // first iteration
            $last = $xorSum = hash_hmac($algorithm, $last, $password, true);
            // perform the other $count - 1 iterations
            for ($j = 1; $j < $count; $j++) {
                $xorSum ^= ($last = hash_hmac($algorithm, $last, $password, true));
            }
            $output .= $xorSum;
        }

        if ($rawOutput) {
            return substr($output, 0, $keyLength);
        } else {
            return bin2hex(substr($output, 0, $keyLength));
        }
    }

}
