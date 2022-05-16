<?php
	
	namespace LocalLiving_Plugin\iTravelAPI\Helper;
	
	// Class for adding resources to XML, so that the iTravel website components can be displayed in proper languages
	class ResourceManager {
		
		var $xml;
		var $resourceFolder;
		var $resourceBaseName;
		
		function __construct($folder){
			$this->resourceBaseName = "iTravelWebsiteResources";
			$this->resourceFolder = $folder;
		}
		
		function ResourcesToXml() {
			$toReturn = "<TranslationList>";
            $resourcesPathExpression = $this->resourceFolder . "/" . $this->resourceBaseName . ".*";
			foreach(glob($resourcesPathExpression) as $filename){
					$toReturn .= "<Translation>";
					
					// Get language of the resource file
					$language = substr($filename, -7, 2);
					$toReturn .= "<LanguageID>" . $language . "</LanguageID>";
					$resourceXML = file_get_contents($filename);
					$resourceXML = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $resourceXML);
					$toReturn .= $resourceXML;
					
					$toReturn .= "</Translation>";
			}
			$toReturn .= "</TranslationList>";
			return $toReturn;
		}
			
	}
