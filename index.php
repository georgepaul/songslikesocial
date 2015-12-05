<?php
/**
 *
 *  SocialStrap - Social Networking Platform
 *  Copyright (c) 2013, Milos Stojanovic (http://interactive32.com) - All rights reserved
 *
 *  PLEASE CAREFULLY READ THE FOLLOWING TERMS AND CONDITIONS. BY YOUR USAGE YOU AGREE TO THE TERMS AND CONDITIONS
 *  OF USE, AND COPYRIGHT OWNER WILL AUTHORIZE YOU TO USE THE SOFTWARE IN ACCORDANCE WITH THE BELOW TERMS AND
 *  CONDITIONS. IF YOU DO NOT AGREE TO ALL OF THE BELOW TERMS AND CONDITIONS, PLEASE DO NOT USE THE SOFTWARE.
 *
 *  This Software is protected by copyright law and international treaties. This Software is licensed (not sold).
 *  The unauthorized use, copying or distribution of this Software may result in severe criminal or civil penalties,
 *  and will be prosecuted to the maximum extent allowed by law.
 *
 *  DISCLAIMER OF WARRANTY: You acknowledge that this software is provided "AS IS" and may not be functional on
 *  any machine or in any environment. Copyright owner have no obligation to correct any bugs, defects or errors,
 *  to otherwise support the Software or otherwise assist you evaluate the Software. IN NO EVENT SHALL THE COPYRIGHT
 *  OWNER BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 *  BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 *  INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 *  (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF
 *  THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

// Define path to public directory
defined('PUBLIC_PATH')
	|| define('PUBLIC_PATH', realpath(dirname(".")));
	
// Define path to addons
defined('ADDONS_PATH')
	|| define('ADDONS_PATH', realpath(dirname(__FILE__) . '/addons'));

// Define path to application directory  
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/app/core'));

// Define path to tmp directory
defined('TMP_PATH')
    || define('TMP_PATH', realpath(dirname(__FILE__) . '/tmp'));

// Define path to cache directory
defined('CACHE_PATH')
    || define('CACHE_PATH', realpath(dirname(__FILE__)) . '/app/tmp');

// Define path to log file
    defined('APPLICATION_LOG')
    || define('APPLICATION_LOG', realpath(dirname(__FILE__)) . '/app/log.txt');

// Define tmp directory public name
defined('TMP_PUBLIC_NAME')
    || define('TMP_PUBLIC_NAME', '/tmp');
    
// Ensure Zend library is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(dirname(__FILE__) . '/app/lib')
)));

// DB driver check
if (extension_loaded('pdo_mysql')) {
	define('DB_ADAPTER', 'PDO_MYSQL');
} elseif (extension_loaded('mysqli'))  {
	define('DB_ADAPTER', 'MYSQLi');
} else {
	die("Error: pdo_mysql or mysqli extension not loaded.");
}

// Run installer?
if ((file_exists('data.zip')) || (!file_exists('config.php') && file_exists('install.php')) || (file_exists('config.php') && isset($_GET['step']) && $_GET['step'] == 4)) {
	require_once 'install.php';
	die;
}

// Load Config
require_once 'config.php';

// System checks
if (APPLICATION_ENV != 'production') {
	if (version_compare(PHP_VERSION, '5.3.0') < 0) die ("Error: PHP version 5.3 or higher is required. Please upgrade your PHP version.");
	if (!extension_loaded('gd')) die ("Error: GD extension not loaded.");
	if (!file_exists('.htaccess')) die ("Error: File .htaccess not found. Please check if you uploaded this file.");
	if (!is_writable(TMP_PATH)) die ("Error: TMP directory not writable: ".TMP_PATH);
	if (!is_writable(APPLICATION_LOG) && !touch(APPLICATION_LOG)) die ("Error: Application log file not available: ".APPLICATION_LOG);
	if (!is_writable(CACHE_PATH)) die ("Error: Cache directory not writable: ".CACHE_PATH);
}

// Set error reporting
error_reporting(APPLICATION_ENV == 'production' || APPLICATION_ENV == 'safe' ? 0 : E_ALL);

// Load APP_VERSION
require_once 'version.php';

// Zend_Application
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.php'
);

$application->bootstrap()
            ->run();