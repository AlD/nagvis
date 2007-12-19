<?php
/**
 * Class of a Host in Nagios with all necessary informations
 */
class NagVisObject {
	var $MAINCFG;
	var $LANG;
	
	// "Global" Configuration variables for all objects
	var $type;
	var $x;
	var $y;
	var $z;
	var $icon;
	
	var $label_show;
	var $recognize_services;
	var $only_hard_states;
	
	var $iconPath;
	var $iconHtmlPath;
	
	/**
	 * Class constructor
	 *
	 * @param		Object 		Object of class GlobalMainCfg
	 * @param		Object 		Object of class GlobalBackendMgmt
	 * @param		Object 		Object of class GlobalLanguage
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function NagVisObject(&$MAINCFG, &$LANG) {
		$this->MAINCFG = &$MAINCFG;
		$this->LANG = &$LANG;
		
		//FIXME: $this->getInformationsFromBackend();
	}
	
	function get($option) {
		return $this->{$option};
	}
	
	function getX() {
		return $this->x;
	}
	
	function getY() {
		return $this->y;
	}
	
	function getZ() {
		return $this->z;
	}
	
	function getType() {
		return $this->type;
	}
	
	function getName() {
		return $this->{$this->getType().'_name'};
	}
	
	function getHoverTemplate() {
		return $this->hover_template;
	}
	
	function setMapCoords($arrCoords) {
		$this->x = $arrCoords['x'];
		$this->y = $arrCoords['y'];
		$this->z = $arrCoords['z'];
	}
	
	/**
	 * PUBLIC setConfiguration()
	 *
	 * Sets options of the object
	 *
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function setConfiguration(&$obj) {
		foreach($obj AS $key => $val) {
			$this->{$key} = $val;
		}
	}
	
	/**
	 * PUBLIC replaceMacros()
	 *
	 * Replaces macros of urls and hover_urls
	 *
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function replaceMacros() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::replaceMacros(&$obj)');
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::replaceMacros(): Array(...)');
		return TRUE;
	}
	
	/**
	 * PUBLIC parseIcon()
	 *
	 * Parses the HTML-Code of an icon
	 *
	 * @return	String		String with Html Code
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function parseIcon() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::parseIcon()');
		
		if($this->type == 'service') {
			$name = 'host_name';
			$alt = $this->host_name.'-'.$this->service_description;
		} else {
			$alt = $this->{$this->type.'_name'};
		}
		
		$ret = '<div class="icon" style="left:'.$this->x.'px;top:'.$this->y.'px;z-index:'.$this->z.';">';
		$ret .= $this->createLink();
		$ret .= '<img src="'.$this->iconHtmlPath.$this->icon.'" '.$this->getHoverMenu().' alt="'.$this->type.'-'.$alt.'">';
		$ret .= '</a>';
		$ret .= '</div>';
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::parseIcon(): Array(...)');
		return $ret;
	}
	
	/**
	 * PUBLIC getHoverMenu
	 *
	 * Creates a hover box for objects
	 *
	 * @return	String		HTML code for the hover box
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function getHoverMenu() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::getHoverMenu(&$obj)');
		$ret = '';
		// FIXME 1.1: check if this is an object, where a menu should be displayed
		if(1) {
			$ret .= 'onmouseover="return overlib(\'';
			if(isset($this->hover_url) && $this->hover_url != '') {
				$ret .= $this->readHoverUrl();
			} else {
				$ret .= $this->createInfoBox();
			}
			
			$ret .= '\', WRAP, VAUTO, DELAY, '.($this->hover_delay*1000).');" onmouseout="return nd();"';
			
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::getHoverMenu(): Array(...)');
			return $ret;
		}
	}
	
	/**
	 * PRIVATE readHoverUrl()
	 *
	 * Reads the given hover url form an object and forms it to a readable format for the hover box
	 *
	 * @return	String		HTML code for the hover box
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function readHoverUrl() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::readHoverUrl(&$obj)');
		/* FIXME: Context is supported in php >= 5.0
		* $http_opts = array(
		*      'http'=>array(
		*      'method'=>"GET",
		*      'header'=>"Accept-language: en\r\n" .
		*                "Authorization: Basic ".base64_encode("user:pw"),
		*      'request_fulluri'=>true  ,
		*      'proxy'=>"tcp://proxy.url.de"
		*   )
		* );
		* $context = stream_context_create($http_opts);
		* $content = file_get_contents($obj['hover_url'],FALSE,$context);
		*/
		if(!$content = @file_get_contents($this->hover_url)) {
			$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'nagvis:global'));
			$FRONTEND->messageToUser('WARNING','couldNotGetHoverUrl','URL~'.$this->hover_url);
		}
		
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::readHoverUrl(): HTML');
		return str_replace('"','\\\'',str_replace('\'','\\\'',str_replace("\t",'',str_replace("\n",'',str_replace("\r\n",'',$content)))));
	}
	
	
	/**
	 * PRIVATE createInfoBox()
	 *
	 * Creates a JavaScript hover menu
	 *
	 * @return	String		HTML code for the Hover-Box
	 * @author	Lars Michelsen <lars@vertical-visions.de>
	 */
	function createInfoBox() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::createInfoBox(&$obj)');
		$ret = $this->readHoverTemplate();
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::createInfoBox(): Array(...)');
		return $ret;
	}
	
	/**
	 * readHoverTemplate 
	 *
	 * Reads the contents of the hover template file
	 *
	 * @return	String		HTML Code for the hover menu
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function readHoverTemplate() {
		if($this->checkHoverTemplateReadable(1)) {
			$ret = file_get_contents($this->MAINCFG->getValue('paths','hovertemplate').'tmpl.'.$this->getHoverTemplate().'.html');
			
			$ret = $this->replaceHoverTemplateMacros($ret);
			
			// Escape chars which could make problems
			$ret = strtr(addslashes($ret),Array('"' => '\'', "\r" => '', "\n" => ''));
		}
		
		return $ret;
	}
	
	/**
	 * PRIVATE replaceHoverTemplateMacros
	 *
	 * Replaces macros for the hover templates in the template code
	 *
	 * @param		String		HTML code with macros
	 * @return	String		HTML code for the hover menu
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function replaceHoverTemplateMacros($ret) {
		if($this->type == 'service') {
			$name = 'host_name';
		} else {
			$name = $this->type . '_name';
		}
		
		/**
		 * Now replace the macros
		 */
		$ret = str_replace('[obj_type]', $this->type, $ret);
		$ret = str_replace('[obj_name]', $this->$name, $ret);
		
		if(isset($this->alias) && $this->alias != '') {
			$ret = str_replace('[obj_alias]',$this->alias,$ret);
		} else {
			$ret = str_replace('[obj_alias]','',$ret);
		}
		
		if(isset($obj['display_name']) && $this->display_name != '') {
			$ret = str_replace('[obj_display_name]',$this->display_name,$ret);
		} else {
			$ret = str_replace('[obj_display_name]','',$ret);
		}
		
		if($this->getSummaryAcknowledgement() == 1) {
			$ret = str_replace('[obj_state]',$this->getState().' (Acknowledged)',$ret);
			$ret = str_replace('[obj_summary_state]',$this->getSummaryState().' (Acknowledged)',$ret);
		} else {
			$ret = str_replace('[obj_state]',$this->getState(),$ret);
			$ret = str_replace('[obj_summary_state]',$this->getSummaryState(),$ret);
		}
		
		if($this->type != 'map') {
			$ret = str_replace('[obj_backendid]',$this->backend_id,$ret);
			if($this->MAINCFG->getValue('backend_'.$this->backend_id,'backendtype') == 'ndomy') {
				$ret = str_replace('[obj_backend_instancename]',$this->MAINCFG->getValue('backend_'.$this->backend_id,'dbinstancename'),$ret);
			} else {
				$ret = str_replace('[obj_backend_instancename]','',$ret);
			}
		} else {
			// Remove the macros in map objects
			$ret = str_replace('[obj_backendid]','',$ret);
			$ret = str_replace('[obj_backend_instancename]','',$ret);
		}
		
		// Macros for PNP images
		
		$ret = str_replace('[obj_output]',strtr($this->output, Array("\r" => '<br />', "\n" => '<br />')),$ret);
		$ret = str_replace('[obj_summary_output]',strtr($this->getSummaryOutput(), Array("\r" => '<br />', "\n" => '<br />')),$ret);
		$ret = str_replace('[lang_name]',$this->LANG->getLabel(str_replace('_','',$name)),$ret);
		$ret = str_replace('[lang_alias]',$this->LANG->getLabel('alias'),$ret);
		$ret = str_replace('[lang_state]',$this->LANG->getLabel('state'),$ret);
		$ret = str_replace('[lang_summary_state]',$this->LANG->getLabel('summaryState'),$ret);
		$ret = str_replace('[lang_output]',$this->LANG->getLabel('output'),$ret);
		$ret = str_replace('[lang_summary_output]',$this->LANG->getLabel('summaryOutput'),$ret);
		$ret = str_replace('[lang_obj_type]',$this->LANG->getLabel($this->type),$ret);
		$ret = str_replace('[lang_overview]',$this->LANG->getLabel('overview'),$ret);
		$ret = str_replace('[lang_instance]',$this->LANG->getLabel('instance'),$ret);
		$ret = str_replace('[html_base]',$this->MAINCFG->getValue('paths','htmlbase'),$ret);
		$ret = str_replace('[html_templates]',$this->MAINCFG->getValue('paths','htmlhovertemplates'),$ret);
		$ret = str_replace('[html_template_images]',$this->MAINCFG->getValue('paths','htmlhovertemplateimages'),$ret);
		
		if($this->type == 'service') {
			$ret = str_replace('[service_description]',$this->service_description,$ret);
			$ret = str_replace('[pnp_service_description]',str_replace(' ','%20',$this->service_description),$ret);
			$ret = str_replace('[lang_service_description]',$this->LANG->getLabel('servicename'),$ret);
		} else {
			$ret = preg_replace('/<!-- BEGIN service -->((?s).*)<!-- END service -->/','',$ret);
		}
		
		if($this->type == 'host') {
			$ret = str_replace('[pnp_hostname]',str_replace(' ','%20',$this->$name),$ret);
		} else {
			$ret = preg_replace('/<!-- BEGIN host -->((?s).*)<!-- END host -->/','',$ret);
		}
		
		return $ret;
	}
	
	/**
	 * PRIVATE checkHoverTemplateReadable()
	 *
	 * Checks if the requested hover template file is readable
	 *
	 * @param		Boolean		Switch for enabling/disabling error messages
	 * @return	Boolean		Check Result
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkHoverTemplateReadable($printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::checkHoverTemplateReadable(&$obj,'.$printErr.')');
		if($this->checkHoverTemplateExists($printErr) && is_readable($this->MAINCFG->getValue('paths','hovertemplate').'tmpl.'.$this->getHoverTemplate().'.html')) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::checkHoverTemplateReadable(): TRUE');
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'nagvis:global'));
				$FRONTEND->messageToUser('ERROR','hoverTemplateNotReadable','FILE~'.$this->MAINCFG->getValue('paths','hovertemplate').'tmpl.'.$this->getHoverTemplate().'.html');
			}
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::checkHoverTemplateReadable(): FALSE');
			return FALSE;
		}
	}
	
	/**
	 * PRIVATE checkHoverTemplateExists()
	 *
	 * Checks if the requested hover template file exists
	 *
	 * @param		Boolean		Switch for enabling/disabling error messages
	 * @return	Boolean		Check Result
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function checkHoverTemplateExists($printErr) {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::checkHoverTemplateExists(&$obj,'.$printErr.')');
		if(file_exists($this->MAINCFG->getValue('paths','hovertemplate').'tmpl.'.$this->getHoverTemplate().'.html')) {
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::checkHoverTemplateExists(): TRUE');
			return TRUE;
		} else {
			if($printErr == 1) {
				$FRONTEND = new GlobalPage($this->MAINCFG,Array('languageRoot'=>'nagvis:global'));
				$FRONTEND->messageToUser('ERROR','hoverTemplateNotExists','FILE~'.$this->MAINCFG->getValue('paths', 'hovertemplate').'tmpl.'.$this->getHoverTemplate().'.html');
			}
			if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::checkHoverTemplateExists(): FALSE');
			return FALSE;
		}
	}
	
	/**
	 * PRIVATE createLink()
	 *
	 * Creates a link to the configured url
	 *
	 * @return	String	HTML code of the Link
	 * @author 	Lars Michelsen <lars@vertical-visions.de>
	 */
	function createLink() {
		if (DEBUG&&DEBUGLEVEL&1) debug('Start method NagVisMap::createLink(&$obj)');
		if (DEBUG&&DEBUGLEVEL&1) debug('End method NagVisMap::createLink(): '.$link);
		return '<a href="'.$this->url.'" target="'.$this->url_target.'">';
	}
	
	/* DEPRECATED
	function fixIconPosition() {
		// 0: width, 1: height
		$imgInfo = getimagesize($this->iconPath.$this->icon);
		$this->x -= $imgInfo[0] * 0.5;
		$this->y -= $imgInfo[1] * 0.5;
	}*/
}
?>
