<?php
/**
 * Simple PHP Git deploy script
 *
 * Automatically deploy the code using PHP and Git.
 *
 * @version 1.3.2
 * @link    https://github.com/markomarkovic/simple-php-git-deploy/
 */

// =========================================[ Configuration start ]===

/**
 * It's preferable to configure the script using `deploy-config.php` file.
 *
 * Rename `deploy-config.example.php` to `deploy-config.php` and edit the
 * configuration options there instead of here. That way, you won't have to edit
 * the configuration again if you download the new version of `deploy.php`.
 */
if (file_exists(basename(__FILE__, '.php').'-config.php')) require_once basename(__FILE__, '.php').'-config.php';

/**
 * Protect the script from unauthorized access by using a secret access token.
 * If it's not present in the access URL as a GET variable named `sat`
 * e.g. deploy.php?sat=Bett...s the script is not going to deploy.
 *
 * @var string
 */
if (!defined('SECRET_ACCESS_TOKEN')) define('SECRET_ACCESS_TOKEN', 'BetterChangeMeNowOrSufferTheConsequences');

/**
 * The address of the remote Git repository that contains the code that's being
 * deployed.
 * If the repository is private, you'll need to use the SSH address.
 *
 * @var string
 */
if (!defined('REMOTE_REPOSITORY')) define('REMOTE_REPOSITORY', 'https://github.com/markomarkovic/simple-php-git-deploy.git');

/**
 * The branch that's being deployed.
 * Must be present in the remote repository.
 *
 * @var string
 */
if (!defined('BRANCH')) define('BRANCH', 'master');

/**
 * OPTIONAL
 * Deploy git on successful merge and close of a pull request
 *
 * @var string Email address
 */
if (!defined('DEPLOY_ON_PR_MERGE')) define('DEPLOY_ON_PR_MERGE', false);


// ===========================================[ Configuration end ]===

// If there's authorization error, set the correct HTTP header.
if (!isset($_GET['sat']) || $_GET['sat'] !== SECRET_ACCESS_TOKEN || SECRET_ACCESS_TOKEN === 'BetterChangeMeNowOrSufferTheConsequences') {
	header('HTTP/1.0 403 Forbidden');
}
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="robots" content="noindex">
	<title>Simple PHP Git deploy script</title>
	<style>
body { padding: 0 1em; background: #222; color: #fff; }
h2, .error { color: #c33; }
.prompt { color: #6be234; }
.command { color: #729fcf; }
.output { color: #999; }
	</style>
</head>
<body>
<?php
if (!isset($_GET['sat']) || $_GET['sat'] !== SECRET_ACCESS_TOKEN) {
	die('<h2>ACCESS DENIED!</h2>');
}
if (SECRET_ACCESS_TOKEN === 'BetterChangeMeNowOrSufferTheConsequences') {
	die("<h2>You're suffering the consequences!<br>Change the SECRET_ACCESS_TOKEN from it's default value!</h2>");
}

if (defined('DEPLOY_ON_PR_MERGE') && DEPLOY_ON_PR_MERGE !== false) {
	$postBody = file_get_contents('php://input');
	$payload  = json_decode($postBody);
	
	if ( $payload->action !== 'closed' && $payload->pull_request->merged !== true) {
	
	  echo $payload->action;
	  echo $payload->pull_request->merged;
	  die("<h2>The pull request was not merged and cannot be deployed.</h2>");
	}
}
?>
<pre>

Checking the environment ...

Running as <b><?php echo trim(shell_exec('whoami')); ?></b>.

<?php
// Check if the required programs are available
$requiredBinaries = array('git');
foreach ($requiredBinaries as $command) {
	$path = trim(shell_exec('which '.$command));
	if ($path == '') {
		die(sprintf('<div class="error"><b>%s</b> not available. It needs to be installed on the server for this script to work.</div>', $command));
	} else {
		$version = explode("\n", shell_exec($command.' --version'));
		printf('<b>%s</b> : %s'."\n"
			, $path
			, $version[0]
		);
	}
}
?>

Environment OK.

Deploying <?php echo REMOTE_REPOSITORY; ?> <?php echo BRANCH."\n"; ?>

<?php
// ==================================================[ Deployment ]===
// The commands
$commands = array(
        'git pull',
        'git submodule sync',
        'git submodule update',
);

// =======================================[ Run the command steps ]===
$output = '';
foreach ($commands as $command) {
        // Run it
        $tmp = shell_exec($command);
        // Output the result
        $output .= "<span style=\"color: #6BE234;\">\$</span> <span style=\"color: #729FCF;\">{$command}\n</span>";
        $output .= htmlentities(trim($tmp)) . "\n";

}
echo $output;
?>

Done.
</pre>
</body>
</html>
