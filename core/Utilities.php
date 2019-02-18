<?php
namespace Syncer\Core;

trait Utilities
{
	/**
	 * @param	array			array with all elements
	 * @param	array|string	values to search or list of column to compare
	 * @return	int|false		if corresponding values found returns index else false
	 */
	public static function binarySearch(array $arr, $toSearch)
	{
        //FIXME workinprogress it doesn't function yet
		// check for empty array 
		if (count($arr) === 0/* || count($toSearch) === 0*/) return false;

		$low = 0;
		$high = count($arr) - 1;

		if(is_array($toSearch)) {
			//main array is associative
			//$toSearchIndexes = array_keys($toSearch);
			$toSearchIndexes = array_keys(end($arr));
			$numIndexes = count($toSearchIndexes);
			while ($low <= $high) { 
				$mid = floor(($low + $high) / 2); 
				$foundEqual = 0;
				$foundMinor = 0;
				
				foreach($toSearchIndexes as $key) {
					if((int)$arr[$mid][$key] == (int)$toSearch[$key]) {
						$foundEqual++;
					} else if((int)$toSearch[$key] < (int)$arr[$mid][$key]) {
						$foundMinor++;
						break;
					}
				}

				if($foundEqual == $numIndexes) return $mid;
				
				if($foundMinor !== 0) {
					$high = $mid -1; 
				} else {
					$low = $mid + 1; 
				}
			}
		} else {
			//main array is indexed
			while ($low <= $high) { 
				$mid = floor(($low + $high) / 2); 
				
				if($arr[$mid] == $toSearch) return $mid;
				
				if($toSearch < $arr[$mid]) {
					$high = $mid -1;
				} else {
					$low = $mid + 1; 
				}
			}
		}

		return false; 
	}
}
?>
