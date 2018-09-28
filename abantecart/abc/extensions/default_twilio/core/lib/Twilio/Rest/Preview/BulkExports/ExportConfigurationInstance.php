<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\Rest\Preview\BulkExports;

use Twilio\Exceptions\TwilioException;
use Twilio\InstanceResource;
use Twilio\Options;
use Twilio\Values;
use Twilio\Version;

/**
 * PLEASE NOTE that this class contains preview products that are subject to change. Use them with caution. If you currently do not have developer preview access, please contact help@twilio.com.
 *
 * @property boolean enabled
 * @property string  webhookUrl
 * @property string  webhookMethod
 * @property string  resourceType
 * @property string  url
 */
class ExportConfigurationInstance extends InstanceResource
{
    /**
     * Initialize the ExportConfigurationInstance
     *
     * @param \Twilio\Version $version      Version that contains the resource
     * @param mixed[]         $payload      The response payload
     * @param string          $resourceType The resource_type
     *
     * @return \Twilio\Rest\Preview\BulkExports\ExportConfigurationInstance
     */
    public function __construct(Version $version, array $payload, $resourceType = null)
    {
        parent::__construct($version);

        // Marshaled Properties
        $this->properties = array(
            'enabled'       => Values::array_get($payload, 'enabled'),
            'webhookUrl'    => Values::array_get($payload, 'webhook_url'),
            'webhookMethod' => Values::array_get($payload, 'webhook_method'),
            'resourceType'  => Values::array_get($payload, 'resource_type'),
            'url'           => Values::array_get($payload, 'url'),
        );

        $this->solution = array('resourceType' => $resourceType ?: $this->properties['resourceType'],);
    }

    /**
     * Generate an instance context for the instance, the context is capable of
     * performing various actions.  All instance actions are proxied to the context
     *
     * @return \Twilio\Rest\Preview\BulkExports\ExportConfigurationContext Context
     *                                                                     for this
     *                                                                     ExportConfigurationInstance
     */
    protected function proxy()
    {
        if (!$this->context) {
            $this->context = new ExportConfigurationContext($this->version, $this->solution['resourceType']);
        }

        return $this->context;
    }

    /**
     * Fetch a ExportConfigurationInstance
     *
     * @return ExportConfigurationInstance Fetched ExportConfigurationInstance
     */
    public function fetch()
    {
        return $this->proxy()->fetch();
    }

    /**
     * Update the ExportConfigurationInstance
     *
     * @param array|Options $options Optional Arguments
     *
     * @return ExportConfigurationInstance Updated ExportConfigurationInstance
     */
    public function update($options = array())
    {
        return $this->proxy()->update($options);
    }

    /**
     * Magic getter to access properties
     *
     * @param string $name Property to access
     *
     * @return mixed The requested property
     * @throws TwilioException For unknown properties
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        }

        if (property_exists($this, '_'.$name)) {
            $method = 'get'.ucfirst($name);
            return $this->$method();
        }

        throw new TwilioException('Unknown property: '.$name);
    }

    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString()
    {
        $context = array();
        foreach ($this->solution as $key => $value) {
            $context[] = "$key=$value";
        }
        return '[Twilio.Preview.BulkExports.ExportConfigurationInstance '.implode(' ', $context).']';
    }
}