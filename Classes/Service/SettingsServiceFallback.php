<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Service;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Fallback SettingsService that uses extension configuration instead of TypoScript
 * This is a temporary workaround for TYPO3 v13 compatibility issues
 */
final class SettingsServiceFallback
{
    protected array $configuration = [];
    protected ExtensionConfiguration $extensionConfiguration;

    public function __construct()
    {
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
    }

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
        // Return true for all menu items by default
        return true;
    }

    public function checkSimpleModeMenuStructure(): bool
    {
        // Return false by default (use full menu)
        return false;
    }

    private function getConfiguration(): array
    {
        if (!empty($this->configuration)) {
            return $this->configuration;
        }

        try {
            // Try to get extension configuration
            $extConfig = $this->extensionConfiguration->get('xima_typo3_frontend_edit');
            $this->configuration = is_array($extConfig) ? $extConfig : [];
        } catch (\Exception $e) {
            error_log('Frontend Edit SettingsServiceFallback: Failed to get extension configuration: ' . $e->getMessage());
            // Use safe defaults
            $this->configuration = [
                'ignorePids' => '',
                'ignoreCTypes' => '',
                'ignoreListTypes' => '',
                'ignoredUids' => '',
                'showElementInfo' => false,
                'showMore' => false,
            ];
        }

        return $this->configuration;
    }
}
