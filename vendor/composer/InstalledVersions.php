<?php











namespace Composer;

use Composer\Autoload\ClassLoader;
use Composer\Semver\VersionParser;






class InstalledVersions
{
private static $installed = array (
  'root' => 
  array (
    'pretty_version' => 'dev-develop',
    'version' => 'dev-develop',
    'aliases' => 
    array (
    ),
    'reference' => '8ba9681827e8c4863c5c96700c6a3ea4d97a7db9',
    'name' => 'maithemewp/mai-engine',
  ),
  'versions' => 
  array (
    'advanced-custom-fields/advanced-custom-fields-pro' => 
    array (
      'pretty_version' => '5.9.5',
      'version' => '5.9.5.0',
      'aliases' => 
      array (
      ),
      'reference' => NULL,
    ),
    'aristath/kirki' => 
    array (
      'pretty_version' => '3.1.6',
      'version' => '3.1.6.0',
      'aliases' => 
      array (
      ),
      'reference' => 'b678315bc09f1fd782e809dd5b2ce0647a6770ae',
    ),
    'composer/installers' => 
    array (
      'pretty_version' => 'v1.10.0',
      'version' => '1.10.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '1a0357fccad9d1cc1ea0c9a05b8847fbccccb78d',
    ),
    'ffraenz/private-composer-installer' => 
    array (
      'pretty_version' => 'v5.0.1',
      'version' => '5.0.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '8655e3da4e8f99203f13ccca33b9ab953ad30a31',
    ),
    'graham-campbell/result-type' => 
    array (
      'pretty_version' => 'v1.0.1',
      'version' => '1.0.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '7e279d2cd5d7fbb156ce46daada972355cea27bb',
    ),
    'maithemewp/mai-engine' => 
    array (
      'pretty_version' => 'dev-develop',
      'version' => 'dev-develop',
      'aliases' => 
      array (
      ),
      'reference' => '8ba9681827e8c4863c5c96700c6a3ea4d97a7db9',
    ),
    'phpoption/phpoption' => 
    array (
      'pretty_version' => '1.7.5',
      'version' => '1.7.5.0',
      'aliases' => 
      array (
      ),
      'reference' => '994ecccd8f3283ecf5ac33254543eb0ac946d525',
    ),
    'proteusthemes/wp-content-importer-v2' => 
    array (
      'pretty_version' => 'v2.1.0',
      'version' => '2.1.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '7414ce1bfb56a8be780c520ed63e99cac7ac403b',
    ),
    'roundcube/plugin-installer' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'shama/baton' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'symfony/polyfill-ctype' => 
    array (
      'pretty_version' => 'v1.22.1',
      'version' => '1.22.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'c6c942b1ac76c82448322025e084cadc56048b4e',
    ),
    'symfony/polyfill-mbstring' => 
    array (
      'pretty_version' => 'v1.22.1',
      'version' => '1.22.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '5232de97ee3b75b0360528dae24e73db49566ab1',
    ),
    'symfony/polyfill-php80' => 
    array (
      'pretty_version' => 'v1.22.1',
      'version' => '1.22.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'dc3063ba22c2a1fd2f45ed856374d79114998f91',
    ),
    'vlucas/phpdotenv' => 
    array (
      'pretty_version' => 'v5.3.0',
      'version' => '5.3.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'b3eac5c7ac896e52deab4a99068e3f4ab12d9e56',
    ),
    'wptrt/customize-section-button' => 
    array (
      'pretty_version' => '1.0.1',
      'version' => '1.0.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '510c956c1142f712cda3041fc2d02d316228f031',
    ),
    'yahnis-elsts/plugin-update-checker' => 
    array (
      'pretty_version' => 'v4.11',
      'version' => '4.11.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '3155f2d3f1ca5e7ed3f25b256f020e370515af43',
    ),
  ),
);
private static $canGetVendors;
private static $installedByVendor = array();







public static function getInstalledPackages()
{
$packages = array();
foreach (self::getInstalled() as $installed) {
$packages[] = array_keys($installed['versions']);
}


if (1 === \count($packages)) {
return $packages[0];
}

return array_keys(array_flip(\call_user_func_array('array_merge', $packages)));
}









public static function isInstalled($packageName)
{
foreach (self::getInstalled() as $installed) {
if (isset($installed['versions'][$packageName])) {
return true;
}
}

return false;
}














public static function satisfies(VersionParser $parser, $packageName, $constraint)
{
$constraint = $parser->parseConstraints($constraint);
$provided = $parser->parseConstraints(self::getVersionRanges($packageName));

return $provided->matches($constraint);
}










public static function getVersionRanges($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

$ranges = array();
if (isset($installed['versions'][$packageName]['pretty_version'])) {
$ranges[] = $installed['versions'][$packageName]['pretty_version'];
}
if (array_key_exists('aliases', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['aliases']);
}
if (array_key_exists('replaced', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['replaced']);
}
if (array_key_exists('provided', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['provided']);
}

return implode(' || ', $ranges);
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['version'])) {
return null;
}

return $installed['versions'][$packageName]['version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getPrettyVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['pretty_version'])) {
return null;
}

return $installed['versions'][$packageName]['pretty_version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getReference($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['reference'])) {
return null;
}

return $installed['versions'][$packageName]['reference'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getRootPackage()
{
$installed = self::getInstalled();

return $installed[0]['root'];
}







public static function getRawData()
{
return self::$installed;
}



















public static function reload($data)
{
self::$installed = $data;
self::$installedByVendor = array();
}




private static function getInstalled()
{
if (null === self::$canGetVendors) {
self::$canGetVendors = method_exists('Composer\Autoload\ClassLoader', 'getRegisteredLoaders');
}

$installed = array();

if (self::$canGetVendors) {

foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
if (isset(self::$installedByVendor[$vendorDir])) {
$installed[] = self::$installedByVendor[$vendorDir];
} elseif (is_file($vendorDir.'/composer/installed.php')) {
$installed[] = self::$installedByVendor[$vendorDir] = require $vendorDir.'/composer/installed.php';
}
}
}

$installed[] = self::$installed;

return $installed;
}
}
