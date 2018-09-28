<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\Rest\Wireless\V1;

use Twilio\InstanceContext;
use Twilio\Options;
use Twilio\Values;
use Twilio\Version;

/**
 * PLEASE NOTE that this class contains beta products that are subject to change. Use them with caution.
 */
class RatePlanContext extends InstanceContext
{
    /**
     * Initialize the RatePlanContext
     *
     * @param \Twilio\Version $version Version that contains the resource
     * @param string          $sid     The sid
     *
     * @return \Twilio\Rest\Wireless\V1\RatePlanContext
     */
    public function __construct(Version $version, $sid)
    {
        parent::__construct($version);

        // Path Solution
        $this->solution = array('sid' => $sid,);

        $this->uri = '/RatePlans/'.rawurlencode($sid).'';
    }

    /**
     * Fetch a RatePlanInstance
     *
     * @return RatePlanInstance Fetched RatePlanInstance
     */
    public function fetch()
    {
        $params = Values::of(array());

        $payload = $this->version->fetch(
            'GET',
            $this->uri,
            $params
        );

        return new RatePlanInstance($this->version, $payload, $this->solution['sid']);
    }

    /**
     * Update the RatePlanInstance
     *
     * @param array|Options $options Optional Arguments
     *
     * @return RatePlanInstance Updated RatePlanInstance
     */
    public function update($options = array())
    {
        $options = new Values($options);

        $data = Values::of(array(
            'UniqueName'   => $options['uniqueName'],
            'FriendlyName' => $options['friendlyName'],
        ));

        $payload = $this->version->update(
            'POST',
            $this->uri,
            array(),
            $data
        );

        return new RatePlanInstance($this->version, $payload, $this->solution['sid']);
    }

    /**
     * Deletes the RatePlanInstance
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
        return '[Twilio.Wireless.V1.RatePlanContext '.implode(' ', $context).']';
    }
}