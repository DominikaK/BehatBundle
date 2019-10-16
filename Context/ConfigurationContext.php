<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\BehatBundle\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Exception;
use EzSystems\BehatBundle\Helper\ConfigurationEditor;
use Symfony\Component\Yaml\Yaml;

class ConfigurationContext implements Context
{
    private const SITEACCESS_KEY_FORMAT = 'ezpublish.system.%s.%s';
    private const SITEACCESS_MATCHER_KEY_FORMAT = 'ezpublish.siteaccess.match.%s';
    private $ezplatformConfigFilePath;

    /**
     * @injectService $projectDir %kernel.project_dir%
     */
    public function __construct(string $projectDir)
    {
        $this->ezplatformConfigFilePath = sprintf('%s/app/config/ezplatform.yml', $projectDir);
    }

    /**
     * @Given I add a siteaccess :siteaccessName to :siteaccessGroup with settings
     */
    public function iAddSiteaccessWithSettings($siteaccessName, $siteaccessGroup, TableNode $settings)
    {
        $configurationEditor = new ConfigurationEditor();

        $config = $configurationEditor->getConfigFromFile($this->ezplatformConfigFilePath);

        $config = $configurationEditor->append($config, 'ezpublish.siteaccess.list', $siteaccessName);
        $config = $configurationEditor->append($config, sprintf('ezpublish.siteaccess.groups.%s', $siteaccessGroup), $siteaccessName);

        foreach ($settings->getHash() as $setting) {
            $key = $setting['key'];
            $value = $this->parseSetting($setting['value']);
            $config = $configurationEditor->set($config, sprintf(self::SITEACCESS_KEY_FORMAT, $siteaccessName, $key), $value);
        }

        $configurationEditor->saveConfigToFile($this->ezplatformConfigFilePath, $config);
    }

    /**
     * @Given I append configuration to :siteaccessName siteaccess
     */
    public function iAppendConfigurationToSiteaccess($siteaccessName, TableNode $settings)
    {
        $configurationEditor = new ConfigurationEditor();
        $config = $configurationEditor->getConfigFromFile($this->ezplatformConfigFilePath);

        foreach ($settings->getHash() as $setting) {
            $key = $setting['key'];
            $value = $this->parseSetting($setting['value']);
            $config = $configurationEditor->append($config, sprintf(self::SITEACCESS_KEY_FORMAT, $siteaccessName, $key), $value);
        }

        $configurationEditor->saveConfigToFile($this->ezplatformConfigFilePath, $config);
    }

    /**
     * @Given I :mode configuration to :parentNode
     *
     * string $mode Available: append|set - whether the new config will be appended (resulting in an array) or replace the current value if it exists
     */
    public function iModifyConfigurationForSiteaccessUnderKey(string $mode, $siteaccessName, $keyName, PyStringNode $configFragment)
    {
        $this->iModifyConfigurationUnderKey($mode, sprintf(self::SITEACCESS_KEY_FORMAT, $siteaccessName, $keyName), $configFragment);
    }

    /**
     * @Given I :mode configuration to :parentNode
     *
     * string $mode Available: append|set - whether the new config will be appended (resulting in an array) or replace the current value if it exists
     */
    public function iModifyConfigurationUnderKey(string $mode, $parentNode, PyStringNode $configFragment)
    {
        $appendToExisting = $this->shouldAppendValue($mode);

        $configurationEditor = new ConfigurationEditor();

        $config = $configurationEditor->getConfigFromFile($this->ezplatformConfigFilePath);
        $parsedConfig = $this->parseConfig($configFragment);

        $config = $appendToExisting ?
            $configurationEditor->append($config, $parentNode, $parsedConfig) :
            $configurationEditor->set($config, $parentNode, $parsedConfig);
        $configurationEditor->saveConfigToFile($this->ezplatformConfigFilePath, $config);
    }
    /**
     * @Given I :mode configuration to :siteaccessName siteaccess under :keyName key
     */
    public function iModifyConfigurationForSiteaccessUnderKey(string $mode, $siteaccessName, $keyName, PyStringNode $configFragment)
    {
        $parentNode = sprintf(self::SITEACCESS_KEY_FORMAT, $siteaccessName, $keyName);
        $this->iModifyConfigurationUnderKey($mode, $parentNode, $configFragment);
    }
    /**
     * @Given I :mode siteaccess matcher configuration of type :type
     */
    public function iModifySiteaccessMatcherConfiguration(string $mode, $type, PyStringNode $configFragment)
    {
        $parentNode = sprintf(self::SITEACCESS_MATCHER_KEY_FORMAT, $type);
        $this->iModifyConfigurationUnderKey($mode, $parentNode, $configFragment);
    }

    private function parseSetting($setting)
    {
        return strpos($setting, ',') !== false ? explode(',', $setting) : $setting;
    }

    private function parseConfig(PyStringNode $configFragment)
    {
        $cleanedConfig = '';

        // Remove indent from first line and adjust the rest
        $firstLine = $configFragment->getStrings()[0];
        $firstLineIndent = \strlen($firstLine) - \strlen(ltrim($firstLine));

        foreach ($configFragment->getStrings() as $line) {
            $cleanedConfig = $cleanedConfig . substr($line, $firstLineIndent) . PHP_EOL;
        }

        return Yaml::parse($cleanedConfig);
    }

    private function shouldAppendValue(string $value): bool
    {
        if (!\in_array($value, ['set', 'append'])) {
            throw new Exception('Supported modes are: set, append');
        }

        return 'append' === $value;
    }
}
