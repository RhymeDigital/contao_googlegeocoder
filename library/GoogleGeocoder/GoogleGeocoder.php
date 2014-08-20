<?php 

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 * 
 * PHP version 5 
 * @copyright  Intelligent Spark 2010
 * @author     Fred Bliss <http://www.intelligentspark.com> 
 * @package    ChartisMaps 
 * @license    Commercial
 * @filesource
 */

namespace GoogleGeocoder;

/**
 * Class Geocoder
 *
 * Provide methods to handle geocoding tasks with Google Maps API
 * @copyright  Intelligent Spark 2010
 * @author     Fred Bliss <http://www.intelligentspark.com> 
 * @package    ChartisMaps 
 */
class GoogleGeocoder extends \System
{

	/**
	 * Google Maps API URL
	 * @var string
	 */
	protected static $strUrl = "http://maps.googleapis.com/maps/api/geocode/json";
	
	
	/**
	 * Get coordinates for each address in the collection
	 * NOTE: Each address must be an array in $arrAddresses (example: $arrAddresses=array(array('address'=>'116 Pleasant St. Easthampton MA 01027')); )
	 * @param array
	 * @return array
	 */
	public static function getCoordinates(array $arrAdresses=array(), $strKey='address')
	{		
		if (empty($arrAdresses))
		{
			return array();
		}
		
		$delay = 2000;
		$intStart = \Input::get('start') ? : 0;		
					
		foreach ($arrAdresses as $key=>$address)
		{
			if (!is_array($address) || !$address[$strKey])
			{
				continue;
			}
			
			$geocode_pending = true;
			$objResponse = new \stdClass();
						
			$strParams = '?address='.urlencode($address[$strKey]).'&sensor=false';								
			
			$data = file_get_contents(static::$strUrl.$strParams);				
			
			if ($data)
			{		
				$objResponse = json_decode($data);
							
				switch($objResponse->status)
				{
					case 'OK':
					
						$arrAdresses[$key]['lat'] = $objResponse->results[0]->geometry->location->lat;
						$arrAdresses[$key]['lng'] = $objResponse->results[0]->geometry->location->lng;
						$intNumSuccessful++;
						break;
						
					case 'OVER_QUERY_LIMIT':
						
						return $arrAdresses;
						
					case 'INVALID_REQUEST':
					case 'REQUEST_DENIED':
					case 'ZERO_RESULTS':
					
						break;
				}
			}
			else
			{
				//$_SESSION['TL_ERROR'][] = sprintf($GLOBALS['TL_LANG']['GSC'][0], $GLOBALS['TL_LANG']['MSC']['gmapsBaseURL'] . $GLOBALS['TL_CONFIG']['defaultMapsCountry']);
				//$this->log(sprintf($GLOBALS['TL_LANG']['GSC'][0], $GLOBALS['TL_LANG']['MSC']['gmapsBaseURL'] . $GLOBALS['TL_CONFIG']['defaultMapsCountry']), 'tl_cm_markers resolveAddress()', TL_ERROR);
				//$this->reload();
			}
						
			usleep($delay);		
					
		}
		
		return $arrAdresses;
	
	}
	
	
	/**
	 * Do geocoding for address - UNUSED (keeping this for now in case we need to use it)
	 * @param array
	 * @return array
	 */
	public function geocode($arrMarker)
	{
		return;
		
		$objRequest = new \Request();

		$objRequest->send($request_url, 'post');
		
		if($objRequest->response)
		{
			$arrResponse = json_decode($objRequest->response);
			
			switch($arrResponse['status'])
			{
				case 'OK':
					$arrPacket['latitude'] 	= $arrResponse['results']['geometry']['location']['lat'];
					$arrPacket['longitude'] = $arrResponse['results']['geometry']['location']['lng'];
					break;
				case 'OVER_QUERY_LIMIT':
					$this->delay += 10000;		//Geocoding rate of delay.  We will increase this each time we get a 620 error response.  Because we can only geocode a max of 15,000 time per day, this is necessary to avoid code 620 in response from the server (TOO MANY QUERIES, too fast.)	
					usleep($this->delay);
					break;
				case 'ZERO_RESULTS':
					if($broadSearch==1)
					{
						if($finalAttempt)	//If we got the same error with the most basic information on second attempt, then display
						{
							$_SESSION['TL_ERROR'][] = $GLOBALS['TL_LANG']['GSC'][$arrResponse['status']];
							$this->log(sprintf($GLOBALS['TL_LANG']['ERR']['geocodingFailed'], $id, $this->Input->get('id')), 'ChartisMapsBE extractAddressData()', TL_ERROR);
						}
						else
						{
							return 999;		//A reserved value we designate that will tell the geocoding script to try again with zip only
							$this->log($GLOBALS['TL_LANG']['MSC']['broadSearchLogAlert'], 'ChartisMapsBE extractAddressData()', TL_ERROR);
						}
					}
					else
					{
						$_SESSION['TL_ERROR'][] = $GLOBALS['TL_LANG']['GSC'][$arrResponse['status']];
						$this->log(sprintf($GLOBALS['TL_LANG']['ERR']['geocodingFailed'], $id, $this->Input->get('id')), 'ChartisMapsBE extractAddressData()', TL_ERROR);
					}
					break;
				case 'INVALID_REQUEST':
				case 'REQUEST_DENIED':
				case 'ZERO_RESULTS':
					$_SESSION['TL_ERROR'][] = $GLOBALS['TL_LANG']['GSC'][$arrResponse['status']];
					$this->reload();
					break;
			}
		}
		else
		{
			$_SESSION['TL_ERROR'][] = sprintf($GLOBALS['TL_LANG']['GSC'][0], $GLOBALS['TL_LANG']['MSC']['gmapsBaseURL'] . $GLOBALS['TL_CONFIG']['defaultMapsCountry']);
			
			$this->log(sprintf($GLOBALS['TL_LANG']['GSC'][0], $GLOBALS['TL_LANG']['MSC']['gmapsBaseURL'] . $GLOBALS['TL_CONFIG']['defaultMapsCountry']), 'tl_cm_markers resolveAddress()', TL_ERROR);
			
			$this->reload();
		}
		
		return $arrReturn;
	}
		
}
