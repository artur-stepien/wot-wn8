<?php
/**
 * @package     Wargaming.API
 * @version     1.00
 * @author      Artur Stępień (artur.stepien@bestproject.pl)
 * @copyright   Copyright (C) 2015 Artur Stępień, All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

namespace WoT;
	
/**
 * Class processing accounts WN8 stat. This class require Wargaming\API class to work. 
 * You can download latest version from here: https://github.com/artur-stepien/wargaming-papi
 */
class WN8 {
	
	// Wargaming/API instance
	protected $api;
	
	// Contains expectd tank values
	protected $expected_tank_values = array();
	
	// Should application search for info about tanks missing in WN8 calculation
	protected $search_missing_tanks = false;
	
	// Account WN8
	public $wn8;
	
	// Account ID
	public $account_id;
	
	// Tanks missing in expected tank values that user drive (excluded from WN8 acounting)
	public $missing_tanks;
	
	/**
	 * Create instance of WN8 class and calculate WN8 stats for given account. If you already have account_id set it in $search, 
	 * if not set $search to account nickname you search for. Application will find account_id. If $missing_search is set to TRUE, application will use aditional API query 
	 * to get Tankopedia tanks info (name etc.) for tanks missing in WN8 calculation (those excluded from expected tank values). Application use settings from API instance. 
	 * So if you want to get WN8 of an account on RU cluster set proper server in Wargaming\API instance set in $api. Same for language.
	 * 
	 * @param   \Wargaming\API   $api                            Wargaming\API instance version at least 1.04. It is used to retrieve data from Wargaming servers.
	 * @param   string/integer   $search                         If $search is integer type application will assume it is account_id, if it is string application 
	 *                                                           will search for account with nickname set to $search
	 * @param   array            $expected_tank_values_version   Array of expected tank values from wnefficiency.net (vBAddict.net)
	 * @param   bool             $missing_search                 If $missing_search is set to TRUE, application will also add informations about tanks missing 
	 *                                                           in WN8 calculation (those who are excluded from expected tank values due to being OP or lack of statistic data)
	 * 
	 * @throws \Exception
	 */
	public function __construct(\Wargaming\API $api, $search, $expected_tank_values_version = 21, $missing_search = false) {
		$this->api = $api;
		$this->search_missing_tanks = $missing_search;
		
		// Be sure we have expected tank values
		if ( !file_exists(dirname(__FILE__).'/expected_tank_values_'.$expected_tank_values_version.'.json') ) {
			file_put_contents(
				dirname(__FILE__).'/expected_tank_values_'.$expected_tank_values_version.'.json', 
				file_get_contents('http://www.wnefficiency.net/exp/expected_tank_values_'.$expected_tank_values_version.'.json')
			);
		}
		
		// Load expected tank values
		$buff = json_decode( file_get_contents(dirname(__FILE__).'/expected_tank_values_21.json') )->data;
		foreach ( $buff AS $tank ) {
			$this->expected_tank_values[$tank->IDNum] = $tank;
		}
		
		// If user provided nickname instead of account_id, get account_id
		if( is_string($search) ) {
			
			// Get account from Wargaming servers
			$account = $api->get('wot/account/list', array('fields'=>'account_id','type'=>'exact', 'search'=>$search));
		
			// If account found, store its account_id
			if( is_array($account) AND !empty($account) ) {
				
				$this->account_id = current($account)->account_id;
				
			// Account not found so return exception
			} else {
				
				throw new \Exception('Account <b>'.$search.'</b> not found on selected server.', '404');
				
			}
			
		// User provided account_id, use it to get stats
		} else {
			
			$this->account_id = $search;
			
		}
		
		// Try to measure account WN8 stat
		try {
			
			$this->getWN8();
			
		// Catch any errors while counting WN8
		} catch(\Exception $e) {
			
			throw new \Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
			
		}
	}
	
	/**
	 * Calculate account WN8
	 * 
	 * @return   Integer
	 */
	protected function getWN8() {
		
		// If WN8 was not calculated yet
		if( is_null($this->wn8) ) {
			
			// Get account_id
			$account_id = $this->account_id;
			
			// Get average values
			$average = $this->api->get('wot/account/info',array(
			   'fields'=>'statistics.all.battles,statistics.all.frags,statistics.all.damage_dealt,statistics.all.dropped_capture_points,statistics.all.spotted,statistics.all.wins',
			   'account_id'=>$account_id
			))->$account_id->statistics->all;
			
			// Get tanks info (battles count)
			$tanks = $this->api->get('wot/account/tanks',array('fields'=>'tank_id,statistics.battles','account_id'=>$account_id))->$account_id;

			// WN8 expected calculation
			$expDAMAGE = $expFRAGS = $expSPOT = $expDEF = $expWIN = 0;
			$missing = array();
			
			foreach( $tanks AS $tank ) {

				// Tank exists in expected tank values
				if( isset($this->expected_tank_values[$tank->tank_id]) ) {
					
					$expected = $this->expected_tank_values[$tank->tank_id];
					$expDAMAGE += $expected->expDamage * $tank->statistics->battles;
					$expSPOT += $expected->expSpot * $tank->statistics->battles;
					$expFRAGS += $expected->expFrag * $tank->statistics->battles;
					$expDEF += $expected->expDef * $tank->statistics->battles;
					$expWIN += 0.01*$expected->expWinRate * $tank->statistics->battles;           
					
				// Tank missing in expected tank values so add it to the list
				} else {
					
					$missing [] = $tank->tank_id;
					
				}
			}

			// If there are missing tanks and searching for info is set to TRUE, get those values
			if( !empty($missing) AND $this->search_missing_tanks ) {
				$this->missing_tanks = $this->api->get('wot/encyclopedia/tankinfo',array('tank_id'=>implode(',',$missing),'fields'=>'localized_name'));
			}

			// Calculate WN8
			$rDAMAGE = $average->damage_dealt / $expDAMAGE;
			$rSPOT = $average->spotted / $expSPOT;
			$rFRAG = $average->frags / $expFRAGS;
			$rDEF = $average->dropped_capture_points / $expDEF;
			$rWIN = $average->wins / $expWIN;  

			$rWINc    = max(0,                      ($rWIN    - 0.71) / (1 - 0.71) );
			$rDAMAGEc = max(0,                      ($rDAMAGE - 0.22) / (1 - 0.22) );
			$rFRAGc   = max(0, min($rDAMAGEc + 0.2, ($rFRAG   - 0.12) / (1 - 0.12)));
			$rSPOTc   = max(0, min($rDAMAGEc + 0.1, ($rSPOT   - 0.38) / (1 - 0.38)));
			$rDEFc    = max(0, min($rDAMAGEc + 0.1, ($rDEF    - 0.10) / (1 - 0.10)));

			$wn8 = 980*$rDAMAGEc + 210*$rDAMAGEc*$rFRAGc + 155*$rFRAGc*$rSPOTc + 75*$rDEFc*$rFRAGc + 145*MIN(1.8,$rWINc);
			
			// Ok we have WN8, store it
			$this->wn8 = round($wn8, 2);  
		}
			
		// Return our mighty number
		return $this->wn8;	
	}
	
	/**
	 * Returns account WN8
	 * 
	 * @return float
	 */
	public function __toString() {
		return (string)$this->getWN8();
	}
	
}