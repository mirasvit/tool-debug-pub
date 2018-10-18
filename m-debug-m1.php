<?php


class Mirasvit_Application
{
    public $registeredCommands = [];

    public function __construct()
    {
        $this->addCommands([
            new Mirasvit_Command_Validate_ValidateCommand(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function addCommands(array $commands)
    {
        foreach ($commands as $command) {
            $this->registeredCommands[] = $command;
        }
    }

    /**
     * @return BaseCommand[]
     */
    public function getCommands()
    {
        return $this->registeredCommands;
    }

    public function run()
    {
        return $this->registeredCommands[0]->execute();
    }
}


class Mirasvit_Command_BaseCommand
{

}



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
            new Mirasvit_Command_Validate_Validators_FpcValidator(),
        ];
        /** @var Mirasvit_Command_Validate_Validators_SearchValidator $validator */
        foreach ($validators as $validator) {
            if (!Mage::getConfig()->getModuleConfig($validator->moduleName())->is('active', 'true')) {
                continue;
            }
            $validator->printH1("=== ".$validator->moduleName()." ===");
            $validator->run();
        }

        $validator->printH1("-- DONE --");
    }
}


//./extension_giftr/package/app/code/local/Mirasvit/Giftr/Helper/Validator.php
//./extension_seoautolink/package/app/code/local/Mirasvit/SeoAutolink/Helper/Validator.php
//./extension_fpc/package/app/code/local/Mirasvit/Fpc/Model/Validator.php
//./extension_fpc/package/app/code/local/Mirasvit/Fpc/Helper/Validator.php
//./extension_fpc/package/app/code/local/Mirasvit/FpcCrawler/Helper/Validator.php
//./extension_kb/package/app/code/local/Mirasvit/Kb/Helper/Validator.php
//./extension_rewards/package/app/code/local/Mirasvit/Rewards/Model/Rewrite/SalesRule/Validator.php
//./extension_rewards/package/app/code/local/Mirasvit/Rewards/Helper/Validator.php
//./extension_searchsphinx/package/app/code/local/Mirasvit/SearchSphinx/Helper/Validator.php
//./extension_searchsphinx/package/app/code/local/Mirasvit/SearchIndex/Helper/Validator.php
//./extension_action/package/app/code/local/Mirasvit/Action/Helper/Validator.php
//./extension_asyncindex/package/app/code/local/Mirasvit/AsyncIndex/Helper/Validator.php
//./extension_autocomplete/package/app/code/local/Mirasvit/SearchAutocomplete/Helper/Validator.php
//./extension_email/package/app/code/local/Mirasvit/Email/Helper/Validator.php
//./extension_email/package/app/code/local/Mirasvit/EmailSmtp/Helper/Validator.php
//./extension_email/package/app/code/local/Mirasvit/EmailDesign/Helper/Validator.php
//./extension_misspell/package/app/code/local/Mirasvit/Misspell/Helper/Validator.php
//./extension_seo/package/app/code/local/Mirasvit/Seo/Helper/Validator.php
//./extension_rma/package/app/code/local/Mirasvit/Rma/Helper/Validator.php
//./extension_seositemap/package/app/code/local/Mirasvit/SeoSitemap/Helper/Validator.php
//./extension_feedexport/package/app/code/local/Mirasvit/FeedExport/Model/Dynamic/Attribute/Validator.php
//./extension_feedexport/package/app/code/local/Mirasvit/FeedExport/Helper/Validator.php
//./extension_advn/package/app/code/local/Mirasvit/EmailSmtp/Helper/Validator.php
//./extension_mcore/package/app/code/local/Mirasvit/MstCore/Helper/Validator.php
//./extension_mcore/package/app/code/local/Mirasvit/MstCore/Block/Adminhtml/Validator.php
//./extension_advr/package/app/code/local/Mirasvit/Advd/Helper/Validator.php
//./extension_helpdesk/package/app/code/local/Mirasvit/Helpdesk/Helper/Validator.php

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
    /**
     * @param string $layoutName - e.g. catalogsearch.xml
     * @param string $handleName - e.g. catalogsearch_result_index
     * @return array $container  - one-dimensional array with nodes
     */
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
}


class Mirasvit_Command_Validate_Validators_FpcValidator extends Mirasvit_Command_Validate_Validators_BaseValidator
{

    public function moduleName()
    {
       return "Mirasvit_Fpc";
    }

    public function run()
    {
        $this->testTablesExists();
        $this->testPhpConfiguration();
        $this->testConflicts();
        $this->testSimilarExtensions();
        $this->testCompatibility();
        $this->testDomainNameIsPinged();
    }

    public function testDomainNameIsPinged()
    {
        $opts = array('http' => array(
            'timeout' => 3,
        ),
        );
        $context = stream_context_create($opts);
        Mage::register('custom_entry_point', true, true);
        $store = Mage::app()->getStore(0);
        $url = parse_url($store->getUrl());
        $u = $url['scheme'].'://'.$url['host'].'/';
        $isPinged = file_get_contents($u, false, $context);

        if (strpos("/skin/frontend/") === false) {
            $this->printError(
                "Your server can't connect to the domain {$url['host']}.",
                "Cralwer can't work",
                "Make sure that command 'curl {$url['host']}' returns valid html page"
            );
        }

    }


    public function testPhpConfiguration()
    {
        $memoryLimit = ini_get('memory_limit');
        $memoryLimit = (int) $memoryLimit;
        if ($memoryLimit < 256 && $memoryLimit != -1) {
            $this->printError(
                "Low memory limit",
                "",
                'You need to increase memory limit to at least 256M (512M or higher is recommended). Current value is '.$memoryLimit.'.');

        }
    }
    public function testTablesExists()
    {
        $tables = array(
            'fpccrawler/crawler_url',
            'fpccrawler/crawlerlogged_url',
            'fpc/log',
            'fpc/log_aggregated_daily',
        );
        foreach ($tables as $table) {
            if (!$this->dbTableExists($table)) {
                $this->printError(
                    "Table '$table' does not exist",
                    "",
                    '');
            }
        }
        return array($result, $title, $description);
    }
    public function testConflicts()
    {
        if (Mage::helper('mstcore')->isModuleInstalled('Devinc_Gomobile')) {
            $this->printError(
                "Conflict",
                "",
                'Devinc Gomobile installed. If you see folowing code "Mage::app()->getCacheInstance()->flush();"'.
                'in file /app/code/community/Devinc/Gomobile/Model/Observer.php, please comment out it and contact to developers of the extension or disable this extension.'.
                "This code periodically flush cache, so Full Page Cache can't work correctly."
        );
        }
        if (Mage::helper('mstcore')->isModuleInstalled('Lesti_Fpc')) {
            $this->printError(
                "Conflict",
                "Full Page Cache can't work correctly with Lesti Fpc installed.",
                'Lesti Fpc installed. Please, disable the extension in file /app/etc/modules/Lesti_Fpc.xml. Then flush all cache.'
            );
        }
        if (Mage::helper('mstcore')->isModuleInstalled('Emagicone_Mobassistantconnector')) {
            $this->printError(
                "Conflict",
                'Extension Emagicone Mobassistantconnector installed. If FPC flush cache very often without visible reason the reason can be in Emagicone_Mobassistantconnector extension.',
                'To fix the issue in file /app/code/community/Emagicone/Mobassistantconnector/controllers/IndexController.php comment line  Mage::app()->cleanCache();'
            );
        }
        if (Mage::helper('mstcore')->isModuleInstalled('Aitoc_Aitsys')) {
            $this->printError(
                "Conflict",
                "Extension Aitoc_Aitsys installed. If FPC don't cache pages without visible reason the reason can be in Aitoc_Aitsys extension.",
                'To fix the issue in file /app/code/community/Aitoc/Aitsys/Abstract/Service.php comment line $this->getCache()->flush();'
            );
        }
        if (Mage::helper('mstcore')->isModuleInstalled('Softag_Powerdash')) {
            $this->printError(
                "Conflict",
                'Extension Softag_Powerdash installed. If FPC flush cache very often without visible reason the reason can be in Softag_Powerdash extension.',
                'To fix the issue in file /app/code/community/Softag/Powerdash/Helper/Data.php comment line Mage::app()->getCache()->clean(\'all\', array(self::CACHE_TAG));'
            );
        }
        if (Mage::helper('mstcore')->isModuleInstalled('Mci_Core')) {
            $this->printError(
                "Conflict",
                "Extension Mci_Core installed. If FPC don't cache pages without visible reason the reason can be in Mci_Core extension.",
                "To fix the issue in file //app/code/community/Mci/Core/Model/Observer.php comment line _967976c690de23ce4d148bc33b3fb384(true);"
            );
        }
        if (Mage::helper('mstcore')->isModuleInstalled('GoMage_Procart')) {
            $this->printError(
                "Conflict",
                '',
                "Extension GoMage_Procart installed. To update cart correctly you need do <a href='https://docs.mirasvit.com/doc/extension_fpc/current/faq#gomage_procart_compatibility'>following steps</a>"
            );
        }
        if (Mage::helper('mstcore')->isModuleInstalled('Cmsmart_Megamenu')) {
            $this->printError(
                "Conflict",
                'Extension Cmsmart_Megamenu installed. FPC crawler can\'t work incorrectly.',
                'To fix the issue in file /app/code/local/Cmsmart/Megamenu/Block/Navigation.php delete first empty line before php tag.'
            );
        }
        if (Mage::helper('mstcore')->isModuleInstalled('MailUp_MailUpSync')) {
            $this->printError(
                "Conflict",
                'Extension MailUp_MailUpSync installed. If FPC flush cache without visible reason the reason can be in MailUp_MailUpSync extension.',
                'To fix the issue in file /app/code/local/MailUp/MailUpSync/Model comment line Mage::app()->cleanCache();'
            );
        }
        if (Mage::helper('mstcore')->isModuleInstalled('Netreviews_Avisverifies')) {
            $this->printError(
                "Conflict",
                'Extension Netreviews_Avisverifies installed. If FPC flush cache without visible reason the reason can be in Netreviews_Avisverifies extension.',
                'To fix the issue in file /app/code/local/Netreviews/Avisverifies/controllers/DialogController.php comment lines Mage::app()->cleanCache();'
            );
        }
    }

    public function testSimilarExtensions()
    {
        $modules = array_keys((array) Mage::getConfig()->getNode('modules')->children());
        foreach ($modules as $module) {
            if (stripos($module, 'fpc') !== false && $module != 'Mirasvit_Fpc' && $module != 'Mirasvit_FpcCrawler') {
                $this->printError(
                    "Conflicts with similar extensions",
                    "Another FPC extension '$module' installed",
                    'Please remove it.'
                );
            }
        }
    }

    public function testCompatibility()
    {
        if (Mage::helper('mstcore')->isModuleInstalled('Simple_Forum')) {

            $description = array();
            $description[] = 'If you want cache forum page add in System->Configuration->Full Page Cache->Cachable Actions: forum/topic_index, forum/topic_view, forum/index_index.';
            $description[] = "And in System->Configuration->Full Page Cache->Ignored Pages: /forum/\like/\like/";
            $description[] = 'To enable autoflush when user like post or add post comment out code <br/>
             $content = Mage::helper(\'fpc/simpleforum\')->prepareContent($content);<br/>
             and<br/>
             if ($topicCacheId = Mage::helper(\'fpc/simpleforum\')->getSimpleForumCacheId()) {<br/>
             &nbsp;&nbsp;&nbsp;   $this->_requestId .= $del . $topicCacheId;<br/>
             }<br/>
             in file /app/code/local/Mirasvit/Fpc/Model/Processor.php (Simple_Forum extension compatibility).
             ';
            $this->printWarning(
                "Compatibility with extensions",
                "Another FPC extension '$module' installed",
                implode("<br>", $description)
            );
        }
        if (Mage::helper('mstcore')->isModuleInstalled('Jn2_Cache')) {
            $this->printWarning(
                "Compatibility with extensions",
                "You use magento 'JN2 solution'",
                "Add /cache-get=/ in Ignored Pages ( System->Full Page Cache->Settings )."
            );
        }
        if (Mage::helper('mstcore')->isModuleInstalled('Xtento_StockImport')) {
            $this->printWarning(
                "Compatibility with extensions",
                "Xtento_StockImport is installed",
                'For Full Page Caching to update stock properly, please set "Cache Level" to "Default"
                    and do one of the following:
                    1) Set Catalog->Stock Import->Import Profiles->Import Settings->"Reindex mode"
                    to "Full reindex (after import)" or 2) Set FPC > Configuration > "Update stock method"
                    to "Update during full reindex". Please note: second solution will //require manual reindex
                    of "Stock Status" after import using Xtento_StockImport extension.'
            );
        }
        if (Mage::helper('mstcore')->isModuleInstalled('Mirasvit_Action')) {
            $this->printWarning(
                "Compatibility with extensions",
                "Mirasvit Promotional Offers is installed",
                'To exclude Mirasvit Promotional Offers timer from FPC cache you need uncomment block action_view_timer (action/link)
             in file /app/code/local/Mirasvit/Fpc/etc/cache.xml.'
            );
        }
        if (Mage::helper('mstcore')->isModuleInstalled('WP_SmartColumns')
            && $this->isBlockExcluded('catalog_product_list', 'catalog/product_list', 'product_list')) {
            $this->printWarning(
                "Compatibility with extensions",
                "WP_SmartColumns is installed",
                'To exclude \'catalog/product_list\'
                block you need change in file /app/code/community/WP/SmartColumns/Block/Toolbar.php
                private function _getLayoutFileName() line "$template = $this->getLayout()->getBlock(\'root\')->getTemplate();"
                at "if (Mage::registry(\'current_category_id\')) {
                    $template = [direct_template_value];
                } else {
                    $template = $this->getLayout()->getBlock(\'root\')->getTemplate();
                }"
                [direct_template_value] you will see if temporarily write "echo $template"
                ([direct_template_value] can be \'page/3columns.phtml\' or similar).'
            );
        }
    }

    private function prepareVersion($version)
    {
        //fix for new versions
        $explodedVersion = explode('.', $version);
        if (isset($explodedVersion[3]) && $explodedVersion[3] == 0) {
            return str_replace('.', '', $version) * 10;
        }
        return str_replace('.', '', $version);
    }
    /**
     * @param string $block
     * @param string $name
     *
     * @return bool
     */
    private function isBlockExcluded($blockContainer, $block, $name)
    {
        $customConfig = Mage::getConfig()->loadModulesConfiguration('custom.xml');
        $customConfigNode = $customConfig->getNode();
        $customConfigNodeAsArray = $customConfigNode->asArray();
        if ($customConfigNodeAsArray
            && isset($customConfigNodeAsArray['containers'][$blockContainer])
            && isset($customConfigNodeAsArray['containers'][$blockContainer]['block'])
            && isset($customConfigNodeAsArray['containers'][$blockContainer]['name'])
            && ($customConfigNodeAsArray['containers'][$blockContainer]['block'] == $block)
            && ($customConfigNodeAsArray['containers'][$blockContainer]['name'] == $name)
        ) {
            return true;
        }
        return false;
    }
}


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
        $this->testAmastySortingAndMDNAdvancedStock();
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
                    "Search may not find products which assigned to subcategories",
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
                'Search indexes are not exist',
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
                    'Required tables are not exists',
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
                'Search index is not valid',
                "",
                'Please run full search reindex at System / Index Management'
            );
        }
    }

    public function testExecIsEnabled()
    {
        if (!function_exists('exec')) {
            $this->printError(
                "The function 'exec' is not enabled",
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
                        "Your server can't connect to the domain {$url['host']}.",
                        "Extension can't run reindexing via backend (only in your current 'External Sphinx Search' mode)",
                        "To solve this issue, you need to ask your hosting administrator to add record '127.0.0.1 {$url['host']}' to the file /etc/hosts."
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
                    'Search index "Products" is not configured',
                    "",
                    "Please configure the search index 'Products' to see more relevant search results."."For this, go to the Search / Manage Search Indexes and open index 'Products': <a href='$url' target='_blank'>$url</a>"."For more information refer to our manual: <a href='http://mirasvit.com/doc/ssu/2.3.2/r/product_index' target='_blank'>http://mirasvit.com/doc/ssu/2.3.2/r/product_index</a>"
                );
            }
        } else {
            $this->printWarning(
                'Search index "Products" is not configured',
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
                'The block "catalogsearch/layer" is not exist.',
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
                "The table `catalogsearch_query` is very big ($size rows).",
                "Slow search",
                "We suggest clear table for improve search performance."
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
                        'Conflicts with another extensions',
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
                        'Conflicts with another extensions',
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
                'Check Rewrites',
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

    public function testAmastySortingAndMDNAdvancedStock()
    {
        if (Mage::helper('mstcore')->isModuleInstalled('Amasty_Sorting') 
            && Mage::helper('mstcore')->isModuleInstalled('MDN_AdvancedStock')
            && Mage::getStoreConfig('amsorting/general/out_of_stock_last') == 1) {
                $this->printError(
                    'Conflicts with another extensions',
                    "SQl query error : Column 'stock_id' in on clause is ambiguous",
                    'If you have problems with your search please set Show `Out of Stock` Products Last option to "Yes for Catalog, No for search" '.
                    'in System->Improved Sorting by Amasty , General section.'
                );
        }
    }
}


if (function_exists("opcache_reset")) {
    opcache_reset();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

define("CLI_ROOT", dirname(__DIR__));

//require __DIR__ . "/app/Application.php";
//require __DIR__ . "/app/Command/BaseCommand.php";
//require __DIR__ . "/app/Command/Validate/Validators/BaseValidator.php";
//require __DIR__ . "/app/Command/Validate/Validators/SearchValidator.php";
//require __DIR__ . "/app/Command/Validate/Validators/FpcValidator.php";
//require __DIR__ . "/app/Command/Validate/ValidateCommand.php";

$application = new Mirasvit_Application();
$application->run();