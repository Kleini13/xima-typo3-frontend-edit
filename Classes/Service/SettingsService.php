<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Service;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\TypoScriptAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

final class SettingsService
{
    protected array $configuration = [];

    public function getIgnoredPids(): array
    {
        $configuration = $this->getConfiguration();
        return array_key_exists('ignorePids', $configuration) ? explode(',', $configuration['ignorePids']) : [];
    }

    public function getIgnoredCTypes(): array
    {
        $configuration = $this->getConfiguration();
        return array_key_exists('ignoreCTypes', $configuration) ? explode(',', $configuration['ignoreCTypes']) : [];
    }

    public function getIgnoredListTypes(): array
    {
        $configuration = $this->getConfiguration();
        return array_key_exists('ignoreListTypes', $configuration) ? explode(',', $configuration['ignoreListTypes']) : [];
    }

    public function getIgnoredUids(): array
    {
        $configuration = $this->getConfiguration();
        return array_key_exists('ignoredUids', $configuration) ? explode(',', $configuration['ignoredUids']) : [];
    }

    public function getShowElementInfo(): bool
    {
        $configuration = $this->getConfiguration();
        return array_key_exists('showElementInfo', $configuration) && (bool)$configuration['showElementInfo'];
    }

    public function getShowMore(): bool
    {
        $configuration = $this->getConfiguration();
        return array_key_exists('showMore', $configuration) && (bool)$configuration['showMore'];
    }

    public function checkDefaultMenuStructure(string $identifier): bool
    {
        $configuration = $this->getConfiguration();

        if (!array_key_exists('defaultMenuStructure', $configuration)) {
            return true;
        }

        return array_key_exists($identifier, $configuration['defaultMenuStructure']) && $configuration['defaultMenuStructure'][$identifier];
    }

    public function checkSimpleModeMenuStructure(): bool
    {
        $configuration = $this->getConfiguration();

        if (!array_key_exists('defaultMenuStructure', $configuration)) {
            return false;
        }

        $menuStructure = $configuration['defaultMenuStructure'];

        if (!is_array($menuStructure) || empty($menuStructure)) {
            return false;
        }

        foreach ($menuStructure as $key => $value) {
            if ($key === 'edit' && (int)$value !== 1) {
                return false;
            }
            if ($key !== 'edit' && (int)$value !== 0) {
                return false;
            }
        }

        return true;
    }

    private function getConfiguration(): array
    {
        if (!empty($this->configuration)) {
            return $this->configuration;
        }

        try {
            if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '12.0.0', '<')) {
                $fullTypoScript = $this->getTypoScriptSetupArrayV11();
            } else {
                $fullTypoScript = $this->getTypoScriptSetupArrayV12($GLOBALS['TYPO3_REQUEST']);
            }

            $settings = $fullTypoScript['plugin.']['tx_ximatypo3frontendedit.']['settings.'] ?? [];
            $this->configuration = GeneralUtility::removeDotsFromTS($settings);
        } catch (\Exception $e) {
            error_log('Frontend Edit SettingsService: Failed to get TypoScript configuration: ' . $e->getMessage());
            // Return empty configuration as fallback
            $this->configuration = [];
        }

        return $this->configuration;
    }

    /**
    * These methods need to handle the case that the TypoScript setup array is not available within full cached setup.
    * I used this workaround from https://github.com/derhansen/fe_change_pwd to ensure that the TypoScript setup is available.
    *
    * @return array
    */
    private function getTypoScriptSetupArrayV11(): array
    {
        // Ensure, TSFE setup is loaded for cached pages
        if ($GLOBALS['TSFE']->tmpl === null || ($GLOBALS['TSFE']->tmpl && empty($GLOBALS['TSFE']->tmpl->setup))) {
            GeneralUtility::makeInstance(Context::class)
                ->setAspect('typoscript', GeneralUtility::makeInstance(TypoScriptAspect::class, true));
            $GLOBALS['TSFE']->getConfigArray();
        }
        return $GLOBALS['TSFE']->tmpl->setup;
    }

    private function getTypoScriptSetupArrayV12(ServerRequestInterface $request): array
    {
        try {
            // Try to get TypoScript from request attribute first
            $typoscriptAttribute = $request->getAttribute('frontend.typoscript');
            if ($typoscriptAttribute) {
                return $typoscriptAttribute->getSetupArray();
            }
            
            // Fallback: try to get from TSFE if available
            if (isset($GLOBALS['TSFE']) && $GLOBALS['TSFE']->tmpl && !empty($GLOBALS['TSFE']->tmpl->setup)) {
                return $GLOBALS['TSFE']->tmpl->setup;
            }
            
            throw new \Exception('No TypoScript setup available');
            
        } catch (\Exception $e) {
            error_log('Frontend Edit SettingsService: TypoScript access failed: ' . $e->getMessage());
            
            try {
                // An exception is thrown, when TypoScript setup array is not available. This is usually the case,
                // when the current page request is cached. Therefore, the TSFE TypoScript parsing is forced here.

                // Set a TypoScriptAspect which forces template parsing
                GeneralUtility::makeInstance(Context::class)
                    ->setAspect('typoscript', GeneralUtility::makeInstance(TypoScriptAspect::class, true));
                
                $tsfe = $request->getAttribute('frontend.controller');
                if (!$tsfe) {
                    throw new \Exception('No frontend controller available');
                }
                
                $requestWithFullTypoScript = $tsfe->getFromCache($request);
                if (!$requestWithFullTypoScript) {
                    throw new \Exception('Failed to get request from cache');
                }

                // Call TSFE getFromCache, which re-processes TypoScript respecting $forcedTemplateParsing property
                // from TypoScriptAspect
                $typoscriptAttribute = $requestWithFullTypoScript->getAttribute('frontend.typoscript');
                if (!$typoscriptAttribute) {
                    throw new \Exception('No TypoScript attribute in cached request');
                }
                
                return $typoscriptAttribute->getSetupArray();
                
            } catch (\Exception $fallbackException) {
                error_log('Frontend Edit SettingsService: TypoScript fallback also failed: ' . $fallbackException->getMessage());
                // Return empty array as last resort
                return [];
            }
        }
    }
}
