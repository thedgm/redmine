<?php
/**
 * Class Image generator
 *
 *  !) Instanse image object.Warning:This object work with graphic and flash files but no generate clear click for IE
 *		$img = new BASIC_MEDIA('name image file',[
 * 			array(
 * 				[folder] => file's folder,
 * 				[default] => default picture if doesn't exist base image
 * 				[fullpath] => reate full path of graphics,
 * 				[fixed] => no check for picture size < box size
 * 			)
 * 		]);
 *
 *  !) Get resize size on the file.Warning:0 for wight and height is flag for no resized.Return array('width'=>int,'height'=>int);
 *		$arr_img_size = $img->size([max wight],[max height]);
 *
 *  !) Get HTML Code
 * 		$img_html_code = $img->view([max wight],[max height],'name att 1=val att 1|name att 2=val att 2|...|name att N=val att N')
 *
 * !) support all graphic types and runtime generic grafphics
 * 		mypicture.jpg => generic_jpg.php?name=mypicture
 * @name ImgGenerator
 * @author Evgeni Baldzisky
 * @version 0.8
 * @since [24-01-2007]
 * @copyright
 *  	update [09-03-2007]
 * 		fix    [03-05-2007] fix reality resize
 * 		update [12-09-2007] add file up path support
 * 		fix    [11-03-2008] fix if does't exist image and add support on generic images
 * @package BASIC.SBND.GENERATOR
 */
class BASIC_MEDIA extends BASIC_CLASS {

	var $defpic = '';  	// if exist : alternative picture
	var $img = '';		// if exist : base picture
	var $var = '';		// if exist : runtime gen file's url
	var $folder = '';	// if exist : file's folder
	var $type = 0;		// picture's type
	var $fixed = false; //
	var $absolute = false;

	var $width = 0;		// picture's width
	var $height = 0;	// picture's height

	var $src = '';		// graphic's path

	var $info = array();

	// system properties
	var $is_full = false;

	var $fullPath = '';
	var $virtualPath = '';

	/**
	 * array signature:
	 *
	 * [folder] folder path
	 * [fullpath] display full virtual path
	 * [default] path to default picture
	 */
	function BASIC_MEDIA($img,$att = array()){
		if(isset($att['folder']) && $att['folder']){
			$this->folder = $att['folder'] .(!ereg("/$",$att['folder']) ? "/" : "");
		}else{
			$img = str_replace(BASIC::init()->ini_get('root_virtual'), "", $img);
			$tmp = explode("/", $img);
			$num = count($tmp);
			if($num > 1){
				for($i = 0; $i < $num - 1; $i++){
					$this->folder .= $tmp[$i]."/";		
				}
				$img = $tmp[$num - 1];
			}else{
				$this->folder = BASIC::init()->ini_get('image_path');
			}
		}
		if(isset($att['default'])){
			$this->defpic = $att['default'];
		}
		if(isset($att['fixed']) && ($att['fixed'] == 'true' || $att['fixed'] == '1')){
			$this->fixed = true;
		}
		if(isset($att['absolute']) && ($att['absolute'] == 'true' || $att['absolute'] == '1')){
			$this->absolute = true;
		}

		$this->is_full = (isset($att['fullpath']) && $att['fullpath'] != 'false');

		$this->getPaths();

		$link = explode("?",$img);
		$this->img = $link[0];
		if(isset($link[1])){
			$this->var = $link[1];
		}
		$this->info['type'] = 0;
		if($this->src = $this->src()){
			//die($this->src);
			if($this->var){
				$this->info = @getimagesize($this->src);
			}else{
				$this->info = @getimagesize($this->fullPath.$this->img);
			}
			if($this->info){
				$this->info['type'] = $this->info[2];

				$this->width = $this->info[0];
				$this->height = $this->info[1];

				$ex = explode("/",$this->info['mime']);
				$this->info['extent'] = $ex[1];

				$this->info['width'] = $this->info[0]; unset($this->info[0]);
				$this->info['height'] = $this->info[1]; unset($this->info[1]);
			}else{
				$this->info['type'] = 0;
				ereg('[^.]+$',$this->img,$ex);
				if(
					$ex[0] == 'flv' ||
					$ex[0] == 'mp3'
				){
					$this->info['type'] = -1;
				}else if($ex[0] == 'mov'){
					$this->info['type'] = -2;
				}else if($ex[0] == 'swf'){
					$this->info['type'] = -4;
				}
				$this->info['width'] = 0;
				$this->info['height'] = 0;
				$this->info['extent'] = $ex[0];
			}
		}
	}

	function _convertAtrribute($attribute){
		$tmp = '';
		if(!is_array($attribute)){
			$attribute = $this->convertStringAtt($attribute);
		}
		foreach ($attribute as $k => $v){
			$v = ereg_replace("&(amp;)?","&amp;",$v);
			$v = ereg_replace("<","&lt;",$v);
			$v = ereg_replace(">","&gt;",$v);
			$v = str_replace('"',"&quot;",$v);

			$tmp .= ' '.strtolower($k).'="'.$v.'"';
		}
		return $tmp;
	}

	function getPaths(){

		$tmp = BASIC::init()->pathFile(array(BASIC::init()->ini_get('root_path'),$this->folder));
		$this->fullPath = $tmp[0].$tmp[1];

		$tmp = array('',$this->folder);
		if($this->is_full){
			$tmp = BASIC::init()->pathFile(array(BASIC::init()->ini_get('root_virtual'),$this->folder));
		}
		$this->virtualPath = $tmp[0].$tmp[1];
	}

	function src(){
		if(!file_exists($this->fullPath.$this->img) || !$this->img){
			if(!$this->defpic) return '';

			$this->img = '';
			$this->var = '';

			$link = explode("?",$this->defpic);
			$this->folder = $link[0];
			if(isset($link[1])){
				$this->var = $link[1];
			}
			$this->getPaths();
		}
		return $this->virtualPath.$this->img.($this->var ? '?'.$this->var : '');
	}
	/**
	 * Resize size on the file.
	 * Warning:0 for wight and height is flag for no resized.
	 * Return array('width'=>int,'height'=>int);
	 *
	 * @param int $width
	 * @param int $height
	 * @return hesh array
	 */
	function size($width=0,$height=0){
		$width = (int)$width;
		$height = (int)$height;

		if(!$this->src){
			return array(
				'width'=>0,
				'height'=>0
			);
		}
		if($this->info['type'] == -1 || $this->info['type'] == -2 || $this->info['type'] == -4){
			return array(
				'width'=>$this->width = $width,
				'height'=>$this->height = $height
			);
		}
		if(!$width && !$height){
			return array(
				'width'=>$this->width,
				'height'=>$this->height
			);
		}

		if($this->absolute){
			$this->width = $width;
			$this->height = $height;
		}else{
			if($width > $this->width && $height > $this->height && !$this->fixed){
			}else{
				$width_gen = ($this->width*$height)/$this->height;
				$height_get = ($this->height*$width)/$this->width;
				if($width && $height && ( ($height < $this->height && $width < $this->width ) || $this->fixed) ){
					if($width_gen > $width){
						$this->height = $height_get;
						$this->width = $width;

					}else if($height_get > $height){
						$this->width = $width_gen;
						$this->height = $height;

					}else if($width < $height){
						$this->height = $height_get;
						$this->width = $width;

					}else{
						$this->width = $width_gen;
						$this->height = $height;

					}

				}else if($width && ($this->width > $width || $this->fixed)){
					$this->height = $height_get;
					$this->width = $width;

				}else if($height && ($this->height > $height || $this->fixed)){
					$this->width = $width_gen;
					$this->height = $height;

				}
			}
		}
		$this->width = (int)$this->width;
		$this->height = (int)$this->height;

		return array(
			'width'=>$this->width,
			'height'=>$this->height
		);
	}
	/**
	 * Generate HTML code
	 * Sintax string attribute
	 * 'name att 1=val att 1|name att 2=val att 2|...|name att N=val att N'
	 * 	settings flash attribute :
	 * 		align
	 *  	bgcolor
	 *  	variables
	 * 		allowScriptAccess
	 *  	version
	 * 		loop
	 * 		autoplay
	 *
	 * 	settings QickTime attributes
	 * 		controller
	 * 		loop
	 * 		play
	 * 		bgcolor
	 *
	 * @param int [width]
	 * @param int [height]
	 * @param array/string [attribute]
	 */
	function view($width = 0,$height = 0,$attribute = array()){
		if(!$this->src) return '';

		$return = '';
		$size = $this->size($width,$height);

		if(!isset($attribute['absolute'])){
			$attribute['width'] = $width = $this->width;
			$attribute['height'] = $height = $this->height;
		}else{
			$attribute['width'] = $width;
			$attribute['height'] = $height;

			unset($attribute['absolute']);
		}
		$version = '8';
		if(isset($attribute['version'])){
			$version = $attribute['version'];
			unset($attribute['aversion']);
		}
		$version = 'version='.$version.',0,0,0';

		$allowScriptAccess = '';
		if(isset($attribute['allowscriptaccess'])){
			$allowScriptAccess = $attribute['allowscriptaccess'];
			unset($attribute['allowscriptaccess']);
		}
		$bgcolor = '';
		if(isset($attribute['bgcolor'])){
			$bgcolor = $attribute['bgcolor'];
			unset($attribute['bgcolor']);
		}
		$align = '';
		if(isset($attribute['align'])){
			$align = $attribute['align'];
			unset($attribute['align']);
		}
		$play = '';
		if(isset($attribute['play'])){
			$play = $attribute['play'];
			unset($attribute['play']);
		}
		$loop = '';
		if(isset($attribute['loop'])){
			$loop = $attribute['loop'];
			unset($attribute['loop']);
		}
		$controller = '';
		if(isset($attribute['controller'])){
			$controller = $attribute['controller'];
			unset($attribute['controller']);
		}
		$flashfars = '';
		if(isset($attribute['variables'])){
			$flashfars = $attribute['variables'];
			unset($attribute['variables']);
		}
		if(!isset($attribute['id'])){
			$attribute['id'] = '';
		}
		if($this->info['type'] == 13 || $this->info['type'] == 4 || $this->info['type'] == -4){
			$tmp = '';
			unset($attribute['width']);
			unset($attribute['height']);

			if(!isset($GLOBALS['BASIC_CNT']->loadscripts['flash'])){
				$tmp .= '<!--[if IE]><script type="text/javascript" src="'.$GLOBALS['BASIC']->ini_get('root_virtual').'basic/scripts/flash/flash.js" defer="defer"></script><![endif]-->';
				$GLOBALS['BASIC_CNT']->loadscripts['flash'] = true;
			}

			if($play == '') $play = 'true';
			if($loop == '') $loop = 'true';

			$tmp .= ('
				<object '.($width ? 'width="'.$width.'" ':'').'
						'.($height ? 'height="'.$height.'" ':'').'
						codebase="http://active.macromedia.com/flash6/cabs/swflash.cab#'.$version.'"
						classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" '.$this->_convertAtrribute($attribute).'>
					<param name="movie" value="'.$this->src().($flashfars ? '?'.$flashfars : "").'" />
					'.($play ? '<param name="play" value="'.$play.'" />' : '').'
					'.($loop ? '<param name="loop" value="'.$loop.'" />' : '').'
					<param name="WMode" value="Opaque" />
					<param name="quality" value="high" />
					'.($allowScriptAccess ? '<param name="allowScriptAccess" value="'.$allowScriptAccess.'" />' : '').'
					'.($bgcolor ? '<param name="bgcolor" value="'.$bgcolor.'" />' : '').'
					'.($align ? '<param name="align" value="'.$align.'" />' : '').'
					<embed src="'.$this->src().'"
						wmode="Opaque"
						quality="high"
						pluginspage="http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash"
						'.($play ? 'play="'.$play.'" ' : '').'
						'.($loop ? 'loop="'.$loop.'" ' : '').'
						'.($allowScriptAccess ? ' allowScriptAccess="'.$allowScriptAccess.'" ' : '').'
						'.($align ? ' align="'.$align.'" ' : '').'
						'.($bgcolor ? ' bgcolor="'.$bgcolor.'" ' : '').'
						'.($attribute['id'] ? ' name="'.$attribute['id'].'" ' : '').'
						'.($width ? 'width="'.$width.'" ':'').'
						'.($height ? 'height="'.$height.'" ':'').'
						'.($flashfars ? ' flashvars="'.$flashfars.'" ' : '').'>
				</object>
			');
			return $tmp;
		}else if($this->info['type'] == -1){
			// help : http://www.jeroenwijering.com/?page=wizard
			return ('
				<object '.($width ? 'width="'.$width.'" ':'').'
						'.($height ? 'height="'.$height.'" ':'').'
						codebase="http://active.macromedia.com/flash6/cabs/swflash.cab#'.$version.'"
						classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" '.$this->_convertAtrribute($attribute).'>
					<param name="movie" value="'.$GLOBALS['BASIC']->ini_get('root_virtual').$GLOBALS['BASIC']->ini_get('basic_path').'scripts/flash/mediaplayer.swf?width='.$width.'&height='.$height.'&file='.$this->src.($flashfars ? '&'.$flashfars : '').'" />
					<param name="WMode" value="Opaque" />
					<param name="quality" value="high" />
					'.($allowScriptAccess ? '<param name="allowScriptAccess" value="'.$allowScriptAccess.'" />' : '').'
					<embed
						src="'.$GLOBALS['BASIC']->ini_get('root_virtual').$GLOBALS['BASIC']->ini_get('basic_path').'scripts/flash/mediaplayer.swf" 
						wmode="Opaque" 
						quality="high" 
						width="'.$width.'"
						height="'.$height.'"
						'.($allowScriptAccess ? ' allowScriptAccess="'.$allowScriptAccess.'" ' : '').'
						allowfullscreen="true"
						flashvars="width='.$width.'&height='.$height.'&file='.$this->src.($flashfars ? '&'.$flashfars : '').'"/>
				</object>
			');
		}else if($this->info['type'] == -2){

			if($play == '') $play = 'false';
			if($loop == '') $loop = 'false';
			if($controller == '') $controller = 'true';

			return ('
				<embed
					width="'.$width.'"
					height="'.$height.'"
					target="QuickTimePlayer"
					pluginspage="http://www.apple.com/quicktime/download/indext.html"
					targetcache="true"
					cache="true"
					'.($bgcolor ? ' bgcolor="'.$bgcolor.'" ' : '').'
					autoplay="'.$play.'"
					loop="'.$loop.'"
					controller="'.$controller.'"
					src="'.$this->src.'"
				/>
			');
		}else if(!isset($this->info['mime'])){
			return '<a href="'.$this->src.'">'.$this->img.'</a>';
		}else{
			if(!isset($attribute['alt'])) $attribute['alt'] = '';
			$attribute['src'] = $this->src;

			return '<img '.$this->_convertAtrribute($attribute)."/>";
		}
	}

	// End Class BASIC_MEDIA
}

class mediaModifer extends BASIC_CLASS {

	var $width = 150;
	var $height = 150;

	var $package = '';

	var $error = 0;

	var $buffer = '';
	var $info = array();
	var $fullPath = '';

	function mediaModifer(){

	}

	/**
	 * for work with movie files used if(class_exists('ffmpeg_movie')){$movie = new ffmpeg_movie();}
	 */


	function addPackage($package=''){
		if(!$package) $package = $GLOBALS['BASIC']->ini_get('upload_path');

		$this->package = $GLOBALS['BASIC']->validPath($package);
	}
	/**
	 * Image resizer method
	 *
	 * $media = new mediaModifer();
	 *
	 *	$media->set('width',140);
	 *	$media->set('height',340);
	 *	$media->addPackage('upload');
	 *
	 *	if($err = $media->resizeImages('AVT971627.gif')){
	 *	//if($err = $media->resizeImages('AVT721112.jpg')){
	 *	//if($err = $media->resizeImages('AVT721112.png')){
	 *		print 'Exist error ('.$err.")";
	 *	}
	 *
	 *	header("Content-type:".$media->info['mime']);
	 *	print $media->get('buffer');
	 *
	 * 	WARNING : # esized animateg gif can't support animation
	 *  		  # but if not wanted sizes is bigger
	 * @param unknown_type $name
	 * @param unknown_type $package
	 * @return unknown
	 */
	function resizeImages($name,$package=''){
		if(!$package) $package = $this->package;

		$obj = new BASIC_MEDIA($name,array('folder' => $package));
		$this->fullPath = $obj->get('fullPath');

		if(!$obj->src){
			$this->error = 1;
			return 1;  // error if no exist image file
		}
		$this->info = $obj->info;
		if($this->width > $obj->info['width'] && $this->height > $obj->info['height']){
			$file = fopen($this->fullPath.$name,'rb');
			while (!feof($file)) {
			   $this->buffer = fread($file,(1024*1024));
			}
			return 0;
		}

		if($obj->info['extent'] == 'jpeg'){
			/**
			 * @todo прекалено много памет се изисква тук при някой файлове. 
			 */ 
			$bgr = imagecreatefromjpeg($this->fullPath.$name);
		}else if($obj->info['extent'] == 'gif'){
			$bgr = imagecreatefromgif ($this->fullPath.$name);
		}else if($obj->info['extent'] == 'png'){
			$bgr = imagecreatefrompng ($this->fullPath.$name);
		}else{
			$this->error = 2;
			return 2;	// error if no support image type
		}
		imageinterlace($bgr,true);
		imagealphablending($bgr,true);

		$newSize = $obj->size($this->width,$this->height);

		$img = imagecreatetruecolor($newSize['width'],$newSize['height']);
		imagecopyresampled($img,$bgr,0,0,0,0,
			$newSize['width'],
			$newSize['height'],
			$obj->info['width'],
			$obj->info['height']
		);

		ob_start();
		if($obj->info['extent'] == 'jpeg'){
			imagejpeg($img);
		}else if($obj->info['extent'] == 'gif'){
			imagegif($img);
		}else if($obj->info['extent'] == 'png'){
			imagepng($img);
		}
		$this->buffer = ob_get_clean();
		return 0;
	}

	// End Class mediaWorker
}
?>