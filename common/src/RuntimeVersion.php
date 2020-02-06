<?php

namespace Amp;

/**
 * Queries https://cdn.ampproject.org/rtv/metadata for the latest AMP runtime version. Uses a stale-while-revalidate
 * caching strategy to avoid refreshing the version.
 *
 * More details: https://cdn.ampproject.org/rtv/metadata returns the following metadata:
 *
 * <pre>
 * {
 *    "ampRuntimeVersion": "CURRENT_PROD",
 *    "ampCssUrl": "https://cdn.ampproject.org/rtv/CURRENT_PROD/v0.css",
 *    "canaryPercentage": "0.1",
 *    "diversions": [
 *      "CURRENT_OPTIN",
 *      "CURRENT_1%",
 *      "CURRENT_CONTROL"
 *    ]
 *  }
 *  </pre>
 *
 *  where:
 *
 *  <ul>
 *    <li> CURRENT_OPTIN: is when you go to https://cdn.ampproject.org/experiments.html and toggle "dev-channel". It's
 *    the earliest possible time to get new code.</li>
 *    <li> CURRENT_1%: 1% is the same code as opt-in that we're now comfortable releasing to 1% of the population.</li>
 *    <li> CURRENT_CONTROL is the same thing as production, but with a different URL. This is to compare experiments
 *    against, since prod's immutable caching would affect metrics.</li>
 *  </ul>
 *
 * @package amp/common
 */
final class RuntimeVersion
{

    /**
     * Option to retrieve the latest canary version data instead of the production version data.
     *
     * @var string
     */
    const OPTION_CANARY = 'canary';

    /**
     * Endpoint to query for retrieving the runtime version data.
     */
    const RUNTIME_METADATA_ENDPOINT = 'https://cdn.ampproject.org/rtv/metadata';

    /**
     * Transport to use for remote requests.
     *
     * @var RemoteRequest
     */
    private $remoteRequest;

    /**
     * Instantiate a RuntimeVersion object.
     *
     * @param RemoteRequest $remoteRequest Transport to use for remote requests.
     */
    public function __construct(RemoteRequest $remoteRequest)
    {
        $this->remoteRequest = $remoteRequest;
    }

    /**
     * Returns the version of the current Amp runtime release.
     *
     * Pass [ canary => true ] to get the latest canary version.
     *
     * @param array $options Optional. Associative array of options.
     * @return string Version string of the Amp runtime.
     */
    public function currentVersion($options = [])
    {
        $response = $this->remoteRequest->fetch(self::RUNTIME_METADATA_ENDPOINT);
        $metadata = json_decode($response);

        $version = (! empty($options['canary']))
            ? $metadata->diversions[0]
            : $metadata->ampRuntimeVersion;

        return $this->padVersionString($version);
    }

    /**
     * Pad the version string to the required length.
     *
     * @param string $version Version string to pad.
     * @return string Padded version string.
     */
    private function padVersionString($version)
    {
        return str_pad($version, 15, 0);
    }
}