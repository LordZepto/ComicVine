<?php

namespace ComicVine;

use Guzzle\Http\ClientInterface;

/**
 * Connect to the ComicVine web service
 *
 * http://www.comicvine.com/api/documentation
 *
 * @link http://github.com/mikealmond/musicbrainz
 */
class ComicVine
{
    const URL = 'http://comicvine.com/api';

    const API_KEY = '44586594c94c050007cf9d6118de40e995205164'; // remove from release! xD

    private static $validIncludes = array(
        'character'=> array(
            'field_list'
        ),
        'characters'=> array(
            'field_list',
            'limit',
            'offset',
            'sort',
            'filter'
        ),
        'chat'=> array(

        ),
        'chats'=> array(
        ),
        'concept'=> array(
        ),
        'concepts'=> array(
        ),
        'issue'=> array(
        ),
        'issues'=> array(
        ),
        'location'=> array(
        ),
        'locations'=> array(
        ),
        'movie'=> array(
        ),
        'movies'=> array(
        ),
        'object'=> array(
        ),
        'objects'=> array(
        ),
        'origin'=> array(
        ),
        'origins'=> array(
        ),
        'person'=> array(
        ),
        'people'=> array(
        ),
        'power'=> array(
        ),
        'powers'=> array(
        ),
        'promo'=> array(
        ),
        'promos'=> array(
        ),
        'publisher'=> array(
        ),
        'publishers'=> array(
        ),
        'search'=> array(
        ),
        'story_arc'=> array(
        ),
        'story_arcs'=> array(
        ),
        'team'=> array(
        ),
        'teams'=> array(
        ),
        'types'=> array(
        ),
        'video'=> array(
        ),
        'videos'=> array(
        ),
        'video_type'=> array(
        ),
        'video_types'=> array(
        ),
        'volume'=> array(
        ),
        'volumes'=> array(
        )
    );

    private static $validBrowseIncludes = array(
        'release'=> array(
        ),
        'recording'=> array(
        ),
        'label'=> array(
        ),
        'artist'=> array(
        ),
        'release-group'=> array(
        )
    );
    private static $validReleaseTypes = array(
    );

    private static $validReleaseStatuses = array(
    );

    private $userAgent = 'ComicVine PHP Api/0.1.0';
    private $userAgentClient = 'ComicVine PHP Api-0.1.0';

    /**
     * The username a ComicVine user. Used for authentication.
     *
     * @var string
     */
    private $user = null;

    /**
     * The password of a ComicVine user. Used for authentication.
     *
     * @var string
     */
    private $password = null;

    /**
     * The Guzzle client used to make cURL requests
     *
     * @var \Guzzle\Http\ClientInterface
     */
    private $client;

    /**
     * Initializes the class. You can pass the user’s username and password
     * However, you can modify or add all values later.
     *
     * @param \Guzzle\Http\ClientInterface $client   The Guzzle client used to make requests
     * @param string                       $user
     * @param string                       $password
     */
    public function __construct(ClientInterface $client, $user = null, $password = null)
    {
        $this->client = $client;

        if (null != $user) {
            $this->setUser($user);
        }

        if (null != $password) {
            $this->setPassword($password);
        }

    }

    /**
     * Do a ComicVine lookup
     *
     * http://www.comicvine.com/api/documentation
     *
     * @param $entity
     * @param $mbid Music Brainz ID
     * @param  array  $inc
     * @return object | bool
     */
    public function lookup($entity, array $includes = array())
    {
		/*
        if (!$this->isValidEntity($entity)) {
            throw new Exception('Invalid entity');
        }

        $this->validateInclude($includes, self::$validIncludes[$entity]);
		*/
      



        $response = $this->call($entity, $includes, 'GET', false);

        return $response;
    }

    protected function browse(Filters\FilterInterface $filter, $entity, $mbid, array $includes, $limit = 25, $offset = null, $releaseType = array(), $releaseStatus = array())
    {
        if (!$this->isValidMBID($mbid)) {
            throw new Exception('Invalid Music Brainz ID');
        }

        if ($limit > 100) {
            throw new Exception('Limit can only be between 1 and 100');
        }

        $this->validateInclude($includes, self::$validBrowseIncludes[$filter->getEntity()]);

        $authRequired = $this->isAuthRequired($filter->getEntity(), $includes);

        $params  = $this->getBrowseFilterParams($filter->getEntity(), $includes, $releaseType, $releaseStatus);
        $params += array(
            $entity  => $mbid,
            'inc'    => implode('+', $includes),
            'limit'  => $limit,
            'offset' => $offset,
            'fmt'    => 'json'
        );

        $response = $this->call($filter->getEntity() . '/', $params, 'GET', $authRequired);

        return $response;
    }

    public function browseArtist($entity, $mbid, array $includes = array(), $limit = 25, $offset = null)
    {
        if (!in_array($entity, array('recording', 'release', 'release-group'))) {
            throw new Exception('Invalid browse entity for artist');
        }

        return $this->browse(new Filters\ArtistFilter(array()), $entity, $mbid, $includes, $limit, $offset);
    }

    public function browseLabel($entity, $mbid, array $includes, $limit = 25, $offset = null)
    {
        if (!in_array($entity, array('release'))) {
            throw new Exception('Invalid browse entity for label');
        }

        return $this->browse(new Filters\LabelFilter(array()), $entity, $mbid, $includes, $limit, $offset);
    }

    public function browseRecording($entity, $mbid, array $includes = array(), $limit = 25, $offset = null)
    {
        if (!in_array($entity, array('artist', 'release'))) {
            throw new Exception('Invalid browse entity for recording');
        }

        return $this->browse(new Filters\RecordingFilter(array()), $entity, $mbid, $includes, $limit, $offset);
    }

    public function browseRelease($entity, $mbid, array $includes = array(), $limit = 25, $offset = null, $releaseType = array(), $releaseStatus = array())
    {
        if (!in_array($entity, array('artist', 'label', 'recording', 'release-group'))) {
            throw new Exception('Invalid browse entity for release');
        }

        return $this->browse(new Filters\ReleaseFilter(array()), $entity, $mbid, $includes, $limit, $offset);
    }

    public function browseReleaseGroup($entity, $mbid, $limit = 25, $offset = null, array $includes, $releaseType = array())
    {
        if (!in_array($entity, array('arist', 'release'))) {
            throw new Exception('Invalid browse entity for release group');
        }

        return $this->browse(new Filters\ReleaseGroupFilter(array()), $entity, $mbid, $includes, $limit, $offset);
    }

    /**
     * Performs a query based on the parameters supplied in the Filter object.
     * Returns an array of possible matches with scores, as returned by the
     * musicBrainz web service.
     *
     * Note that these types of queries only return some information, and not all the
     * information available about a particular item is available using this type of query.
     * You will need to get the ComicVine id (mbid) and perform a lookup with browse
     * to return complete information about a release. This method returns an array of
     * objects that are possible matches.
     *
     * @param  \ComicVine\Filters\FilterInterface $trackFilter
     * @return array
     */
    public function search(Filters\FilterInterface $filter, $limit = 25, $offset = null)
    {
        if (count($filter->createParameters()) < 1) {
            throw new Exception('The artist filter object needs at least 1 argument to create a query.');
        }

        if ($limit > 100) {
            throw new Exception('Limit can only be between 1 and 100');
        }

        $params = $filter->createParameters(array('limit' => $limit, 'offset' => $offset, 'fmt' => 'json'));

        $response = $this->call($filter->getEntity() . '/', $params);

        return $filter->parseResponse($response);

    }

    /**
     * Perform a cUrl call based on a path and paramaters using
     * HTTP Digest for POST and certain GET calls (user-ratings, etc)
     * Ask for JSON to be returned instead of XML and set the user agent
     * based on ComicVine::setUserAgent
     *
     * @param  string $path
     * @param  array  $params
     * @param  string $method GET|POST
     * @return array
     */
    private function call($path, array $params = array(), $method = 'GET', $isAuthRequred = false)
    {

        if ($this->userAgent == '') {
            throw new Exception('You must set a valid User Agent before accessing the ComicVine API');
        }

        $this->client->setBaseUrl(self::URL);
        $this->client->setConfig(array(
            'data' => $params
        ));

        $request = $this->client->get($path . '/?api_key=' . self::API_KEY . '&format=json');
		echo $request->getUrl() . "\n";

        if ($isAuthRequred) {
            if ($this->user != null && $this->password != null) {
                $request->setAuth($this->user, $this->password, CURLAUTH_DIGEST);
            } else {
                throw new Exception('Authentication is required');
            }
        }

        $request->getQuery()->useUrlEncoding(false);
        $req = $request->send()->json();
        /*echo '<pre>';
		print_r($req);
		echo '</pre>';*/
			try {
				$this->isResponseOk($req['status_code']);
			} catch (Exception $e) {
			
			}
		return $req;
        
    }

    /**
     * Check that the status or type values are valid. Then, check that
     * the filters can be used with the given includes.
     *
     * @param  string $entity
     * @param  array  $includes
     * @param  array  $releaseType
     * @param  array  $releaseStatus
     * @return array
     */
    public function getBrowseFilterParams($entity, $includes, array $releaseType = array(), array $releaseStatus = array())
    {
        //$this->validateFilter(array($entity), self::$validIncludes);
        $this->validateFilter($releaseStatus, self::$validReleaseStatuses);
        $this->validateFilter($releaseType, self::$validReleaseTypes);

        if (!empty($releaseStatus)
            && !in_array('releases', $includes)) {
            throw new Exception("Can't have a status with no release include");
        }

        if (!empty($releaseType)
            && !in_array('release-groups', $includes)
            && !in_array('releases', $includes)
            && $entity != 'release-group') {
            throw new Exception("Can't have a release type with no release-group include");
        }

        $params = array();

        if (!empty($releaseType)) {
            $params['type'] = implode('|', $releaseType);
        }

        if (!empty($releaseStatus)) {
            $params['status'] = implode('|', $releaseStatus);
        }

        return $params;
    }

    public function isValidMBID($mbid)
    {
        return preg_match("/^(\{)?[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}(?(1)\})$/i", $mbid);
    }

    public function validateInclude($includes, $validIncludes)
    {
        foreach ($includes as $include) {
            if (!in_array($include, $validIncludes)) {
                throw new \OutOfBoundsException(sprintf('%s is not a valid include', $include));
            }
        }

        return true;
    }

    public function validateFilter($values, $valid)
    {
        foreach ($values as $value) {
            if (!in_array($value, $valid)) {
                throw new Exception(sprintf('%s is not a valid filter', $value));
            }
        }

        return true;
    }
    /**
     * Some calls require authentication
     * @return bool
     */
    protected function isAuthRequired($entity, $includes)
    {
        if (in_array('user-tags', $includes) || in_array('user-ratings', $includes)) {
            return true;
        }

        if (substr($entity, 0, strlen('collection')) === 'collection') {
            return true;
        }

        return false;
    }

    /**
     * Check the list of allowed entities
     *
     * @param $entity
     * @return bool
     */
    private function isValidEntity($entity)
    {
        return array_key_exists($entity, self::$validIncludes);
    }

    /**
     * Set the user agent for POST requests (and GET requests for user tags)
     *
     * @param $application The name of the application using this library
     * @param $version The version of the application using this library
     * @param $contactInfo E-mail or website of the application
     * @throws Exception
     */
    public function setUserAgent($application, $version, $contactInfo)
    {
        if (strpos($version, '-') !== false) {
            throw new Exception('User agent: version should not contain a "-" character.');
        }

        $this->userAgent       = $application . '/' . $version . ' (' . $contactInfo . ')';
        $this->userAgentClient = $application . '-' . $version;

    }

    /**
     * Returns the user agent.
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * Sets the ComicVine user
     *
     * @param string $email
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * Returns the ComicVine user
     *
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Sets the user’s password
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Returns the user’s password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
    * ERROR HANDLING?
    */

    private function isResponseOk($status_code)
    {
        switch ($status_code) {
            case 1:
                return true;
            case 100:
                throw new Exception('100: Invalid API Key');
            case 101:
                throw new Exception('101: Object not found');
            case 102:
                throw new Exception('102: Error in URL format');
            case 103:
                throw new Exception('103: \'jsonp\' format requieres a \'json_callback\' argument');
            case 104:
                throw new Exception('104: Filter Error');
            case 105:
                throw new Exception('105: Subscriber only video is for suscribers only');
            default:
                return true;
        }
    }


}
