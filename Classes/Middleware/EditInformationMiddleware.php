<?php

declare(strict_types=1);

namespace Xima\XimaTypo3FrontendEdit\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use Xima\XimaTypo3FrontendEdit\Configuration;
use Xima\XimaTypo3FrontendEdit\Service\MenuGenerator;
use Xima\XimaTypo3FrontendEdit\Utility\UrlUtility;

class EditInformationMiddleware implements MiddlewareInterface
{
    protected array $configuration;

    public function __construct(protected readonly MenuGenerator $menuGenerator, private readonly ExtensionConfiguration $extensionConfiguration)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $params = $request->getQueryParams();

        if (isset($params['type']) && $params['type'] === Configuration::TYPE) {
            try {
                $this->configuration = $this->extensionConfiguration->get(Configuration::EXT_KEY);

                $routing = $request->getAttribute('routing');
                $language = $request->getAttribute('language');
                
                if (!$routing) {
                    return new JsonResponse(['error' => 'No routing information available'], 400);
                }
                
                if (!$language) {
                    return new JsonResponse(['error' => 'No language information available'], 400);
                }

                $pid = $routing->getPageId();
                $languageUid = $language->getLanguageId();
                $returnUrl = ($request->getHeaderLine('Referer') === '' || (array_key_exists('forceReturnUrlGeneration', $this->configuration) && $this->configuration['forceReturnUrlGeneration'])) ? UrlUtility::getUrl($pid, $languageUid) : $request->getHeaderLine('Referer');

                $bodyContents = $request->getBody()->getContents();
                $data = json_decode($bodyContents, true) ?? [];
                
                if ($bodyContents && json_last_error() !== JSON_ERROR_NONE) {
                    return new JsonResponse(['error' => 'Invalid JSON data'], 400);
                }

                if (!$this->checkBackendUserPageAccess((int)$pid)) {
                    return new JsonResponse(['error' => 'Access denied'], 403);
                }

                $dropdownData = $this->menuGenerator->getDropdown(
                    (int)$pid,
                    $returnUrl,
                    (int)$languageUid,
                    $data
                );

                return new JsonResponse(
                    mb_convert_encoding($dropdownData, 'UTF-8')
                );
            } catch (\Exception $e) {
                error_log('Frontend Edit: Exception in middleware: ' . $e->getMessage());
                error_log('Frontend Edit: Exception trace: ' . $e->getTraceAsString());
                return new JsonResponse(['error' => 'Internal server error: ' . $e->getMessage()], 500);
            }
        }

        return $response;
    }

    private function checkBackendUserPageAccess(int $pid): bool
    {
        /* @var $backendUser \TYPO3\CMS\Core\Authentication\BackendUserAuthentication */
        $backendUser = $GLOBALS['BE_USER'];
        if ($backendUser->user === null) {
            Bootstrap::initializeBackendAuthentication();
            $backendUser->initializeUserSessionManager();
            $backendUser = $GLOBALS['BE_USER'];
        }

        if (!BackendUtility::readPageAccess(
            $pid,
            $backendUser->getPagePermsClause(Permission::PAGE_SHOW)
        )) {
            return false;
        }
        return true;
    }
}
