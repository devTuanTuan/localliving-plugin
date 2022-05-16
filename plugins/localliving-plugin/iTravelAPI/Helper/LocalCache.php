<?php

namespace LocalLiving_Plugin\iTravelAPI\Helper;

class LocalCache {

	public $CacheLocation;
	public $LockLocation;

	public function __construct () {
		// Set up cache directories
		$this->CacheLocation = __DIR__ . '/../Cache';
		$this->LockLocation = $this->CacheLocation . '/Lock';
		// If cache directories do not exist, create them.
		if(!is_dir($this->CacheLocation)) {
			mkdir($this->CacheLocation);
		}
		if(!is_dir($this->LockLocation)) {
			mkdir($this->LockLocation);
		}
	}

	/**
	  * Logs a message.
	  * @param 	string 	$message 	Message to be logged.
	  */
	private function LogMessage ($message) {
		if(function_exists('iTravelPluginLogMessage')) {
			iTravelPluginLogMessage($message);
		}
	}

	/**
	 * Determines if a cache is stale.
	 * @param	string $key				Cache key.
	 * @param	string $method			Method of checking wheter the file is stale (see $STALE_METHODS).
	 * @param	array  $methodParams	Parameters for stale-checking, depends on the method.
	 * @throws	\Exception				Throws a generic exception if misconfigured method/method params.
	 * @todo							Not customizable enough.
	 * @return 
	 */
	private function IsStale ($key, $method = 'TimeBased', $methodParams = array('TimeOffset' => 86400)) {
		$STALE_METHODS = array(
			'TimeBased' => array(
				'requiresParam' => 'TimeOffset',
			),
		);

		if(!array_key_exists($method, $STALE_METHODS)) {
			throw new \Exception('Invalid stale cache determination method.');
		}
		
		// Determine if stale
		$absoluteCacheFilePath = $this->GetPath($key);
		if ($method === 'TimeBased') {
			if(!array_key_exists($STALE_METHODS['TimeBased']['requiresParam'], $methodParams)) {
				throw new \Exception('Invalid time offset for determining stale file.');
			}
			clearstatcache(true, $absoluteCacheFilePath);
			return (time() - filemtime($absoluteCacheFilePath) > $methodParams[$STALE_METHODS['TimeBased']['requiresParam']]);
		}
	}

	/**
	  * Generates the filepath for a given cache key.
	  * @param	string	$key	Cache key.
	  * @return	string	Returns the absolute path to where the cache file should be.
	  */
	private function GenerateCachePath ($key) {
		return $this->CacheLocation . '/' . $key . '.localcache';
	}

	/**
	  * Generates the filepath for a cache lock files.
	  * @param	string	$key	Cache key.
	  * @return	string	Returns the absolute path to where the lock path should be.
	  */
	private function GenerateLockPath ($key) {
		return $this->LockLocation . '/' . $key . '.localcache';
	}

	/**
	 * Writes data to local cache.
	 * @param	string	$key	Cache key.
	 * @param	string	$data	Data to write.
	 * @return 	string|null 	Returns newly cached path or null if nothing was written (file is currently being written to)
	 */
	private function WriteData ($key, $data) {
		$absoluteCacheFilePath = $this->GenerateCachePath($key);
		$absoluteLockFilePath = $this->GenerateLockPath($key);
		// Write unless lock exists (cache writing is in progress).
		if(!file_exists($absoluteLockFilePath)) {
			// Create temporary file with the data to be cached. Also serves as a lock file.
			file_put_contents($absoluteLockFilePath, $data, LOCK_EX);
			// Delete old  cache if it exists.
			if(file_exists($absoluteCacheFilePath)) {
				unlink($absoluteCacheFilePath);
			}
			// Move temp file/lock content to main cache dir.
			rename($absoluteLockFilePath, $absoluteCacheFilePath);
			// Return cache path.
			return $absoluteCacheFilePath;
		}
		return null;
	}

	/**
	 * Gets the file path of the cached file.
	 * @param	mixed $key	Cache key.
	 * @return	string|null	File path or null if cache doesn't exist.
	 */
	private function GetPath ($key) {
		$absoluteCacheFilePath = $this->GenerateCachePath($key);
		if(file_exists($absoluteCacheFilePath)) {
			return $absoluteCacheFilePath;
		}
		return null;
	}

	/**
	 * Gets the cached key's value.
	 * @param	string	$key	Cache key.
	 * @return 	mixed|null		Cache key value or null if the key does not exist.
	 */
	private function ReadData ($key) {
		if($this->GetPath($key)) {
			return file_get_contents($this->GetPath($key));	
		}
		return null;		
	}

	/**
	 * Gets cached data's cache path or writes to cache if it doesn't exist.
	 * @param	string	$key				Cache key.
	 * @param	string	$uncachedDataPath	Path of the uncached data.
	 * @return 	string 	PHP path to uncached or cached data.
	 * @todo	Params have todo items.
	 */
	public function GetPathOrWrite ($key, $uncachedDataPath) {
		try {
			/* 
			 * Differentiate caches based on file/url path
			 */
			$uncachedDataPathObject = parse_url($uncachedDataPath);
			if(array_key_exists('host', $uncachedDataPathObject)) {
				// If a hostname exists (ie. uncachedDataPath is a URL), use that.
				$key = $key . '_' . $uncachedDataPathObject['host'];
			} else {
				// Else just calculate the md5 hash of the given path, as it's the easiest solution that should work everywhere
				$key = $key . '_' . md5($uncachedDataPath);
				$this->LogMessage("Caching non-URL: $uncachedDataPath");
			}

			/* 
			 * If no cache exists or if it is stale, fetch new data.
			 */
			if(!$this->GetPath($key) || $this->IsStale($key)) {
				$this->WriteData($key, fopen($uncachedDataPath, 'r'));
			}
		} catch (Exception $ex) {
			$this->LogMessage($ex->getMessage());
			return $uncachedDataPath;
		}

		return $this->GetPath($key);
	}

	/**
	 * Reads cached API responses or writes to cache if it doesn't exist.
	 * In case of a write exception it will return uncached data. 
	 * In case of an API exception it will return null.
	 * @param	mixed	&$InitAPIObject		iTravel API class (from InitAPI())
	 * @param	string	$method				API method to be called (eg. GetSearchResults)
	 * @return	mixed	API response object or null
	 * Exapmple usage:
	 *		$getSearchResultsResponse = GetAPIMethodDataOrWrite(InitAPI(), 'GetRegions', ['getRegionsParameters' => $parameters]);
	 */
	public function GetAPIMethodDataOrWrite (&$InitAPIObject, $method, $params) {
		$key = $method . '_' . md5(serialize($params));
		
		// If no cache exists or if it is stale, fetch new data.
		if(!$this->ReadData($key) || $this->IsStale($key, 'TimeBased', ['TimeOffset' => 3600])) {
			$apiResponse = null;
			// Try getting fresh data from API
			try {
				$apiResponse = $InitAPIObject->$method($params);
			} catch (Exception $ex) {
				$this->LogMessage($ex->getMessage());
				return null;
			}
			// Try writing to cache
			try {
				$this->WriteData($key, serialize($apiResponse), false);
				return $apiResponse;
			} catch (Exception $ex) {
				$this->LogMessage($ex->getMessage());
				return $apiResponse;
			}
		}
		// Get cached data
		$cachedData = unserialize($this->ReadData($key));
		
		// In case unserialize fails, make the call and return fresh data
		if ($cachedData === false) 
			return $InitAPIObject->$method($params);

		return $cachedData;
	}
}