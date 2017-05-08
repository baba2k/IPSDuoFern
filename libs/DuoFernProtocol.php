<?
/**
 * Require
 */
// DuoFern Message
require_once (__DIR__ . DIRECTORY_SEPARATOR . "DuoFernMessage.php");

// DuoFern Function
require_once (__DIR__ . DIRECTORY_SEPARATOR . "DuoFernFunction.php");

/**
 * Regex
 */
// Valid DuoFern Code
define ( "DUOFERN_REGEX_DUOFERN_CODE", "/^6F[0-9A-F]{4}$/" );
// Valid DuoFern Message
define ( "DUOFERN_REGEX_MSG", "/^[0-9A-F]{44}$/" );
?>