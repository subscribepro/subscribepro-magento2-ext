<?php

namespace Swarming\SubscribePro\Block\Adminhtml\System\Config;

use GuzzleHttp\Exception\ClientException;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Framework\Xml\Parser;

class InstalledVersion extends \Magento\Config\Block\System\Config\Form\Field
{
    const PACKAGE_NAME = 'subscribepro/subscribepro-magento2-ext';

    /**
     * @var Reader
     */
    protected $moduleDirReader;

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected $packageData;

    /**
     * @var string
     */
    protected $latestVersion;

    /**
     * @param Context $context
     * @param Reader $moduleDirReader
     * @param Parser $parser
     * @param \Psr\Log\LoggerInterface $logger
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        Context $context,
        Reader $moduleDirReader,
        Parser $parser,
        \Psr\Log\LoggerInterface $logger,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        $this->moduleDirReader = $moduleDirReader;
        $this->parser = $parser;
        $this->logger = $logger;
        $this->packageData = [];
        parent::__construct($context, $data, $secureRenderer);
    }

    /**
     * Returns element html - Entrypoint!
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $currentVersion = $this->getVersionFromXml();
        if (false === $currentVersion) {
            $element->setValue('Cannot parse version from extension files.');
            return $element->getValue();
        }
        $element->setValue($currentVersion);

        $latestVersion = $this->getLatestVersion();

        return $this->getVersionStringFormatted($latestVersion, $currentVersion);
    }

    protected function getVersionStringFormatted($latestVersion, $currentVersion)
    {
        $isLatestVersion = $this->isLatestVersion($latestVersion, $currentVersion);
        $versionStringColor = $this->getVersionStringColor($latestVersion, $currentVersion);

        $versionString = '<strong style="color:' . $versionStringColor . ';">' . $currentVersion . '</strong>';
        $releasesUrl = 'https://github.com/subscribepro/subscribepro-magento2-ext/releases';
        $viewReleasesLink = '[<a href="' . $releasesUrl . '" target="_blank">View Releases</a>]';
        $releaseNotesLink = '[<a href="' . $releasesUrl . '/tag/' . $latestVersion . '" target="_blank">Release Notes</a>]';
        if (!$isLatestVersion) {
            $versionString .= ' - ' . $viewReleasesLink;
            $versionString .= '<br /><strong>Latest Version: ' . $latestVersion . '</strong>';
        } else {
            $versionString .= ' - ' . $releaseNotesLink;
        }
        return $versionString;
    }

    /**
     * @return string
     */
    protected function getVersionFromXml()
    {
        $filePath = $this->moduleDirReader->getModuleDir('etc', 'Swarming_SubscribePro') . '/module.xml';
        $parsedArray = $this->parser->load($filePath)->xmlToArray();
        if (empty($parsedArray['config']['_value']['module']['_attribute']['setup_version'])) {
            return false;
        }
        return $parsedArray['config']['_value']['module']['_attribute']['setup_version'];
    }

    protected function getLatestPackageData()
    {
        if (!empty($this->packageData)) {
            return $this->packageData;
        }
        // Docs: https://packagist.org/apidoc#get-package-by-name
        // GET https://repo.packagist.org/p/[vendor]/[package].json
        $client = new \GuzzleHttp\Client([
            'base_uri' => 'https://repo.packagist.org/p/'
        ]);

        try {
            $response = $client->get(self::PACKAGE_NAME . '.json');
        } catch (ClientException $e) {
            $this->logger->debug('Swarming_SubscribePro: Could not get latest version information from Packagist repo. See debug log for more info.');
            $this->logger->debug('Swarming_SubscribePro: ' . $e->getMessage());
            return $this->packageData = false;
        }

        return $this->packageData = json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param $latestVersion
     * @param $currentVersion
     * @return bool|null
     */
    protected function isLatestVersion($latestVersion, $currentVersion)
    {
        if (null === $latestVersion) {
            return null;
        }
        return $currentVersion >= $latestVersion;
    }

    /**
     * @return mixed|null
     */
    protected function getLatestVersion()
    {
        if ($this->latestVersion) {
            return $this->latestVersion;
        }
        $packageData = $this->getLatestPackageData();
        if (
            false === $packageData ||
            !isset($packageData['packages']) ||
            !isset($packageData['packages'][self::PACKAGE_NAME])
        ) {
            $this->logger->debug('Swarming_SubscribePro: Package data invalid');
            return null;
        }
        $versions = $packageData['packages'][self::PACKAGE_NAME];
        $versionKeys = array_keys($versions);
        $versionNumbers = array_filter($versionKeys, function ($value) {
            // Filter out dev and feat branches
            return strpos($value, 'dev') === false && strpos($value, 'feat') === false;
        });
        // sort $versionNumbers with version_compare
        usort($versionNumbers, 'version_compare');
        // Latest version will be at the end, so pop it off
        $latestVersion = array_pop($versionNumbers);
        $this->logger->debug('Swarming_SubscribePro: Latest version: ' . $latestVersion);
        return $this->latestVersion = $latestVersion;
    }

    /**
     * @param $latestVersion
     * @param $currentVersion
     * @return string
     */
    protected function getVersionStringColor($latestVersion, $currentVersion)
    {
        $isLatestVersion = $this->isLatestVersion($latestVersion, $currentVersion);
        if (null === $isLatestVersion) {
            return '#000000';
        }

        return $isLatestVersion ? '#155724' : '#ab2e46';
    }
}
