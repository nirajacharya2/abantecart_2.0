<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\Rest\Preview\Understand\Service;

use Twilio\InstanceContext;
use Twilio\Options;
use Twilio\Values;
use Twilio\Version;

/**
 * PLEASE NOTE that this class contains preview products that are subject to change. Use them with caution. If you currently do not have developer preview access, please contact help@twilio.com.
 */
class ModelBuildContext extends InstanceContext
{
    /**
     * Initialize the ModelBuildContext
     *
     * @param \Twilio\Version $version    Version that contains the resource
     * @param string          $serviceSid The service_sid
     * @param string          $sid        The sid
     *
     * @return \Twilio\Rest\Preview\Understand\Service\ModelBuildContext
     */
    public function __construct(Version $version, $serviceSid, $sid)
    {
        parent::__construct($version);

        // Path Solution
        $this->solution = array('serviceSid' => $serviceSid, 'sid' => $sid,);

        $this->uri = '/Services/'.rawurlencode($serviceSid).'/ModelBuilds/'.rawurlencode($sid).'';
    }

    /**
     * Fetch a ModelBuildInstance
     *
     * @return ModelBuildInstance Fetched ModelBuildInstance
     */
    public function fetch()
    {
        $params = Values::of(array());

        $payload = $this->version->fetch(
            'GET',
            $this->uri,
            $params
        );

        return new ModelBuildInstance(
            $this->version,
            $payload,
            $this->solution['serviceSid'],
            $this->solution['sid']
        );
    }

    /**
     * Update the ModelBuildInstance
     *
     * @param array|Options $options Optional Arguments
     *
     * @return ModelBuildInstance Updated ModelBuildInstance
     */
    public function update($options = array())
    {
        $options = new Values($options);

        $data = Values::of(array('UniqueName' => $options['uniqueName'],));

        $payload = $this->version->update(
            'POST',
            $this->uri,
            array(),
            $data
        );

        return new ModelBuildInstance(
            $this->version,
            $payload,
            $this->solution['serviceSid'],
            $this->solution['sid']
        );
    }

    /**
     * Deletes the ModelBuildInstance
     *
     * @return boolean True if delete succeeds, false otherwise
     */
    public function delete()
    {
        return $this->version->delete('delete', $this->uri);
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
        return '[Twilio.Preview.Understand.ModelBuildContext '.implode(' ', $context).']';
    }
}