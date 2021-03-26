<?php
/**
 * Kodekit - http://timble.net/kodekit
 *
 * @copyright   Copyright (C) 2007 - 2016 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license     MPL v2.0 <https://www.mozilla.org/en-US/MPL/2.0>
 * @link        https://github.com/timble/kodekit for the canonical source repository
 */

/**
 * APC 3.1.4 compatibility
 */
if(extension_loaded('apc') && !function_exists('apc_exists'))
{
    /**
     * Check if an APC key exists
     *
     * @param  mixed  $keys A string, or an array of strings, that contain keys.
     * @return boolean Returns TRUE if the key exists, otherwise FALSE
     */
    function apc_exists($keys)
    {
        $result = null;

        apc_fetch($keys,$result);

        return $result;
    }
}

/**
 * PHP 5.5 compatibility
 */
if (!function_exists('json_last_error_msg'))
{
    function json_last_error_msg()
    {
        switch (json_last_error())
        {
            case JSON_ERROR_DEPTH:
                $error = 'Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $error = 'Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $error = 'Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $error = 'Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                $error = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $error = 'Unknown Error';
        }

        return $error;
    }
}

/**
 * PHP5.4 compatibility
 *
 * @link http://nikic.github.io/2012/01/28/htmlspecialchars-improvements-in-PHP-5-4.html
 */
if (!defined('ENT_SUBSTITUTE')) {
    define('ENT_SUBSTITUTE', ENT_IGNORE); //PHP 5.3 behavior
}

/**
 * mbstring compatibility
 *
 * @link http://php.net/manual/en/book.mbstring.php
 */

if (!function_exists('mb_strlen'))
{
    function mb_strlen($str)
    {
        return strlen(utf8_decode($str));
    }
}

if (!function_exists('mb_substr'))
{
    function mb_substr($str, $offset, $length = NULL)
    {
        // generates E_NOTICE
        // for PHP4 objects, but not PHP5 objects
        $str = (string)$str;
        $offset = (int)$offset;
        if (!is_null($length)) $length = (int)$length;

        // handle trivial cases
        if ($length === 0) return '';
        if ($offset < 0 && $length < 0 && $length < $offset)
            return '';

        // normalise negative offsets (we could use a tail
        // anchored pattern, but they are horribly slow!)
        if ($offset < 0) {

            // see notes
            $strlen = strlen(utf8_decode($str));
            $offset = $strlen + $offset;
            if ($offset < 0) $offset = 0;

        }

        $Op = '';
        $Lp = '';

        // establish a pattern for offset, a
        // non-captured group equal in length to offset
        if ($offset > 0) {

            $Ox = (int)($offset/65535);
            $Oy = $offset%65535;

            if ($Ox) {
                $Op = '(?:.{65535}){'.$Ox.'}';
            }

            $Op = '^(?:'.$Op.'.{'.$Oy.'})';

        } else {

            // offset == 0; just anchor the pattern
            $Op = '^';

        }

        // establish a pattern for length
        if (is_null($length)) {

            // the rest of the string
            $Lp = '(.*)$';

        } else {

            if (!isset($strlen)) {
                // see notes
                $strlen = strlen(utf8_decode($str));
            }

            // another trivial case
            if ($offset > $strlen) return '';

            if ($length > 0) {

                // reduce any length that would
                // go passed the end of the string
                $length = min($strlen-$offset, $length);

                $Lx = (int)( $length / 65535 );
                $Ly = $length % 65535;

                // negative length requires a captured group
                // of length characters
                if ($Lx) $Lp = '(?:.{65535}){'.$Lx.'}';
                $Lp = '('.$Lp.'.{'.$Ly.'})';

            } else if ($length < 0) {

                if ( $length < ($offset - $strlen) ) {
                    return '';
                }

                $Lx = (int)((-$length)/65535);
                $Ly = (-$length)%65535;

                // negative length requires ... capture everything
                // except a group of  -length characters
                // anchored at the tail-end of the string
                if ($Lx) $Lp = '(?:.{65535}){'.$Lx.'}';
                $Lp = '(.*)(?:'.$Lp.'.{'.$Ly.'})$';

            }

        }

        if (!preg_match( '#'.$Op.$Lp.'#us',$str, $match )) {
            return '';
        }

        return $match[1];

    }
}

/**
 * A Compatibility library with PHP 5.5's simplified password hashing API.
 *
 * @author Anthony Ferrara <ircmaxell@php.net>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @copyright 2012 The Authors
 */

if (version_compare(PHP_VERSION, '5.3.7', '<'))
{
    trigger_error("The Password Compatibility Library requires PHP >= 5.3.7", E_USER_WARNING);
    // Prevent defining the functions
    return;
}

if (!defined('PASSWORD_BCRYPT'))
{
    define('PASSWORD_BCRYPT', 1);
    define('PASSWORD_DEFAULT', PASSWORD_BCRYPT);

    /**
     * Hash the password using the specified algorithm
     *
     * @param string $password The password to hash
     * @param int    $algo     The algorithm to use (Defined by PASSWORD_* constants)
     * @param array  $options  The options for the algorithm to use
     *
     * @returns string|false The hashed password, or false on error.
     */
    function password_hash($password, $algo, array $options = array())
    {
        if (!function_exists('crypt')) {
            trigger_error("Crypt must be loaded for password_hash to function", E_USER_WARNING);
            return null;
        }

        if (!is_string($password)) {
            trigger_error("password_hash(): Password must be a string", E_USER_WARNING);
            return null;
        }

        if (!is_int($algo)) {
            trigger_error("password_hash() expects parameter 2 to be long, " . gettype($algo) . " given", E_USER_WARNING);
            return null;
        }

        switch ($algo)
        {
            case PASSWORD_BCRYPT:
                // Note that this is a C constant, but not exposed to PHP, so we don't define it here.
                $cost = 10;
                if (isset($options['cost']))
                {
                    $cost = $options['cost'];
                    if ($cost < 4 || $cost > 31) {
                        trigger_error(sprintf("password_hash(): Invalid bcrypt cost parameter specified: %d", $cost), E_USER_WARNING);
                        return null;
                    }
                }
                $required_salt_len = 22;
                $hash_format       = sprintf("$2y$%02d$", $cost);
                break;
            default:
                trigger_error(sprintf("password_hash(): Unknown password hashing algorithm: %s", $algo), E_USER_WARNING);
                return null;
        }

        if (isset($options['salt']))
        {
            switch (gettype($options['salt']))
            {
                case 'NULL':
                case 'boolean':
                case 'integer':
                case 'double':
                case 'string':
                    $salt = (string) $options['salt'];
                    break;
                case 'object':
                    if (method_exists($options['salt'], '__tostring')) {
                        $salt = (string) $options['salt'];
                        break;
                    }
                case 'array':
                case 'resource':
                default:
                    trigger_error('password_hash(): Non-string salt parameter supplied', E_USER_WARNING);
                    return null;
            }

            if (strlen($salt) < $required_salt_len)
            {
                trigger_error(sprintf("password_hash(): Provided salt is too short: %d expecting %d", strlen($salt), $required_salt_len), E_USER_WARNING);
                return null;
            }
            elseif (0 == preg_match('#^[a-zA-Z0-9./]+$#D', $salt)) {
                $salt = str_replace('+', '.', base64_encode($salt));
            }

        }
        else
        {
            $buffer       = '';
            $raw_length   = (int) ($required_salt_len * 3 / 4 + 1);
            $buffer_valid = false;

            if (function_exists('mcrypt_create_iv'))
            {
                $buffer = mcrypt_create_iv($raw_length, MCRYPT_DEV_URANDOM);
                if ($buffer) {
                    $buffer_valid = true;
                }
            }

            if (!$buffer_valid && function_exists('openssl_random_pseudo_bytes'))
            {
                $buffer = openssl_random_pseudo_bytes($raw_length);
                if ($buffer) {
                    $buffer_valid = true;
                }
            }

            if (!$buffer_valid && file_exists('/dev/urandom'))
            {
                $f = @fopen('/dev/urandom', 'r');
                if ($f)
                {
                    $read = strlen($buffer);
                    while ($read < $raw_length)
                    {
                        $buffer .= fread($f, $raw_length - $read);
                        $read = strlen($buffer);
                    }
                    fclose($f);
                    if ($read >= $raw_length) {
                        $buffer_valid = true;
                    }
                }
            }

            if (!$buffer_valid || strlen($buffer) < $raw_length)
            {
                $bl = strlen($buffer);
                for ($i = 0; $i < $raw_length; $i++)
                {
                    if ($i < $bl) {
                        $buffer[$i] = $buffer[$i] ^ chr(mt_rand(0, 255));
                    } else {
                        $buffer .= chr(mt_rand(0, 255));
                    }
                }
            }

            $salt = str_replace('+', '.', base64_encode($buffer));
        }

        $salt = substr($salt, 0, $required_salt_len);
        $hash = $hash_format . $salt;
        $ret = crypt($password, $hash);

        if (!is_string($ret) || strlen($ret) <= 13) {
            return false;
        }

        return $ret;
    }

    /**
     * Get information about the password hash. Returns an array of the information
     * that was used to generate the password hash.
     *
     * array(
     *    'algo' => 1,
     *    'algoName' => 'bcrypt',
     *    'options' => array(
     *        'cost' => 10,
     *    ),
     * )
     *
     * @param string $hash The password hash to extract info from
     *
     * @return array The array of information about the hash.
     */
    function password_get_info($hash)
    {
        $return = array(
            'algo' => 0,
            'algoName' => 'unknown',
            'options' => array(),
        );

        if (substr($hash, 0, 4) == '$2y$' && strlen($hash) == 60)
        {
            $return['algo']     = PASSWORD_BCRYPT;
            $return['algoName'] = 'bcrypt';
            list($cost)         = sscanf($hash, "$2y$%d$");
            $return['options']['cost'] = $cost;
        }
        return $return;
    }

    /**
     * Determine if the password hash needs to be rehashed according to the options provided
     *
     * If the answer is true, after validating the password using password_verify, rehash it.
     *
     * @param string $hash    The hash to test
     * @param int    $algo    The algorithm used for new password hashes
     * @param array  $options The options array passed to password_hash
     *
     * @return boolean True if the password needs to be rehashed.
     */
    function password_needs_rehash($hash, $algo, array $options = array())
    {
        $info = password_get_info($hash);
        if ($info['algo'] != $algo) {
            return true;
        }

        switch ($algo)
        {
            case PASSWORD_BCRYPT:
                $cost = isset($options['cost']) ? $options['cost'] : 10;
                if ($cost != $info['options']['cost']) {
                    return true;
                }
                break;
        }
        return false;
    }

    /**
     * Verify a password against a hash using a timing attack resistant approach
     *
     * @param string $password The password to verify
     * @param string $hash     The hash to verify against
     *
     * @return boolean If the password matches the hash
     */
    function password_verify($password, $hash)
    {
        if (!function_exists('crypt')) {
            trigger_error("Crypt must be loaded for password_verify to function", E_USER_WARNING);
            return false;
        }

        $ret = crypt($password, $hash);
        if (!is_string($ret) || strlen($ret) != strlen($hash) || strlen($ret) <= 13) {
            return false;
        }

        $status = 0;
        for ($i = 0; $i < strlen($ret); $i++) {
            $status |= (ord($ret[$i]) ^ ord($hash[$i]));
        }

        return $status === 0;
    }
}

/**
 * uri template support compatibility
 *
 * @link https://tools.ietf.org/html/rfc6570
 * @link https://pecl.php.net/package/uri_template
 *
 * Based on https://github.com/seebz/uri-template/blob/master/src/functions.php
 */
if(!function_exists('uri_template'))
{
    function uri_template($template, array $variables = array())
    {
        // Expression replacement
        $expr_callback = function ($match) use ($variables)
        {
            list(, $operator, $variable_list) = $match;

            $separators = array(
                ''  => ',',
                '+' => ',',
                '#' => ',',
                '.' => '.',
                '/' => '/',
                ';' => ';',
                '?' => '&',
                '&' => '&',
            );
            $separator = $separators[$operator];

            $prefixes = array(
                ''  => '',
                '+' => '',
                '#' => '#',
                '.' => '.',
                '/' => '/',
                ';' => ';',
                '?' => '?',
                '&' => '&',
            );
            $prefix = $prefixes[$operator];


            // Callbacks
            $encode = function($value) use ($operator)
            {
                $value = rawurlencode($value);
                $value = str_replace('+', '%20', $value);

                if ($operator == '+' or $operator == '#')
                {
                    // Reserved chars are now allowed
                    $reserved = array(
                        ':' => '%3A',
                        '/' => '%2F',
                        '?' => '%3F',
                        '#' => '%23',
                        '[' => '%5B',
                        ']' => '%5D',
                        '@' => '%40',
                        '!' => '%21',
                        '$' => '%24',
                        '&' => '%26',
                        "'" => '%27',
                        '(' => '%28',
                        ')' => '%29',
                        '*' => '%2A',
                        '+' => '%2B',
                        ',' => '%2C',
                        ';' => '%3B',
                        '=' => '%3D',
                    );
                    $value = str_replace(
                        $reserved,
                        array_keys($reserved),
                        $value
                    );

                    // pct-encoded chars are allowed
                    $value = preg_replace('`%25([0-9]{2})`', '%\\1', $value);
                }

                return $value;
            };

            $add_key = function ($key, $value) use ($operator)
            {
                if (empty($value) and $operator == ';')
                {
                    $value = $key;
                }
                elseif ($operator == ';' or $operator == '?' or $operator == '&')
                {
                    $value = $key . '=' . $value;
                }

                return $value;
            };

            // Scalar values
            $format_scalars = function ($key, $value, $modifier = null, $modifier_option = null)
            use ($encode, $add_key)
            {
                if ($modifier == ':' and $modifier_option)
                {
                    $value = substr($value, 0, $modifier_option);
                }

                $value = $encode($value);
                $value = $add_key($key, $value);

                return $value;
            };

            // List-type array
            $format_lists = function ($key, $value, $modifier = null)
            use ($separator, $encode, $add_key)
            {
                if ($modifier == '*')
                {
                    foreach($value as $k => $v)
                    {
                        $v = $encode($v);
                        $v = $add_key($key, $v);
                        $value[$k] = $v;
                    }
                    $value = implode($separator, $value);
                }
                else
                {
                    $value = array_map($encode, $value);
                    $value = implode(',', $value);
                    $value = $add_key($key, $value);
                }

                return $value;
            };

            // Key-type array
            $format_keys = function ($key, $value, $modifier = null, $modifier_option = null)
            use ($operator, $separator, $encode, $add_key)
            {
                if ($modifier == '*')
                {
                    foreach($value as $k => $v)
                    {
                        $v = $k . '=' . $encode($v);
                        $value[$k] = $v;
                    }
                    $value = implode($separator, $value);
                }
                else
                {
                    foreach($value as $k => $v)
                    {
                        $v = $k . ',' . $encode($v);
                        $value[$k] = $v;
                    }
                    $value = implode(',', $value);
                    $value = $add_key($key, $value);
                }

                return $value;
            };


            // The loop
            foreach(explode(',', $variable_list) as $variable_key)
            {
                preg_match('`^([^:\*]+)(:([1-9][0-9]*)|\*)?$`', $variable_key, $m);
                $key = $m[1];
                $modifier        = count($m) > 2 ? $m[2][0] : null;
                $modifier_option = count($m) > 3 ? $m[3] : null;

                if (isset($variables[$key]))
                {
                    $value = $variables[$key];

                    if (is_scalar($value))
                    {
                        $format_func = $format_scalars;
                    }
                    elseif (empty($value))
                    {
                        continue;
                    }
                    elseif (array_values($value) === $value)
                    {
                        $format_func = $format_lists;
                    }
                    else
                    {
                        $format_func = $format_keys;
                    }
                    $founds[] = $format_func($key, $value, $modifier, $modifier_option);
                }
            }

            return empty($founds) ? '' : $prefix . implode($separator, $founds);
        };

        $expr_pattern = '`\{'
            . '(&|\?|;|/|\.|#|\+|)' // operator
            . '([^\}]+)'            // variable_list
            . '\}`';

        return preg_replace_callback($expr_pattern, $expr_callback, $template);
    }
}

/**
 * is_countable polyfill
 *
 * @link https://www.php.net/manual/en/function.is-countable.php
 *
 * Based on https://github.com/Ayesh/is_countable-polyfill
 */
if (!function_exists('is_countable')) {
    /**
     * Verify that the content of a variable is an array or an object
     * implementing Countable
     *
     * @param mixed $var The value to check.
     * @return bool Returns TRUE if var is countable, FALSE otherwise.
     */
    function is_countable($var) {
        return is_array($var)
            || $var instanceof \Countable
            || $var instanceof \SimpleXMLElement
            || $var instanceof \ResourceBundle;
    }
}

/**
 * locale_get_default stub
 *
 * @link https://www.php.net/manual/en/locale.getdefault.php
 */
if (!function_exists('locale_get_default')) {
    /**
     * Get the default Locale
     *
     * @return string Always returns en-GB
     */
    function locale_get_default() {
        return 'en-GB';
    }
}

/**
 * locale_set_default stub
 *
 * @link https://www.php.net/manual/en/locale.setdefault.php
 */
if (!function_exists('locale_set_default')) {
    /**
     * Set the default Locale
     *
     * @param string $name
     * @return bool Always returns false
     */
    function locale_set_default($name) {
        return false;
    }
}

/**
 * New string functions from PHP 8
 */
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return 0 === strncmp($haystack, $needle, strlen($needle));
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return '' === $needle || ('' !== $haystack && 0 === substr_compare($haystack, $needle, -strlen($needle)));
    }
}

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return '' === $needle || false !== strpos($haystack, $needle);
    }
}