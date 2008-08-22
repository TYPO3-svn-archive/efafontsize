<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Francois Suter (Cobweb) <support@cobweb.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
*
* $Id: class.tx_efafontsize_pi1.php 3655 2008-01-11 13:17:59Z fsuter $
***************************************************************/

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * Plugin 'Dynamic Font Resizer' for the 'efafontsize' extension.
 *
 * @author	Francois Suter (Cobweb) <support@cobweb.ch>
 * @package	TYPO3
 * @subpackage	tx_efafontsize
 */
class tx_efafontsize_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_efafontsize_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_efafontsize_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'efafontsize';	// The extension key.
	var $pi_checkCHash = true;
	var $localconf; // Local TS configuration
	var $sizes = array('smaller','reset','bigger'); // List of control keys
	
	/**
	 * This is the main method of plugin
	 * It writes the actual code for the font resizing controls
	 *
	 * @param	string		$content: The plugin's content
	 * @param	array		$conf: The plugin's configuration
	 * @return	string		The content that is displayed on the website
	 */
	function main($content,$conf) {

// Perform initalizations

		$this->localconf = $conf;
		$initializationScript = '';
		$controlsScript = '';

// Load localized strings

		$this->pi_loadLL();

// Check that the configuration of the controls order indeed contains the 3 controls

		$configurationOrder = t3lib_div::trimExplode(',', $this->localconf['controlOrder']);
		$difference = array_diff($this->sizes, $configurationOrder);
		if (count($difference) > 0) { // If there's at least one difference, replace configuration with default array
			$this->localconf['controlOrder'] = $this->sizes;
		}
		else {
			$this->localconf['controlOrder'] = $configurationOrder;
		}

// Loop on each control (smaller, reset and bigger)

		foreach ($this->localconf['controlOrder'] as $aSize) {
			if (!empty($this->localconf[$aSize])) $controlsScript .= 'document.write(efa_fontSize06.'.$aSize.'Link);';
			$initializationScript .= $this->initializeControl($aSize);
		}
		if (!empty($controlsScript)) $content .= t3lib_div::wrapJS($controlsScript);

// Include EfA base scripts

		$GLOBALS['TSFE']->additionalHeaderData[$this->extKey.'1'] = '<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/js/cookies.js"></script>';
		$GLOBALS['TSFE']->additionalHeaderData[$this->extKey.'2'] = '<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath($this->extKey).'res/js/efa_fontsize.js"></script>';

// Assemble complete EfA intialization script
// This includes the default font size and increment, the controls initializations assembled above
// and the instanciation of the Efa_Fontsize06 object

		$fullInitializationScript = '';
		$fullInitializationScript .= 'var efa_default = '.((empty($this->localconf['defaultFontSize'])) ? 100 : intval($this->localconf['defaultFontSize'])).";\n";
		$fullInitializationScript .= 'var efa_increment = '.((empty($this->localconf['defaultFontSize'])) ? 10 : intval($this->localconf['fontSizeIncrement'])).";\n";
		$fullInitializationScript .= $initializationScript;
		$fullInitializationScript .= 'var efa_fontSize06 = new Efa_Fontsize06(efa_increment,efa_bigger,efa_reset,efa_smaller,efa_default);'."\n";
		$GLOBALS['TSFE']->additionalJavaScript[] = $fullInitializationScript;

// Wrap the whole result, with baseWrap if defined, else with standard pi_wrapInBaseClass() call

		if (isset($this->localconf['baseWrap.'])) {
			return $this->cObj->stdWrap($content,$this->localconf['baseWrap.']);
		}
		else {
			return $this->pi_wrapInBaseClass($content);
		}
	}

	/**
	 * This function is an alternate userFunc for calling up the initialization of the EfA script
	 *
	 * @param	string		$content: The plugin's content
	 * @param	array		$conf: The plugin's configuration
	 * @return	string		The content that is displayed on the website
	 */
	function initEfA($content, $conf) {
		$content = 'if (efa_fontSize06) efa_fontSize06.efaInit();';
		return t3lib_div::wrapJS($content);
	}

	/**
	 * This method writes the parameter array for a given control (smaller, reset or bigger)
	 *
	 * @param	string	$key: Key of the control to render
	 * @return	string	JavaScript array with the control's parameters
	 */
	function initializeControl($key) {

// Initialize all parameters
// The order is the following (indices in squares brackets), according to Efa Font Size doc:
//		 [0] before HTML
//		 [1] inside HTML
//		 [2] title text
//		 [3] class text
//		 [4] id text
//		 [5] name text
//		 [6] accesskey text
//		 [7] onmouseover JavaScript
//		 [8] onmouseout JavaScript
//		 [9] on focus JavaScript
//		[10] after HTML
//
// Note there's no TS property for "title text" as it uses a localised string which can be changed with TypoScript anyway

		$controlParameters = array('',addslashes($this->pi_getLL($key)),addslashes($this->pi_getLL($key)),'','','','','','','','');

// Set values for each parameter according to the corresponding TS property

		if (isset($this->localconf[$key.'.'])) {
			$conf = $this->localconf[$key.'.'];
			$this->cObj->data['controlKey'] = $key; // Add the current key to the available cObj data
			if (isset($conf['beforeHTML.'])) {
				$controlParameters[0] = trim(str_replace("\r\n",'',$this->cObj->cObjGetSingle($conf['beforeHTML'],$conf['beforeHTML.'])));
			}
			if (isset($conf['insideHTML.'])) {
				$controlParameters[1] = trim(str_replace("\r\n",'',$this->cObj->cObjGetSingle($conf['insideHTML'],$conf['insideHTML.'])));
			}
			if (!empty($conf['class'])) {
				$controlParameters[3] = $conf['class'];
			}
			if (!empty($conf['id'])) {
				$controlParameters[4] = $conf['id'];
			}
			if (!empty($conf['name'])) {
				$controlParameters[5] = $conf['name'];
			}
			if (!empty($conf['accesskey'])) {
				$controlParameters[6] = $conf['accesskey'];
			}
			if (isset($conf['onmouseover.'])) {
				$controlParameters[7] = addslashes($this->cObj->stdWrap((empty($conf['onmouseover'])) ? '' : $conf['onmouseover'],$conf['onmouseover.']));
			}
			elseif (!empty($conf['onmouseover'])) {
				$controlParameters[7] = addslashes($conf['onmouseover']);
			}
			if (isset($conf['onmouseout.'])) {
				$controlParameters[8] = addslashes($this->cObj->stdWrap((empty($conf['onmouseout'])) ? '' : $conf['onmouseout'],$conf['onmouseout.']));
			}
			elseif (!empty($conf['onmouseout'])) {
				$controlParameters[8] = addslashes($conf['onmouseout']);
			}
			if (isset($conf['onfocus.'])) {
				$controlParameters[9] = addslashes($this->cObj->stdWrap((empty($conf['onfocus'])) ? '' : $conf['onfocus'],$conf['onfocus.']));
			}
			elseif (!empty($conf['onfocus'])) {
				$controlParameters[9] = addslashes($conf['onfocus']);
			}
			if (isset($conf['afterHTML.'])) {
				$controlParameters[10] = trim(str_replace("\r\n",'',$this->cObj->cObjGetSingle($conf['afterHTML'],$conf['afterHTML.'])));
			}
		}

// Assemble the array of parameters for the control

		$controlScript = "var efa_".$key." = ['".implode("','",$controlParameters)."'];\n";
		return $controlScript;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/efafontsize/pi1/class.tx_efafontsize_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/efafontsize/pi1/class.tx_efafontsize_pi1.php']);
}

?>