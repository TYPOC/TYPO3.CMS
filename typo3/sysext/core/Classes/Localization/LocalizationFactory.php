<?php
namespace TYPO3\CMS\Core\Localization;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides a language parser factory.
 */
class LocalizationFactory implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Core\Cache\Frontend\StringFrontend
     */
    protected $cacheInstance;

    /**
     * @var int
     */
    protected $errorMode;

    /**
     * @var \TYPO3\CMS\Core\Localization\LanguageStore
     */
    public $store;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->store = GeneralUtility::makeInstance(LanguageStore::class);
        $this->initializeCache();
    }

    /**
     * Initialize cache instance to be ready to use
     */
    protected function initializeCache()
    {
        $this->cacheInstance = GeneralUtility::makeInstance(CacheManager::class)->getCache('l10n');
    }

    /**
     * Returns parsed data from a given file and language key.
     *
     * @param string $fileReference Input is a file-reference (see \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName). That file is expected to be a supported locallang file format
     * @param string $languageKey Language key
     * @param string $charset Character set (option); if not set, determined by the language key
     * @param int $errorMode Error mode (when file could not be found): 0 - syslog entry, 1 - do nothing, 2 - throw an exception$
     * @param bool $isLocalizationOverride TRUE if $fileReference is a localization override
     * @return array|bool
     */
    public function getParsedData($fileReference, $languageKey, $charset = '', $errorMode = 0, $isLocalizationOverride = false)
    {
        // @deprecated since TYPO3 v8, will be removed with TYPO3 v9
        // this is a fallback to convert references to old 'lang' locallang files to the new location
        if (strpos($fileReference, 'EXT:lang/locallang_') === 0) {
            $mapping = [
                'lang/locallang_alt_doc.xlf' => 'lang/Resources/Private/Language/locallang_alt_doc.xlf',
                'lang/locallang_alt_intro.xlf' => 'lang/Resources/Private/Language/locallang_alt_intro.xlf',
                'lang/locallang_browse_links.xlf' => 'lang/Resources/Private/Language/locallang_browse_links.xlf',
                'lang/locallang_common.xlf' => 'lang/Resources/Private/Language/locallang_common.xlf',
                'lang/locallang_core.xlf' => 'lang/Resources/Private/Language/locallang_core.xlf',
                'lang/locallang_general.xlf' => 'lang/Resources/Private/Language/locallang_general.xlf',
                'lang/locallang_login.xlf' => 'lang/Resources/Private/Language/locallang_login.xlf',
                'lang/locallang_misc.xlf' => 'lang/Resources/Private/Language/locallang_misc.xlf',
                'lang/locallang_mod_admintools.xlf' => 'lang/Resources/Private/Language/locallang_mod_admintools.xlf',
                'lang/locallang_mod_file_list.xlf' => 'lang/Resources/Private/Language/locallang_mod_file_list.xlf',
                'lang/locallang_mod_file.xlf' => 'lang/Resources/Private/Language/locallang_mod_file.xlf',
                'lang/locallang_mod_help_about.xlf' => 'about/Resources/Private/Language/Modules/about.xlf',
                'lang/locallang_mod_help_cshmanual.xlf' => 'lang/Resources/Private/Language/locallang_mod_help_cshmanual.xlf',
                'lang/locallang_mod_help.xlf' => 'lang/Resources/Private/Language/locallang_mod_help.xlf',
                'lang/locallang_mod_system.xlf' => 'lang/Resources/Private/Language/locallang_mod_system.xlf',
                'lang/locallang_mod_usertools.xlf' => 'lang/Resources/Private/Language/locallang_mod_usertools.xlf',
                'lang/locallang_mod_user_ws.xlf' => 'lang/Resources/Private/Language/locallang_mod_user_ws.xlf',
                'lang/locallang_mod_web_func.xlf' => 'lang/Resources/Private/Language/locallang_mod_web_func.xlf',
                'lang/locallang_mod_web_info.xlf' => 'lang/Resources/Private/Language/locallang_mod_web_info.xlf',
                'lang/locallang_mod_web_list.xlf' => 'lang/Resources/Private/Language/locallang_mod_web_list.xlf',
                'lang/locallang_mod_web.xlf' => 'lang/Resources/Private/Language/locallang_mod_web.xlf',
                'lang/locallang_show_rechis.xlf' => 'lang/Resources/Private/Language/locallang_show_rechis.xlf',
                'lang/locallang_t3lib_fullsearch.xlf' => 'lang/Resources/Private/Language/locallang_t3lib_fullsearch.xlf',
                'lang/locallang_tca.xlf' => 'lang/Resources/Private/Language/locallang_tca.xlf',
                'lang/locallang_tcemain.xlf' => 'lang/Resources/Private/Language/locallang_tcemain.xlf',
                'lang/locallang_tsfe.xlf' => 'lang/Resources/Private/Language/locallang_tsfe.xlf',
                'lang/locallang_tsparser.xlf' => 'lang/Resources/Private/Language/locallang_tsparser.xlf',
                'lang/locallang_view_help.xlf' => 'lang/Resources/Private/Language/locallang_view_help.xlf',
                'lang/locallang_wizards.xlf' => 'lang/Resources/Private/Language/locallang_wizards.xlf',
            ];
            $filePath = substr($fileReference, 4);
            GeneralUtility::deprecationLog('There is a reference to "' . $fileReference . '", which has been moved to "EXT:' . $mapping[$filePath] . '". This fallback will be removed with TYPO3 v9.');
            $fileReference = 'EXT:' . $mapping[$filePath];
        }

        $hash = md5($fileReference . $languageKey . $charset);
        $this->errorMode = $errorMode;

        // Check if the default language is processed before processing other language
        if (!$this->store->hasData($fileReference, 'default') && $languageKey !== 'default') {
            $this->getParsedData($fileReference, 'default', $charset, $this->errorMode);
        }
        // If the content is parsed (local cache), use it
        if ($this->store->hasData($fileReference, $languageKey)) {
            return $this->store->getData($fileReference);
        }

        // If the content is in cache (system cache), use it
        $data = $this->cacheInstance->get($hash);
        if ($data !== false) {
            $this->store->setData($fileReference, $languageKey, $data);
            return $this->store->getData($fileReference);
        }

        try {
            $this->store->setConfiguration($fileReference, $languageKey, $charset);
            /** @var $parser \TYPO3\CMS\Core\Localization\Parser\LocalizationParserInterface */
            $parser = $this->store->getParserInstance($fileReference);
            // Get parsed data
            $LOCAL_LANG = $parser->getParsedData($this->store->getAbsoluteFileReference($fileReference), $languageKey);
        } catch (Exception\FileNotFoundException $exception) {
            // Source localization file not found, set empty data as there could be an override
            $this->store->setData($fileReference, $languageKey, []);
            $LOCAL_LANG = $this->store->getData($fileReference);
        }

        // Override localization
        if (!$isLocalizationOverride && isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'])) {
            $this->localizationOverride($fileReference, $languageKey, $charset, $errorMode, $LOCAL_LANG);
        }

        // Save parsed data in cache
        $this->store->setData($fileReference, $languageKey, $LOCAL_LANG[$languageKey]);

        // Cache processed data
        $this->cacheInstance->set($hash, $this->store->getDataByLanguage($fileReference, $languageKey));

        return $this->store->getData($fileReference);
    }

    /**
     * Override localization file
     *
     * This method merges the content of the override file with the default file
     *
     * @param string $fileReference
     * @param string $languageKey
     * @param string $charset
     * @param int $errorMode
     * @param array $LOCAL_LANG
     */
    protected function localizationOverride($fileReference, $languageKey, $charset, $errorMode, array &$LOCAL_LANG)
    {
        $overrides = [];
        $fileReferenceWithoutExtension = $this->store->getFileReferenceWithoutExtension($fileReference);
        $locallangXMLOverride = $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'];
        foreach ($this->store->getSupportedExtensions() as $extension) {
            if (isset($locallangXMLOverride[$languageKey][$fileReferenceWithoutExtension . '.' . $extension]) && is_array($locallangXMLOverride[$languageKey][$fileReferenceWithoutExtension . '.' . $extension])) {
                $overrides = array_merge($overrides, $locallangXMLOverride[$languageKey][$fileReferenceWithoutExtension . '.' . $extension]);
            } elseif (isset($locallangXMLOverride[$fileReferenceWithoutExtension . '.' . $extension]) && is_array($locallangXMLOverride[$fileReferenceWithoutExtension . '.' . $extension])) {
                $overrides = array_merge($overrides, $locallangXMLOverride[$fileReferenceWithoutExtension . '.' . $extension]);
            }
        }
        if (!empty($overrides)) {
            foreach ($overrides as $overrideFile) {
                $languageOverrideFileName = GeneralUtility::getFileAbsFileName($overrideFile);
                ArrayUtility::mergeRecursiveWithOverrule($LOCAL_LANG, $this->getParsedData($languageOverrideFileName, $languageKey, $charset, $errorMode, true));
            }
        }
    }
}
