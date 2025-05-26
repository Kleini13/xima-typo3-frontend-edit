<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Service;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Modern SettingsService using TYPO3 v13 Site Configuration
 * Replaces TypoScript-based configuration with Site Configuration for better performance
 */
final class SiteConfigurationService
{
    protected array $configuration = [];
    protected ?Site $currentSite = null;

    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration
    ) {
    }

    public function getIgnoredPids(): array
    {
        $configuration = $this->getConfiguration();
        return array_key_exists('ignorePids', $configuration) ? 
            GeneralUtility::intExplode(',', $configuration['ignorePids'], true) : [];
    }

    public function getIgnoredCTypes(): array
    {
        $configuration = $this->getConfiguration();
        return array_key_exists('ignoreCTypes', $configuration) ? 
            GeneralUtility::trimExplode(',', $configuration['ignoreCTypes'], true) : [];
    }

    public function getIgnoredListTypes(): array
    {
        $configuration = $this->getConfiguration();
        return array_key_exists('ignoreListTypes', $configuration) ? 
            GeneralUtility::trimExplode(',', $configuration['ignoreListTypes'], true) : [];
    }

    public function getIgnoredUids(): array
    {
        $configuration = $this->getConfiguration();
        return array_key_exists('ignoredUids', $configuration) ? 
            GeneralUtility::intExplode(',', $configuration['ignoredUids'], true) : [];
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

        return array_key_exists($identifier, $configuration['defaultMenuStructure']) && 
               (bool)$configuration['defaultMenuStructure'][$identifier];
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

    /**
     * Initialize the service with the current site from request
     */
    public function initializeFromRequest(ServerRequestInterface $request): void
    {
        $this->currentSite = $request->getAttribute('site');
        // Reset configuration cache when site changes
        $this->configuration = [];
    }

    private function getConfiguration(): array
    {
        if (!empty($this->configuration)) {
            return $this->configuration;
        }

        // Priority 1: Site-specific configuration
        $siteConfig = $this->getSiteConfiguration();
        
        // Priority 2: Extension configuration as fallback
        $extensionConfig = $this->getExtensionConfiguration();
        
        // Merge configurations with site config taking priority
        $this->configuration = array_merge($extensionConfig, $siteConfig);

        return $this->configuration;
    }

    private function getSiteConfiguration(): array
    {
        if (!$this->currentSite) {
            return [];
        }

        $siteSettings = $this->currentSite->getSettings();
        
        // Look for frontend edit settings in site configuration
        return $siteSettings->get('frontendEdit', []);
    }

    private function getExtensionConfiguration(): array
    {
        try {
            $config = $this->extensionConfiguration->get('xima_typo3_frontend_edit');
            return is_array($config) ? $config : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get configuration for a specific site (useful for multi-site setups)
     */
    public function getConfigurationForSite(Site $site): array
    {
        $originalSite = $this->currentSite;
        $originalConfig = $this->configuration;
        
        $this->currentSite = $site;
        $this->configuration = [];
        
        $config = $this->getConfiguration();
        
        // Restore original state
        $this->currentSite = $originalSite;
        $this->configuration = $originalConfig;
        
        return $config;
    }

    /**
     * Check if frontend editing is enabled for the current site
     */
    public function isFrontendEditEnabled(): bool
    {
        $configuration = $this->getConfiguration();
        return !array_key_exists('disabled', $configuration) || !(bool)$configuration['disabled'];
    }

    /**
     * Get allowed user groups for frontend editing
     */
    public function getAllowedUserGroups(): array
    {
        $configuration = $this->getConfiguration();
        return array_key_exists('allowedUserGroups', $configuration) ? 
            GeneralUtility::intExplode(',', $configuration['allowedUserGroups'], true) : [];
    }
}
