#!/usr/bin/env php
<?php
/**
 * Generated by Box.
 *
 * @link https://github.com/herrera-io/php-box/
 */
define('BOX_EXTRACT_PATTERN_DEFAULT', '__HALT' . '_COMPILER(); ?>');
define('BOX_EXTRACT_PATTERN_OPEN', "__HALT" . "_COMPILER(); ?>\r\n");
if (class_exists('Phar')) {
Phar::mapPhar('debug.phar');
require 'phar://' . __FILE__ . '/bin/debug-m1.php';
} else {
$extract = new Extract(__FILE__, Extract::findStubLength(__FILE__));
$dir = $extract->go();
set_include_path($dir . PATH_SEPARATOR . get_include_path());
require "$dir/bin/debug-m1.php";
}
class Extract
{
const PATTERN_DEFAULT = BOX_EXTRACT_PATTERN_DEFAULT;
const PATTERN_OPEN = BOX_EXTRACT_PATTERN_OPEN;
const GZ = 0x1000;
const BZ2 = 0x2000;
const MASK = 0x3000;
private $file;
private $handle;
private $stub;
public function __construct($file, $stub)
{
if (!is_file($file)) {
throw new InvalidArgumentException(
sprintf(
'The path "%s" is not a file or does not exist.',
$file
)
);
}
$this->file = $file;
$this->stub = $stub;
}
public static function findStubLength(
$file,
$pattern = self::PATTERN_OPEN
) {
if (!($fp = fopen($file, 'rb'))) {
throw new RuntimeException(
sprintf(
'The phar "%s" could not be opened for reading.',
$file
)
);
}
$stub = null;
$offset = 0;
$combo = str_split($pattern);
while (!feof($fp)) {
if (fgetc($fp) === $combo[$offset]) {
$offset++;
if (!isset($combo[$offset])) {
$stub = ftell($fp);
break;
}
} else {
$offset = 0;
}
}
fclose($fp);
if (null === $stub) {
throw new InvalidArgumentException(
sprintf(
'The pattern could not be found in "%s".',
$file
)
);
}
return $stub;
}
public function go($dir = null)
{
if (null === $dir) {
$dir = rtrim(sys_get_temp_dir(), '\\/')
. DIRECTORY_SEPARATOR
. 'pharextract'
. DIRECTORY_SEPARATOR
. basename($this->file, '.phar');
} else {
$dir = realpath($dir);
}
$md5 = $dir . DIRECTORY_SEPARATOR . md5_file($this->file);
if (file_exists($md5)) {
return $dir;
}
if (!is_dir($dir)) {
$this->createDir($dir);
}
$this->open();
if (-1 === fseek($this->handle, $this->stub)) {
throw new RuntimeException(
sprintf(
'Could not seek to %d in the file "%s".',
$this->stub,
$this->file
)
);
}
$info = $this->readManifest();
if ($info['flags'] & self::GZ) {
if (!function_exists('gzinflate')) {
throw new RuntimeException(
'The zlib extension is (gzinflate()) is required for "%s.',
$this->file
);
}
}
if ($info['flags'] & self::BZ2) {
if (!function_exists('bzdecompress')) {
throw new RuntimeException(
'The bzip2 extension (bzdecompress()) is required for "%s".',
$this->file
);
}
}
self::purge($dir);
$this->createDir($dir);
$this->createFile($md5);
foreach ($info['files'] as $info) {
$path = $dir . DIRECTORY_SEPARATOR . $info['path'];
$parent = dirname($path);
if (!is_dir($parent)) {
$this->createDir($parent);
}
if (preg_match('{/$}', $info['path'])) {
$this->createDir($path, 0777, false);
} else {
$this->createFile(
$path,
$this->extractFile($info)
);
}
}
return $dir;
}
public static function purge($path)
{
if (is_dir($path)) {
foreach (scandir($path) as $item) {
if (('.' === $item) || ('..' === $item)) {
continue;
}
self::purge($path . DIRECTORY_SEPARATOR . $item);
}
if (!rmdir($path)) {
throw new RuntimeException(
sprintf(
'The directory "%s" could not be deleted.',
$path
)
);
}
} else {
if (!unlink($path)) {
throw new RuntimeException(
sprintf(
'The file "%s" could not be deleted.',
$path
)
);
}
}
}
private function createDir($path, $chmod = 0777, $recursive = true)
{
if (!mkdir($path, $chmod, $recursive)) {
throw new RuntimeException(
sprintf(
'The directory path "%s" could not be created.',
$path
)
);
}
}
private function createFile($path, $contents = '', $mode = 0666)
{
if (false === file_put_contents($path, $contents)) {
throw new RuntimeException(
sprintf(
'The file "%s" could not be written.',
$path
)
);
}
if (!chmod($path, $mode)) {
throw new RuntimeException(
sprintf(
'The file "%s" could not be chmodded to %o.',
$path,
$mode
)
);
}
}
private function extractFile($info)
{
if (0 === $info['size']) {
return '';
}
$data = $this->read($info['compressed_size']);
if ($info['flags'] & self::GZ) {
if (false === ($data = gzinflate($data))) {
throw new RuntimeException(
sprintf(
'The "%s" file could not be inflated (gzip) from "%s".',
$info['path'],
$this->file
)
);
}
} elseif ($info['flags'] & self::BZ2) {
if (false === ($data = bzdecompress($data))) {
throw new RuntimeException(
sprintf(
'The "%s" file could not be inflated (bzip2) from "%s".',
$info['path'],
$this->file
)
);
}
}
if (($actual = strlen($data)) !== $info['size']) {
throw new UnexpectedValueException(
sprintf(
'The size of "%s" (%d) did not match what was expected (%d) in "%s".',
$info['path'],
$actual,
$info['size'],
$this->file
)
);
}
$crc32 = sprintf('%u', crc32($data) & 0xffffffff);
if ($info['crc32'] != $crc32) {
throw new UnexpectedValueException(
sprintf(
'The crc32 checksum (%s) for "%s" did not match what was expected (%s) in "%s".',
$crc32,
$info['path'],
$info['crc32'],
$this->file
)
);
}
return $data;
}
private function open()
{
if (null === ($this->handle = fopen($this->file, 'rb'))) {
$this->handle = null;
throw new RuntimeException(
sprintf(
'The file "%s" could not be opened for reading.',
$this->file
)
);
}
}
private function read($bytes)
{
$read = '';
$total = $bytes;
while (!feof($this->handle) && $bytes) {
if (false === ($chunk = fread($this->handle, $bytes))) {
throw new RuntimeException(
sprintf(
'Could not read %d bytes from "%s".',
$bytes,
$this->file
)
);
}
$read .= $chunk;
$bytes -= strlen($chunk);
}
if (($actual = strlen($read)) !== $total) {
throw new RuntimeException(
sprintf(
'Only read %d of %d in "%s".',
$actual,
$total,
$this->file
)
);
}
return $read;
}
private function readManifest()
{
$size = unpack('V', $this->read(4));
$size = $size[1];
$raw = $this->read($size);
$count = unpack('V', substr($raw, 0, 4));
$count = $count[1];
$aliasSize = unpack('V', substr($raw, 10, 4));
$aliasSize = $aliasSize[1];
$raw = substr($raw, 14 + $aliasSize);
$metaSize = unpack('V', substr($raw, 0, 4));
$metaSize = $metaSize[1];
$offset = 0;
$start = 4 + $metaSize;
$manifest = array(
'files' => array(),
'flags' => 0,
);
for ($i = 0; $i < $count; $i++) {
$length = unpack('V', substr($raw, $start, 4));
$length = $length[1];
$start += 4;
$path = substr($raw, $start, $length);
$start += $length;
$file = unpack(
'Vsize/Vtimestamp/Vcompressed_size/Vcrc32/Vflags/Vmetadata_length',
substr($raw, $start, 24)
);
$file['path'] = $path;
$file['crc32'] = sprintf('%u', $file['crc32'] & 0xffffffff);
$file['offset'] = $offset;
$offset += $file['compressed_size'];
$start += 24 + $file['metadata_length'];
$manifest['flags'] |= $file['flags'] & self::MASK;
$manifest['files'][] = $file;
}
return $manifest;
}
}

__HALT_COMPILER(); ?>
�                    vendor/autoload.php�   �$�[�   ��X�      '   vendor/composer/autoload_namespaces.php�   �$�[�   �x�         vendor/composer/ClassLoader.php�  �$�[�  �A�d�      !   vendor/composer/autoload_psr4.phpd   �$�[d   Z��H�      %   vendor/composer/autoload_classmap.phpd   �$�[d   Z��H�      #   vendor/composer/autoload_static.php�  �$�[�  � *�      !   vendor/composer/autoload_real.phpg  �$�[g  PO �         bin/debug-m1.php�  �$�[�  ����         app/Application.php�  �$�[�  4���      (   app/Command/Validate/ValidateCommand.php�  �$�[�  &D]�      1   app/Command/Validate/Validators/BaseValidator.phps  �$�[s  #"O�      3   app/Command/Validate/Validators/SearchValidator.phph  �$�[h  ��Ŷ         app/Command/BaseCommand.php/   �$�[/   +��$�         app/Service/EnvService.php$  �$�[$  c���         app/Service/ConfigService.php  �$�[  �wz�         config.jsoni  �$�[i  �NQ$�      <?php



require_once __DIR__ . '/composer/autoload_real.php';

return ComposerAutoloaderInit59731e7df730dea676368e06b813acba::getLoader();
<?php



$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
'Mirasvit_' => array($baseDir . '/app'),
);
<?php











namespace Composer\Autoload;





























class ClassLoader
{

 private $prefixLengthsPsr4 = array();
private $prefixDirsPsr4 = array();
private $fallbackDirsPsr4 = array();


 private $prefixesPsr0 = array();
private $fallbackDirsPsr0 = array();

private $useIncludePath = false;
private $classMap = array();
private $classMapAuthoritative = false;
private $missingClasses = array();
private $apcuPrefix;

public function getPrefixes()
{
if (!empty($this->prefixesPsr0)) {
return call_user_func_array('array_merge', $this->prefixesPsr0);
}

return array();
}

public function getPrefixesPsr4()
{
return $this->prefixDirsPsr4;
}

public function getFallbackDirs()
{
return $this->fallbackDirsPsr0;
}

public function getFallbackDirsPsr4()
{
return $this->fallbackDirsPsr4;
}

public function getClassMap()
{
return $this->classMap;
}




public function addClassMap(array $classMap)
{
if ($this->classMap) {
$this->classMap = array_merge($this->classMap, $classMap);
} else {
$this->classMap = $classMap;
}
}









public function add($prefix, $paths, $prepend = false)
{
if (!$prefix) {
if ($prepend) {
$this->fallbackDirsPsr0 = array_merge(
(array) $paths,
$this->fallbackDirsPsr0
);
} else {
$this->fallbackDirsPsr0 = array_merge(
$this->fallbackDirsPsr0,
(array) $paths
);
}

return;
}

$first = $prefix[0];
if (!isset($this->prefixesPsr0[$first][$prefix])) {
$this->prefixesPsr0[$first][$prefix] = (array) $paths;

return;
}
if ($prepend) {
$this->prefixesPsr0[$first][$prefix] = array_merge(
(array) $paths,
$this->prefixesPsr0[$first][$prefix]
);
} else {
$this->prefixesPsr0[$first][$prefix] = array_merge(
$this->prefixesPsr0[$first][$prefix],
(array) $paths
);
}
}











public function addPsr4($prefix, $paths, $prepend = false)
{
if (!$prefix) {

 if ($prepend) {
$this->fallbackDirsPsr4 = array_merge(
(array) $paths,
$this->fallbackDirsPsr4
);
} else {
$this->fallbackDirsPsr4 = array_merge(
$this->fallbackDirsPsr4,
(array) $paths
);
}
} elseif (!isset($this->prefixDirsPsr4[$prefix])) {

 $length = strlen($prefix);
if ('\\' !== $prefix[$length - 1]) {
throw new \InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
}
$this->prefixLengthsPsr4[$prefix[0]][$prefix] = $length;
$this->prefixDirsPsr4[$prefix] = (array) $paths;
} elseif ($prepend) {

 $this->prefixDirsPsr4[$prefix] = array_merge(
(array) $paths,
$this->prefixDirsPsr4[$prefix]
);
} else {

 $this->prefixDirsPsr4[$prefix] = array_merge(
$this->prefixDirsPsr4[$prefix],
(array) $paths
);
}
}








public function set($prefix, $paths)
{
if (!$prefix) {
$this->fallbackDirsPsr0 = (array) $paths;
} else {
$this->prefixesPsr0[$prefix[0]][$prefix] = (array) $paths;
}
}










public function setPsr4($prefix, $paths)
{
if (!$prefix) {
$this->fallbackDirsPsr4 = (array) $paths;
} else {
$length = strlen($prefix);
if ('\\' !== $prefix[$length - 1]) {
throw new \InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
}
$this->prefixLengthsPsr4[$prefix[0]][$prefix] = $length;
$this->prefixDirsPsr4[$prefix] = (array) $paths;
}
}






public function setUseIncludePath($useIncludePath)
{
$this->useIncludePath = $useIncludePath;
}







public function getUseIncludePath()
{
return $this->useIncludePath;
}







public function setClassMapAuthoritative($classMapAuthoritative)
{
$this->classMapAuthoritative = $classMapAuthoritative;
}






public function isClassMapAuthoritative()
{
return $this->classMapAuthoritative;
}






public function setApcuPrefix($apcuPrefix)
{
$this->apcuPrefix = function_exists('apcu_fetch') && ini_get('apc.enabled') ? $apcuPrefix : null;
}






public function getApcuPrefix()
{
return $this->apcuPrefix;
}






public function register($prepend = false)
{
spl_autoload_register(array($this, 'loadClass'), true, $prepend);
}




public function unregister()
{
spl_autoload_unregister(array($this, 'loadClass'));
}







public function loadClass($class)
{
if ($file = $this->findFile($class)) {
includeFile($file);

return true;
}
}








public function findFile($class)
{

 if (isset($this->classMap[$class])) {
return $this->classMap[$class];
}
if ($this->classMapAuthoritative || isset($this->missingClasses[$class])) {
return false;
}
if (null !== $this->apcuPrefix) {
$file = apcu_fetch($this->apcuPrefix.$class, $hit);
if ($hit) {
return $file;
}
}

$file = $this->findFileWithExtension($class, '.php');


 if (false === $file && defined('HHVM_VERSION')) {
$file = $this->findFileWithExtension($class, '.hh');
}

if (null !== $this->apcuPrefix) {
apcu_add($this->apcuPrefix.$class, $file);
}

if (false === $file) {

 $this->missingClasses[$class] = true;
}

return $file;
}

private function findFileWithExtension($class, $ext)
{

 $logicalPathPsr4 = strtr($class, '\\', DIRECTORY_SEPARATOR) . $ext;

$first = $class[0];
if (isset($this->prefixLengthsPsr4[$first])) {
$subPath = $class;
while (false !== $lastPos = strrpos($subPath, '\\')) {
$subPath = substr($subPath, 0, $lastPos);
$search = $subPath.'\\';
if (isset($this->prefixDirsPsr4[$search])) {
$pathEnd = DIRECTORY_SEPARATOR . substr($logicalPathPsr4, $lastPos + 1);
foreach ($this->prefixDirsPsr4[$search] as $dir) {
if (file_exists($file = $dir . $pathEnd)) {
return $file;
}
}
}
}
}


 foreach ($this->fallbackDirsPsr4 as $dir) {
if (file_exists($file = $dir . DIRECTORY_SEPARATOR . $logicalPathPsr4)) {
return $file;
}
}


 if (false !== $pos = strrpos($class, '\\')) {

 $logicalPathPsr0 = substr($logicalPathPsr4, 0, $pos + 1)
. strtr(substr($logicalPathPsr4, $pos + 1), '_', DIRECTORY_SEPARATOR);
} else {

 $logicalPathPsr0 = strtr($class, '_', DIRECTORY_SEPARATOR) . $ext;
}

if (isset($this->prefixesPsr0[$first])) {
foreach ($this->prefixesPsr0[$first] as $prefix => $dirs) {
if (0 === strpos($class, $prefix)) {
foreach ($dirs as $dir) {
if (file_exists($file = $dir . DIRECTORY_SEPARATOR . $logicalPathPsr0)) {
return $file;
}
}
}
}
}


 foreach ($this->fallbackDirsPsr0 as $dir) {
if (file_exists($file = $dir . DIRECTORY_SEPARATOR . $logicalPathPsr0)) {
return $file;
}
}


 if ($this->useIncludePath && $file = stream_resolve_include_path($logicalPathPsr0)) {
return $file;
}

return false;
}
}






function includeFile($file)
{
include $file;
}
<?php



$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
);
<?php



$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
);
<?php



namespace Composer\Autoload;

class ComposerStaticInit59731e7df730dea676368e06b813acba
{
public static $prefixesPsr0 = array (
'M' =>
array (
'Mirasvit_' =>
array (
0 => __DIR__ . '/../..' . '/app',
),
),
);

public static function getInitializer(ClassLoader $loader)
{
return \Closure::bind(function () use ($loader) {
$loader->prefixesPsr0 = ComposerStaticInit59731e7df730dea676368e06b813acba::$prefixesPsr0;

}, null, ClassLoader::class);
}
}
<?php



class ComposerAutoloaderInit59731e7df730dea676368e06b813acba
{
private static $loader;

public static function loadClassLoader($class)
{
if ('Composer\Autoload\ClassLoader' === $class) {
require __DIR__ . '/ClassLoader.php';
}
}

public static function getLoader()
{
if (null !== self::$loader) {
return self::$loader;
}

spl_autoload_register(array('ComposerAutoloaderInit59731e7df730dea676368e06b813acba', 'loadClassLoader'), true, true);
self::$loader = $loader = new \Composer\Autoload\ClassLoader();
spl_autoload_unregister(array('ComposerAutoloaderInit59731e7df730dea676368e06b813acba', 'loadClassLoader'));

$useStaticLoader = PHP_VERSION_ID >= 50600 && !defined('HHVM_VERSION') && (!function_exists('zend_loader_file_encoded') || !zend_loader_file_encoded());
if ($useStaticLoader) {
require_once __DIR__ . '/autoload_static.php';

call_user_func(\Composer\Autoload\ComposerStaticInit59731e7df730dea676368e06b813acba::getInitializer($loader));
} else {
$map = require __DIR__ . '/autoload_namespaces.php';
foreach ($map as $namespace => $path) {
$loader->set($namespace, $path);
}

$map = require __DIR__ . '/autoload_psr4.php';
foreach ($map as $namespace => $path) {
$loader->setPsr4($namespace, $path);
}

$classMap = require __DIR__ . '/autoload_classmap.php';
if ($classMap) {
$loader->addClassMap($classMap);
}
}

$loader->register(true);

return $loader;
}
}
<?php

require __DIR__.'/../vendor/autoload.php'; 

define("CLI_ROOT", dirname(__DIR__));

require __DIR__."/../app/Application.php";
require __DIR__."/../app/Command/BaseCommand.php";
require __DIR__."/../app/Command/Validate/Validators/BaseValidator.php";
require __DIR__."/../app/Command/Validate/Validators/SearchValidator.php";
require __DIR__."/../app/Command/Validate/ValidateCommand.php";

$application = new Mirasvit_Application();
$application->run();<?php

class Mirasvit_Application
{
public $registeredCommands = [];

public function __construct()
{
$this->addCommands([
new Mirasvit_Command_Validate_ValidateCommand(),
]);
}




public function addCommands(array $commands)
{
foreach ($commands as $command) {
$this->registeredCommands[] = $command;
}
}




public function getCommands()
{
return $this->registeredCommands;
}

public function run()
{
return $this->registeredCommands[0]->execute();
}
}<?php

class Mirasvit_Command_Validate_ValidateCommand extends Mirasvit_Command_BaseCommand
{
public function execute()
{
$basePath = "./";
if (!file_exists($basePath. '/app/Mage.php')) {
$basePath = $_SERVER['DOCUMENT_ROOT'];
}
require_once realpath($basePath). '/app/Mage.php';
umask(0);
Mage::app();

$validators = [
new Mirasvit_Command_Validate_Validators_SearchValidator(),
];

foreach ($validators as $validator) {
if (!Mage::getConfig()->getModuleConfig($validator->moduleName())->is('active', 'true')) {
continue;
}
$validator->printH1("=== ".$validator->moduleName()." ===");
$validator->run();
}

$validator->printH1("-- DONE --");
}
}<?php





























abstract class Mirasvit_Command_Validate_Validators_BaseValidator
{
public abstract function run();

public abstract function moduleName();

public function printH1($message){
$this->writeln("<br><message>{$message}</message>");
}

public function printError($problemDescription, $whatErrorsWeWillSee, $howToFixIt){
$this->writeln("<error>{$problemDescription}</error>");
$this->writeln("<command>It leads to the issues:</command> {$whatErrorsWeWillSee}");
$this->writeln("<command>How to fix it:</command> {$howToFixIt}\n");
}

public function printWarning($problemDescription, $whatErrorsWeWillSee, $howToFixIt){
$this->writeln("<warning>{$problemDescription}</warning>");
$this->writeln("<command>It leads to the issues:</command> {$whatErrorsWeWillSee}");
$this->writeln("<command>How to fix it:</command> {$howToFixIt}\n");
}

public function writeln($message){
$message = str_replace("<message>", "<font color=green>", $message);
$message = str_replace("</message>", "</font>", $message);

$message = str_replace("<command>", "<font color=blue>", $message);
$message = str_replace("</command>", "</font>", $message);


$message = str_replace("<error>", "<font color=red>", $message);
$message = str_replace("</error>", "</font>", $message);

$message = str_replace("<warning>", "<font color=yellow>", $message);
$message = str_replace("</warning>", "</font>", $message);

echo $message."<br>";
}


public function validateRewrite($class, $classNameB)
{
$classNameA = get_class(Mage::getModel($class));
if ($classNameA == $classNameB) {
return true;
} else {
return "$class must be $classNameB, current rewrite is $classNameA";
}
}
public function dbGetTableEngine($tableName)
{
$table = $this->_dbRes()->getTableName($tableName);
$status = $this->_dbConn()->showTableStatus($table);
if ($status && isset($status['Engine'])) {
return $status['Engine'];
}
}
public function dbCheckTables($tables)
{
$result = self::SUCCESS;
$title = 'Required tables exist';
$description = array();
foreach ($tables as $table) {
if (!$this->dbTableExists($table)) {
$tableName = $this->_dbRes()->getTableName($table);
$description[] = "Table '$tableName' doesn't exist";
$result = self::FAILED;
continue;
}
if ($table == 'catalogsearch/fulltext') {
continue;
}
$engine = $this->dbGetTableEngine($table);
if ($engine != 'InnoDB') {
$description[] = "Table '$table' has engine $engine. It should have engine InnoDB.";
$result = self::FAILED;
}
}
return array($result, $title, $description);
}
public function dbTableExists($tableName)
{
$table = $this->_dbRes()->getTableName($tableName);
return $this->_dbConn()->showTableStatus($table) !== false;
}
public function dbDescribeTable($tableName)
{
$table = $this->_dbRes()->getTableName($tableName);
return $this->_dbConn()->describeTable($table);
}
public function dbTableColumnExists($tableName, $column)
{
$desribe = $this->dbDescribeTable($tableName);
return array_key_exists($column, $desribe);
}
public function dbTableIsEmpty($table)
{
$select = $this->_dbConn()->select()->from($this->_dbRes()->getTableName($table));
$row = $this->_dbConn()->fetchRow($select);
if (is_array($row)) {
return false;
}
return true;
}
public function ioIsReadable($path)
{
if (is_file($path) && !is_readable($path)) {
return false;
}
return true;
}
public function ioIsWritable($path)
{
if (is_writable($path)) {
return true;
}
return false;
}
public function ioNumberOfFiles($path)
{
$cnt = 0;
$dir = new DirectoryIterator($path);
foreach($dir as $file) {
$cnt += (is_file($path.DS.$file)) ? 1 : 0;
}
return $cnt;
}
protected function _dbRes()
{
return Mage::getSingleton('core/resource');
}
protected function _dbConn()
{
return $this->_dbRes()->getConnection('core_write');
}





protected function getHandleNodesFromLayout($layoutName, $handleName)
{
$appEmulation = Mage::getSingleton('core/app_emulation');
$initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation(
Mage::app()->getDefaultStoreView()->getId(),
Mage_Core_Model_App_Area::AREA_FRONTEND
);
$catalogSearchLayoutFile = Mage::getDesign()->getLayoutFilename($layoutName);
$catalogSearchXml = new Zend_Config_Xml($catalogSearchLayoutFile, $handleName);
$catalogSearchArray = $catalogSearchXml->toArray();
$iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($catalogSearchArray));
$container = iterator_to_array($iterator, false);
$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
return $container;
}
}<?php

class Mirasvit_Command_Validate_Validators_SearchValidator extends Mirasvit_Command_Validate_Validators_BaseValidator
{

public function moduleName()
{
return "Mirasvit_SearchSphinx";
}

public function run()
{
$this->testTopLevelCategoryIsAnchor();
$this->testProductIndexExists();
$this->testTablesExists();
$this->testReindexIsCompleted();
$this->testExecIsEnabled();
$this->testDomainNameIsPinged();
$this->testProductIndexConfigured();
$this->testBlockCatalogSearchLayerExists();
$this->testCatalogSearchQuerySize();
$this->testConflictExtensions();
$this->testFulltextCollectionRewrite();
}


public function testTopLevelCategoryIsAnchor()
{
foreach (Mage::app()->getStores() as $store) {
$rootCategoryId = $store->getRootCategoryId();
$rootCategory = Mage::getModel('catalog/category')->load($rootCategoryId);
$categoryName = $rootCategory->getName();
if ($rootCategory->getIsAnchor() == 0) {
$this->printError(
"Root Store Category is not Anchor",
"",
"Go to the Catalog > Manage Categories. Change option 'Is Anchor' to 'Yes' for category '$categoryName (ID: $rootCategoryId)'");
return;
}
}
}

public function testProductIndexExists()
{
$count = Mage::getModel('searchindex/index')->getCollection()->count();
if ($count == 0) {
$this->printError(
'Search Sphinx: Search indexes are not exist',
"",
'Create required search indexes at Search / Manage Indexes');
}
}

public function testTablesExists()
{
$tables = array(
'catalogsearch/fulltext',
'searchindex/index',
'searchsphinx/synonym',
'searchsphinx/stopword',
);
foreach ($tables as $table) {
if (!$this->dbTableExists($table)) {
$this->printError(
'Search Sphinx: Required tables are not exists',
"",
"Table '$table' is not exists"
);
}
}
}


public function testReindexIsCompleted()
{
if (!$this->dbTableColumnExists('catalogsearch/fulltext', 'searchindex_weight')) {
$this->printError(
'Search Sphinx: Search index is not valid',
"",
'Please run full search reindex at System / Index Management'
);
}
}

public function testExecIsEnabled()
{
if (!function_exists('exec')) {
$this->printError(
"Search Sphinx: The function 'exec' is not enabled",
"",
"The function 'exec' is disabled. Please, ask your hosting administrator to enable this function."
);
}
}

public function testDomainNameIsPinged()
{
if (Mage::getSingleton('searchsphinx/config')->getSearchEngine() === 'sphinx') {
if (function_exists('exec')) {
$opts = array('http' => array(
'timeout' => 3,
),
);
$context = stream_context_create($opts);
Mage::register('custom_entry_point', true, true);
$store = Mage::app()->getStore(0);
$url = parse_url($store->getUrl());
$isPinged = file_get_contents($url['scheme'].'://'.$url['host'].'/shell/search.php?ping', false, $context);
if ($isPinged !== 'ok') {
$this->printError(
'Search Sphinx: The server can\'t connect to the domain name',
"",
"Your server can't connect to the domain {$url['host']}. In the 'External Sphinx Search' mode extension can't run reindexing via backend."."To solve this issue, you need to ask your hosting administrator to add record '127.0.0.1 {$url['host']}' to the file /etc/hosts."
);
}
}
}
}

public function testProductIndexConfigured()
{
if ($index = Mage::helper('searchindex/index')->getIndex('mage_catalog_product')) {
$attributes = Mage::getModel('searchindex/index')->load($index->getId())->getAttributes();
if (empty($attributes)) {
$url = Mage::helper('adminhtml')->getUrl('adminhtml/searchindex_index/edit', array('id' => $index->getId()));
$this->printWarning(
'Search Sphinx: Search index "Products" is not configured',
"",
"Please configure the search index 'Products' to see more relevant search results."."For this, go to the Search / Manage Search Indexes and open index 'Products': <a href='$url' target='_blank'>$url</a>"."For more information refer to our manual: <a href='http://mirasvit.com/doc/ssu/2.3.2/r/product_index' target='_blank'>http://mirasvit.com/doc/ssu/2.3.2/r/product_index</a>"
);
}
} else {
$this->printWarning(
'Search Sphinx: Search index "Products" is not configured',
"",
'First you need to create a search index for products.'
);
}
}

public function testBlockCatalogSearchLayerExists()
{
$container = $this->getHandleNodesFromLayout('catalogsearch.xml', 'catalogsearch_result_index');
if (false === array_search('catalogsearch/layer', $container)) {
$this->printWarning(
'Search Sphinx: The block "catalogsearch/layer" is not exist.',
"",
'The block "catalogsearch/layer" does not exist. Layered navigation can be missing on search results page.'
);
}
}

public function testCatalogSearchQuerySize()
{
$size = Mage::getModel('catalogsearch/query')->getCollection()->getSize();
if ($size > 50000) {
$this->printError(
'Search Sphinx: catalogsearch_query size',
"",
"The table `catalogsearch_query` is very big ($size rows). We suggest clear table for improve search performance."
);
}
}

public function testConflictExtensions()
{
$troubleshootLink = "https://docs.mirasvit.com/doc/extension_searchsphinx/current/troubleshooting";

$modules = (array) Mage::getConfig()->getNode('modules')->children();

$searchMatches = preg_grep('/(Search|search|autocomplete|autosuggest)/', array_keys($modules));
$searchModules = array_intersect_key($modules, array_flip($searchMatches));

$thirdPartyMatches = preg_grep('/^((?!Mirasvit|Mage|research|Research).)*$/', array_keys($searchModules));

$thirdPartyModules = array_intersect_key($searchModules, array_flip($thirdPartyMatches));

$nonSearchModules = array('MageWorx_CustomOptions','Netzarbeiter_GroupsCatalog2','Mana_Filters');

foreach ($nonSearchModules as $moduleName) {
if (Mage::helper('mstcore')->isModuleInstalled($moduleName)) {
if ($this->validateRewrite('catalogsearch_resource/fulltext_collection',
'Mirasvit_SearchIndex_Model_Catalogsearch_Resource_Fulltext_Collection') !== true
) {
$this->printError(
'Search Index: Conflicts with another extensions',
"",
$moduleName ." is installed. Please disable this extension or solve conflict between collection models as described"
." in our <a href='$troubleshootLink'>manual</a> (preferred)."
);
}
}
}

if (!empty($thirdPartyModules)) {
foreach ($thirdPartyModules as $moduleName => $values) {
if ($values->is('active')) {
$this->printError(
'Search Index: Conflicts with another extensions',
"",
$moduleName." is installed. If you have problems with your search please disable this extension. "
. "Also you can check the conflict solution in our <a href='$troubleshootLink'>manual</a>."
);
}
}
}
}

public function testFulltextCollectionRewrite()
{
$validateRewrite = $this->validateRewrite(
'catalogsearch_resource/fulltext_collection',
'Mirasvit_SearchIndex_Model_Catalogsearch_Resource_Fulltext_Collection'
);

if ($validateRewrite !== true) {
$this->printError(
'Search Index: Check Rewrites',
"",
$validateRewrite
);
}
}

public function validateRewrite($class, $classNameB)
{
$object = Mage::getModel($class);
if ($object instanceof $classNameB) {
return true;
} else {
return "$class must be $classNameB, current rewrite is " . get_class($object);
}
}
}<?php

class Mirasvit_Command_BaseCommand
{

}
<?php

namespace Mirasvit\Service;

class EnvService
{





public function get($key = '')
{
$basePath = getcwd();

$envPath = "$basePath/app/etc/env.php";

if (!file_exists($envPath)) {
throw new \Exception("Wrong working directory. Env file doesn't exists: $envPath");
}

$config = require $envPath;
if ($key) {
return $this->_get(explode('/', $key), $config);
}

return $config;
}

protected function _get($keys, $scope)
{
foreach ($keys as $key) {
if (isset($scope[$key])) {
$scope = $scope[$key];
} else {
return false;
}
}

return $scope;
}
}<?php

namespace Mirasvit\Service;

class ConfigService extends EnvService
{



public function get($key = '')
{
$config = json_decode(file_get_contents(CLI_ROOT . '/config.json'), true);

if ($key) {
return $this->_get(explode('/', $key), $config);
}

return $config;
}
}{"application":{"name":"Mirasvit Debug Tool for Magento 2","version":"1.0.10","manifest_url":"https:\/\/raw.githubusercontent.com\/mirasvit\/tool-debug-pub\/master\/manifest.json","version_url":"https:\/\/raw.githubusercontent.com\/mirasvit\/tool-debug-pub\/master\/VERSION","base_url":"https:\/\/raw.githubusercontent.com\/mirasvit\/tool-debug-pub\/master\/"}}[��ƀ1iVUݎ��(C   GBMB