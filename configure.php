<?php
/**
 * Configure
 * =========
 * 
 * This script checks for all of the necessary configuration that has to
 * be done, and tells the user what to fix in the configuration before running
 * the tests. It checks that the media folders are writable, that curl, gd,
 * and any other PHP extensions are installed, that ffmpeg or avconv is
 * installed, etc. It also installs the database, if there is not already a
 * database installation. As a result it also tells you if the database
 * credentials are invalid.
 * 
 * The user will be told what to change and they will have to do it manually.
 * In the future this script might accept some input and output a local
 * config file.
 * 
 * Command Line Options:
 * -f: Force uninstall databases before installing them.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

set_time_limit(0);

require_once(__DIR__."/resources/config.php");
require_once(__DIR__."/resources/db/NestedPDO.class.php");

/**
 * Wraps string in red color code for terminal output.
 * 
 * @param string $str
 * @return string
 */
function error_text($str) {
    return "\e[31m$str\e[0m";
}

/**
 * Wraps string in green color code for terminal output.
 * 
 * @param string $str
 * @return string
 */
function success_text($str) {
    return "\e[32m$str\e[0m";
}

/**
 * Start out with no error.
 */
$error = false;

/**
 * Check for the -f option for force uninstalling databases.
 */
$force = in_array("-f", $argv);

/**
 * Check database credentials and install databases.
 */
$db_install_script = file_get_contents(__DIR__."/resources/db/install.sql");
$db_uninstall_script = file_get_contents(__DIR__."/resources/db/uninstall.sql");

//Do test database
try {
    $dbh_test = new NestedPDO(
        "mysql:host=".TEST_DB_HOST.";dbname=".TEST_DB_NAME.";charset=utf8mb4",
        TEST_DB_USER, TEST_DB_PASS
    );
    $testdb_results = $dbh_test->query("
        SELECT * FROM `information_schema`.`tables`
        WHERE `table_schema` = '".TEST_DB_NAME."'
    ");
    $testdb_is_installed = $testdb_results && $testdb_results->rowCount();
    $testdb_do_install = true;
    if ($force) {
        if ($dbh_test->query($db_uninstall_script) !== false) {
            echo success_text("Uninstalled existing test database.\n");
        } else {
            $error = true;
            echo error_text("Error uninstalling existing test database.\n");
            echo "Database said: ".print_r($dbh_test->errorInfo(), true)."\n";
        }
    } else if ($testdb_is_installed) {
        echo "Existing test database found. Use -f to reinstall the database.\n";
        $testdb_do_install = false;
    }
    if ($testdb_do_install) {
        if ($dbh_test->query($db_install_script) !== false) {
            echo success_text("Installed test database.\n");
        } else {
            $error = true;
            echo error_text("Error installing test database.\n");
            echo "Database said: ".print_r($dbh_test->errorInfo(), true)."\n";
        }
    }
} catch (Exception $ex) {
    $error = true;
    echo error_text("Unable to connect to the Test Database.\n");
    echo "Database said: ".$ex->getMessage()."\n";
}

//Do production database
try {
    $dbh_prod = new NestedPDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER, DB_PASS
    );
    $proddb_results = $dbh_test->query("
        SELECT * FROM `information_schema`.`tables`
        WHERE `table_schema` = '".TEST_DB_NAME."'
    ");
    $proddb_is_installed = $proddb_results && $proddb_results->rowCount();
    $proddb_do_install = true;
    if ($force) {
        if ($dbh_prod->query($db_uninstall_script) !== false) {
            echo success_text("Uninstalled existing production database.\n");
        } else {
            $error = true;
            echo error_text("Error uninstalling existing production database.\n");
            echo "Database said: ".print_r($dbh_prod->errorInfo(), true)."\n";
        }
    } else if ($proddb_is_installed) {
        echo "Existing production database found. Use -f to reinstall the database.\n";
        $proddb_do_install = false;
    }
    if ($proddb_do_install) {
        if ($dbh_prod->query($db_install_script) !== false) {
            echo success_text("Installed production database.\n");
        } else {
            $error = true;
            echo error_text("Error installing production database.\n");
            echo "Database said: ".print_r($dbh_prod->errorInfo(), true)."\n";
        }
    }
} catch (Exception $ex) {
    $error = true;
    echo error_text("Unable to connect to the production Database.\n");
    echo "Database said: ".$ex->getMessage()."\n";
}

/**
 * Check for curl and gd PHP extensions.
 */
if (function_exists("curl_init")) {
    echo "cURL extension for PHP found.\n";
} else {
    $error = true;
    echo error_text("You must install the cURL extension for PHP.\n");
    echo "Use 'apt-get install php5-curl' or the equivalent for your system.\n";
}

if (function_exists("imagecreatetruecolor")) {
    echo "GD image processing library found.\n";
} else {
    $error = true;
    echo error_text("You must install the GD image processing library for PHP.\n");
    echo "Use 'apt-get install php5-gd' or the equivalent for your system.\n";
}

/**
 * Check for writable media directories.
 */
$media_dir_errors = false;
$media_subpaths = array(
    "/avatars", "/avatars/small",
    "/posts", "/posts/medium", "/posts/small",
    "/riffs", "/riffs/hq", "/riffs/raw"
);
$all_media_subpaths = array_merge(
    array(MEDIA_ABSOLUTE_PATH, TEST_MEDIA_ABSOLUTE_PATH),
    array_map(function($i) { return MEDIA_ABSOLUTE_PATH.$i; }, $media_subpaths),
    array_map(function($i) { return TEST_MEDIA_ABSOLUTE_PATH.$i; }, $media_subpaths)
);
foreach ($all_media_subpaths as $subpath) {
    if (!file_exists($subpath)) {
        $error = true;
        $media_dir_errors = true;
        echo error_text("Unable to find media directory: $subpath.\n");
    } else if (!is_writable($subpath)) {
        if (!chmod($subpath, 0777)) {
            $error = true;
            $media_dir_errors = true;
            echo error_text("Unable to make media directory writable: $subpath.\n");
            echo "Run 'chmod 777 $subpath' to fix this.\n";
        } else {
            echo success_text("Successfully made media directory writable: $subpath.\n");
        }
    }
}
if (!$media_dir_errors) {
    echo "All media directories exist and are writable.\n";
}

/**
 * Checks for audio conversion software.
 */
$ffmpeg_error = false;
$current_command = FFMPEG_COMMAND." --help > /dev/null 2>&1";
exec($current_command, $output, $return_var);
if ($return_var === 127) {
    $error = true;
    $ffmpeg_error = true;
    echo error_text("Command '".FFMPEG_COMMAND."' not found.\n");
} else if ($return_var) {
    $error = true;
    $ffmpeg_error = true;
    echo error_text("Command '".FFMPEG_COMMAND."' caused an error.\n");
} else {
    echo "Command '".FFMPEG_COMMAND."' found.\n";
}
if ($ffmpeg_error) {
    exec("ffmpeg --help > /dev/null 2>&1", $output, $ffmpeg_return_var);
    exec("avconv --help > /dev/null 2>&1", $output, $avconv_return_var);
    if (!$ffmpeg_return_var) {
        echo "Command 'ffmpeg' found, change FFMPEG_COMMAND in config.php.\n";
        echo "You should also change AUDIO_INFO_COMMAND to use 'ffprobe'.\n";
    } else if (!$avconv_return_var) {
        echo "Command 'avconv' found, change FFMPEG_COMMAND in config.php.\n";
        echo "You should laso change AUDIO_INFO_COMMAND to use 'avprobe'.\n";
    } else {
        echo error_text("No command line audio conversion software found.\n");
        echo "Please install 'ffmpeg' or 'avconv' (on Ubuntu).\n";
    }
}

/**
 * If there were errors, let the user know.
 */
if ($error) {
    echo error_text("Completed with errors.\n");
} else {
    echo success_text("Completed successfully.\n");
}
