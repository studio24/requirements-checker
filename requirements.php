<?php
/*
 * PHP requirements checker to check minimum server requirements for common applications
 * 
 * This is a standalone script, run on the CLI:
 * 
 * php requirements.php
 * 
 * You can optionally email the output via the email parameter:
 * 
 * php requirements.php --email=name@domain.com
 *
 * (c) Studio 24 Ltd
 * 
 * Inspired by Symfony requirements checker
 * @see https://github.com/symfony/requirements-checker
 */


// Define requirements
// Express boolean ini settings as 1|0
$req = array(
    'php_version' => '7.1.0',
    'modules'     => array(
        'bcmath',
        'exif',
        'fileinfo',
        'gd',
        'intl',
        'json',
        'mbstring',
        'mcrypt',
        'mysqlnd',
        'opcache',
        'soap',
    ),
    'ini'         => array(
        'memory_limit'        => '1024M',
        'opcache.enable'      => '1',
        'post_max_size'       => '21M',
        'upload_max_filesize' => '20M',
        'allow_url_include'   => '0',
    )
);

// Run requirements checker
$emailContent = '';
$ok = true;

echo_title('Studio 24 requirements checker');

// Get options
$sendEmailTo = false;
if ($argc > 1 && preg_match('/^--email=(.+)$/', $argv[1], $m)) {
    $email = $m[1];
    if (version_compare(PHP_VERSION, '5.2.0', '<')) {
        echo_style('red', 'You need to be running a minimum of PHP 5.2.0 to run this script!');
        exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo_style('red', 'You must pass a valid email address');
        exit();
    }
    $sendEmailTo = $email;
}

// Version
if (version_compare(PHP_VERSION, $req['php_version'], '>=')) {
    echo_style('green', 'PHP version OK, required: ' . $req['php_version'] . ', detected: ' . phpversion());
} else {
    echo_style('red', 'PHP version NOT OK, required: ' . $req['php_version'] . ', detected: ' . phpversion());
    $ok = false;
}
// Modules
$installed = get_loaded_extensions();
foreach ($req['modules'] as $module) {
    if (in_array($module, $installed)) {
        echo_style('green', 'PHP module installed: ' . $module);
    } else {
        echo_style('red', 'PHP module NOT installed: ' . $module);
        $ok = false;
    }
}

// PHP configuration
foreach ($req['ini'] as $varname => $requiredValue) {
    if (check_php_ini($varname, $requiredValue)) {
        echo_style('green', "PHP ini setting $varname OK, required: " . $requiredValue . ', detected: ' . ini_get($varname));
    } else {
        echo_style('red', "PHP ini setting $varname NOT OK, required: " . $requiredValue . ', detected: ' . ini_get($varname));
        $ok = false;
    }
}

// Line return
echo_style('green', '');

if ($ok) {
    echo_style('green', 'Requirements check passed, hosting environment is A-OK!');
} else {
    echo_style('red', 'Requirements check did not pass, see above for more details');
}

if ($sendEmailTo !== false) {
    $to = $sendEmailTo;
    $subject = 'PHP requirements checker on ' . gethostname();
    $message = $emailContent;
    $headers = 'From: no-reply@studio24.net' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();

    mail($to, $subject, $message, $headers);
    echo_style('green', "Email sent to $email");

}

function echo_title($title, $style = null)
{
    global $emailContent;
    $emailContent .= $title . PHP_EOL;

    $style = $style ?: 'title';
    echo PHP_EOL;
    echo_style($style, $title . PHP_EOL);
    echo_style($style, str_repeat('~', strlen($title)) . PHP_EOL);
    echo PHP_EOL;
}

function echo_style($style, $message)
{
    global $emailContent;
    $emailContent .= $message . PHP_EOL;

    // ANSI color codes
    $styles = array(
        'reset'   => "\033[0m",
        'red'     => "\033[31m",
        'green'   => "\033[32m",
        'yellow'  => "\033[33m",
        'error'   => "\033[37;41m",
        'success' => "\033[37;42m",
        'title'   => "\033[34m",
    );
    $supports = has_color_support();
    echo ($supports ? $styles[$style] : '') . $message . ($supports ? $styles['reset'] : '') . PHP_EOL;
}

function has_color_support()
{
    static $support;
    if (null === $support) {
        if (DIRECTORY_SEPARATOR == '\\') {
            $support = false !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI');
        } else {
            $support = function_exists('posix_isatty') && @posix_isatty(STDOUT);
        }
    }
    return $support;
}

/**
 * Check a PHP ini setting
 *
 * @see http://php.net/manual/en/ini.list.php To add new settings
 */
function check_php_ini($varname, $requiredValue)
{
    $actualValue = ini_get($varname);

    $ini_settings_bytes = array(
        'memory_limit',
        'post_max_size',
        'upload_max_filesize',
    );

    $ini_settings_boolean = array(
        'allow_call_time_pass_reference',
        'allow_url_fopen',
        'allow_url_include',
        'opcache.enable'
    );

    if (in_array($varname, $ini_settings_bytes)) {
        $actualValue = convert_size_to_bytes($actualValue);
        $requiredValue = convert_size_to_bytes($requiredValue);
        return ($actualValue >= $requiredValue);

    } elseif (in_array($varname, $ini_settings_boolean)) {
        if ($actualValue === '1') {
            $actualValue = '1';
        } else {
            $actualValue = '0';
        }
        return ($actualValue === $requiredValue);

    } else {
        return ($varname === $requiredValue);
    }
}

function convert_size_to_bytes($size)
{
    if (preg_match('/^([0-9]+)([K|M|G])$/', $size, $m)) {
        $size = $m[1];
        $factor = $m[2];
        switch ($factor) {
            case 'K':
                return ($size * 1024);
                break;
            case 'M':
                return ($size * 1048576);
                break;
            case 'G':
                return ($size * 1073741824);
                break;
        }

    } else {
        // assume a number = bytes
        return $size;
    }
}
